<?php

require_once(__DIR__ . '/init.php');

echo "=== JobRouter REST API Client Beispiel ===\n\n";

try {
    // 1. Client erstellen und authentifizieren
    $client = new CurlClient();
    echo "1. Authentifizierung...\n";
    $client->authenticate();
    echo "   ✓ Erfolgreich authentifiziert!\n\n";

    // 2. Archive auflisten (falls konfiguriert)
    if (!empty(USERNAME)) {
        echo "2. Archive auflisten...\n";
        $archives = $client->get('application/users/' . USERNAME . '/archives');
        
        if (isset($archives['users']['archives'])) {
            foreach ($archives['users']['archives'] as $archive) {
                echo "   - Archiv: " . $archive['name'] . " (GUID: " . $archive['guid'] . ")\n";
            }
        } else {
            echo "   Keine Archive gefunden oder Zugriff nicht erlaubt.\n";
        }
        echo "\n";
    }

    // 3. Verfügbare Prozesse auflisten
    echo "3. Prozesse auflisten...\n";
    $processes = $client->get('application/processes');
    
    if (isset($processes['processes'])) {
        foreach ($processes['processes'] as $process) {
            echo "   - Prozess: " . $process['name'] . " (Version: " . $process['version'] . ")\n";
        }
    } else {
        echo "   Keine Prozesse gefunden oder Zugriff nicht erlaubt.\n";
    }
    echo "\n";

    // 4. Session beenden
    echo "4. Session beenden...\n";
    $client->destroySession();
    echo "   ✓ Session erfolgreich beendet!\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Bitte überprüfe deine Konfiguration in config.php\n";
}

echo "\n=== Fertig ===\n";
