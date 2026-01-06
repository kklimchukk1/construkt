@echo off
title Construkt - Installation
color 0A
chcp 65001 >nul

echo.
echo ============================================================
echo            CONSTRUKT - Installation Script
echo            PHP + Python + MySQL
echo ============================================================
echo.

set "ROOT_DIR=%~dp0"

:: ============================================================
:: CHECK REQUIREMENTS
:: ============================================================

echo [Step 1] Checking requirements...
echo.

set "HAS_ERROR=0"

:: Check PHP
set "PHP_CMD="
for %%P in (
    "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
    "C:\laragon\bin\php\php-8.2.12-Win32-vs16-x64\php.exe"
    "C:\laragon\bin\php\php-8.1.25-Win32-vs16-x64\php.exe"
) do (
    if exist %%P (
        set "PHP_CMD=%%~P"
        goto :check_php_done
    )
)
where php >nul 2>nul
if %errorlevel% equ 0 set "PHP_CMD=php"
:check_php_done

if defined PHP_CMD (
    echo [OK] PHP found
) else (
    echo [X] PHP not found!
    echo     Install Laragon: https://laragon.org/download/
    set "HAS_ERROR=1"
)

:: Check Python
where python >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Python found
) else (
    echo [X] Python not found!
    echo     Install: https://www.python.org/downloads/
    set "HAS_ERROR=1"
)

:: Check Node.js (optional for widget)
where node >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Node.js found
) else (
    echo [!] Node.js not found (optional for widget rebuild)
)

:: Check MySQL
where mysql >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] MySQL CLI found
) else (
    echo [!] MySQL CLI not in PATH
    echo     Make sure MySQL server is running on localhost:3306
)

if "%HAS_ERROR%"=="1" goto :error

echo.
echo ============================================================
echo [Step 2] Installing Python dependencies...
echo ============================================================
echo.

cd /d "%ROOT_DIR%chatbot"
pip install -r requirements.txt
if %errorlevel% neq 0 (
    echo [!] Some packages may have failed, continuing...
)

echo.
echo ============================================================
echo [Step 3] Creating MySQL database and tables...
echo ============================================================
echo.

cd /d "%ROOT_DIR%database"
python create_tables.py
if %errorlevel% neq 0 (
    echo [!] Table creation had issues
    echo     Make sure MySQL is running!
)

echo.
echo ============================================================
echo [Step 4] Seeding database with test data...
echo ============================================================
echo.

python seeder.py
if %errorlevel% neq 0 (
    echo [!] Seeding had issues
)

echo.
echo ============================================================
echo [Step 5] Building chatbot widget (optional)...
echo ============================================================
echo.

where node >nul 2>nul
if %errorlevel% equ 0 (
    cd /d "%ROOT_DIR%chatbot-widget"
    if exist package.json (
        call npm install
        call npm run build
        if exist "dist\chatbot-widget.js" (
            if not exist "%ROOT_DIR%php-site\js" mkdir "%ROOT_DIR%php-site\js"
            copy /Y "dist\chatbot-widget.js" "%ROOT_DIR%php-site\js\" >nul
            echo [OK] Widget built and copied
        )
    ) else (
        echo [!] No package.json found, skipping widget build
    )
) else (
    echo [!] Skipping widget build (Node.js not found)
)

echo.
echo ============================================================
echo               INSTALLATION COMPLETE
echo ============================================================
echo.
echo    Requirements:
echo      - MySQL server running on localhost:3306
echo      - User: root (no password)
echo      - Database: construkt (auto-created)
echo.
echo    To start: run start.bat
echo.
echo    Test accounts (password: "password"):
echo      - admin@construkt.com
echo      - manager@construkt.com
echo      - customer1@test.com
echo.
echo ============================================================
echo.
pause
exit /b 0

:error
echo.
echo ============================================================
echo               INSTALLATION FAILED
echo    Fix the errors above and try again.
echo ============================================================
echo.
pause
exit /b 1
