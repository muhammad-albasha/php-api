<?php

require_once(__DIR__ . '/init.php');

echo "=== Erweiterte Archive-Suche ===\n\n";

try {
    $client = new CurlClient();
    $client->authenticate();
    echo "✓ Authentifizierung erfolgreich\n\n";

    // Test verschiedene Parameter und Endpunkte
    $tests = [
        'application/users/' . USERNAME . '/archives' => 'Standard Archive',
        'application/users/' . USERNAME . '/archives?limit=100' => 'Archive mit Limit',
        'application/users/' . USERNAME . '/archives?offset=0&limit=50' => 'Archive mit Paginierung',
        'application/users/' . USERNAME . '/archives?includeAll=true' => 'Alle Archive einschließen',
        'application/jobarchive/archives' => 'JobArchive Endpunkt',
        'application/archives' => 'Globale Archive',
    ];

    foreach ($tests as $endpoint => $description) {
        echo "Test: $description\n";
        echo "URL: " . BASE_URL . $endpoint . "\n";
        echo str_repeat("-", 60) . "\n";
        
        $result = $client->get($endpoint);
        
        if (!empty($result)) {
            echo "✓ Erfolg!\n";
            
            // Archive zählen
            if (isset($result['users']['archives'])) {
                $count = count($result['users']['archives']);
                echo "Anzahl Archive: $count\n";
                
                foreach ($result['users']['archives'] as $i => $archive) {
                    echo "  Archiv " . ($i+1) . ": " . $archive['name'] . "\n";
                }
            } elseif (isset($result['archives'])) {
                $count = count($result['archives']);
                echo "Anzahl Archive: $count\n";
                
                foreach ($result['archives'] as $i => $archive) {
                    echo "  Archiv " . ($i+1) . ": " . (isset($archive['name']) ? $archive['name'] : 'Unbekannt') . "\n";
                }
            } else {
                echo "Unerwartete Struktur:\n";
                print_r($result);
            }
        } else {
            echo "❌ Keine Daten erhalten\n";
        }
        echo "\n" . str_repeat("=", 70) . "\n\n";
    }

    // Teste auch direkte Archive-Zugriffe
    echo "Teste direkte Archive-IDs...\n";
    echo str_repeat("-", 60) . "\n";
    
    // Bekanntes Archiv
    $knownGuid = '8412F1B5-8BA7-56E0-C864-73AA09167FE5';
    $archiveInfo = $client->get("application/archives/$knownGuid");
    
    if (!empty($archiveInfo)) {
        echo "✓ Direkte Archive-Info für $knownGuid:\n";
        print_r($archiveInfo);
    } else {
        echo "❌ Keine direkte Archive-Info verfügbar\n";
    }

    $client->destroySession();
    echo "\n✓ Session beendet\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Suche abgeschlossen ===\n";
