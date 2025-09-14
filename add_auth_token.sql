-- Добавление колонки auth_token в таблицу Users
-- Миграция для исправления ошибки авторизации

-- Подключение к базе данных
\c mydatabase;

-- Добавление колонки auth_token
ALTER TABLE Users ADD COLUMN IF NOT EXISTS auth_token VARCHAR(255);

-- Создание индекса для auth_token (опционально, для производительности)
CREATE INDEX IF NOT EXISTS idx_users_auth_token ON Users(auth_token);

-- Обновление существующих записей (если нужно)
-- UPDATE Users SET auth_token = NULL WHERE auth_token IS NULL;

COMMIT;
