<?php

require_once(__DIR__ . '/init.php');

echo "=== JobRouter Prozesse und Schritte ===\n\n";

try {
    $client = new CurlClient();
    $client->authenticate();

    // 1. Verfügbare Prozesse auflisten
    echo "1. Verfügbare Prozesse:\n";
    echo str_repeat("=", 40) . "\n";
    
    $processes = $client->get('application/processes');
    
    if (isset($processes['processes'])) {
        foreach ($processes['processes'] as $process) {
            echo "📋 Prozess: " . $process['name'] . "\n";
            echo "   Version: " . $process['version'] . "\n";
            echo "   Beschreibung: " . ($process['description'] ?? 'Keine Beschreibung') . "\n";
            
            // Prozess-Details abrufen
            $processDetails = $client->get('application/processes/' . $process['name'] . '/' . $process['version']);
            
            if (isset($processDetails['processes'][0]['steps'])) {
                echo "   Schritte:\n";
                foreach ($processDetails['processes'][0]['steps'] as $step) {
                    $stepType = $step['stepType'] ?? 'unknown';
                    $stepNumber = $step['step'] ?? 'N/A';
                    $stepName = $step['stepname'] ?? 'Unbenannt';
                    
                    echo "     - Schritt $stepNumber: $stepName ($stepType)\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "Keine Prozesse gefunden oder Zugriff verweigert.\n";
        // Alternative API-Endpunkte versuchen
        echo "\n2. Versuche alternative Endpunkte...\n";
        
        // Versuche incidents endpoint
        $incidents = $client->get('application/incidents');
        if (isset($incidents['processes'])) {
            echo "Prozesse über incidents-Endpunkt gefunden:\n";
            print_r($incidents);
        }
    }

    $client->destroySession();

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Fertig ===\n";
