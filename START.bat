@echo off
title StockSathi - Quick Start
color 0A

echo.
echo ========================================
echo    STOCKSATHI - QUICK START
echo ========================================
echo.
echo This script will help you start StockSathi
echo.

REM Check if running from correct directory
if not exist "INSTALLER.php" (
    echo ERROR: Please run this script from the stocksathi directory!
    echo.
    pause
    exit /b 1
)

echo [1/3] Checking XAMPP installation...
echo.

REM Check for XAMPP
set XAMPP_PATH=C:\xampp
if not exist "%XAMPP_PATH%" (
    set XAMPP_PATH=C:\xampp_new
)

if not exist "%XAMPP_PATH%" (
    echo ERROR: XAMPP not found!
    echo Please install XAMPP from: https://www.apachefriends.org/
    echo.
    pause
    exit /b 1
)

echo Found XAMPP at: %XAMPP_PATH%
echo.

echo [2/3] Starting XAMPP services...
echo.

REM Start XAMPP Control Panel
echo Starting XAMPP Control Panel...
start "" "%XAMPP_PATH%\xampp-control.exe"

echo.
echo IMPORTANT: Please ensure Apache and MySQL are started (green)
echo in the XAMPP Control Panel before continuing.
echo.
pause

echo [3/3] Opening installer in browser...
echo.

REM Get current directory path
set CURRENT_DIR=%CD%
for %%I in ("%CURRENT_DIR%") do set FOLDER_NAME=%%~nxI

REM Open browser with installer
start http://localhost/%FOLDER_NAME%/INSTALLER.php

echo.
echo ========================================
echo    INSTALLATION STARTED
echo ========================================
echo.
echo The installer has been opened in your browser.
echo.
echo Follow these steps:
echo   1. Complete the installation wizard
echo   2. Login with: admin@stocksathi.com / admin123
echo   3. Start using StockSathi!
echo.
echo If the browser didn't open, manually navigate to:
echo http://localhost/%FOLDER_NAME%/INSTALLER.php
echo.
echo ========================================
echo.
pause
