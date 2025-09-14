# Mage Game - Многопользовательская карточная игра

## Описание
Mage Game - это многопользовательская карточная игра с WebSocket поддержкой для реального времени взаимодействия между игроками.

## Особенности
- 🎮 Многопользовательская игра до 2 игроков
- ⚡ WebSocket поддержка для реального времени
- 🎨 Современный UI с анимациями
- 👥 Система комнат и приглашений
- 🎭 Выбор персонажей
- 📱 Адаптивный дизайн

## Требования
- PHP 7.4 или выше
- PostgreSQL 12 или выше
- Composer
- Веб-сервер (Apache/Nginx) или встроенный PHP сервер

## Установка

### 1. Клонирование проекта
```bash
git clone <repository-url>
cd magegame
```

### 2. Установка зависимостей
```bash
composer install
```

### 3. Настройка базы данных
1. Установите PostgreSQL
2. Создайте пользователя `admin` с паролем `1`:
```sql
CREATE USER admin WITH PASSWORD '1';
ALTER USER admin CREATEDB;
```
3. Запустите скрипт настройки базы данных:
```bash
start_database.bat
```

### 4. Запуск приложения

#### Запуск WebSocket сервера
```bash
start_server.bat
```
или
```bash
php server.php
```

#### Запуск веб-сервера
```bash
php -S localhost:8000
```

## Структура проекта

```
magegame/
├── backimg/              # Фоновые изображения
├── char_icons/           # Иконки персонажей
├── demo-cards/           # Демо карты
├── fonts/                # Шрифты
├── images/               # Изображения карт
├── js/                   # JavaScript файлы
├── markers/              # Маркеры статуса
├── style/                # CSS стили
├── vendor/               # Composer зависимости
├── *.php                 # PHP скрипты
├── *.html                # HTML страницы
├── server.php            # WebSocket сервер
├── db.php                # Конфигурация БД
├── setup_database.sql    # SQL скрипт настройки БД
└── README.md             # Этот файл
```

## API Endpoints

### Комнаты
- `GET get_rooms.php` - Получить список комнат
- `POST create_room.php` - Создать комнату
- `POST join_room.php` - Войти в комнату
- `POST leave_room.php` - Покинуть комнату

### Игроки
- `POST update_character.php` - Обновить персонажа
- `POST update_ready_status.php` - Обновить статус готовности
- `GET get_room_info.php` - Получить информацию о комнате

### Приглашения
- `POST send_invite.php` - Отправить приглашение
- `GET check_invitations.php` - Проверить приглашения

## WebSocket Events

### Клиент -> Сервер
- `joinRoom` - Присоединиться к комнате
- `leaveRoom` - Покинуть комнату
- `playerReady` - Игрок готов
- `gameAction` - Действие в игре
- `timer` - Обновление таймера

### Сервер -> Клиент
- `playerJoined` - Игрок присоединился
- `playerLeft` - Игрок покинул комнату
- `playerReady` - Игрок готов
- `startPhase2` - Начало второй фазы
- `gameAction` - Действие в игре
- `timer` - Обновление таймера

## Конфигурация

### База данных (db.php)
```php
$config = [
    'host' => 'localhost',
    'dbname' => 'mydatabase',
    'username' => 'admin',
    'password' => '1',
    'port' => 5432
];
```

### WebSocket сервер (server.php)
```php
$config = [
    'host' => '0.0.0.0',
    'port' => 8080,
    'max_connections' => 1000
];
```

## Разработка

### Добавление новых функций
1. Создайте PHP endpoint для API
2. Добавьте соответствующий JavaScript код
3. Обновите WebSocket обработчики при необходимости
4. Добавьте CSS стили для UI

### Отладка
- Логи WebSocket сервера выводятся в консоль
- Ошибки PHP записываются в error_log.txt
- Используйте браузерные инструменты разработчика для отладки JavaScript

## Лицензия
Этот проект создан для образовательных целей.

## Поддержка
При возникновении проблем:
1. Проверьте логи ошибок
2. Убедитесь, что все зависимости установлены
3. Проверьте настройки базы данных
4. Убедитесь, что WebSocket сервер запущен
