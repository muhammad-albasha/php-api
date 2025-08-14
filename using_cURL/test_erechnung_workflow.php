<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/ERechnungWorkflow.php');

echo "=== E-Rechnung Workflow Test ===\n\n";

$workflow = new ERechnungWorkflow();

// Test mit vorhandener E-Rechnung
$testFiles = [
    'incoming/workflow_test_klein_500eur.xml',
    'incoming/workflow_test_gross_17850eur.json'
];

foreach ($testFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (file_exists($fullPath)) {
        echo "🧾 Teste E-Rechnung-Workflow mit: " . basename($file) . "\n";
        echo str_repeat("=", 60) . "\n";
        
        $result = $workflow->processERechnung($fullPath);
        
        echo "\n📊 Workflow-Ergebnis:\n";
        if ($result['success']) {
            echo "✅ Erfolgreich verarbeitet\n";
            if (isset($result['incidentNumber'])) {
                echo "📋 Incident-Nummer: " . $result['incidentNumber'] . "\n";
            }
        } else {
            echo "❌ Verarbeitung fehlgeschlagen\n";
            if (isset($result['error'])) {
                echo "🔍 Fehler: " . $result['error'] . "\n";
            }
        }
        
        echo "\n" . str_repeat("-", 60) . "\n\n";
    } else {
        echo "⚠️ Datei nicht gefunden: $file\n\n";
    }
}

echo "=== Test abgeschlossen ===\n";
