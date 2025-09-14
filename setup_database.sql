-- Создание базы данных для Mage Game
-- PostgreSQL скрипт

-- Создание базы данных (если не существует)
-- CREATE DATABASE mydatabase;

-- Подключение к базе данных
\c mydatabase;

-- Создание таблицы пользователей
CREATE TABLE IF NOT EXISTS Users (
    login VARCHAR(50) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    character VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создание таблицы игр (комнат)
CREATE TABLE IF NOT EXISTS Games (
    id_game SERIAL PRIMARY KEY,
    creator_login VARCHAR(50) NOT NULL,
    time_start_round TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    phase INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_login) REFERENCES Users(login) ON DELETE CASCADE
);

-- Создание таблицы игроков
CREATE TABLE IF NOT EXISTS Players (
    id_player SERIAL PRIMARY KEY,
    id_game INTEGER NOT NULL,
    login VARCHAR(50) NOT NULL,
    lives INTEGER DEFAULT 3,
    tokens INTEGER DEFAULT 0,
    cards_chosen BOOLEAN DEFAULT FALSE,
    ready BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_game) REFERENCES Games(id_game) ON DELETE CASCADE,
    FOREIGN KEY (login) REFERENCES Users(login) ON DELETE CASCADE,
    UNIQUE(id_game, login)
);

-- Создание таблицы карт
CREATE TABLE IF NOT EXISTS Cards (
    id_card SERIAL PRIMARY KEY,
    png VARCHAR(255) NOT NULL,
    descr TEXT,
    cardtype INTEGER NOT NULL,
    lead INTEGER DEFAULT 0,
    heal INTEGER DEFAULT 0,
    damage INTEGER DEFAULT 0
);

-- Создание таблицы карт в руке игрока
CREATE TABLE IF NOT EXISTS Cards_in_hand (
    id SERIAL PRIMARY KEY,
    id_player INTEGER NOT NULL,
    id_card INTEGER NOT NULL,
    FOREIGN KEY (id_player) REFERENCES Players(id_player) ON DELETE CASCADE,
    FOREIGN KEY (id_card) REFERENCES Cards(id_card) ON DELETE CASCADE
);

-- Создание таблицы выбранных карт
CREATE TABLE IF NOT EXISTS Chosen_cards (
    id SERIAL PRIMARY KEY,
    id_player INTEGER NOT NULL,
    id_card INTEGER NOT NULL,
    card_position INTEGER NOT NULL,
    FOREIGN KEY (id_player) REFERENCES Players(id_player) ON DELETE CASCADE,
    FOREIGN KEY (id_card) REFERENCES Cards(id_card) ON DELETE CASCADE
);

-- Создание таблицы заклинаний
CREATE TABLE IF NOT EXISTS Spells (
    id SERIAL PRIMARY KEY,
    id_player INTEGER NOT NULL,
    id_card INTEGER NOT NULL,
    card_position INTEGER NOT NULL,
    FOREIGN KEY (id_player) REFERENCES Players(id_player) ON DELETE CASCADE,
    FOREIGN KEY (id_card) REFERENCES Cards(id_card) ON DELETE CASCADE
);

-- Создание таблицы приглашений
CREATE TABLE IF NOT EXISTS Invitations (
    id SERIAL PRIMARY KEY,
    room_id INTEGER NOT NULL,
    inviter_login VARCHAR(50) NOT NULL,
    invited_login VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES Games(id_game) ON DELETE CASCADE,
    FOREIGN KEY (inviter_login) REFERENCES Users(login) ON DELETE CASCADE,
    FOREIGN KEY (invited_login) REFERENCES Users(login) ON DELETE CASCADE
);

-- Создание индексов для улучшения производительности
CREATE INDEX IF NOT EXISTS idx_games_creator ON Games(creator_login);
CREATE INDEX IF NOT EXISTS idx_players_game ON Players(id_game);
CREATE INDEX IF NOT EXISTS idx_players_login ON Players(login);
CREATE INDEX IF NOT EXISTS idx_cards_in_hand_player ON Cards_in_hand(id_player);
CREATE INDEX IF NOT EXISTS idx_chosen_cards_player ON Chosen_cards(id_player);
CREATE INDEX IF NOT EXISTS idx_spells_player ON Spells(id_player);
CREATE INDEX IF NOT EXISTS idx_invitations_invited ON Invitations(invited_login);
CREATE INDEX IF NOT EXISTS idx_invitations_status ON Invitations(status);

-- Создание функции для обновления updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Создание триггера для автоматического обновления updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON Users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Вставка тестовых карт
INSERT INTO Cards (png, descr, cardtype, lead, heal, damage) VALUES
('images/card1.jpg', 'Атака', 1, 0, 0, 2),
('images/card2.jpg', 'Защита', 2, 0, 1, 0),
('images/card3.jpg', 'Лидерство', 3, 3, 0, 0),
('images/card4.jpg', 'Лечение', 1, 0, 3, 0),
('images/card5.jpg', 'Мощная атака', 1, 0, 0, 4),
('images/card6.jpg', 'Щит', 2, 0, 2, 0),
('images/card7.jpg', 'Командование', 3, 5, 0, 0),
('images/card8.jpg', 'Исцеление', 1, 0, 5, 0),
('images/card9.jpg', 'Критический удар', 1, 0, 0, 6),
('images/card10.jpg', 'Броня', 2, 0, 3, 0),
('images/card11.jpg', 'Вдохновение', 3, 7, 0, 0),
('images/card12.jpg', 'Регенерация', 1, 0, 4, 0),
('images/card13.jpg', 'Разрушение', 1, 0, 0, 8),
('images/card14.jpg', 'Защитный барьер', 2, 0, 4, 0),
('images/card15.jpg', 'Верховенство', 3, 10, 0, 0),
('images/card17.jpg', 'Молния', 1, 0, 0, 3),
('images/card18.jpg', 'Магический щит', 2, 0, 2, 0),
('images/card19.jpg', 'Доминирование', 3, 4, 0, 0),
('images/card20.jpg', 'Восстановление', 1, 0, 3, 0),
('images/card21.jpg', 'Огненный шар', 1, 0, 0, 5),
('images/card22.jpg', 'Ледяная стена', 2, 0, 3, 0),
('images/card23.jpg', 'Власть', 3, 6, 0, 0),
('images/card24.jpg', 'Исцеляющий свет', 1, 0, 4, 0),
('images/card25.jpg', 'Темная магия', 1, 0, 0, 7),
('images/card26.jpg', 'Энергетический щит', 2, 0, 3, 0),
('images/card27.jpg', 'Превосходство', 3, 8, 0, 0),
('images/card28.jpg', 'Божественное исцеление', 1, 0, 6, 0),
('images/card29.jpg', 'Уничтожение', 1, 0, 0, 9),
('images/card30.jpg', 'Непробиваемый щит', 2, 0, 5, 0),
('images/card31.jpg', 'Абсолютная власть', 3, 12, 0, 0),
('images/card32.jpg', 'Воскрешение', 1, 0, 8, 0),
('images/card33.jpg', 'Апокалипсис', 1, 0, 0, 10),
('images/card34.jpg', 'Божественная защита', 2, 0, 6, 0),
('images/card35.jpg', 'Верховная власть', 3, 15, 0, 0),
('images/card36.jpg', 'Бессмертие', 1, 0, 10, 0),
('images/card37.jpg', 'Конец света', 1, 0, 0, 12),
('images/card38.jpg', 'Абсолютная защита', 2, 0, 8, 0),
('images/card39.jpg', 'Власть над всем', 3, 20, 0, 0);

-- Вставка тестовых пользователей (опционально)
-- INSERT INTO Users (login, password, character) VALUES 
-- ('testuser1', 'password123', 'char_icons/1.png'),
-- ('testuser2', 'password123', 'char_icons/2.png');

-- Создание пользователя для подключения (если нужно)
-- CREATE USER admin WITH PASSWORD '1';
-- GRANT ALL PRIVILEGES ON DATABASE mydatabase TO admin;
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO admin;
-- GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO admin;

COMMIT;
