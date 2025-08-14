<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/JobRouterProcessStarter.php');

echo "=== JobRouter Prozess-Start Beispiele ===\n\n";

$starter = new JobRouterProcessStarter();

// Beispiel 1: Standard-Prozess-Start (mit Startschritt)
echo "1️⃣ Standard-Prozess-Start (Eingangsrechnung):\n";
echo str_repeat("=", 50) . "\n";

$processData = [
    'RECHNUNGSNUMMER' => 'API-' . date('YmdHis'),
    'LIEFERANT' => 'API Testfirma GmbH',
    'BETRAG' => '1500.00',
    'RECHNUNGSDATUM' => date('Y-m-d'),
    'BESCHREIBUNG' => 'Automatisch via REST API erstellt'
];

$result1 = $starter->startProcessNormal('Eingangsrechnung', $processData);
echo "Ergebnis: " . ($result1['success'] ? '✅ Erfolgreich' : '❌ Fehlgeschlagen') . "\n\n";

sleep(2);

// Beispiel 2: Prozess-Start mit Überspringen des Startschritts
echo "2️⃣ Prozess-Start mit übersprungenen Startschritt (RECHNUNGEN):\n";
echo str_repeat("=", 50) . "\n";

$processData2 = [
    'BETRAG' => '2500.00',
    'LIEFERANT' => 'Skip-Start Firma',
    'BESCHREIBUNG' => 'Direkt zu Schritt 2 gesprungen'
];

$result2 = $starter->startProcessAtStep('RECHNUNGEN', 2, $processData2);
echo "Ergebnis: " . ($result2['success'] ? '✅ Erfolgreich' : '❌ Fehlgeschlagen') . "\n\n";

sleep(2);

// Beispiel 3: Hardware-Antrag Prozess
echo "3️⃣ Hardware-Antrag Prozess:\n";
echo str_repeat("=", 50) . "\n";

$hardwareData = [
    'GERAET' => 'Laptop Dell XPS 15',
    'BEGRUENDUNG' => 'Entwicklungsarbeit Remote',
    'ABTEILUNG' => 'IT',
    'DRINGLICHKEIT' => 'Hoch'
];

$result3 = $starter->startProcessNormal('antrag_Hardware', $hardwareData);
echo "Ergebnis: " . ($result3['success'] ? '✅ Erfolgreich' : '❌ Fehlgeschlagen') . "\n\n";

sleep(2);

// Beispiel 4: Bestellanforderung
echo "4️⃣ Bestellanforderung Prozess:\n";
echo str_repeat("=", 50) . "\n";

$bestellData = [
    'ARTIKEL' => 'Office Software Lizenzen',
    'MENGE' => '10',
    'GESCHAETZTER_PREIS' => '5000.00',
    'LIEFERANT' => 'Microsoft Deutschland',
    'BENOETIGT_BIS' => date('Y-m-d', strtotime('+14 days'))
];

$result4 = $starter->startProcessNormal('Bestellanforderung', $bestellData);
echo "Ergebnis: " . ($result4['success'] ? '✅ Erfolgreich' : '❌ Fehlgeschlagen') . "\n\n";

// Wenn erfolgreich, Status abfragen
if ($result4['success'] && isset($result4['incidentNumber'])) {
    echo "📊 Status-Abfrage für Incident " . $result4['incidentNumber'] . ":\n";
    $status = $starter->getIncidentStatus($result4['incidentNumber']);
}

echo "\n=== Alle Tests abgeschlossen ===\n";
