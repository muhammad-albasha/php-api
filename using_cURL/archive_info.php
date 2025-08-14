<?php

require_once(__DIR__ . '/init.php');

echo "=== Archive-Informationen für Rechnung ===\n\n";

try {
    $client = new CurlClient();
    $client->authenticate();

    $response = $client->get('application/users/' . USERNAME . '/archives/' . ARCHIVE);
    
    if (isset($response['users']['archives'][0])) {
        $archiveDetails = $response['users']['archives'][0];
        
        echo "Archive-Name: " . $archiveDetails['name'] . "\n";
        echo "Archive-GUID: " . $archiveDetails['guid'] . "\n";
        echo "Tabelle: " . $archiveDetails['table'] . "\n\n";
        
        echo "Verfügbare Index-Felder:\n";
        echo "=" . str_repeat("=", 50) . "\n";
        
        if (isset($archiveDetails['indexFieldDefinitions'])) {
            foreach ($archiveDetails['indexFieldDefinitions'] as $field) {
                $required = $field['required'] ? " (PFLICHT)" : "";
                echo "- " . $field['name'] . " (" . $field['type'] . ")" . $required . "\n";
                if (isset($field['description']) && !empty($field['description'])) {
                    echo "  Beschreibung: " . $field['description'] . "\n";
                }
            }
        } else {
            echo "Keine Index-Felder definiert oder Zugriff verweigert.\n";
        }
        
    } else {
        echo "Archive-Details konnten nicht abgerufen werden.\n";
        print_r($response);
    }

    $client->destroySession();

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Fertig ===\n";
