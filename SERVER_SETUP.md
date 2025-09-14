# 🚀 Гайд по установке на сервер

Краткое руководство по развертыванию Mage Duel на VPS/сервере.

## 📋 Требования к серверу

### Минимальные требования:
- **CPU**: 1 ядро
- **RAM**: 1 GB
- **Диск**: 10 GB SSD
- **ОС**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+
- **Сеть**: Статический IP адрес

### Рекомендуемые требования:
- **CPU**: 2 ядра
- **RAM**: 2 GB
- **Диск**: 20 GB SSD
- **ОС**: Ubuntu 22.04 LTS
- **Сеть**: Домен + SSL сертификат

## 🐳 Быстрая установка с Docker (Рекомендуется)

### 1. Подготовка сервера

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Добавление пользователя в группу docker
sudo usermod -aG docker $USER

# Установка Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Перезагрузка для применения изменений
sudo reboot
```

### 2. Клонирование и запуск

```bash
# Клонирование репозитория
git clone https://github.com/Sonofrya/mage-duel-web-game.git
cd mage-duel-web-game

# Запуск всех сервисов
docker-compose up --build -d

# Проверка статуса
docker-compose ps
```

### 3. Настройка файрвола

```bash
# Открытие необходимых портов
sudo ufw allow 22      # SSH
sudo ufw allow 80      # HTTP
sudo ufw allow 443     # HTTPS
sudo ufw allow 8080    # Web приложение
sudo ufw allow 8081    # WebSocket
sudo ufw enable
```

### 4. Настройка домена (опционально)

```bash
# Установка Nginx для проксирования
sudo apt install nginx -y

# Создание конфигурации
sudo nano /etc/nginx/sites-available/mage-duel
```

Конфигурация Nginx:
```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /ws {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Активация конфигурации
sudo ln -s /etc/nginx/sites-available/mage-duel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. SSL сертификат (Let's Encrypt)

```bash
# Установка Certbot
sudo apt install certbot python3-certbot-nginx -y

# Получение SSL сертификата
sudo certbot --nginx -d your-domain.com

# Автоматическое обновление
sudo crontab -e
# Добавить строку:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

## 🛠️ Ручная установка (Альтернатива)

### 1. Установка зависимостей

```bash
# PHP и расширения
sudo apt install php8.1 php8.1-fpm php8.1-pgsql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip -y

# PostgreSQL
sudo apt install postgresql postgresql-contrib -y

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Nginx
sudo apt install nginx -y
```

### 2. Настройка базы данных

```bash
# Переключение на пользователя postgres
sudo -u postgres psql

# Создание пользователя и базы данных
CREATE USER admin WITH PASSWORD 'your_secure_password';
CREATE DATABASE mydatabase OWNER admin;
GRANT ALL PRIVILEGES ON DATABASE mydatabase TO admin;
\q

# Импорт схемы базы данных
psql -U admin -d mydatabase -f setup_database.sql
```

### 3. Настройка веб-сервера

```bash
# Создание конфигурации Nginx
sudo nano /etc/nginx/sites-available/mage-duel
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/mage-duel-web-game;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /ws {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

### 4. Запуск сервисов

```bash
# Копирование файлов
sudo cp -r . /var/www/mage-duel-web-game
sudo chown -R www-data:www-data /var/www/mage-duel-web-game

# Активация сайта
sudo ln -s /etc/nginx/sites-available/mage-duel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Запуск WebSocket сервера
cd /var/www/mage-duel-web-game
php server.php &
```

## 🔧 Настройка окружения

### Переменные окружения

Создайте файл `.env`:
```bash
# База данных
DB_HOST=localhost
DB_NAME=mydatabase
DB_USER=admin
DB_PASSWORD=your_secure_password
DB_PORT=5432

# WebSocket
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8081

# Приложение
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Оптимизация производительности

```bash
# Настройка PHP-FPM
sudo nano /etc/php/8.1/fpm/pool.d/www.conf

# Изменить параметры:
# pm.max_children = 20
# pm.start_servers = 5
# pm.min_spare_servers = 5
# pm.max_spare_servers = 10

sudo systemctl restart php8.1-fpm
```

## 📊 Мониторинг и логи

### Просмотр логов

```bash
# Docker логи
docker-compose logs -f

# Nginx логи
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# PHP логи
sudo tail -f /var/log/php8.1-fpm.log

# PostgreSQL логи
sudo tail -f /var/log/postgresql/postgresql-*.log
```

### Мониторинг ресурсов

```bash
# Использование ресурсов
htop
df -h
free -h

# Статус сервисов
systemctl status nginx
systemctl status postgresql
systemctl status php8.1-fpm
```

## 🔄 Обновление

### Docker обновление

```bash
# Остановка сервисов
docker-compose down

# Обновление кода
git pull origin main

# Пересборка и запуск
docker-compose up --build -d
```

### Ручное обновление

```bash
# Остановка WebSocket сервера
pkill -f "php server.php"

# Обновление кода
git pull origin main

# Установка зависимостей
composer install --no-dev --optimize-autoloader

# Запуск WebSocket сервера
php server.php &
```

## 🛡️ Безопасность

### Основные меры безопасности

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Настройка файрвола
sudo ufw enable
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Отключение root SSH
sudo nano /etc/ssh/sshd_config
# PermitRootLogin no
sudo systemctl restart ssh

# Настройка fail2ban
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
```

### Резервное копирование

```bash
# Создание скрипта бэкапа
sudo nano /usr/local/bin/backup-mage-duel.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/mage-duel"
mkdir -p $BACKUP_DIR

# Бэкап базы данных
pg_dump -U admin mydatabase > $BACKUP_DIR/db_$DATE.sql

# Бэкап файлов
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/mage-duel-web-game

# Удаление старых бэкапов (старше 7 дней)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Делаем скрипт исполняемым
sudo chmod +x /usr/local/bin/backup-mage-duel.sh

# Добавляем в cron (ежедневно в 2:00)
sudo crontab -e
# 0 2 * * * /usr/local/bin/backup-mage-duel.sh
```

## 🆘 Устранение неполадок

### Частые проблемы

1. **WebSocket не подключается**
   ```bash
   # Проверка порта
   netstat -tlnp | grep 8081
   
   # Проверка файрвола
   sudo ufw status
   ```

2. **База данных не подключается**
   ```bash
   # Проверка статуса PostgreSQL
   sudo systemctl status postgresql
   
   # Проверка подключения
   psql -U admin -d mydatabase -h localhost
   ```

3. **Высокая нагрузка**
   ```bash
   # Мониторинг процессов
   top
   
   # Проверка логов на ошибки
   sudo tail -f /var/log/nginx/error.log
   ```

### Полезные команды

```bash
# Перезапуск всех сервисов (Docker)
docker-compose restart

# Перезапуск всех сервисов (ручная установка)
sudo systemctl restart nginx postgresql php8.1-fpm

# Очистка логов
sudo truncate -s 0 /var/log/nginx/*.log
sudo truncate -s 0 /var/log/php8.1-fpm.log
```

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи сервисов
2. Убедитесь, что все порты открыты
3. Проверьте статус всех сервисов
4. Создайте issue в [GitHub репозитории](https://github.com/Sonofrya/mage-duel-web-game/issues)

---

**Удачного развертывания! 🚀**
