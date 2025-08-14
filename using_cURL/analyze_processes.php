<?php

require_once(__DIR__ . '/init.php');

echo "=== JobRouter Prozess-Details und Schritt-Analyse ===\n\n";

try {
    $client = new CurlClient();
    $client->authenticate();

    // Interessante Prozesse für Demo
    $testProcesses = [
        'Eingangsrechnung',
        'Bestellanforderung', 
        'RECHNUNGEN',
        'antrag_Hardware'
    ];

    foreach ($testProcesses as $processName) {
        echo "🔍 Analysiere Prozess: $processName\n";
        echo str_repeat("=", 50) . "\n";
        
        // Prozess-Details abrufen
        $processUrl = 'application/users/' . USERNAME . '/processes/' . $processName . '/1';
        $processDetails = $client->get($processUrl);
        
        if (isset($processDetails['users']['processes'][0])) {
            $process = $processDetails['users']['processes'][0];
            
            echo "📋 Name: " . $process['processname'] . "\n";
            echo "📝 Beschreibung: " . ($process['description'] ?? 'Keine') . "\n";
            echo "🔢 Version: " . $process['version'] . "\n\n";
            
            // Schritte analysieren
            if (isset($process['steps'])) {
                echo "📊 Prozess-Schritte:\n";
                foreach ($process['steps'] as $step) {
                    $stepNum = $step['step'] ?? 'N/A';
                    $stepName = $step['stepname'] ?? 'Unbenannt';
                    $stepType = $step['steptype'] ?? 'unknown';
                    $startStep = isset($step['startstep']) && $step['startstep'] ? ' (START)' : '';
                    
                    echo "   Step $stepNum: $stepName [$stepType]$startStep\n";
                    
                    // Felder des Schritts
                    if (isset($step['fields'])) {
                        echo "     Felder:\n";
                        foreach ($step['fields'] as $field) {
                            $fieldName = $field['name'] ?? 'Unbekannt';
                            $fieldType = $field['type'] ?? 'text';
                            $required = isset($field['required']) && $field['required'] ? ' (PFLICHT)' : '';
                            echo "       - $fieldName [$fieldType]$required\n";
                        }
                    }
                }
            }
            
            // Startbare Schritte identifizieren
            echo "\n🚀 Start-Optionen:\n";
            if (isset($process['steps'])) {
                foreach ($process['steps'] as $step) {
                    if (isset($step['startstep']) && $step['startstep']) {
                        echo "   ✓ Standard-Start: Schritt " . $step['step'] . " (" . $step['stepname'] . ")\n";
                    }
                }
                
                // Ersten Nicht-Start-Schritt als Skip-Option
                foreach ($process['steps'] as $step) {
                    if (!isset($step['startstep']) || !$step['startstep']) {
                        echo "   ⏭️ Skip-Start möglich: Schritt " . $step['step'] . " (" . $step['stepname'] . ")\n";
                        break;
                    }
                }
            }
            
        } else {
            echo "❌ Prozess-Details nicht verfügbar\n";
        }
        
        echo "\n" . str_repeat("-", 60) . "\n\n";
    }

    $client->destroySession();

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "=== Fertig ===\n";
