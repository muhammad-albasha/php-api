@echo off
echo Starting JobRouter Archive Upload UI...
echo.
echo Opening browser to: http://localhost:8080/upload_ui.html
echo.
echo Press Ctrl+C to stop the server
echo.

start "" "http://localhost:8080/upload_ui.html"
php -S localhost:8080
