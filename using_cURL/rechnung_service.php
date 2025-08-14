<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/ERechnungParser.php');

class RechnungService
{
    private $incomingDir;
    private $processedDir;
    private $logFile;
    private $client;
    private $running = true;

    public function __construct()
    {
        $this->incomingDir = __DIR__ . '/incoming/';
        $this->processedDir = __DIR__ . '/processed/';
        $this->logFile = __DIR__ . '/logs/service_' . date('Y-m-d') . '.log';
        
        // Signal Handler für graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
        }
    }

    public function start()
    {
        $this->log("=== Rechnung Upload Service gestartet ===");
        $this->log("Überwache Ordner: " . $this->incomingDir);
        $this->log("Verarbeitete Dateien: " . $this->processedDir);
        
        echo "🚀 Rechnung Upload Service gestartet...\n";
        echo "📁 Überwache: " . $this->incomingDir . "\n";
        echo "📋 Logs: " . $this->logFile . "\n";
        echo "⏹️  Zum Beenden: Strg+C\n\n";

        while ($this->running) {
            try {
                $this->scanForFiles();
                sleep(5); // 5 Sekunden warten
                
                // Signal handling (falls verfügbar)
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                
            } catch (Exception $e) {
                $this->log("FEHLER im Service: " . $e->getMessage());
                echo "❌ Fehler: " . $e->getMessage() . "\n";
                sleep(10); // Bei Fehler länger warten
            }
        }
        
        $this->log("Service beendet");
        echo "⏹️ Service beendet\n";
    }

    private function scanForFiles()
    {
        $files = glob($this->incomingDir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileName = basename($file);
                echo "📄 Neue E-Rechnung gefunden: " . $fileName . "\n";
                $this->log("Neue E-Rechnung gefunden: " . $fileName);
                
                if ($this->uploadERechnung($file)) {
                    $this->moveToProcessed($file);
                } else {
                    $this->log("E-Rechnung Upload fehlgeschlagen für: " . $fileName);
                    echo "❌ E-Rechnung Upload fehlgeschlagen: " . $fileName . "\n";
                }
            }
        }
    }

    private function uploadERechnung($filePath)
    {
        try {
            $fileName = basename($filePath);
            $this->log("Starte E-Rechnung Upload für: " . $fileName);
            
            // E-Rechnung parsen
            $metadata = ERechnungParser::parseERechnung($filePath);
            echo "📊 E-Rechnung-Typ: " . $metadata['type'] . "\n";
            
            if ($metadata['rechnungsnummer']) {
                echo "📋 Rechnungsnummer: " . $metadata['rechnungsnummer'] . "\n";
            }
            if ($metadata['betrag']) {
                echo "💰 Betrag: " . $metadata['betrag'] . " " . $metadata['waehrung'] . "\n";
            }
            if ($metadata['lieferant']) {
                echo "🏢 Lieferant: " . $metadata['lieferant'] . "\n";
            }
            
            // Client erstellen und authentifizieren
            $this->client = new CurlClient();
            $this->client->authenticate();
            
            // Datei für Upload vorbereiten
            $fileInfo = pathinfo($fileName);
            $fileExtension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
            $mimeType = ERechnungParser::getMimeType($fileExtension);
            
            $uploadFile = new CURLFile($filePath, $mimeType, $fileName);
            
            // Mandant aus geparsten Daten oder Dateiname
            $mandant = $metadata['lieferant'] ?? $this->extractMandantFromFilename($fileName);
            if (!$mandant) {
                $mandant = "E-Rechnung " . ($metadata['type'] ?? 'Unbekannt');
            }
            
            // Archive-Route
            $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
            
            // Index-Daten mit E-Rechnung-Metadaten
            $indexData = [
                'indexFields[0][name]' => 'Mandant',
                'indexFields[0][value]' => $mandant,
                'files[0]' => $uploadFile
            ];
            
            // Zusätzliche Metadaten als Kommentar hinzufügen
            $metadataComment = $this->buildMetadataComment($metadata);
            if ($metadataComment) {
                // Falls es ein Kommentar-Feld gibt, verwende es
                $indexData['indexFields[1][name]'] = 'Bemerkung';
                $indexData['indexFields[1][value]'] = $metadataComment;
            }
            
            // Upload durchführen
            $response = $this->client->post($route, $indexData);
            
            if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
                $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
                $logMessage = "✅ E-Rechnung Upload erfolgreich - Dokument-ID: " . $documentId . " für Datei: " . $fileName;
                $logMessage .= " | Typ: " . $metadata['type'];
                if ($metadata['rechnungsnummer']) {
                    $logMessage .= " | RG-Nr: " . $metadata['rechnungsnummer'];
                }
                if ($metadata['betrag']) {
                    $logMessage .= " | Betrag: " . $metadata['betrag'] . " " . $metadata['waehrung'];
                }
                
                $this->log($logMessage);
                echo "✅ E-Rechnung Upload erfolgreich - ID: " . $documentId . " (" . $fileName . ")\n";
                
                // Session beenden
                $this->client->destroySession();
                return true;
            } else {
                $this->log("E-Rechnung Upload-Antwort unvollständig für: " . $fileName);
                $this->client->destroySession();
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("E-Rechnung Upload-Fehler für " . $fileName . ": " . $e->getMessage());
            if ($this->client) {
                try {
                    $this->client->destroySession();
                } catch (Exception $ex) {
                    // Session cleanup fehler ignorieren
                }
            }
            return false;
        }
    }
    
    private function buildMetadataComment($metadata)
    {
        $comments = [];
        
        if ($metadata['rechnungsnummer']) {
            $comments[] = "RG-Nr: " . $metadata['rechnungsnummer'];
        }
        if ($metadata['datum']) {
            $comments[] = "Datum: " . $metadata['datum'];
        }
        if ($metadata['betrag']) {
            $comments[] = "Betrag: " . $metadata['betrag'] . " " . $metadata['waehrung'];
        }
        if ($metadata['kunde']) {
            $comments[] = "Kunde: " . $metadata['kunde'];
        }
        if ($metadata['faelligkeitsdatum']) {
            $comments[] = "Fällig: " . $metadata['faelligkeitsdatum'];
        }
        if ($metadata['type']) {
            $comments[] = "Format: " . $metadata['type'];
        }
        
        return implode(' | ', $comments);
    }

    private function uploadFile($filePath)
    {
        try {
            $fileName = basename($filePath);
            $this->log("Starte Upload für: " . $fileName);
            
            // Client erstellen und authentifizieren
            $this->client = new CurlClient();
            $this->client->authenticate();
            
            // Datei für Upload vorbereiten
            $fileInfo = pathinfo($fileName);
            $fileExtension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
            $mimeType = $this->getMimeType($fileExtension);
            
            $uploadFile = new CURLFile($filePath, $mimeType, $fileName);
            
            // Mandant aus Dateiname extrahieren (falls möglich)
            $mandant = $this->extractMandantFromFilename($fileName);
            
            // Archive-Route
            $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
            
            // Index-Daten
            $indexData = [
                'indexFields[0][name]' => 'Mandant',
                'indexFields[0][value]' => $mandant,
                'files[0]' => $uploadFile
            ];
            
            // Upload durchführen
            $response = $this->client->post($route, $indexData);
            
            if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
                $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
                $this->log("✅ Upload erfolgreich - Dokument-ID: " . $documentId . " für Datei: " . $fileName);
                echo "✅ Upload erfolgreich - ID: " . $documentId . " (" . $fileName . ")\n";
                
                // Session beenden
                $this->client->destroySession();
                return true;
            } else {
                $this->log("Upload-Antwort unvollständig für: " . $fileName);
                $this->client->destroySession();
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Upload-Fehler für " . $fileName . ": " . $e->getMessage());
            if ($this->client) {
                try {
                    $this->client->destroySession();
                } catch (Exception $ex) {
                    // Session cleanup fehler ignorieren
                }
            }
            return false;
        }
    }

    private function extractMandantFromFilename($fileName)
    {
        // Einfache Mandant-Extraktion aus Dateiname
        // Format: mandant_rechnung_2025001.pdf -> "mandant"
        $parts = explode('_', $fileName);
        if (count($parts) >= 2) {
            return ucfirst($parts[0]);
        }
        
        // Standard-Mandant falls kein Muster erkannt
        return "Automatischer Upload";
    }

    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
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
            $this->log("Datei verschoben: " . $fileName . " -> " . $newFileName);
            echo "📁 Datei archiviert: " . $newFileName . "\n";
        } else {
            $this->log("FEHLER: Konnte Datei nicht verschieben: " . $fileName);
            echo "⚠️ Warnung: Datei konnte nicht archiviert werden: " . $fileName . "\n";
        }
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public function shutdown()
    {
        $this->running = false;
        $this->log("Shutdown-Signal empfangen");
        echo "\n🛑 Shutdown-Signal empfangen, beende Service...\n";
    }
}

// Service starten
if (php_sapi_name() === 'cli') {
    $service = new RechnungService();
    $service->start();
} else {
    echo "Dieser Service muss über die Kommandozeile gestartet werden!\n";
    echo "Verwende: php rechnung_service.php\n";
}
