<?php

require_once(__DIR__ . '/init.php');

echo "=== Interaktive Rechnung Upload ===\n\n";

function getUserInput($prompt, $default = null) {
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

function listAvailableFiles($directory) {
    $files = glob($directory . '*');
    $fileList = [];
    
    echo "Verfügbare Dateien:\n";
    echo str_repeat("-", 30) . "\n";
    
    $index = 1;
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileName = basename($file);
            echo "[$index] $fileName\n";
            $fileList[$index] = $file;
            $index++;
        }
    }
    
    if (empty($fileList)) {
        echo "Keine Dateien gefunden.\n";
        return null;
    }
    
    echo str_repeat("-", 30) . "\n";
    return $fileList;
}

try {
    // 1. Datei auswählen
    echo "📁 Datei-Auswahl:\n";
    $fileList = listAvailableFiles(__DIR__ . '/files/');
    
    if (!$fileList) {
        echo "❌ Keine Dateien im files/ Ordner gefunden.\n";
        echo "Lege zuerst Dateien in den files/ Ordner.\n";
        exit(1);
    }
    
    $fileChoice = getUserInput("Wähle eine Datei (Nummer)", "1");
    
    if (!isset($fileList[$fileChoice])) {
        echo "❌ Ungültige Auswahl.\n";
        exit(1);
    }
    
    $selectedFile = $fileList[$fileChoice];
    $fileName = basename($selectedFile);
    
    echo "✅ Gewählt: $fileName\n\n";
    
    // 2. Mandant eingeben
    echo "🏢 Mandant-Informationen:\n";
    $mandant = getUserInput("Mandant/Firma-Name", "Meine Firma GmbH");
    
    if (empty($mandant)) {
        echo "❌ Mandant ist erforderlich.\n";
        exit(1);
    }
    
    echo "✅ Mandant: $mandant\n\n";
    
    // 3. Optionale zusätzliche Informationen
    echo "📋 Zusätzliche Informationen (optional):\n";
    $rechnungsnummer = getUserInput("Rechnungsnummer (optional)");
    $betrag = getUserInput("Betrag (optional)");
    $beschreibung = getUserInput("Beschreibung (optional)");
    
    // 4. Zusammenfassung
    echo "\n📊 Upload-Zusammenfassung:\n";
    echo str_repeat("=", 40) . "\n";
    echo "Datei: $fileName\n";
    echo "Mandant: $mandant\n";
    if ($rechnungsnummer) echo "Rechnungsnummer: $rechnungsnummer\n";
    if ($betrag) echo "Betrag: $betrag\n";
    if ($beschreibung) echo "Beschreibung: $beschreibung\n";
    echo str_repeat("=", 40) . "\n";
    
    $confirm = getUserInput("Upload starten? (j/N)", "j");
    
    if (strtolower($confirm) !== 'j' && strtolower($confirm) !== 'ja') {
        echo "❌ Upload abgebrochen.\n";
        exit(0);
    }
    
    // 5. Upload durchführen
    echo "\n🚀 Starte Upload...\n";
    
    $client = new CurlClient();
    $client->authenticate();
    echo "✅ Authentifizierung erfolgreich\n";
    
    // Datei für Upload vorbereiten
    $fileInfo = pathinfo($fileName);
    $fileExtension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    $mimeType = $mimeTypes[strtolower($fileExtension)] ?? 'application/octet-stream';
    
    $uploadFile = new CURLFile($selectedFile, $mimeType, $fileName);
    
    // Archive-Route
    $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
    
    // Index-Daten mit benutzerdefinierten Werten
    $indexData = [
        'indexFields[0][name]' => 'Mandant',
        'indexFields[0][value]' => $mandant,
        'files[0]' => $uploadFile
    ];
    
    echo "📤 Sende Daten zu JobRouter...\n";
    
    $response = $client->post($route, $indexData);
    
    if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
        $documentId = $response['archivedocumentrevisions'][0]['revisionId'];
        
        echo "🎉 Upload erfolgreich!\n";
        echo "📋 Dokument-ID: $documentId\n";
        echo "📁 Datei: $fileName\n";
        echo "🏢 Mandant: $mandant\n";
        
        // Log-Eintrag erstellen
        $logEntry = date('Y-m-d H:i:s') . " - Upload erfolgreich - ID: $documentId - Datei: $fileName - Mandant: $mandant";
        if ($rechnungsnummer) $logEntry .= " - RG-Nr: $rechnungsnummer";
        if ($betrag) $logEntry .= " - Betrag: $betrag";
        $logEntry .= "\n";
        
        file_put_contents(__DIR__ . '/logs/interactive_upload_' . date('Y-m-d') . '.log', $logEntry, FILE_APPEND | LOCK_EX);
        
    } else {
        echo "❌ Upload fehlgeschlagen\n";
        echo "Antwort: ";
        print_r($response);
    }
    
    $client->destroySession();
    echo "✅ Session beendet\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Upload abgeschlossen ===\n";
