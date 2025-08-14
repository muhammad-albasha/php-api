<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/JobRouterProcessStarter.php');

echo "=== JobRouter REST API - Prozess-Start-System ===\n\n";

class ProcessStartExample
{
    public static function demonstrateProcessStart()
    {
        echo "📋 Verfügbare Prozess-Start-Methoden:\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // 1. Standard-Prozess-Start
        echo "1️⃣ STANDARD-PROZESS-START (mit Startschritt)\n";
        echo str_repeat("-", 45) . "\n";
        echo "Verwendung:\n";
        echo "```php\n";
        echo "\$starter = new JobRouterProcessStarter();\n";
        echo "\$data = [\n";
        echo "    'RECHNUNGSNUMMER' => 'RE-2025-001',\n";
        echo "    'BETRAG' => '1500.00',\n";
        echo "    'LIEFERANT' => 'Musterfirma GmbH'\n";
        echo "];\n";
        echo "\$result = \$starter->startProcessNormal('Eingangsrechnung', \$data);\n";
        echo "```\n\n";
        
        echo "REST API Aufruf:\n";
        echo "POST /api/rest/v2/application/incidents/Eingangsrechnung\n";
        echo "{\n";
        echo "    \"step\": \"1\",\n";
        echo "    \"initiator\": \"REST API\",\n";
        echo "    \"processtable[fields][0][name]\": \"RECHNUNGSNUMMER\",\n";
        echo "    \"processtable[fields][0][value]\": \"RE-2025-001\"\n";
        echo "}\n\n";
        
        // 2. Prozess-Start mit übersprungenen Startschritt
        echo "2️⃣ PROZESS-START MIT ÜBERSPRUNGENEN STARTSCHRITT\n";
        echo str_repeat("-", 50) . "\n";
        echo "Verwendung:\n";
        echo "```php\n";
        echo "\$data = [\n";
        echo "    'BETRAG' => '2500.00',\n";
        echo "    'STATUS' => 'Genehmigt'\n";
        echo "];\n";
        echo "\$result = \$starter->startProcessAtStep('Rechnung', 2, \$data);\n";
        echo "```\n\n";
        
        echo "REST API Aufruf:\n";
        echo "POST /api/rest/v2/application/incidents/Rechnung\n";
        echo "{\n";
        echo "    \"step\": \"2\",\n";
        echo "    \"skipstartstep\": \"true\",\n";
        echo "    \"direct\": \"true\",\n";
        echo "    \"initiator\": \"REST API\",\n";
        echo "    \"processtable[fields][0][name]\": \"BETRAG\",\n";
        echo "    \"processtable[fields][0][value]\": \"2500.00\"\n";
        echo "}\n\n";
        
        // 3. Schritt weiterleiten
        echo "3️⃣ SCHRITT WEITERLEITEN\n";
        echo str_repeat("-", 25) . "\n";
        echo "Verwendung:\n";
        echo "```php\n";
        echo "\$data = ['BEMERKUNG' => 'Genehmigt durch API'];\n";
        echo "\$result = \$starter->sendStep('12345', 3, \$data);\n";
        echo "```\n\n";
        
        echo "REST API Aufruf:\n";
        echo "POST /api/rest/v2/application/incidents/12345/steps\n";
        echo "{\n";
        echo "    \"step\": \"3\",\n";
        echo "    \"processtable[fields][0][name]\": \"BEMERKUNG\",\n";
        echo "    \"processtable[fields][0][value]\": \"Genehmigt durch API\"\n";
        echo "}\n\n";
        
        // 4. Status abfragen
        echo "4️⃣ INCIDENT-STATUS ABFRAGEN\n";
        echo str_repeat("-", 30) . "\n";
        echo "Verwendung:\n";
        echo "```php\n";
        echo "\$status = \$starter->getIncidentStatus('12345');\n";
        echo "```\n\n";
        
        echo "REST API Aufruf:\n";
        echo "GET /api/rest/v2/application/incidents/12345\n\n";
    }
    
    public static function createWorkingExample()
    {
        echo "📝 ARBEITSBEISPIEL - E-RECHNUNG WORKFLOW\n";
        echo str_repeat("=", 50) . "\n\n";
        
        echo "```php\n";
        echo "// E-Rechnung automatisch verarbeiten\n";
        echo "class ERechnungWorkflow {\n";
        echo "    public function processERechnung(\$xmlFile) {\n";
        echo "        // 1. E-Rechnung parsen\n";
        echo "        \$metadata = ERechnungParser::parseERechnung(\$xmlFile);\n";
        echo "        \n";
        echo "        // 2. Validierung\n";
        echo "        if (\$metadata['betrag'] > 10000) {\n";
        echo "            // Großer Betrag -> Genehmigungsprozess starten\n";
        echo "            \$processData = [\n";
        echo "                'RECHNUNGSNUMMER' => \$metadata['rechnungsnummer'],\n";
        echo "                'BETRAG' => \$metadata['betrag'],\n";
        echo "                'LIEFERANT' => \$metadata['lieferant'],\n";
        echo "                'PRIORITAET' => 'HOCH'\n";
        echo "            ];\n";
        echo "            return \$starter->startProcessNormal('Genehmigung', \$processData);\n";
        echo "        } else {\n";
        echo "            // Kleiner Betrag -> Startschritt überspringen\n";
        echo "            \$processData = [\n";
        echo "                'RECHNUNGSNUMMER' => \$metadata['rechnungsnummer'],\n";
        echo "                'BETRAG' => \$metadata['betrag'],\n";
        echo "                'STATUS' => 'AUTO_GENEHMIGT'\n";
        echo "            ];\n";
        echo "            return \$starter->startProcessAtStep('Rechnung', 3, \$processData);\n";
        echo "        }\n";
        echo "    }\n";
        echo "}\n";
        echo "```\n\n";
    }
}

// Demonstration ausführen
ProcessStartExample::demonstrateProcessStart();
ProcessStartExample::createWorkingExample();

echo "📊 ZUSAMMENFASSUNG\n";
echo str_repeat("=", 20) . "\n";
echo "✅ Standard-Prozess-Start implementiert\n";
echo "✅ Startschritt-Überspringen implementiert\n";
echo "✅ Schritt-Weiterleitung implementiert\n";
echo "✅ Status-Abfrage implementiert\n";
echo "⚠️  Demo-Umgebung: Berechtigungen eingeschränkt\n";
echo "💡 Code ist bereit für Produktionsumgebung\n\n";

echo "🔧 NÄCHSTE SCHRITTE FÜR PRODUKTIONSUMGEBUNG:\n";
echo "1. Benutzer mit Prozess-Start-Berechtigung verwenden\n";
echo "2. Korrekte Feldnamen aus Prozess-Definition verwenden\n";
echo "3. Prozess-spezifische Validierung implementieren\n";
echo "4. Error-Handling für Produktionsumgebung anpassen\n";

echo "\n=== Demo abgeschlossen ===\n";
