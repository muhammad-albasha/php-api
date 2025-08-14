<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/ERechnungParser.php');
require_once(__DIR__ . '/JobRouterProcessStarter.php');

class ERechnungWorkflow
{
    private $processStarter;
    private $logFile;
    
    // Konfiguration für automatische Prozess-Entscheidungen
    private $config = [
        'auto_approve_limit' => 1000.00,  // Automatische Genehmigung bis 1000 EUR
        'high_priority_limit' => 10000.00, // Hohe Priorität ab 10.000 EUR
        'process_mapping' => [
            'low_amount' => ['process' => 'Eingangsrechnung', 'start_step' => 3], // Skip zu Schritt 3
            'medium_amount' => ['process' => 'Eingangsrechnung', 'start_step' => 1], // Normal starten
            'high_amount' => ['process' => 'Genehmigungsprozess', 'start_step' => 1] // Genehmigung erforderlich
        ]
    ];
    
    public function __construct()
    {
        $this->processStarter = new JobRouterProcessStarter();
        $this->logFile = __DIR__ . '/logs/erechnung_workflow_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Verarbeitet eine E-Rechnung und startet automatisch den passenden Workflow
     */
    public function processERechnung($filePath)
    {
        try {
            $fileName = basename($filePath);
            $this->log("=== E-Rechnung Workflow gestartet: $fileName ===");
            
            echo "🧾 Verarbeite E-Rechnung: $fileName\n";
            
            // 1. E-Rechnung parsen
            $metadata = ERechnungParser::parseERechnung($filePath);
            $this->log("E-Rechnung geparst - Typ: " . $metadata['type']);
            
            echo "📊 E-Rechnung-Details:\n";
            echo "   📋 Typ: " . $metadata['type'] . "\n";
            if ($metadata['rechnungsnummer']) {
                echo "   📄 Rechnungsnummer: " . $metadata['rechnungsnummer'] . "\n";
            }
            if ($metadata['betrag']) {
                echo "   💰 Betrag: " . $metadata['betrag'] . " " . $metadata['waehrung'] . "\n";
            }
            if ($metadata['lieferant']) {
                echo "   🏢 Lieferant: " . $metadata['lieferant'] . "\n";
            }
            
            // 2. Workflow-Entscheidung basierend auf Betrag
            $workflowDecision = $this->determineWorkflow($metadata);
            
            echo "🎯 Workflow-Entscheidung: " . $workflowDecision['description'] . "\n";
            $this->log("Workflow-Entscheidung: " . $workflowDecision['description']);
            
            // 3. Prozess-Daten vorbereiten
            $processData = $this->prepareProcessData($metadata, $fileName);
            
            // 4. Prozess starten
            $result = $this->startWorkflowProcess($workflowDecision, $processData);
            
            if ($result['success']) {
                echo "✅ Workflow erfolgreich gestartet!\n";
                echo "   📋 Incident: " . $result['incidentNumber'] . "\n";
                echo "   🆔 Workflow-ID: " . $result['workflowId'] . "\n";
                
                $this->log("Workflow erfolgreich gestartet - Incident: " . $result['incidentNumber']);
                
                // 5. Automatische Weiterleitung bei kleinen Beträgen
                if ($workflowDecision['type'] === 'auto_approve') {
                    $this->autoApprove($result['incidentNumber'], $metadata);
                }
                
                return $result;
            } else {
                echo "❌ Workflow-Start fehlgeschlagen\n";
                $this->log("Workflow-Start fehlgeschlagen: " . $result['error']);
                return $result;
            }
            
        } catch (Exception $e) {
            $this->log("Fehler bei E-Rechnung-Verarbeitung: " . $e->getMessage());
            echo "❌ Fehler: " . $e->getMessage() . "\n";
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function determineWorkflow($metadata)
    {
        $betrag = floatval($metadata['betrag'] ?? 0);
        
        if ($betrag <= $this->config['auto_approve_limit']) {
            return [
                'type' => 'auto_approve',
                'process' => $this->config['process_mapping']['low_amount']['process'],
                'start_step' => $this->config['process_mapping']['low_amount']['start_step'],
                'skip_start' => true,
                'description' => "Automatische Genehmigung (Betrag ≤ {$this->config['auto_approve_limit']} EUR)"
            ];
        } elseif ($betrag <= $this->config['high_priority_limit']) {
            return [
                'type' => 'normal_approval',
                'process' => $this->config['process_mapping']['medium_amount']['process'],
                'start_step' => $this->config['process_mapping']['medium_amount']['start_step'],
                'skip_start' => false,
                'description' => "Standard-Genehmigungsverfahren (Betrag ≤ {$this->config['high_priority_limit']} EUR)"
            ];
        } else {
            return [
                'type' => 'high_priority',
                'process' => $this->config['process_mapping']['high_amount']['process'],
                'start_step' => $this->config['process_mapping']['high_amount']['start_step'],
                'skip_start' => false,
                'description' => "Hochpriorisierte Genehmigung (Betrag > {$this->config['high_priority_limit']} EUR)"
            ];
        }
    }
    
    private function prepareProcessData($metadata, $fileName)
    {
        $processData = [
            'QUELLE' => 'E-Rechnung API',
            'DATEINAME' => $fileName,
            'E_RECHNUNG_TYP' => $metadata['type'],
            'EINGANGSDATUM' => date('Y-m-d H:i:s')
        ];
        
        // Metadaten hinzufügen falls verfügbar
        if ($metadata['rechnungsnummer']) {
            $processData['RECHNUNGSNUMMER'] = $metadata['rechnungsnummer'];
        }
        if ($metadata['betrag']) {
            $processData['BETRAG'] = $metadata['betrag'];
            $processData['WAEHRUNG'] = $metadata['waehrung'];
        }
        if ($metadata['lieferant']) {
            $processData['LIEFERANT'] = $metadata['lieferant'];
        }
        if ($metadata['kunde']) {
            $processData['KUNDE'] = $metadata['kunde'];
        }
        if ($metadata['datum']) {
            $processData['RECHNUNGSDATUM'] = $metadata['datum'];
        }
        if ($metadata['faelligkeitsdatum']) {
            $processData['FAELLIGKEITSDATUM'] = $metadata['faelligkeitsdatum'];
        }
        if ($metadata['umsatzsteuer']) {
            $processData['UMSATZSTEUER'] = $metadata['umsatzsteuer'];
        }
        
        return $processData;
    }
    
    private function startWorkflowProcess($workflowDecision, $processData)
    {
        if ($workflowDecision['skip_start']) {
            // Startschritt überspringen
            return $this->processStarter->startProcessAtStep(
                $workflowDecision['process'],
                $workflowDecision['start_step'],
                $processData
            );
        } else {
            // Standard-Start
            return $this->processStarter->startProcessNormal(
                $workflowDecision['process'],
                $processData
            );
        }
    }
    
    private function autoApprove($incidentNumber, $metadata)
    {
        echo "🤖 Automatische Genehmigung wird eingeleitet...\n";
        
        $approvalData = [
            'STATUS' => 'AUTO_GENEHMIGT',
            'GENEHMIGER' => 'System (E-Rechnung API)',
            'GENEHMIGUNGSDATUM' => date('Y-m-d H:i:s'),
            'BEMERKUNG' => 'Automatisch genehmigt aufgrund geringen Betrags'
        ];
        
        // Versuche Schritt weiterzuleiten (falls Berechtigung vorhanden)
        $result = $this->processStarter->sendStep($incidentNumber, 4, $approvalData);
        
        if ($result['success']) {
            echo "✅ Automatische Genehmigung erfolgreich durchgeführt\n";
            $this->log("Automatische Genehmigung erfolgreich - Incident: $incidentNumber");
        } else {
            echo "⚠️ Automatische Genehmigung vorbereitet (manuelle Freigabe erforderlich)\n";
            $this->log("Automatische Genehmigung vorbereitet - Incident: $incidentNumber");
        }
    }
    
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Batch-Verarbeitung für mehrere E-Rechnungen
     */
    public function processBatch($directory)
    {
        echo "📁 Batch-Verarbeitung gestartet: $directory\n\n";
        
        $files = glob($directory . '/*');
        $results = [];
        
        foreach ($files as $file) {
            if (is_file($file)) {
                echo str_repeat("-", 60) . "\n";
                $result = $this->processERechnung($file);
                $results[] = [
                    'file' => basename($file),
                    'success' => $result['success'],
                    'incident' => $result['incidentNumber'] ?? null
                ];
                echo "\n";
            }
        }
        
        // Zusammenfassung
        $successful = count(array_filter($results, function($r) { return $r['success']; }));
        $total = count($results);
        
        echo str_repeat("=", 60) . "\n";
        echo "📊 Batch-Verarbeitung abgeschlossen: $successful/$total erfolgreich\n";
        
        return $results;
    }
}
