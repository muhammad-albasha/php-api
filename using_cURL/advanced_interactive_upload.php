<?php

require_once(__DIR__ . '/init.php');

echo "=== JobRouter Dokument Upload - Interaktiv ===\n\n";

class InteractiveUploader
{
    private $client;
    
    public function __construct()
    {
        $this->client = new CurlClient();
    }
    
    public function run()
    {
        try {
            echo "🎯 Willkommen beim interaktiven JobRouter Upload!\n";
            echo "Hier kannst du Dokumente mit benutzerdefinierten Daten hochladen.\n\n";
            
            // 1. Upload-Typ wählen
            $uploadType = $this->selectUploadType();
            
            // 2. Datei auswählen
            $selectedFile = $this->selectFile();
            
            // 3. Daten eingeben
            $uploadData = $this->collectUploadData($uploadType);
            
            // 4. Bestätigung
            $this->showSummary($selectedFile, $uploadData);
            
            if (!$this->confirmUpload()) {
                echo "❌ Upload abgebrochen.\n";
                return;
            }
            
            // 5. Upload durchführen
            $this->performUpload($selectedFile, $uploadData);
            
        } catch (Exception $e) {
            echo "❌ Fehler: " . $e->getMessage() . "\n";
        }
    }
    
    private function selectUploadType()
    {
        echo "📋 Upload-Typ auswählen:\n";
        echo "[1] Einfach (nur Mandant)\n";
        echo "[2] Erweitert (alle verfügbaren Felder)\n";
        echo "[3] E-Rechnung (mit Parsing)\n";
        
        $choice = $this->getUserInput("Wähle Upload-Typ", "1");
        
        switch ($choice) {
            case "2":
                return "extended";
            case "3":
                return "erechnung";
            default:
                return "simple";
        }
    }
    
    private function selectFile()
    {
        $directories = [
            'files/' => 'Standard-Dateien',
            'incoming/' => 'E-Rechnungen/Neue Dateien'
        ];
        
        echo "\n📁 Ordner auswählen:\n";
        $dirIndex = 1;
        $dirList = [];
        
        foreach ($directories as $dir => $description) {
            echo "[$dirIndex] $description ($dir)\n";
            $dirList[$dirIndex] = $dir;
            $dirIndex++;
        }
        
        $dirChoice = $this->getUserInput("Wähle Ordner", "1");
        $selectedDir = $dirList[$dirChoice] ?? 'files/';
        
        echo "\n📂 Dateien in $selectedDir:\n";
        $fileList = $this->listFiles(__DIR__ . '/' . $selectedDir);
        
        if (!$fileList) {
            throw new Exception("Keine Dateien im gewählten Ordner gefunden.");
        }
        
        $fileChoice = $this->getUserInput("Wähle eine Datei (Nummer)", "1");
        
        if (!isset($fileList[$fileChoice])) {
            throw new Exception("Ungültige Datei-Auswahl.");
        }
        
        $selectedFile = $fileList[$fileChoice];
        echo "✅ Gewählt: " . basename($selectedFile) . "\n";
        
        return $selectedFile;
    }
    
    private function collectUploadData($uploadType)
    {
        echo "\n🏢 Daten-Eingabe:\n";
        
        $data = [];
        
        // Mandant ist immer erforderlich
        $data['mandant'] = $this->getUserInput("Mandant/Firma-Name", "Standard Firma");
        
        if ($uploadType === "extended") {
            echo "\n📝 Erweiterte Felder (Enter = überspringen):\n";
            $data['rechnungsnummer'] = $this->getUserInput("Rechnungsnummer");
            $data['betrag'] = $this->getUserInput("Betrag (nur Zahl, ohne €)");
            $data['datum'] = $this->getUserInput("Datum (YYYY-MM-DD)", date('Y-m-d'));
            $data['beschreibung'] = $this->getUserInput("Beschreibung");
            $data['kategorie'] = $this->getUserInput("Kategorie");
            $data['status'] = $this->getUserInput("Status", "Neu");
        }
        
        if ($uploadType === "erechnung") {
            echo "\n🧾 E-Rechnung-Felder:\n";
            $data['rechnungsnummer'] = $this->getUserInput("Rechnungsnummer");
            $data['betrag'] = $this->getUserInput("Betrag");
            $data['waehrung'] = $this->getUserInput("Währung", "EUR");
            $data['lieferant'] = $this->getUserInput("Lieferant");
            $data['rechnungsdatum'] = $this->getUserInput("Rechnungsdatum (YYYY-MM-DD)", date('Y-m-d'));
            $data['faelligkeitsdatum'] = $this->getUserInput("Fälligkeitsdatum (YYYY-MM-DD)");
            $data['umsatzsteuer'] = $this->getUserInput("Umsatzsteuer-Betrag");
        }
        
        return $data;
    }
    
    private function showSummary($filePath, $data)
    {
        echo "\n📊 Upload-Zusammenfassung:\n";
        echo str_repeat("=", 50) . "\n";
        echo "📁 Datei: " . basename($filePath) . "\n";
        echo "🏢 Mandant: " . $data['mandant'] . "\n";
        
        foreach ($data as $key => $value) {
            if ($key !== 'mandant' && !empty($value)) {
                $label = ucfirst(str_replace('_', ' ', $key));
                echo "📋 $label: $value\n";
            }
        }
        
        echo str_repeat("=", 50) . "\n";
    }
    
    private function confirmUpload()
    {
        $confirm = $this->getUserInput("Upload starten? (j/N)", "j");
        return in_array(strtolower($confirm), ['j', 'ja', 'y', 'yes']);
    }
    
    private function performUpload($filePath, $data)
    {
        echo "\n🚀 Starte Upload...\n";
        
        $this->client->authenticate();
        echo "✅ Authentifizierung erfolgreich\n";
        
        // Datei vorbereiten
        $fileName = basename($filePath);
        $fileInfo = pathinfo($fileName);
        $fileExtension = $fileInfo['extension'] ?? '';
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'xml' => 'application/xml',
            'json' => 'application/json',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        $mimeType = $mimeTypes[strtolower($fileExtension)] ?? 'application/octet-stream';
        $uploadFile = new CURLFile($filePath, $mimeType, $fileName);
        
        // Index-Daten vorbereiten
        $indexData = [
            'indexFields[0][name]' => 'Mandant',
            'indexFields[0][value]' => $data['mandant'],
            'files[0]' => $uploadFile
        ];
        
        // Zusätzliche Felder als Kommentar hinzufügen
        $comments = [];
        foreach ($data as $key => $value) {
            if ($key !== 'mandant' && !empty($value)) {
                $comments[] = ucfirst($key) . ": " . $value;
            }
        }
        
        if (!empty($comments)) {
            $commentString = implode(" | ", $comments);
            // Kommentar-Feld falls verfügbar
            $indexData['indexFields[1][name]'] = 'Bemerkung';
            $indexData['indexFields[1][value]'] = $commentString;
        }
        
        echo "📤 Sende Daten zu JobRouter...\n";
        
        $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
        $response = $this->client->post($route, $indexData);
        
        if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
            $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
            
            echo "🎉 Upload erfolgreich!\n";
            echo "📋 Dokument-ID: $documentId\n";
            echo "📁 Datei: $fileName\n";
            echo "🏢 Mandant: " . $data['mandant'] . "\n";
            
            // Detailliertes Log schreiben
            $this->writeLog($documentId, $fileName, $data);
            
        } else {
            echo "❌ Upload fehlgeschlagen\n";
            echo "Antwort: ";
            print_r($response);
        }
        
        $this->client->destroySession();
        echo "✅ Session beendet\n";
    }
    
    private function writeLog($documentId, $fileName, $data)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'document_id' => $documentId,
            'filename' => $fileName,
            'mandant' => $data['mandant']
        ];
        
        foreach ($data as $key => $value) {
            if ($key !== 'mandant' && !empty($value)) {
                $logData[$key] = $value;
            }
        }
        
        $logEntry = json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents(__DIR__ . '/logs/interactive_uploads_' . date('Y-m-d') . '.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function listFiles($directory)
    {
        $files = glob($directory . '*');
        $fileList = [];
        
        echo "Verfügbare Dateien:\n";
        echo str_repeat("-", 40) . "\n";
        
        $index = 1;
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileName = basename($file);
                $fileSize = $this->formatFileSize(filesize($file));
                echo "[$index] $fileName ($fileSize)\n";
                $fileList[$index] = $file;
                $index++;
            }
        }
        
        if (empty($fileList)) {
            echo "Keine Dateien gefunden.\n";
            return null;
        }
        
        echo str_repeat("-", 40) . "\n";
        return $fileList;
    }
    
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    private function getUserInput($prompt, $default = null)
    {
        if ($default) {
            echo "$prompt [$default]: ";
        } else {
            echo "$prompt: ";
        }
        
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        return empty($input) && $default ? $default : $input;
    }
}

// Interaktiven Uploader starten
if (php_sapi_name() === 'cli') {
    $uploader = new InteractiveUploader();
    $uploader->run();
} else {
    echo "Dieses Script muss über die Kommandozeile ausgeführt werden!\n";
}

echo "\n=== Upload-Session beendet ===\n";
