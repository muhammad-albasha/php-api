<?php

require_once(__DIR__ . '/init.php');

echo "=== JobRouter Demo - Prozess-Discovery ===\n\n";

try {
    $client = new CurlClient();
    $client->authenticate();

    // Verschiedene mögliche Endpunkte testen
    $endpoints = [
        'application/jobdata/tables',
        'application/users/' . USERNAME . '/processes',
        'application/users/' . USERNAME . '/incidents',
        'application/workflow/processes',
        'application/incidents/processes',
        'application/jobarchive/archives'
    ];

    foreach ($endpoints as $endpoint) {
        echo "Teste Endpunkt: " . $endpoint . "\n";
        
        $response = $client->get($endpoint);
        
        if (!empty($response) && !isset($response['error'])) {
            echo "✅ Erfolgreich! Antwort:\n";
            print_r($response);
            echo "\n" . str_repeat("-", 60) . "\n\n";
        } else {
            echo "❌ Nicht verfügbar oder leer\n\n";
        }
    }

    $client->destroySession();

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "=== Fertig ===\n";
