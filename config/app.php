<?php

return [
    // Настройки базы данных
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'dbname' => $_ENV['DB_NAME'] ?? 'mydatabase',
        'username' => $_ENV['DB_USER'] ?? 'admin',
        'password' => $_ENV['DB_PASS'] ?? '1',
        'charset' => 'utf8',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    // Настройки WebSocket
    'websocket' => [
        'host' => $_ENV['WS_HOST'] ?? '0.0.0.0',
        'port' => $_ENV['WS_PORT'] ?? 8080,
        'max_connections' => $_ENV['WS_MAX_CONNECTIONS'] ?? 1000,
    ],
    
    // Настройки безопасности
    'security' => [
        'csrf_token_lifetime' => $_ENV['CSRF_LIFETIME'] ?? 3600, // 1 час
        'rate_limit' => [
            'login' => [
                'max_attempts' => $_ENV['RATE_LIMIT_LOGIN'] ?? 5,
                'time_window' => $_ENV['RATE_LIMIT_LOGIN_WINDOW'] ?? 300, // 5 минут
            ],
            'register' => [
                'max_attempts' => $_ENV['RATE_LIMIT_REGISTER'] ?? 3,
                'time_window' => $_ENV['RATE_LIMIT_REGISTER_WINDOW'] ?? 300, // 5 минут
            ],
        ],
        'password' => [
            'min_length' => $_ENV['PASSWORD_MIN_LENGTH'] ?? 8,
            'max_length' => $_ENV['PASSWORD_MAX_LENGTH'] ?? 100,
            'require_uppercase' => $_ENV['PASSWORD_REQUIRE_UPPERCASE'] ?? true,
            'require_lowercase' => $_ENV['PASSWORD_REQUIRE_LOWERCASE'] ?? true,
            'require_numbers' => $_ENV['PASSWORD_REQUIRE_NUMBERS'] ?? true,
            'require_special' => $_ENV['PASSWORD_REQUIRE_SPECIAL'] ?? true,
        ],
    ],
    
    // Настройки игры
    'game' => [
        'max_players_per_room' => $_ENV['MAX_PLAYERS_PER_ROOM'] ?? 4,
        'room_cleanup_timeout' => $_ENV['ROOM_CLEANUP_TIMEOUT'] ?? 120, // 2 минуты
        'session_timeout' => $_ENV['SESSION_TIMEOUT'] ?? 1800, // 30 минут
        'card_effects' => [
            'damage_multiplier' => $_ENV['DAMAGE_MULTIPLIER'] ?? 1.0,
            'heal_multiplier' => $_ENV['HEAL_MULTIPLIER'] ?? 1.0,
        ],
    ],
    
    // Настройки логирования
    'logging' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'INFO', // DEBUG, INFO, WARNING, ERROR, CRITICAL
        'file' => $_ENV['LOG_FILE'] ?? 'logs/app.log',
        'max_file_size' => $_ENV['LOG_MAX_FILE_SIZE'] ?? 10485760, // 10MB
        'max_files' => $_ENV['LOG_MAX_FILES'] ?? 5,
        'enable_web_logs' => $_ENV['ENABLE_WEB_LOGS'] ?? false,
    ],
    
    // Настройки кэширования
    'cache' => [
        'enabled' => $_ENV['CACHE_ENABLED'] ?? false,
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file', // file, redis, memcached
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => $_ENV['REDIS_DATABASE'] ?? 0,
        ],
        'ttl' => $_ENV['CACHE_TTL'] ?? 3600, // 1 час
    ],
    
    // Настройки мониторинга
    'monitoring' => [
        'enabled' => $_ENV['MONITORING_ENABLED'] ?? true,
        'metrics_endpoint' => $_ENV['METRICS_ENDPOINT'] ?? '/metrics',
        'health_check_endpoint' => $_ENV['HEALTH_CHECK_ENDPOINT'] ?? '/health',
        'performance_tracking' => $_ENV['PERFORMANCE_TRACKING'] ?? true,
    ],
    
    // Настройки разработки
    'development' => [
        'debug' => $_ENV['DEBUG'] ?? false,
        'error_reporting' => $_ENV['ERROR_REPORTING'] ?? E_ALL,
        'display_errors' => $_ENV['DISPLAY_ERRORS'] ?? false,
        'profiling' => $_ENV['PROFILING'] ?? false,
    ],
    
    // Настройки сессий
    'session' => [
        'name' => $_ENV['SESSION_NAME'] ?? 'MAGE_GAME_SESSION',
        'lifetime' => $_ENV['SESSION_LIFETIME'] ?? 86400, // 24 часа
        'path' => $_ENV['SESSION_PATH'] ?? '/',
        'domain' => $_ENV['SESSION_DOMAIN'] ?? '',
        'secure' => $_ENV['SESSION_SECURE'] ?? false,
        'httponly' => $_ENV['SESSION_HTTPONLY'] ?? true,
        'samesite' => $_ENV['SESSION_SAMESITE'] ?? 'Lax',
    ],
    
    // Настройки файлов
    'files' => [
        'upload_path' => $_ENV['UPLOAD_PATH'] ?? 'uploads/',
        'max_file_size' => $_ENV['MAX_FILE_SIZE'] ?? 5242880, // 5MB
        'allowed_types' => explode(',', $_ENV['ALLOWED_FILE_TYPES'] ?? 'jpg,jpeg,png,gif,svg'),
    ],
    
    // Настройки API
    'api' => [
        'version' => $_ENV['API_VERSION'] ?? 'v1',
        'rate_limit' => $_ENV['API_RATE_LIMIT'] ?? 100, // запросов в минуту
        'cors' => [
            'enabled' => $_ENV['CORS_ENABLED'] ?? true,
            'origins' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
            'methods' => explode(',', $_ENV['CORS_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS'),
            'headers' => explode(',', $_ENV['CORS_HEADERS'] ?? 'Content-Type,Authorization,X-CSRF-Token'),
        ],
    ],
];
?>
