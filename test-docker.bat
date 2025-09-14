@echo off
echo Testing Docker configuration for Mage Duel...
echo.

echo 1. Checking Docker installation...
docker --version
if %errorlevel% neq 0 (
    echo ERROR: Docker is not installed or not running!
    pause
    exit /b 1
)

echo.
echo 2. Checking Docker Compose...
docker-compose --version
if %errorlevel% neq 0 (
    echo ERROR: Docker Compose is not available!
    pause
    exit /b 1
)

echo.
echo 3. Validating docker-compose.yml...
docker-compose config --quiet
if %errorlevel% neq 0 (
    echo ERROR: docker-compose.yml has syntax errors!
    pause
    exit /b 1
)

echo.
echo 4. Checking if ports are available...
netstat -an | findstr :8080
if %errorlevel% equ 0 (
    echo WARNING: Port 8080 is already in use!
)

netstat -an | findstr :5432
if %errorlevel% equ 0 (
    echo WARNING: Port 5432 is already in use!
)

echo.
echo ✅ Docker configuration is valid!
echo.
echo Ready to start Mage Duel with:
echo   docker-compose up --build -d
echo.
echo Or use: docker-start.bat
echo.
pause
