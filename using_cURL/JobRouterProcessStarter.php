<?php

class JobRouterProcessStarter
{
    private $client;
    
    public function __construct()
    {
        $this->client = new CurlClient();
    }
    
    /**
     * Startet einen neuen Prozess mit optionalem Überspringen des Startschritts
     */
    public function startProcess($processName, $data = [], $skipStartStep = false, $targetStep = null)
    {
        try {
            $this->client->authenticate();
            
            echo "🚀 Starte Prozess: $processName\n";
            
            if ($skipStartStep && $targetStep) {
                echo "⏭️ Überspringe Startschritt -> Direkt zu Schritt $targetStep\n";
            }
            
            // Standard-Prozess-Start-Route
            $route = 'application/incidents/' . $processName;
            
            // Basis-Daten für Prozess-Start
            $processData = [
                'step' => $targetStep ?? '1',
                'initiator' => 'REST API',
                'summary' => 'Prozess gestartet via REST API'
            ];
            
            // Benutzerdefinierte Daten hinzufügen
            if (!empty($data)) {
                $fieldIndex = 0;
                foreach ($data as $fieldName => $fieldValue) {
                    $processData["processtable[fields][$fieldIndex][name]"] = $fieldName;
                    $processData["processtable[fields][$fieldIndex][value]"] = $fieldValue;
                    $fieldIndex++;
                }
            }
            
            // Skip-Startschritt-Parameter
            if ($skipStartStep) {
                $processData['skipstartstep'] = 'true';
                $processData['direct'] = 'true';
            }
            
            echo "📤 Sende Prozess-Daten...\n";
            foreach ($data as $key => $value) {
                echo "   - $key: $value\n";
            }
            
            $response = $this->client->post($route, $processData, 200);
            
            if (isset($response['incidents']) && !empty($response['incidents'])) {
                $incident = $response['incidents'][0];
                
                echo "✅ Prozess erfolgreich gestartet!\n";
                echo "   📋 Incident-Nummer: " . $incident['incidentnumber'] . "\n";
                echo "   🆔 Workflow-ID: " . $incident['workflowId'] . "\n";
                
                if (isset($incident['currentstep'])) {
                    echo "   📍 Aktueller Schritt: " . $incident['currentstep'] . "\n";
                }
                
                return [
                    'success' => true,
                    'incidentNumber' => $incident['incidentnumber'],
                    'workflowId' => $incident['workflowId'],
                    'currentStep' => $incident['currentstep'] ?? null
                ];
                
            } else {
                echo "❌ Unerwartete Antwort:\n";
                print_r($response);
                return ['success' => false, 'error' => 'Unerwartete Antwort'];
            }
            
        } catch (Exception $e) {
            echo "❌ Fehler beim Prozess-Start: " . $e->getMessage() . "\n";
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            try {
                $this->client->destroySession();
            } catch (Exception $e) {
                // Session cleanup fehler ignorieren
            }
        }
    }
    
    /**
     * Startet Prozess direkt im gewünschten Schritt (ohne Startschritt)
     */
    public function startProcessAtStep($processName, $stepNumber, $data = [])
    {
        return $this->startProcess($processName, $data, true, $stepNumber);
    }
    
    /**
     * Startet Standard-Prozess (mit Startschritt)
     */
    public function startProcessNormal($processName, $data = [])
    {
        return $this->startProcess($processName, $data, false, 1);
    }
    
    /**
     * Sendet einen Schritt weiter
     */
    public function sendStep($incidentNumber, $stepNumber, $data = [])
    {
        try {
            $this->client->authenticate();
            
            echo "📤 Sende Schritt $stepNumber für Incident $incidentNumber\n";
            
            $route = 'application/incidents/' . $incidentNumber . '/steps';
            
            $stepData = [
                'step' => $stepNumber,
                'summary' => 'Schritt gesendet via REST API'
            ];
            
            // Benutzerdefinierte Daten hinzufügen
            if (!empty($data)) {
                $fieldIndex = 0;
                foreach ($data as $fieldName => $fieldValue) {
                    $stepData["processtable[fields][$fieldIndex][name]"] = $fieldName;
                    $stepData["processtable[fields][$fieldIndex][value]"] = $fieldValue;
                    $fieldIndex++;
                }
            }
            
            $response = $this->client->post($route, $stepData, 200);
            
            if (isset($response['steps'])) {
                echo "✅ Schritt erfolgreich gesendet!\n";
                return ['success' => true, 'response' => $response];
            } else {
                echo "❌ Unerwartete Antwort beim Schritt-Senden:\n";
                print_r($response);
                return ['success' => false, 'error' => 'Unerwartete Antwort'];
            }
            
        } catch (Exception $e) {
            echo "❌ Fehler beim Schritt-Senden: " . $e->getMessage() . "\n";
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            try {
                $this->client->destroySession();
            } catch (Exception $e) {
                // Session cleanup fehler ignorieren
            }
        }
    }
    
    /**
     * Ruft den aktuellen Status eines Incidents ab
     */
    public function getIncidentStatus($incidentNumber)
    {
        try {
            $this->client->authenticate();
            
            $route = 'application/incidents/' . $incidentNumber;
            $response = $this->client->get($route);
            
            if (isset($response['incidents']) && !empty($response['incidents'])) {
                $incident = $response['incidents'][0];
                
                echo "📊 Incident Status:\n";
                echo "   📋 Nummer: " . $incident['incidentnumber'] . "\n";
                echo "   📍 Aktueller Schritt: " . ($incident['currentstep'] ?? 'Unbekannt') . "\n";
                echo "   👤 Aktueller Bearbeiter: " . ($incident['currentuser'] ?? 'Unbekannt') . "\n";
                echo "   📅 Erstellt: " . ($incident['created'] ?? 'Unbekannt') . "\n";
                
                return ['success' => true, 'incident' => $incident];
            } else {
                return ['success' => false, 'error' => 'Incident nicht gefunden'];
            }
            
        } catch (Exception $e) {
            echo "❌ Fehler beim Status-Abruf: " . $e->getMessage() . "\n";
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            try {
                $this->client->destroySession();
            } catch (Exception $e) {
                // Session cleanup fehler ignorieren
            }
        }
    }
}
