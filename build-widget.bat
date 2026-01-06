@echo off
title Construkt - Build Chatbot Widget
color 0B

echo.
echo ============================================================
echo            Building React Chatbot Widget
echo ============================================================
echo.

set "ROOT_DIR=%~dp0"

:: Check if node_modules exists
if not exist "%ROOT_DIR%chatbot-widget\node_modules" (
    echo [1/3] Installing npm dependencies...
    cd /d "%ROOT_DIR%chatbot-widget"
    call npm install
    if errorlevel 1 (
        echo [ERROR] Failed to install dependencies!
        pause
        exit /b 1
    )
) else (
    echo [1/3] Dependencies already installed, skipping...
)

echo [2/3] Building widget bundle...
cd /d "%ROOT_DIR%chatbot-widget"
call npm run build
if errorlevel 1 (
    echo [ERROR] Build failed!
    pause
    exit /b 1
)

echo [3/3] Copying widget to PHP site...
if not exist "%ROOT_DIR%php-site\js" mkdir "%ROOT_DIR%php-site\js"
copy /Y "%ROOT_DIR%chatbot-widget\dist\chatbot-widget.js" "%ROOT_DIR%php-site\js\"

echo.
echo ============================================================
echo               Widget built successfully!
echo.
echo    Output: php-site/js/chatbot-widget.js
echo ============================================================
echo.
pause
