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
    @error_log(date('Y-m-d H:i:s') . " - Rechnung Error: " . $message . "\n", 3, $logDir . '/rechnung_errors.log');
}

function logSuccess($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    @error_log(date('Y-m-d H:i:s') . " - Rechnung Success: " . $message . "\n", 3, $logDir . '/rechnung_success.log');
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, ['error' => 'Keine Datei hochgeladen oder Upload-Fehler.']);
    }

    $uploadedFile = $_FILES['file'];
    
    // Get form data
    $rechnungsnummer = trim($_POST['rechnungsnummer'] ?? '');
    $lieferant = trim($_POST['lieferant'] ?? '');
    $betrag = trim($_POST['betrag'] ?? '');
    $waehrung = trim($_POST['waehrung'] ?? 'EUR');
    $rechnungsdatum = trim($_POST['rechnungsdatum'] ?? '');
    $faelligkeitsdatum = trim($_POST['faelligkeitsdatum'] ?? '');
    $kategorie = trim($_POST['kategorie'] ?? '');
    $mandant = trim($_POST['mandant'] ?? '');
    $bemerkungen = trim($_POST['bemerkungen'] ?? '');

    // Validate required fields
    if (empty($rechnungsnummer)) {
        sendJsonResponse(false, ['error' => 'Rechnungsnummer ist ein Pflichtfeld.']);
    }
    if (empty($lieferant)) {
        sendJsonResponse(false, ['error' => 'Lieferant/Kunde ist ein Pflichtfeld.']);
    }
    if (empty($betrag)) {
        sendJsonResponse(false, ['error' => 'Rechnungsbetrag ist ein Pflichtfeld.']);
    }
    if (empty($rechnungsdatum)) {
        sendJsonResponse(false, ['error' => 'Rechnungsdatum ist ein Pflichtfeld.']);
    }
    if (empty($mandant)) {
        sendJsonResponse(false, ['error' => 'Mandant ist ein Pflichtfeld.']);
    }

    // Validate amount format
    if (!is_numeric($betrag) || $betrag <= 0) {
        sendJsonResponse(false, ['error' => 'Ungültiger Rechnungsbetrag.']);
    }

    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $rechnungsdatum)) {
        sendJsonResponse(false, ['error' => 'Ungültiges Rechnungsdatum.']);
    }

    // Validate file type (more restrictive for invoices)
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

    // Prepare document data for archive with extended invoice fields
    $documentContentAndMetaData = [
        [
            'name' => 'files[0]',
            'contents' => fopen($uploadedFile['tmp_name'], 'r'),
            'filename' => $uploadedFile['name']
        ]
    ];

    // Add invoice-specific index fields
    $indexFieldCounter = 0;
    
    // Required fields
    $indexFields = [
        'Mandant' => $mandant,
        'Rechnungsnummer' => $rechnungsnummer,
        'Lieferant' => $lieferant,
        'Betrag' => number_format($betrag, 2, '.', ''),
        'Waehrung' => $waehrung,
        'Rechnungsdatum' => $rechnungsdatum
    ];
    
    // Optional fields
    if (!empty($faelligkeitsdatum)) {
        $indexFields['Faelligkeitsdatum'] = $faelligkeitsdatum;
    }
    if (!empty($kategorie)) {
        $indexFields['Kategorie'] = $kategorie;
    }
    if (!empty($bemerkungen)) {
        $indexFields['Bemerkungen'] = $bemerkungen;
    }
    
    // Add upload timestamp
    $indexFields['Upload_Datum'] = date('Y-m-d H:i:s');
    $indexFields['Dokumenttyp'] = 'Rechnung';

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

    // Custom DocumentManager for invoice UI
    class InvoiceDocumentManager extends DocumentManager {
        public function archiveInvoiceUI($documentContentAndMetaData) {
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

    // Upload invoice to archive
    $documentManager = new InvoiceDocumentManager(new Authenticator());
    $result = $documentManager->archiveInvoiceUI($documentContentAndMetaData);

    if ($result['success']) {
        $logMessage = "Successfully uploaded invoice: {$uploadedFile['name']} (ID: {$result['documentId']}) - Rechnung: $rechnungsnummer, Lieferant: $lieferant, Betrag: $betrag $waehrung";
        logSuccess($logMessage);

        sendJsonResponse(true, [
            'documentId' => $result['documentId'],
            'filename' => $uploadedFile['name'],
            'size' => $uploadedFile['size'],
            'rechnungsnummer' => $rechnungsnummer,
            'lieferant' => $lieferant,
            'betrag' => $betrag,
            'waehrung' => $waehrung,
            'rechnungsdatum' => $rechnungsdatum,
            'mandant' => $mandant
        ]);
    } else {
        logError("Invoice upload failed for {$uploadedFile['name']}: " . $result['error']);
        sendJsonResponse(false, ['error' => $result['error']]);
    }

} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    sendJsonResponse(false, ['error' => 'Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage()]);
}

?>
