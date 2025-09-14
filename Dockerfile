# Используем официальный PHP образ с Apache
FROM php:8.2-apache

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    libpq-dev \
    postgresql-client \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем PHP расширения
RUN docker-php-ext-install pdo pdo_pgsql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Включаем mod_rewrite для Apache
RUN a2enmod rewrite

# Копируем конфигурацию Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем файлы проекта
COPY . .

# Устанавливаем зависимости Composer (если composer.lock существует)
RUN if [ -f composer.lock ]; then composer install --no-dev --optimize-autoloader; else composer install --no-dev --optimize-autoloader --ignore-platform-reqs; fi

# Устанавливаем права доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Создаем директорию для логов
RUN mkdir -p /var/log/apache2 && chown www-data:www-data /var/log/apache2

# Открываем порт 80
EXPOSE 80

# Запускаем Apache в foreground режиме
CMD ["apache2-foreground"]
