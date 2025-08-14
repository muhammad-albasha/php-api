@echo off
echo ====================================
echo JobRouter Rechnung Upload Service
echo ====================================
echo.

echo Wahle eine Option:
echo.
echo 1. Service als Daemon starten (Endlosschleife)
echo 2. Einmalige Batch-Verarbeitung
echo 3. Windows geplante Aufgabe erstellen
echo 4. Service testen
echo 5. Logs anzeigen
echo.

set /p choice="Deine Wahl (1-5): "

if "%choice%"=="1" goto :daemon
if "%choice%"=="2" goto :batch
if "%choice%"=="3" goto :schedule
if "%choice%"=="4" goto :test
if "%choice%"=="5" goto :logs

echo Ungultige Wahl!
pause
exit

:daemon
echo.
echo Starte Service-Daemon...
echo Zum Beenden: Strg+C
echo.
php rechnung_service.php
pause
exit

:batch
echo.
echo Starte Batch-Verarbeitung...
php rechnung_batch.php
echo.
pause
exit

:schedule
echo.
echo Erstelle geplante Aufgabe (alle 5 Minuten)...
set SCRIPT_PATH=%CD%\rechnung_batch.php
schtasks /create /tn "JobRouter-Rechnung-Upload" /tr "php %SCRIPT_PATH%" /sc minute /mo 5 /ru "SYSTEM"
echo.
echo Geplante Aufgabe erstellt!
echo Zum Anzeigen: schtasks /query /tn "JobRouter-Rechnung-Upload"
echo Zum Loschen: schtasks /delete /tn "JobRouter-Rechnung-Upload" /f
echo.
pause
exit

:test
echo.
echo Teste Service-Konfiguration...
php -c . -r "require_once 'init.php'; echo 'JobRouter URL: ' . BASE_URL . chr(10); echo 'Username: ' . USERNAME . chr(10); echo 'Archive GUID: ' . ARCHIVE . chr(10);"
echo.
echo Teste Verzeichnisse...
if exist "incoming\" (echo ✓ incoming\ Ordner vorhanden) else (echo ✗ incoming\ Ordner fehlt)
if exist "processed\" (echo ✓ processed\ Ordner vorhanden) else (echo ✗ processed\ Ordner fehlt)
if exist "logs\" (echo ✓ logs\ Ordner vorhanden) else (echo ✗ logs\ Ordner fehlt)
echo.
pause
exit

:logs
echo.
echo Zeige aktuelle Logs...
echo.
if exist "logs\service_%date:~10,4%-%date:~4,2%-%date:~7,2%.log" (
    type "logs\service_%date:~10,4%-%date:~4,2%-%date:~7,2%.log"
) else (
    echo Keine Logs fur heute gefunden.
    echo.
    echo Verfugbare Log-Dateien:
    dir /b logs\*.log 2>nul
)
echo.
pause
exit
