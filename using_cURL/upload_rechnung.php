<?php

require_once(__DIR__ . '/init.php');

echo "=== Rechnung zu JobRouter Archive hochladen ===\n\n";

try {
    // Client erstellen und authentifizieren
    $client = new CurlClient();
    echo "1. Authentifizierung...\n";
    $client->authenticate();
    echo "   ✓ Erfolgreich authentifiziert!\n\n";

    // Archive-Route für Rechnung
    $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
    echo "2. Hochladen zur Route: " . $route . "\n";

    // Test-Rechnung-Datei
    $rechnungFile = new CURLFile(FILE_STORAGE . 'test_rechnung.txt', 'text/plain', 'rechnung_2025_001.txt');
    
    // Index-Felder für die Rechnung (basierend auf Archive-Definition)
    $indexData = [
        'indexFields[0][name]' => 'Mandant',
        'indexFields[0][value]' => 'Firma Mustermann GmbH',
        'files[0]' => $rechnungFile
    ];

    echo "3. Sende Rechnung-Daten...\n";
    echo "   - Mandant: Firma Mustermann GmbH\n";
    echo "   - Datei: test_rechnung.txt\n\n";

    $response = $client->post($route, $indexData);

    if (isset($response['archivedocumentrevisions']) && count($response['archivedocumentrevisions']) > 0) {
        echo "✅ Rechnung erfolgreich archiviert!\n";
        echo "   Dokument-ID: " . $response['archivedocumentrevisions'][0]['revisionId'] . "\n";
        if (isset($response['archivedocumentrevisions'][0]['documentId'])) {
            echo "   Archiv-Dokument-ID: " . $response['archivedocumentrevisions'][0]['documentId'] . "\n";
        }
    } else {
        echo "⚠️ Antwort erhalten, aber unerwartete Struktur:\n";
        print_r($response);
    }

    // Session beenden
    echo "\n4. Session beenden...\n";
    $client->destroySession();
    echo "   ✓ Session beendet!\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "\nMögliche Ursachen:\n";
    echo "- Index-Felder stimmen nicht mit Archive-Definition überein\n";
    echo "- Keine Berechtigung zum Archivieren\n";
    echo "- Archive-GUID ist falsch\n";
}

echo "\n=== Fertig ===\n";
