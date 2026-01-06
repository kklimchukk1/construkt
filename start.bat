@echo off
title Construkt - Start Servers
color 0A
chcp 65001 >nul

echo.
echo ============================================================
echo            CONSTRUKT - Construction Materials Store
echo ============================================================
echo.

set "ROOT_DIR=%~dp0"

:: ============================================================
:: CHECK REQUIREMENTS
:: ============================================================

echo [Checking requirements...]
echo.

:: Find PHP
set "PHP_CMD="
for %%P in (
    "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
    "C:\laragon\bin\php\php-8.2.12-Win32-vs16-x64\php.exe"
    "C:\laragon\bin\php\php-8.1.25-Win32-vs16-x64\php.exe"
) do (
    if exist %%P (
        set "PHP_CMD=%%~P"
        goto :found_php
    )
)
where php >nul 2>nul
if %errorlevel% equ 0 (
    set "PHP_CMD=php"
    goto :found_php
)
echo [X] PHP not found! Install Laragon or PHP 8.1+
pause
exit /b 1

:found_php
echo [OK] PHP: %PHP_CMD%

:: Check Python
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo [X] Python not found! Install Python 3.10+
    pause
    exit /b 1
)
echo [OK] Python found

:: Check MySQL
where mysql >nul 2>nul
if %errorlevel% neq 0 (
    echo [!] MySQL CLI not in PATH (optional)
) else (
    echo [OK] MySQL CLI found
)

echo.
echo ============================================================
echo                    STARTING SERVICES
echo ============================================================
echo.

:: Start Chatbot API
echo [1/2] Starting Chatbot API (Flask + MySQL) on port 5000...
start "Chatbot API" cmd /k "cd /d "%ROOT_DIR%chatbot" && color 0B && title Chatbot API && python app.py"

timeout /t 2 /nobreak > nul

:: Start PHP Site
echo [2/2] Starting PHP Website on port 8000...
start "PHP Website" cmd /k "cd /d "%ROOT_DIR%php-site" && color 0E && title PHP Website && %PHP_CMD% -S localhost:8000"

echo.
echo ============================================================
echo                    SERVERS RUNNING
echo ============================================================
echo.
echo    Website:     http://localhost:8000
echo    Chatbot:     http://localhost:5000
echo.
echo    Database:    MySQL (localhost:3306/construkt)
echo.
echo    Test Accounts (password: "password"):
echo      Admin:     admin@construkt.com
echo      Manager:   manager@construkt.com
echo      Customer:  customer1@test.com
echo.
echo ============================================================
echo.
echo Press any key to open website...
pause > nul

start http://localhost:8000
