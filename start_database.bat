@echo off
echo Starting PostgreSQL Database Setup...
echo.

REM Проверяем наличие PostgreSQL
psql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PostgreSQL is not installed or not in PATH
    echo Please install PostgreSQL and add it to your PATH
    echo.
    echo You can download PostgreSQL from: https://www.postgresql.org/download/
    pause
    exit /b 1
)

echo PostgreSQL found!
echo.

REM Проверяем подключение к PostgreSQL
echo Testing PostgreSQL connection...
psql -U admin -d postgres -c "SELECT version();" >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo Error: Cannot connect to PostgreSQL
    echo Please make sure PostgreSQL is running and user 'admin' exists
    echo.
    echo To create user 'admin' with password '1', run:
    echo psql -U postgres -c "CREATE USER admin WITH PASSWORD '1';"
    echo psql -U postgres -c "ALTER USER admin CREATEDB;"
    echo.
    pause
    exit /b 1
)

echo PostgreSQL connection successful!
echo.

REM Проверяем существование базы данных
echo Checking if database 'mydatabase' exists...
psql -U admin -d postgres -c "SELECT 1 FROM pg_database WHERE datname = 'mydatabase';" | findstr "1" >nul 2>&1
if %errorlevel% neq 0 (
    echo Database 'mydatabase' does not exist. Creating...
    psql -U admin -d postgres -c "CREATE DATABASE mydatabase;"
    if %errorlevel% neq 0 (
        echo Error: Failed to create database
        pause
        exit /b 1
    )
    echo Database created successfully!
) else (
    echo Database 'mydatabase' already exists.
)

echo.
echo Setting up database schema...
psql -U admin -d mydatabase -f setup_database.sql
if %errorlevel% neq 0 (
    echo Error: Failed to setup database schema
    pause
    exit /b 1
)

echo.
echo Database setup completed successfully!
echo.
echo Database connection details:
echo - Host: localhost
echo - Port: 5432
echo - Database: mydatabase
echo - Username: admin
echo - Password: 1
echo.
echo You can now start the WebSocket server using start_server.bat
echo.
pause
