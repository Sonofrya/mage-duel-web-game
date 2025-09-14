@echo off
echo Stopping Mage Duel Docker containers...
echo.

docker-compose down

if %errorlevel% equ 0 (
    echo.
    echo ✅ Mage Duel has been stopped successfully!
    echo.
    echo 💡 To start again, run: docker-start.bat
    echo 💡 To remove all data, run: docker-compose down -v
    echo.
) else (
    echo.
    echo ❌ Failed to stop some containers!
    echo You may need to stop them manually.
)

pause
