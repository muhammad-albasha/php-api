<?php

require_once(__DIR__ . '/init.php');

class UploadManager
{
    public function showMainMenu()
    {
        while (true) {
            $this->clearScreen();
            echo "╔════════════════════════════════════════════════╗\n";
            echo "║              JobRouter Upload Manager          ║\n";
            echo "╠════════════════════════════════════════════════╣\n";
            echo "║                                                ║\n";
            echo "║  [1] 📁 Einfacher Upload (Mandant eingeben)   ║\n";
            echo "║  [2] 🧾 Erweiterte Upload (alle Felder)       ║\n";
            echo "║  [3] ⚡ E-Rechnung Upload                     ║\n";
            echo "║  [4] 📊 Upload-Verlauf anzeigen               ║\n";
            echo "║  [5] ⚙️  Einstellungen                        ║\n";
            echo "║  [6] ❌ Beenden                               ║\n";
            echo "║                                                ║\n";
            echo "╚════════════════════════════════════════════════╝\n\n";
            
            $choice = $this->getUserInput("Wähle eine Option (1-6)");
            
            switch ($choice) {
                case "1":
                    $this->simpleUpload();
                    break;
                case "2":
                    $this->advancedUpload();
                    break;
                case "3":
                    $this->eRechnungUpload();
                    break;
                case "4":
                    $this->showUploadHistory();
                    break;
                case "5":
                    $this->showSettings();
                    break;
                case "6":
                    echo "👋 Auf Wiedersehen!\n";
                    return;
                default:
                    echo "❌ Ungültige Auswahl. Drücke Enter zum Fortfahren...";
                    $this->waitForEnter();
            }
        }
    }
    
    private function simpleUpload()
    {
        $this->clearScreen();
        echo "📁 EINFACHER UPLOAD\n";
        echo str_repeat("=", 50) . "\n\n";
        
        try {
            // Datei auswählen
            $file = $this->selectFileFromMenu();
            if (!$file) return;
            
            // Mandant eingeben
            echo "\n🏢 Mandant-Informationen:\n";
            $mandant = $this->getUserInput("Mandant/Firma-Name");
            
            if (empty($mandant)) {
                echo "❌ Mandant ist erforderlich!\n";
                $this->waitForEnter();
                return;
            }
            
            // Upload durchführen
            $this->performSimpleUpload($file, $mandant);
            
        } catch (Exception $e) {
            echo "❌ Fehler: " . $e->getMessage() . "\n";
        }
        
        $this->waitForEnter();
    }
    
    private function advancedUpload()
    {
        $this->clearScreen();
        echo "🧾 ERWEITERTE UPLOAD\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Führe advanced_interactive_upload.php aus
        echo "Starte erweiterten Upload-Assistenten...\n\n";
        system('php ' . __DIR__ . '/advanced_interactive_upload.php');
        
        $this->waitForEnter();
    }
    
    private function eRechnungUpload()
    {
        $this->clearScreen();
        echo "⚡ E-RECHNUNG UPLOAD\n";
        echo str_repeat("=", 50) . "\n\n";
        
        echo "E-Rechnung-Verarbeitung mit automatischer Workflow-Entscheidung...\n\n";
        
        // E-Rechnung-Batch verarbeiten
        system('php ' . __DIR__ . '/rechnung_batch.php');
        
        $this->waitForEnter();
    }
    
    private function showUploadHistory()
    {
        $this->clearScreen();
        echo "📊 UPLOAD-VERLAUF\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $logFiles = glob(__DIR__ . '/logs/*.log');
        
        if (empty($logFiles)) {
            echo "Keine Log-Dateien gefunden.\n";
            $this->waitForEnter();
            return;
        }
        
        // Neueste Log-Datei anzeigen
        $latestLog = max($logFiles);
        echo "📋 Neueste Aktivitäten aus: " . basename($latestLog) . "\n";
        echo str_repeat("-", 50) . "\n";
        
        $lines = file($latestLog);
        $recentLines = array_slice($lines, -10); // Letzte 10 Einträge
        
        foreach ($recentLines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // JSON-Log-Einträge formatieren
                $data = json_decode($line, true);
                if ($data) {
                    echo "🕒 " . $data['timestamp'] . "\n";
                    echo "   📄 Datei: " . $data['filename'] . "\n";
                    echo "   🏢 Mandant: " . $data['mandant'] . "\n";
                    echo "   🆔 ID: " . $data['document_id'] . "\n";
                    echo "\n";
                } else {
                    echo $line . "\n";
                }
            }
        }
        
        $this->waitForEnter();
    }
    
    private function showSettings()
    {
        $this->clearScreen();
        echo "⚙️ EINSTELLUNGEN\n";
        echo str_repeat("=", 50) . "\n\n";
        
        echo "📋 Aktuelle Konfiguration:\n";
        echo "URL: " . BASE_URL . "\n";
        echo "Benutzer: " . USERNAME . "\n";
        echo "Archive: " . ARCHIVE . "\n";
        echo "Cookie-Pfad: " . CURL_COOKIE_PATH . "\n";
        echo "Datei-Speicher: " . FILE_STORAGE . "\n\n";
        
        echo "📊 Statistiken:\n";
        $filesCount = count(glob(__DIR__ . '/files/*'));
        $incomingCount = count(glob(__DIR__ . '/incoming/*'));
        $processedCount = count(glob(__DIR__ . '/processed/*'));
        
        echo "Dateien im files/ Ordner: $filesCount\n";
        echo "Dateien im incoming/ Ordner: $incomingCount\n";
        echo "Verarbeitete Dateien: $processedCount\n\n";
        
        echo "🔧 Verfügbare Aktionen:\n";
        echo "[1] Test-Verbindung zu JobRouter\n";
        echo "[2] Archive-Informationen anzeigen\n";
        echo "[3] Zurück zum Hauptmenü\n\n";
        
        $choice = $this->getUserInput("Wähle eine Aktion");
        
        switch ($choice) {
            case "1":
                $this->testConnection();
                break;
            case "2":
                $this->showArchiveInfo();
                break;
            default:
                return;
        }
        
        $this->waitForEnter();
    }
    
    private function testConnection()
    {
        echo "\n🔍 Teste Verbindung zu JobRouter...\n";
        
        try {
            $client = new CurlClient();
            $client->authenticate();
            echo "✅ Verbindung erfolgreich!\n";
            echo "✅ Authentifizierung erfolgreich!\n";
            $client->destroySession();
            echo "✅ Session erfolgreich beendet!\n";
        } catch (Exception $e) {
            echo "❌ Verbindungsfehler: " . $e->getMessage() . "\n";
        }
    }
    
    private function showArchiveInfo()
    {
        echo "\n📂 Archive-Informationen...\n";
        system('php ' . __DIR__ . '/archive_info.php');
    }
    
    private function selectFileFromMenu()
    {
        echo "📁 Datei auswählen:\n";
        echo "[1] Aus files/ Ordner\n";
        echo "[2] Aus incoming/ Ordner\n";
        echo "[3] Abbrechen\n\n";
        
        $choice = $this->getUserInput("Wähle Ordner");
        
        $directory = '';
        switch ($choice) {
            case "1":
                $directory = __DIR__ . '/files/';
                break;
            case "2":
                $directory = __DIR__ . '/incoming/';
                break;
            default:
                return null;
        }
        
        $files = glob($directory . '*');
        $fileList = [];
        
        echo "\nVerfügbare Dateien:\n";
        echo str_repeat("-", 30) . "\n";
        
        $index = 1;
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileName = basename($file);
                $fileSize = round(filesize($file) / 1024, 1) . ' KB';
                echo "[$index] $fileName ($fileSize)\n";
                $fileList[$index] = $file;
                $index++;
            }
        }
        
        if (empty($fileList)) {
            echo "Keine Dateien gefunden.\n";
            return null;
        }
        
        echo str_repeat("-", 30) . "\n";
        $fileChoice = $this->getUserInput("Wähle Datei-Nummer");
        
        return $fileList[$fileChoice] ?? null;
    }
    
    private function performSimpleUpload($filePath, $mandant)
    {
        echo "\n🚀 Starte Upload...\n";
        
        try {
            $client = new CurlClient();
            $client->authenticate();
            
            $fileName = basename($filePath);
            $uploadFile = new CURLFile($filePath, 'application/octet-stream', $fileName);
            
            $indexData = [
                'indexFields[0][name]' => 'Mandant',
                'indexFields[0][value]' => $mandant,
                'files[0]' => $uploadFile
            ];
            
            $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
            $response = $client->post($route, $indexData);
            
            if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
                $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
                
                echo "🎉 Upload erfolgreich!\n";
                echo "📋 Dokument-ID: $documentId\n";
                echo "📁 Datei: $fileName\n";
                echo "🏢 Mandant: $mandant\n";
                
                // Log schreiben
                $logEntry = json_encode([
                    'timestamp' => date('Y-m-d H:i:s'),
                    'document_id' => $documentId,
                    'filename' => $fileName,
                    'mandant' => $mandant,
                    'type' => 'simple_upload'
                ], JSON_UNESCAPED_UNICODE) . "\n";
                
                file_put_contents(__DIR__ . '/logs/uploads_' . date('Y-m-d') . '.log', $logEntry, FILE_APPEND | LOCK_EX);
                
            } else {
                echo "❌ Upload fehlgeschlagen\n";
            }
            
            $client->destroySession();
            
        } catch (Exception $e) {
            echo "❌ Upload-Fehler: " . $e->getMessage() . "\n";
        }
    }
    
    private function clearScreen()
    {
        // Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            // Unix/Linux/Mac
            system('clear');
        }
    }
    
    private function getUserInput($prompt)
    {
        echo "$prompt: ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        return $input;
    }
    
    private function waitForEnter()
    {
        echo "\nDrücke Enter zum Fortfahren...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
}

// Upload Manager starten
if (php_sapi_name() === 'cli') {
    $manager = new UploadManager();
    $manager->showMainMenu();
} else {
    echo "Dieses Script muss über die Kommandozeile ausgeführt werden!\n";
}
