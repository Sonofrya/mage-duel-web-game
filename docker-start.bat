@echo off
echo Starting Mage Duel with Docker...
echo.

REM Проверяем наличие Docker
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Docker is not installed or not running!
    echo Please install Docker Desktop and start it.
    pause
    exit /b 1
)

REM Проверяем наличие docker-compose
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Docker Compose is not available!
    echo Please install Docker Compose.
    pause
    exit /b 1
)

echo Building and starting containers...
docker-compose up --build -d

if %errorlevel% equ 0 (
    echo.
    echo ✅ Mage Duel is now running!
    echo.
    echo 🌐 Web Application: http://localhost:8080
    echo 🔌 WebSocket Server: ws://localhost:8081
    echo 🗄️  Database: localhost:5432
    echo.
    echo 📋 Useful commands:
    echo   docker-compose logs -f    - View logs
    echo   docker-compose stop       - Stop all services
    echo   docker-compose down       - Stop and remove containers
    echo   docker-compose restart    - Restart all services
    echo.
) else (
    echo.
    echo ❌ Failed to start Mage Duel!
    echo Check the error messages above.
)

pause
