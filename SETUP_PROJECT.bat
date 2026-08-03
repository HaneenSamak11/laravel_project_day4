@echo off
setlocal
cd /d "%~dp0"
title Laravel Project Setup

echo ==============================================
echo   Laravel Project Day 4 - First Time Setup
echo ==============================================
echo.

where php >nul 2>nul
if errorlevel 1 (
    echo [ERROR] PHP is not installed or not added to PATH.
    echo Install XAMPP or Laravel Herd, then try again.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo [ERROR] Composer is not installed or not added to PATH.
    echo Download Composer, then try again.
    pause
    exit /b 1
)

if not exist .env copy .env.example .env >nul
if not exist database\database.sqlite type nul > database\database.sqlite

echo [1/5] Installing PHP packages...
call composer install
if errorlevel 1 goto :failed

echo [2/5] Generating application key...
php artisan key:generate
if errorlevel 1 goto :failed

echo [3/5] Clearing cached settings...
php artisan optimize:clear
if errorlevel 1 goto :failed

echo [4/5] Creating database and demo data...
php artisan migrate:fresh --seed
if errorlevel 1 goto :failed

echo [5/5] Running tests...
php artisan test
if errorlevel 1 goto :failed

echo.
echo ==============================================
echo Setup completed successfully.
echo Run START_PROJECT.bat to open the project.
echo Demo admin: admin@example.com / password
echo Demo user : user@example.com / password
echo ==============================================
pause
exit /b 0

:failed
echo.
echo [ERROR] Setup stopped because a command failed.
echo Read the error above, then try again.
pause
exit /b 1
