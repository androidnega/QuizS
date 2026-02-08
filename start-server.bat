@echo off
REM Double-click to start the Laravel dev server.
cd /d "%~dp0"

REM Use XAMPP PHP if available; otherwise use system php
if exist "C:\xampp\php\php.exe" (
  set PHP=C:\xampp\php\php.exe
) else (
  set PHP=php
)

echo Starting Laravel development server...
echo Stop with Ctrl+C. Leave this window open.
echo.
"%PHP%" artisan serve

pause
