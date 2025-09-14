@echo off
echo Starting Mage Game Server...
echo.

REM Проверяем наличие PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PHP is not installed or not in PATH
    echo Please install PHP and add it to your PATH
    pause
    exit /b 1
)

REM Проверяем наличие Composer
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: Composer is not installed or not in PATH
    echo Please install Composer and add it to your PATH
    pause
    exit /b 1
)

REM Устанавливаем зависимости если нужно
if not exist "vendor\autoload.php" (
    echo Installing dependencies...
    composer install
    if %errorlevel% neq 0 (
        echo Error: Failed to install dependencies
        pause
        exit /b 1
    )
)

echo.
echo Starting WebSocket server on port 8080...
echo Press Ctrl+C to stop the server
echo.

REM Запускаем сервер
php server.php

pause
