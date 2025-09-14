# ⚔️ Mage Duel - Multiplayer Fantasy Card Game

**A real-time multiplayer fantasy card battle game built with PHP, WebSocket, and Docker.**

## 🎮 Game Overview

Mage Duel is an innovative multiplayer card game where players take on the role of powerful mages competing in magical duels. The game features a unique two-phase combat system with strategic card combinations and real-time WebSocket communication.

## ✨ Key Features

- 🔥 **Real-time Multiplayer**: WebSocket-powered live gameplay
- ⚡ **Two-Phase Combat**: Strategic planning followed by explosive spell execution  
- 🎯 **Card Combinations**: Build powerful spells with 3-card combinations (Lead → Amplify → Finish)
- 👥 **Room System**: Create/join game rooms with friends
- 🎭 **Character Selection**: Choose your mage and customize your playstyle
- 📱 **Responsive Design**: Play on desktop, tablet, or mobile
- 🏆 **Tournament System**: Win medals to claim ultimate victory
- 🐳 **Docker Ready**: Easy deployment with Docker containers

## 🚀 Quick Start with Docker (Recommended)

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop)

### 1. Clone the Repository
```bash
git clone https://github.com/Sonofrya/mage-duel-web-game.git
cd mage-duel-web-game
```

### 2. Start with Docker (Windows)
```bash
# Simply double-click the batch file
docker-start.bat

# Or run manually:
docker-compose up --build -d
```

### 3. Access the Game
- 🌐 **Web Application**: http://localhost:8080
- 🔌 **WebSocket Server**: ws://localhost:8081
- 🗄️ **Database**: localhost:5432

### 4. Useful Docker Commands
```bash
# View logs
docker-compose logs -f

# Stop all services
docker-compose stop

# Stop and remove containers
docker-compose down

# Restart services
docker-compose restart

# Remove all data (database included)
docker-compose down -v
```

## 🛠️ Manual Installation (Alternative)

### Requirements
- PHP 8.0 or higher
- PostgreSQL 12 or higher
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### 1. Clone and Install Dependencies
```bash
git clone https://github.com/Sonofrya/mage-duel-web-game.git
cd mage-duel-web-game
composer install
```

### 2. Database Setup
1. Install PostgreSQL
2. Create user `admin` with password `1`:
```sql
CREATE USER admin WITH PASSWORD '1';
ALTER USER admin CREATEDB;
```
3. Run database setup:
```bash
start_database.bat  # Windows
# or
psql -f setup_database.sql  # Linux/macOS
```

### 3. Start Services
```bash
# Start WebSocket server
php server.php

# Start web server (in another terminal)
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

## 🐳 Docker Configuration

### Architecture
The Docker setup includes:
- **PostgreSQL**: Database server with automatic initialization
- **PHP/Apache**: Web application server
- **WebSocket**: Real-time communication server
- **Nginx**: Reverse proxy (optional, for production)

### Environment Variables
Create a `.env` file from `.env.example`:
```bash
cp .env.example .env
```

Key variables:
- `DB_*`: Database connection settings
- `WEBSOCKET_*`: WebSocket server configuration
- `APP_*`: Application settings

### Development vs Production
- **Development**: Use `docker-compose up` for basic setup
- **Production**: Use `docker-compose --profile production up` to include Nginx

### Troubleshooting Docker
```bash
# Check container status
docker-compose ps

# View container logs
docker-compose logs [service-name]

# Rebuild containers
docker-compose up --build

# Reset everything (WARNING: deletes database)
docker-compose down -v
```

## 🎯 Game Mechanics

### Phase 1 - Spell Crafting (100 seconds)
- Select up to 3 cards from your hand
- Cards must follow specific order: Lead → Amplify → Finish
- Strategic timing and combination building

### Phase 2 - Spell Execution
- Cards activate in sequence with visual effects
- Apply damage, healing, and special effects
- Last mage standing wins the round

### Card Types
- **Lead Cards**: Can only be first in sequence
- **Amplify Cards**: Can only be second in sequence  
- **Finish Cards**: Can only be third in sequence

## 🛠️ Development

### Project Structure
```
mage-duel-web-game/
├── docker/                 # Docker configurations
├── src/                    # PHP source code
├── js/                     # JavaScript files
├── style/                  # CSS styles
├── images/                 # Game assets
├── vendor/                 # Composer dependencies
├── Dockerfile              # Main Docker image
├── docker-compose.yml      # Multi-service setup
└── README.md              # This file
```

### Adding Features
1. Create PHP endpoint for API
2. Add corresponding JavaScript code
3. Update WebSocket handlers if needed
4. Add CSS styles for UI
5. Test with Docker environment

### Debugging
- **WebSocket logs**: `docker-compose logs websocket`
- **Web app logs**: `docker-compose logs web`
- **Database logs**: `docker-compose logs postgres`
- **All logs**: `docker-compose logs -f`

## 📈 Roadmap

- [ ] Mobile app (React Native)
- [ ] Tournament brackets
- [ ] Custom card editor
- [ ] Spectator mode
- [ ] AI opponents
- [ ] Multi-language support
- [ ] Advanced statistics

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is created for educational purposes. Feel free to use and modify as needed.

## 🆘 Support

If you encounter issues:
1. Check the [Issues](https://github.com/Sonofrya/mage-duel-web-game/issues) page
2. Review Docker logs: `docker-compose logs -f`
3. Ensure all dependencies are installed
4. Verify database connection settings
5. Make sure WebSocket server is running

---

**Built with ❤️ for the gaming community**
