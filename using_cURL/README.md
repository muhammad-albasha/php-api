# JobRouter REST API Client - Anleitung

## Vorbereitung

### 1. Konfiguration anpassen
Bearbeite die Datei `config.php` und fülle die folgenden Werte aus:

```php
const BASE_URL = 'https://ihre-jobrouter-url.de/api/rest/v2/';  // Ihre JobRouter REST API URL
const USERNAME = 'ihr-benutzername'; // Benutzername für Authentifizierung
const PASSWORD = "ihr-passwort"; // Passwort für Authentifizierung

// Optional: Für spezifische Funktionen
const ARCHIVE = "archive-name-oder-guid"; // Für Archive-Beispiele
const PROCESS = "prozess-name"; // Für Prozess-Beispiele
const PROCESS_VERSION = 1; // Prozessversion
const JOBDATA_TABLE_GUID = 'guid-der-jobdata-tabelle'; // Für JobData-Beispiele

// Pfade anpassen
const CURL_COOKIE_PATH = 'c:\\Users\\MAlbasha\\Desktop\\php\\PHP\\using_cURL\\cookie_path';
const FILE_STORAGE = 'c:\\Users\\MAlbasha\\Desktop\\php\\PHP\\using_cURL\\files';
```

### 2. Ordner erstellen
Erstelle die benötigten Ordner:
- `cookie_path` (für Session-Cookies)
- `files` (für Test-Dateien)

## Verwendung

### Grundlegendes Beispiel ausführen
```bash
php example_usage.php
```

### Verfügbare Funktionen

#### Archive-Funktionen
- `Archive/listArchives.php` - Alle verfügbaren Archive auflisten
- `Archive/listDocuments.php` - Dokumente in einem Archiv auflisten
- `Archive/archiveDocument.php` - Dokument archivieren
- `Archive/downloadDocumentFiles.php` - Dateien herunterladen
- `Archive/deleteDocument.php` - Dokument löschen

#### Prozess-Funktionen
- `Process/startProcess.php` - Neuen Prozess starten
- `Process/getInboxSteps.php` - Eingangsstufen abrufen
- `Process/sendStep.php` - Prozessschritt senden

#### Datenbank-Funktionen
- `Database/listDatasets.php` - Datensätze auflisten
- `Database/createDataset.php` - Neuen Datensatz erstellen
- `Database/changeDataset.php` - Datensatz ändern

## Eigene Skripte erstellen

### Basis-Template
```php
<?php

require_once(__DIR__ . '/init.php');

try {
    // Client erstellen und authentifizieren
    $client = new CurlClient();
    $client->authenticate();
    
    // Ihre API-Aufrufe hier
    $result = $client->get('application/processes');
    
    // Ergebnisse verarbeiten
    print_r($result);
    
    // Session beenden
    $client->destroySession();
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
```

### Verfügbare Methoden

#### GET-Request
```php
$result = $client->get('application/processes');
```

#### POST-Request (JSON)
```php
$client->setJson(true);
$data = ['field' => 'value'];
$result = $client->post('route', $data);
```

#### POST-Request (Form-Data/Datei-Upload)
```php
$data = [
    'field' => 'value',
    'file' => new CURLFile('/path/to/file.pdf')
];
$result = $client->post('route', $data);
```

#### PUT/PATCH/DELETE
```php
$result = $client->put('route', $data);
$result = $client->patch('route', $data);
$result = $client->delete('route');
```

## Wichtige Hinweise

1. **Authentifizierung**: Jeder API-Aufruf benötigt eine Authentifizierung
2. **Session-Management**: Sessions sollten nach Gebrauch beendet werden
3. **Fehlerbehandlung**: Verwende try-catch für robuste Skripte
4. **Pfade**: Alle Pfade in config.php müssen korrekt gesetzt werden
5. **Berechtigungen**: Der Benutzer muss entsprechende Rechte in JobRouter haben

## Fehlerbehebung

### Häufige Fehler:
- **Authentication Error**: Benutzername/Passwort oder URL falsch
- **404 Error**: Route nicht gefunden oder falsche API-Version
- **403 Error**: Keine Berechtigung für die Aktion
- **Cookie-Probleme**: CURL_COOKIE_PATH nicht beschreibbar
