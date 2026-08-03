@echo off
cd /d "%~dp0"
title Laravel Project Day 4

if not exist vendor\autoload.php (
    echo The project has not been prepared yet.
    echo Run SETUP_PROJECT.bat first.
    pause
    exit /b 1
)

if not exist .env (
    copy .env.example .env >nul
    php artisan key:generate
)

start "" http://127.0.0.1:8000
php artisan serve
pause
