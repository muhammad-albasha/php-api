<?php

// Suppress PHP warnings for cleaner JSON response
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/Archive/DocumentManager.php');

function sendJsonResponse($success, $data = []) {
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    error_log(date('Y-m-d H:i:s') . " - Upload Error: " . $message . "\n", 3, $logDir . '/upload_errors.log');
}

function logSuccess($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    error_log(date('Y-m-d H:i:s') . " - Upload Success: " . $message . "\n", 3, $logDir . '/upload_success.log');
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, ['error' => 'Keine Datei hochgeladen oder Upload-Fehler.']);
    }

    $uploadedFile = $_FILES['file'];
    $mandant = trim($_POST['mandant'] ?? '');

    // Validate required fields
    if (empty($mandant)) {
        sendJsonResponse(false, ['error' => 'Mandant ist ein Pflichtfeld.']);
    }

    // Validate file
    $allowedTypes = ['application/pdf', 'text/plain', 'application/msword', 
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'image/jpeg', 'image/png'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        sendJsonResponse(false, ['error' => 'Dateityp nicht erlaubt. Erlaubt sind: PDF, TXT, DOC, DOCX, JPG, PNG']);
    }

    // Check file size (max 10MB)
    if ($uploadedFile['size'] > 10 * 1024 * 1024) {
        sendJsonResponse(false, ['error' => 'Datei zu groß. Maximale Größe: 10MB']);
    }

    // Prepare document data for archive
    $documentContentAndMetaData = [
        [
            'name' => 'files[0]',
            'contents' => fopen($uploadedFile['tmp_name'], 'r'),
            'filename' => $uploadedFile['name']
        ]
    ];

    // Add index fields - Mandant is required
    $indexFieldCounter = 0;
    
    $documentContentAndMetaData[] = [
        'name' => "indexFields[{$indexFieldCounter}][name]",
        'contents' => 'Mandant'
    ];
    $documentContentAndMetaData[] = [
        'name' => "indexFields[{$indexFieldCounter}][value]",
        'contents' => $mandant
    ];

    // Custom DocumentManager for UI with better error handling
    class UIDocumentManager extends DocumentManager {
        public function archiveDocumentUI($documentContentAndMetaData) {
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

    // Upload to archive
    $documentManager = new UIDocumentManager(new Authenticator());
    $result = $documentManager->archiveDocumentUI($documentContentAndMetaData);

    if ($result['success']) {
        // Log successful upload
        $logMessage = "Successfully uploaded: {$uploadedFile['name']} (ID: {$result['documentId']})";
        if (!empty($mandant)) $logMessage .= " - Mandant: $mandant";
        logSuccess($logMessage);

        sendJsonResponse(true, [
            'documentId' => $result['documentId'],
            'filename' => $uploadedFile['name'],
            'size' => $uploadedFile['size']
        ]);
    } else {
        logError("Upload failed for {$uploadedFile['name']}: " . $result['error']);
        sendJsonResponse(false, ['error' => $result['error']]);
    }

} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    sendJsonResponse(false, ['error' => 'Ein unerwarteter Fehler ist aufgetreten.']);
}

?>
