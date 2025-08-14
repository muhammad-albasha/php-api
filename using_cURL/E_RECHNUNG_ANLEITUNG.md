# E-Rechnung Upload System 

## 🧾 Unterstützte E-Rechnung-Formate

### ✅ **XRechnung (XML)**
- Standard: EN 16931 / XRechnung 3.0
- Format: Cross Industry Invoice (CII)
- Dateiendung: `.xml`
- **Automatische Extraktion**: Rechnungsnummer, Datum, Betrag, Lieferant, Kunde

### ✅ **ZUGFeRD (PDF)**
- PDF mit eingebetteter XML-Struktur
- Format: ZUGFeRD 2.x
- Dateiendung: `.pdf`
- **Automatische Extraktion**: XML-Daten aus PDF

### ✅ **JSON E-Rechnung**
- Eigene JSON-Strukturen
- Dateiendung: `.json`
- **Automatische Extraktion**: Alle Standard-Felder

### ✅ **Dateiname-basierte Erkennung**
- Für nicht-strukturierte Formate
- Pattern-Erkennung aus Dateinamen
- Fallback für alle anderen Formate

## 📊 Automatisch extrahierte Metadaten

| Feld | XML (XRechnung) | JSON | Dateiname |
|------|----------------|------|-----------|
| **Rechnungsnummer** | `<ID>` | `invoiceNumber` | Pattern-Matching |
| **Datum** | `<IssueDate>` | `invoiceDate` | YYYY-MM-DD Pattern |
| **Betrag** | `<GrandTotalAmount>` | `grossAmount` | Betrag aus Name |
| **Währung** | `<InvoiceCurrencyCode>` | `currency` | EUR (Standard) |
| **Lieferant** | `<SellerTradeParty>` | `supplier.name` | Erstes Wort |
| **Kunde** | `<BuyerTradeParty>` | `customer.name` | - |
| **Fälligkeitsdatum** | `<DueDate>` | `dueDate` | - |
| **USt-Betrag** | `<TaxTotalAmount>` | `vatAmount` | - |

## 🏷️ Dateiname-Patterns für automatische Erkennung

```
# XRechnung XML
musterfirma_xrechnung_2025001.xml
-> Mandant: "Musterfirma", RG-Nr: aus XML

# JSON E-Rechnung  
techservice_json_invoice_2025002.json
-> Mandant: "Techservice", Daten: aus JSON

# Pattern-basiert
RE-2025001-500.50-FIRMA.pdf
-> RG-Nr: 2025001, Betrag: 500.50, Mandant: FIRMA

# ZUGFeRD PDF
rechnung-KUNDE-2025-08-14-1200.00.pdf
-> Mandant: KUNDE, Datum: 2025-08-14, Betrag: 1200.00
```

## 📁 E-Rechnung-Verarbeitung

### **1. Automatischer Service**
```powershell
php rechnung_service.php
```
- Überwacht `incoming/` Ordner kontinuierlich
- Parst alle E-Rechnung-Formate automatisch
- Zeigt Metadaten in Echtzeit an

### **2. Batch-Verarbeitung**
```powershell
php rechnung_batch.php
```
- Verarbeitet alle Dateien im `incoming/` Ordner
- Perfekt für geplante Aufgaben
- Detaillierte Logs mit E-Rechnung-Metadaten

### **3. Service Manager**
```powershell
service_manager.bat
```
- Grafische Auswahl der Verarbeitungsart
- Log-Anzeige
- Geplante Aufgaben erstellen

## 📋 Workflow

1. **E-Rechnung empfangen** (Email, Portal, etc.)
2. **Datei speichern** in `incoming/` Ordner
3. **Service erkennt** automatisch das Format
4. **Metadaten extrahieren** aus Dateiinhalt
5. **Upload zu JobRouter** mit angereicherten Daten
6. **Archivierung** in `processed/` mit Zeitstempel
7. **Logging** aller Aktivitäten

## 🔧 Erweiterte Konfiguration

### **Neue Archive-Felder hinzufügen**
```php
// In ERechnungParser::buildMetadataComment()
if ($metadata['rechnungsnummer']) {
    $indexData['indexFields[1][name]'] = 'Rechnungsnummer';
    $indexData['indexFields[1][value]'] = $metadata['rechnungsnummer'];
}
```

### **Eigene Parser hinzufügen**
```php
// In ERechnungParser::parseERechnung()
case 'csv':
    $metadata = self::parseCSV($filePath);
    break;
```

### **Pattern erweitern**
```php
// In ERechnungParser::parseFromFilename()
$patterns[] = '/dein-pattern-hier/';
```

## 📊 Monitoring & Logs

### **Service-Logs**
```
[2025-08-14 10:30:15] E-Rechnung Upload erfolgreich - ID: 618 - musterfirma_xrechnung_2025001.xml | Typ: XRechnung | RG-Nr: E-RE-2025-001 | Betrag: 238.00 EUR
```

### **Console-Output**
```
📄 Neue E-Rechnung gefunden: musterfirma_xrechnung_2025001.xml
📊 E-Rechnung-Typ: XRechnung
📋 Rechnungsnummer: E-RE-2025-001  
💰 Betrag: 238.00 EUR
🏢 Lieferant: Musterfirma GmbH
✅ E-Rechnung Upload erfolgreich - ID: 618
```

## ⚡ Performance-Tipps

- **XML-Dateien**: Bis zu 10MB ohne Probleme
- **JSON-Dateien**: Sehr schnell, auch bei großen Dateien
- **PDF-Dateien**: ZUGFeRD-Extraktion kann länger dauern
- **Batch-Modus**: Effizienter für viele Dateien

Das E-Rechnung-System ist jetzt vollständig einsatzbereit! 🚀
