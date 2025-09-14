#!/bin/bash

# Скрипт для запуска очистки неактивных комнат
# Рекомендуется запускать каждые 30 секунд через cron

# Переходим в директорию скрипта
cd "$(dirname "$0")"

# Запускаем PHP скрипт очистки
php cleanup_inactive_rooms.php

# Логируем время выполнения
echo "$(date): Cleanup script executed" >> cleanup.log
