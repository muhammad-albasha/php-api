<?php

// Clean output buffer and suppress all warnings
if (ob_get_level()) {
    ob_clean();
}
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/Archive/DocumentManager.php');

function sendJsonResponse($success, $data = []) {
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode(array_merge(['success' => $success], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    @error_log(date('Y-m-d H:i:s') . " - Simple Rechnung Error: " . $message . "\n", 3, $logDir . '/simple_rechnung_errors.log');
}

function logSuccess($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    @error_log(date('Y-m-d H:i:s') . " - Simple Rechnung Success: " . $message . "\n", 3, $logDir . '/simple_rechnung_success.log');
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, ['error' => 'Keine Datei hochgeladen oder Upload-Fehler.']);
    }

    $uploadedFile = $_FILES['file'];
    $mandant = 'Automatisch'; // Default value for simple upload without Mandant

    // No required field validation - allow upload without additional data

    // Validate file type (invoices only)
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        sendJsonResponse(false, ['error' => 'Dateityp nicht erlaubt. Erlaubt sind: PDF, JPG, PNG']);
    }

    // Check file size (max 15MB for invoices)
    if ($uploadedFile['size'] > 15 * 1024 * 1024) {
        sendJsonResponse(false, ['error' => 'Datei zu groß. Maximale Größe: 15MB']);
    }

    // Prepare document data for archive with minimal invoice data
    $documentContentAndMetaData = [
        [
            'name' => 'files[0]',
            'contents' => fopen($uploadedFile['tmp_name'], 'r'),
            'filename' => $uploadedFile['name']
        ]
    ];

    // Add minimal index fields for simple invoice upload
    $indexFieldCounter = 0;
    
    $indexFields = [
        'Mandant' => $mandant,
        'Dokumenttyp' => 'Rechnung',
        'Upload_Datum' => date('Y-m-d H:i:s'),
        'Upload_Typ' => 'Einfacher_Upload'
    ];

    // Add all index fields to multipart data
    foreach ($indexFields as $fieldName => $fieldValue) {
        $documentContentAndMetaData[] = [
            'name' => "indexFields[{$indexFieldCounter}][name]",
            'contents' => $fieldName
        ];
        $documentContentAndMetaData[] = [
            'name' => "indexFields[{$indexFieldCounter}][value]",
            'contents' => $fieldValue
        ];
        $indexFieldCounter++;
    }

    // Always add "Aktiv" field with value "1"
    $documentContentAndMetaData[] = [
        'name' => "indexFields[{$indexFieldCounter}][name]",
        'contents' => 'Aktiv'
    ];
    $documentContentAndMetaData[] = [
        'name' => "indexFields[{$indexFieldCounter}][value]",
        'contents' => '1'
    ];

    // Custom DocumentManager for simple invoice UI
    class SimpleInvoiceDocumentManager extends DocumentManager {
        public function archiveSimpleInvoiceUI($documentContentAndMetaData) {
            try {
                $response = $this->client->request(
                    'POST',
                    static::RESOURCE_URI,
                    [
                        'multipart' => $documentContentAndMetaData,
                    ]
                );

                if ($response->getStatusCode() == 201) {
                    $body = json_decode($response->getBody(), true);
                    return [
                        'success' => true,
                        'documentId' => $body[static::ROOT][0]['revisionId']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Unerwarteter Status Code: ' . $response->getStatusCode()
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            } finally {
                // Always try to delete session, ignore errors
                try {
                    $this->authenticator->deleteSession($this->sessionId);
                } catch (Exception $e) {
                    // Ignore session deletion errors
                }
            }
        }
    }

    // Upload simple invoice to archive
    $documentManager = new SimpleInvoiceDocumentManager(new Authenticator());
    $result = $documentManager->archiveSimpleInvoiceUI($documentContentAndMetaData);

    if ($result['success']) {
        $logMessage = "Successfully uploaded simple invoice: {$uploadedFile['name']} (ID: {$result['documentId']}) - Mandant: $mandant";
        logSuccess($logMessage);

        sendJsonResponse(true, [
            'documentId' => $result['documentId'],
            'filename' => $uploadedFile['name'],
            'size' => $uploadedFile['size'],
            'mandant' => $mandant,
            'documentType' => 'Rechnung',
            'uploadType' => 'Einfacher Upload'
        ]);
    } else {
        logError("Simple invoice upload failed for {$uploadedFile['name']}: " . $result['error']);
        sendJsonResponse(false, ['error' => $result['error']]);
    }

} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    sendJsonResponse(false, ['error' => 'Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage()]);
}

?>
