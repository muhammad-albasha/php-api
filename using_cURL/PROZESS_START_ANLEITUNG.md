# JobRouter REST API - Prozess-Start mit Startschritt-Überspringen

## 🎯 Übersicht

Dieses System ermöglicht es, JobRouter-Prozesse über die REST API zu starten und dabei optional den Startschritt zu überspringen. Es ist besonders nützlich für automatisierte E-Rechnung-Workflows.

## 🚀 Hauptfunktionen

### 1. **Standard-Prozess-Start**
```php
$starter = new JobRouterProcessStarter();
$data = [
    'RECHNUNGSNUMMER' => 'RE-2025-001',
    'BETRAG' => '1500.00',
    'LIEFERANT' => 'Musterfirma GmbH'
];
$result = $starter->startProcessNormal('Eingangsrechnung', $data);
```

### 2. **Prozess-Start mit übersprungenen Startschritt**
```php
$data = [
    'BETRAG' => '500.00',
    'STATUS' => 'Vorab-genehmigt'
];
$result = $starter->startProcessAtStep('Rechnung', 3, $data);
```

### 3. **Schritt weiterleiten**
```php
$data = ['BEMERKUNG' => 'Genehmigt durch System'];
$result = $starter->sendStep('12345', 4, $data);
```

### 4. **Incident-Status abfragen**
```php
$status = $starter->getIncidentStatus('12345');
```

## 🧾 E-Rechnung-Workflow-Integration

### **Intelligente Workflow-Entscheidungen:**

| Betrag | Workflow | Startschritt |
|--------|----------|--------------|
| ≤ 1.000 EUR | Automatische Genehmigung | ⏭️ Überspringen → Schritt 3 |
| ≤ 10.000 EUR | Standard-Genehmigung | ✅ Normal starten |
| > 10.000 EUR | Hochpriorisierte Genehmigung | ✅ Genehmigungsprozess |

### **Verwendung:**
```php
$workflow = new ERechnungWorkflow();
$result = $workflow->processERechnung('path/to/erechnung.xml');
```

### **Automatische Features:**
- ✅ **E-Rechnung-Parsing** (XML, JSON, PDF)
- ✅ **Betrags-basierte Entscheidungen**
- ✅ **Automatisches Startschritt-Überspringen**
- ✅ **Metadaten-Extraktion**
- ✅ **Prozess-spezifische Datenaufbereitung**

## 📋 REST API Referenz

### **Prozess starten (Standard)**
```http
POST /api/rest/v2/application/incidents/{processName}
Content-Type: application/x-www-form-urlencoded

step=1&
initiator=REST API&
processtable[fields][0][name]=RECHNUNGSNUMMER&
processtable[fields][0][value]=RE-2025-001
```

### **Prozess starten (Startschritt überspringen)**
```http
POST /api/rest/v2/application/incidents/{processName}
Content-Type: application/x-www-form-urlencoded

step=3&
skipstartstep=true&
direct=true&
initiator=REST API&
processtable[fields][0][name]=BETRAG&
processtable[fields][0][value]=500.00
```

### **Schritt weiterleiten**
```http
POST /api/rest/v2/application/incidents/{incidentNumber}/steps
Content-Type: application/x-www-form-urlencoded

step=4&
processtable[fields][0][name]=STATUS&
processtable[fields][0][value]=GENEHMIGT
```

### **Status abfragen**
```http
GET /api/rest/v2/application/incidents/{incidentNumber}
```

## 🔧 Konfiguration

### **Workflow-Einstellungen anpassen:**
```php
// In ERechnungWorkflow.php
private $config = [
    'auto_approve_limit' => 1000.00,     // Automatische Genehmigung
    'high_priority_limit' => 10000.00,   // Hohe Priorität
    'process_mapping' => [
        'low_amount' => [
            'process' => 'Eingangsrechnung', 
            'start_step' => 3  // Überspringen zu Schritt 3
        ],
        'medium_amount' => [
            'process' => 'Eingangsrechnung', 
            'start_step' => 1  // Normal starten
        ],
        'high_amount' => [
            'process' => 'Genehmigungsprozess', 
            'start_step' => 1  // Genehmigungsprozess
        ]
    ]
];
```

## 📂 Dateistruktur

```
JobRouterProcessStarter.php     # Hauptklasse für Prozess-Start
ERechnungWorkflow.php          # E-Rechnung-Workflow-Integration
ERechnungParser.php            # E-Rechnung-Parser (XML, JSON, PDF)
test_process_start.php         # Test-Beispiele
process_start_demo.php         # Demonstrations-Script
```

## 💡 Anwendungsfälle

### **1. E-Rechnung-Automatisierung**
- Kleine Beträge: Automatische Verarbeitung ohne manuellen Eingriff
- Große Beträge: Genehmigungsworkflow mit Benachrichtigungen
- Duplikatsprüfung und Validierung

### **2. Bestellanforderungen**
- Express-Bestellungen: Startschritt überspringen
- Standard-Bestellungen: Vollständiger Approval-Prozess

### **3. Genehmigungsworkflows**
- Vorgenehmigungen: Direkt zum Bestätigungsschritt
- Komplexe Genehmigungen: Vollständiger Prüfprozess

## ⚠️ Wichtige Hinweise

### **Berechtigungen**
- Benutzer muss Berechtigung zum Starten der Prozesse haben
- REST API Zugriff erforderlich
- Prozess-spezifische Berechtigungen beachten

### **Feldnamen**
- Feldnamen müssen mit Prozess-Definition übereinstimmen
- Datentypen beachten (Text, Datum, Zahl)
- Pflichtfelder berücksichtigen

### **Fehlerbehandlung**
- HTTP-Status-Codes prüfen
- Antwort-Validierung implementieren
- Retry-Mechanismen für temporäre Fehler

## 🔮 Erweiterte Features

### **Batch-Verarbeitung**
```php
$workflow = new ERechnungWorkflow();
$results = $workflow->processBatch('/path/to/invoices/');
```

### **Custom Process Mapping**
```php
// Eigene Prozess-Zuordnungen definieren
$workflow->setProcessMapping([
    'urgent' => ['process' => 'ExpressProzess', 'start_step' => 2],
    'standard' => ['process' => 'StandardProzess', 'start_step' => 1]
]);
```

### **Webhook-Integration**
```php
// Prozess-Events an externe Systeme senden
$workflow->setWebhook('https://api.example.com/webhook');
```

## 📊 Monitoring & Logging

- **Detaillierte Logs** in `/logs/` Ordner
- **Prozess-Tracking** mit Incident-Nummern
- **Performance-Metriken** für Workflow-Optimierung
- **Error-Reporting** für Fehleranalyse

Das System ist **produktionsbereit** und kann sofort in JobRouter-Umgebungen eingesetzt werden! 🚀
