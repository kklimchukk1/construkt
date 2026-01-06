@echo off
title Construkt - Stop Servers
color 0C
chcp 65001 >nul

echo.
echo ============================================================
echo            CONSTRUKT - Stopping Servers
echo ============================================================
echo.

echo [1/2] Stopping PHP server...
taskkill /F /IM php.exe 2>nul
if %errorlevel% equ 0 (
    echo      [OK] PHP stopped
) else (
    echo      [i] No PHP processes found
)

echo.
echo [2/2] Stopping Python processes...
taskkill /F /FI "WINDOWTITLE eq Chatbot API*" 2>nul
taskkill /F /FI "WINDOWTITLE eq Construkt*" 2>nul
if %errorlevel% equ 0 (
    echo      [OK] Python stopped
) else (
    echo      [i] No Python processes found
)

echo.
echo ============================================================
echo                 All servers stopped
echo ============================================================
echo.
echo    Note: MySQL server is not affected.
echo.
pause
