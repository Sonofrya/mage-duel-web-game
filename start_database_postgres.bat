@echo off
title PostgreSQL Database Setup - Mage Game
echo ========================================
echo    PostgreSQL Database Setup
echo ========================================
echo.
echo This script will help you set up PostgreSQL database
echo.
echo Make sure PostgreSQL is installed and running
echo.
echo ========================================
echo.

REM Check if PostgreSQL is running
pg_isready -h localhost -p 5432 >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PostgreSQL is not running or not accessible
    echo Please start PostgreSQL service first
    echo.
    pause
    exit /b 1
)

echo PostgreSQL is running!
echo.

REM Create database and run setup script
echo Creating database and tables...
psql -h localhost -U admin -d postgres -f setup_database.sql

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo Database setup completed successfully!
    echo ========================================
    echo.
    echo You can now run the WebSocket server:
    echo start_websocket_server.bat
    echo.
) else (
    echo.
    echo ========================================
    echo Database setup failed!
    echo ========================================
    echo.
    echo Please check PostgreSQL connection and permissions
    echo.
)

pause
