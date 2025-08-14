<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/JobRouterProcessStarter.php');

echo "=== JobRouter Berechtigungs-Test ===\n\n";

$starter = new JobRouterProcessStarter();

// Alle verfügbaren Prozesse testen
$availableProcesses = [
    'Academy_Bestellanforderung',
    'AcademyDBFunktionen', 
    'myTest',
    'PDF_TEST',
    'externerProzess'
];

foreach ($availableProcesses as $processName) {
    echo "🔍 Teste Prozess: $processName\n";
    echo str_repeat("-", 40) . "\n";
    
    // Minimale Daten für Test
    $testData = [
        'TEST_FELD' => 'Test Wert',
        'DATUM' => date('Y-m-d'),
        'BESCHREIBUNG' => 'API Test'
    ];
    
    $result = $starter->startProcessNormal($processName, $testData);
    
    if ($result['success']) {
        echo "✅ $processName ist verfügbar und funktioniert!\n";
        echo "   Incident: " . $result['incidentNumber'] . "\n";
        break; // Stoppe beim ersten erfolgreichen Prozess
    } else {
        echo "❌ $processName nicht verfügbar\n";
        if (isset($result['error'])) {
            echo "   Fehler: " . $result['error'] . "\n";
        }
    }
    echo "\n";
}

echo "=== Test abgeschlossen ===\n";
