# JobRouter Rechnung Upload Service

## 🚀 Automatischer Rechnung-Upload-Dienst

Dieser Service überwacht automatisch einen Ordner auf neue Rechnungen und lädt sie zu JobRouter hoch.

## 📁 Ordner-Struktur

```
incoming/     <- Hier neue Rechnungen hineinlegen
processed/    <- Verarbeitete Dateien (mit Zeitstempel)
logs/         <- Service-Logs
```

## 🔧 Service starten

### Einmalig starten:
```powershell
php rechnung_service.php
```

### Als Windows-Dienst (Optional):
```powershell
# 1. NSSM installieren (Non-Sucking Service Manager)
# 2. Service registrieren:
nssm install "JobRouter-Rechnung-Service" "C:\path\to\php.exe" "C:\Users\MAlbasha\Desktop\php\PHP\using_cURL\rechnung_service.php"

# 3. Service starten:
net start "JobRouter-Rechnung-Service"
```

### Als geplante Aufgabe:
```powershell
# Alle 5 Minuten ausführen:
schtasks /create /tn "JobRouter Upload" /tr "php C:\Users\MAlbasha\Desktop\php\PHP\using_cURL\rechnung_batch.php" /sc minute /mo 5
```

## 📄 Unterstützte Dateiformate

- PDF (.pdf)
- Text (.txt)
- Word (.doc, .docx)
- Excel (.xls, .xlsx)
- Bilder (.jpg, .jpeg, .png)

## 🏷️ Datei-Namenskonvention

**Für automatische Mandant-Erkennung:**
```
mandantname_rechnung_details.pdf
beispiel: firma_rechnung_2025001.pdf -> Mandant: "Firma"
```

**Ohne Namenskonvention:**
```
rechnung.pdf -> Mandant: "Automatischer Upload"
```

## 📋 Verwendung

1. **Service starten**
2. **Rechnung-Dateien** in `incoming/` Ordner legen
3. **Service erkennt automatisch** neue Dateien
4. **Upload zu JobRouter** erfolgt automatisch
5. **Dateien werden** nach `processed/` verschoben

## 📊 Monitoring

- **Konsolen-Ausgabe**: Live-Status des Services
- **Log-Dateien**: `logs/service_YYYY-MM-DD.log`
- **Processed-Ordner**: Erfolgreich verarbeitete Dateien

## ⚠️ Fehlerbehebung

### Service stoppt:
- Überprüfe Log-Dateien
- Teste JobRouter-Verbindung
- Überprüfe Ordner-Berechtigungen

### Upload fehlschlägt:
- Überprüfe Dateiformat
- Überprüfe JobRouter-Zugangsdaten
- Überprüfe Archive-Konfiguration

## 🔧 Konfiguration

Alle Einstellungen in `config.php`:
- JobRouter URL
- Zugangsdaten
- Archive-GUID
