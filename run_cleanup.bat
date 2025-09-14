@echo off
REM Скрипт для запуска очистки неактивных комнат в Windows
REM Рекомендуется запускать каждые 30 секунд через планировщик задач

REM Переходим в директорию скрипта
cd /d "%~dp0"

REM Запускаем PHP скрипт очистки
php cleanup_inactive_rooms.php

REM Логируем время выполнения
echo %date% %time%: Cleanup script executed >> cleanup.log
