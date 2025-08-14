<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/ERechnungParser.php');

class RechnungBatch
{
    private $incomingDir;
    private $processedDir;
    private $logFile;

    public function __construct()
    {
        $this->incomingDir = __DIR__ . '/incoming/';
        $this->processedDir = __DIR__ . '/processed/';
        $this->logFile = __DIR__ . '/logs/batch_' . date('Y-m-d') . '.log';
    }

    public function run()
    {
        $this->log("=== Batch-Upload gestartet ===");
        
        $files = glob($this->incomingDir . '*');
        $processedCount = 0;
        $errorCount = 0;
        
        if (empty($files)) {
            $this->log("Keine Dateien zum Verarbeiten gefunden");
            echo "📭 Keine neuen Rechnungen gefunden\n";
            return;
        }
        
        echo "📄 " . count($files) . " Datei(en) gefunden\n";
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileName = basename($file);
                echo "Verarbeite E-Rechnung: " . $fileName . "\n";
                
                if ($this->uploadERechnung($file)) {
                    $this->moveToProcessed($file);
                    $processedCount++;
                    echo "✅ " . $fileName . " erfolgreich hochgeladen\n";
                } else {
                    $errorCount++;
                    echo "❌ " . $fileName . " Upload fehlgeschlagen\n";
                }
            }
        }
        
        $this->log("Batch abgeschlossen: $processedCount erfolgreich, $errorCount Fehler");
        echo "\n📊 Batch abgeschlossen: $processedCount erfolgreich, $errorCount Fehler\n";
    }

    private function uploadERechnung($filePath)
    {
        try {
            $fileName = basename($filePath);
            $this->log("E-Rechnung Upload: " . $fileName);
            
            // E-Rechnung parsen
            $metadata = ERechnungParser::parseERechnung($filePath);
            $this->log("E-Rechnung-Typ: " . $metadata['type']);
            
            // Client erstellen
            $client = new CurlClient();
            $client->authenticate();
            
            // Datei vorbereiten
            $fileInfo = pathinfo($fileName);
            $fileExtension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
            $mimeType = ERechnungParser::getMimeType($fileExtension);
            
            $uploadFile = new CURLFile($filePath, $mimeType, $fileName);
            
            // Mandant aus geparsten Daten oder Dateiname
            $mandant = $metadata['lieferant'] ?? $this->extractMandantFromFilename($fileName);
            if (!$mandant) {
                $mandant = "E-Rechnung Batch " . date('Y-m-d H:i');
            }
            
            // Upload
            $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
            $indexData = [
                'indexFields[0][name]' => 'Mandant',
                'indexFields[0][value]' => $mandant,
                'files[0]' => $uploadFile
            ];
            
            $response = $client->post($route, $indexData);
            
            if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
                $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
                $logMessage = "✅ E-Rechnung Upload erfolgreich - ID: " . $documentId . " - " . $fileName;
                $logMessage .= " | Typ: " . $metadata['type'];
                if ($metadata['rechnungsnummer']) {
                    $logMessage .= " | RG-Nr: " . $metadata['rechnungsnummer'];
                }
                if ($metadata['betrag']) {
                    $logMessage .= " | Betrag: " . $metadata['betrag'] . " " . $metadata['waehrung'];
                }
                
                $this->log($logMessage);
                $client->destroySession();
                return true;
            }
            
            $client->destroySession();
            return false;
            
        } catch (Exception $e) {
            $this->log("E-Rechnung Upload-Fehler " . $fileName . ": " . $e->getMessage());
            return false;
        }
    }

    private function uploadFile($filePath)
    {
        try {
            $fileName = basename($filePath);
            $this->log("Upload: " . $fileName);
            
            // Client erstellen
            $client = new CurlClient();
            $client->authenticate();
            
            // Datei vorbereiten
            $fileInfo = pathinfo($fileName);
            $fileExtension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
            $mimeType = $this->getMimeType($fileExtension);
            
            $uploadFile = new CURLFile($filePath, $mimeType, $fileName);
            
            // Mandant extrahieren
            $mandant = $this->extractMandantFromFilename($fileName);
            
            // Upload
            $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
            $indexData = [
                'indexFields[0][name]' => 'Mandant',
                'indexFields[0][value]' => $mandant,
                'files[0]' => $uploadFile
            ];
            
            $response = $client->post($route, $indexData);
            
            if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
                $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
                $this->log("✅ Upload erfolgreich - ID: " . $documentId . " - " . $fileName);
                $client->destroySession();
                return true;
            }
            
            $client->destroySession();
            return false;
            
        } catch (Exception $e) {
            $this->log("Upload-Fehler " . $fileName . ": " . $e->getMessage());
            return false;
        }
    }

    private function extractMandantFromFilename($fileName)
    {
        $parts = explode('_', $fileName);
        if (count($parts) >= 2) {
            return ucfirst($parts[0]);
        }
        return "Batch Upload " . date('Y-m-d H:i');
    }

    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        return isset($mimeTypes[strtolower($extension)]) ? $mimeTypes[strtolower($extension)] : 'application/octet-stream';
    }

    private function moveToProcessed($filePath)
    {
        $fileName = basename($filePath);
        $timestamp = date('Y-m-d_H-i-s');
        $newFileName = $timestamp . '_' . $fileName;
        $newPath = $this->processedDir . $newFileName;
        
        if (rename($filePath, $newPath)) {
            $this->log("Datei archiviert: " . $newFileName);
        } else {
            $this->log("FEHLER: Konnte Datei nicht verschieben: " . $fileName);
        }
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Batch ausführen
if (php_sapi_name() === 'cli') {
    $batch = new RechnungBatch();
    $batch->run();
} else {
    echo "Dieses Script muss über die Kommandozeile ausgeführt werden!\n";
}
