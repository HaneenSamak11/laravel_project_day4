@echo off
setlocal
cd /d "%~dp0"
title Upload Laravel Project to GitHub

set "REPO=https://github.com/HaneenSamak11/laravel_project_day4.git"

echo ==============================================
echo Uploading project to:
echo %REPO%
echo ==============================================
echo.

where git >nul 2>nul
if errorlevel 1 (
    echo [ERROR] Git is not installed or not added to PATH.
    echo Install Git for Windows, then try again.
    pause
    exit /b 1
)

if exist .env (
    echo .env exists locally and will NOT be uploaded.
)

if not exist .git (
    git init
    if errorlevel 1 goto :failed
)

git add .
if errorlevel 1 goto :failed

git diff --cached --quiet
if errorlevel 1 (
    git commit -m "Complete Laravel authentication API CRUD chatbot project"
    if errorlevel 1 goto :failed
) else (
    echo No new changes to commit.
)

git branch -M main

git remote get-url origin >nul 2>nul
if errorlevel 1 (
    git remote add origin "%REPO%"
) else (
    git remote set-url origin "%REPO%"
)
if errorlevel 1 goto :failed

echo.
echo GitHub may open a browser window for sign-in.
git push -u origin main
if errorlevel 1 goto :failed

echo.
echo ==============================================
echo Upload completed successfully.
echo Open: https://github.com/HaneenSamak11/laravel_project_day4
echo ==============================================
start "" "https://github.com/HaneenSamak11/laravel_project_day4"
pause
exit /b 0

:failed
echo.
echo [ERROR] The upload did not complete.
echo Copy the error shown above and send it in the chat.
pause
exit /b 1
