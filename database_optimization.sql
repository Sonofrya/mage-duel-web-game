-- Оптимизация базы данных для Mage Game

-- 1. Добавление индексов для улучшения производительности

-- Индексы для таблицы Users
CREATE INDEX IF NOT EXISTS idx_users_login ON users(login);
CREATE INDEX IF NOT EXISTS idx_users_auth_token ON users(auth_token);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);

-- Индексы для таблицы Games
CREATE INDEX IF NOT EXISTS idx_games_created_at ON Games(created_at);
CREATE INDEX IF NOT EXISTS idx_games_status ON Games(status);

-- Индексы для таблицы Players
CREATE INDEX IF NOT EXISTS idx_players_game ON Players(id_game);
CREATE INDEX IF NOT EXISTS idx_players_login ON Players(login);
CREATE INDEX IF NOT EXISTS idx_players_ready ON Players(ready);
CREATE INDEX IF NOT EXISTS idx_players_cards_chosen ON Players(cards_chosen);

-- Индексы для таблицы Cards_in_hand
CREATE INDEX IF NOT EXISTS idx_cards_hand_player ON Cards_in_hand(id_player);
CREATE INDEX IF NOT EXISTS idx_cards_hand_game ON Cards_in_hand(id_game);

-- Индексы для таблицы Cards_chosen
CREATE INDEX IF NOT EXISTS idx_cards_chosen_player ON Cards_chosen(id_player);
CREATE INDEX IF NOT EXISTS idx_cards_chosen_game ON Cards_chosen(id_game);
CREATE INDEX IF NOT EXISTS idx_cards_chosen_position ON Cards_chosen(card_position);

-- Индексы для таблицы Spells
CREATE INDEX IF NOT EXISTS idx_spells_player ON Spells(id_player);
CREATE INDEX IF NOT EXISTS idx_spells_game ON Spells(id_game);

-- Индексы для таблицы Invitations
CREATE INDEX IF NOT EXISTS idx_invitations_inviter ON Invitations(inviter_login);
CREATE INDEX IF NOT EXISTS idx_invitations_invited ON Invitations(invited_login);
CREATE INDEX IF NOT EXISTS idx_invitations_room ON Invitations(room_id);
CREATE INDEX IF NOT EXISTS idx_invitations_status ON Invitations(status);

-- Индексы для таблицы room_activity
CREATE INDEX IF NOT EXISTS idx_room_activity_last_activity ON room_activity(last_activity);
CREATE INDEX IF NOT EXISTS idx_room_activity_created_at ON room_activity(created_at);

-- 2. Оптимизированные запросы

-- Пример оптимизированного запроса для получения информации об игре
-- Вместо множественных SELECT использовать JOIN
CREATE OR REPLACE VIEW game_info_optimized AS
SELECT 
    g.id_game,
    g.game_name,
    g.created_at,
    g.status,
    ra.last_activity,
    COUNT(p.id_player) as player_count,
    COUNT(CASE WHEN p.ready = true THEN 1 END) as ready_count,
    COUNT(CASE WHEN p.cards_chosen = true THEN 1 END) as cards_chosen_count
FROM Games g
LEFT JOIN room_activity ra ON g.id_game = ra.id_game
LEFT JOIN Players p ON g.id_game = p.id_game
GROUP BY g.id_game, g.game_name, g.created_at, g.status, ra.last_activity;

-- Пример оптимизированного запроса для получения игроков с их персонажами
CREATE OR REPLACE VIEW players_with_characters AS
SELECT 
    p.id_player,
    p.login,
    p.lives,
    p.tokens,
    p.ready,
    p.cards_chosen,
    u.character,
    p.id_game
FROM Players p
JOIN Users u ON p.login = u.login;

-- 3. Триггеры для автоматического обновления статистики

-- Триггер для обновления активности комнаты при изменении игроков
CREATE OR REPLACE FUNCTION update_room_activity_on_player_change()
RETURNS TRIGGER AS $$
BEGIN
    -- Обновляем активность комнаты
    INSERT INTO room_activity (id_game, last_activity, created_at) 
    VALUES (COALESCE(NEW.id_game, OLD.id_game), NOW(), NOW())
    ON CONFLICT (id_game) DO UPDATE SET last_activity = NOW();
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_update_activity_on_player_insert
    AFTER INSERT ON Players
    FOR EACH ROW
    EXECUTE FUNCTION update_room_activity_on_player_change();

CREATE TRIGGER tr_update_activity_on_player_update
    AFTER UPDATE ON Players
    FOR EACH ROW
    EXECUTE FUNCTION update_room_activity_on_player_change();

-- 4. Функции для оптимизированных операций

-- Функция для получения статистики игрока
CREATE OR REPLACE FUNCTION get_player_stats(player_login TEXT)
RETURNS TABLE(
    games_played BIGINT,
    games_won BIGINT,
    win_rate NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(DISTINCT p.id_game) as games_played,
        COUNT(CASE WHEN p.tokens >= 2 THEN 1 END) as games_won,
        CASE 
            WHEN COUNT(DISTINCT p.id_game) > 0 
            THEN ROUND((COUNT(CASE WHEN p.tokens >= 2 THEN 1 END)::NUMERIC / COUNT(DISTINCT p.id_game)) * 100, 2)
            ELSE 0 
        END as win_rate
    FROM Players p 
    WHERE p.login = player_login;
END;
$$ LANGUAGE plpgsql;

-- Функция для очистки неактивных комнат
CREATE OR REPLACE FUNCTION cleanup_inactive_rooms(inactive_minutes INTEGER DEFAULT 2)
RETURNS INTEGER AS $$
DECLARE
    cleaned_count INTEGER := 0;
    room_record RECORD;
BEGIN
    -- Находим неактивные комнаты
    FOR room_record IN 
        SELECT ra.id_game 
        FROM room_activity ra
        WHERE ra.last_activity < NOW() - INTERVAL '1 minute' * inactive_minutes
    LOOP
        -- Удаляем связанные данные
        DELETE FROM Spells WHERE id_game = room_record.id_game;
        DELETE FROM Cards_chosen WHERE id_game = room_record.id_game;
        DELETE FROM Cards_in_hand WHERE id_game = room_record.id_game;
        DELETE FROM Players WHERE id_game = room_record.id_game;
        DELETE FROM room_activity WHERE id_game = room_record.id_game;
        DELETE FROM Games WHERE id_game = room_record.id_game;
        
        cleaned_count := cleaned_count + 1;
    END LOOP;
    
    RETURN cleaned_count;
END;
$$ LANGUAGE plpgsql;

-- 5. Материализованные представления для часто используемых данных

-- Материализованное представление для статистики игроков
CREATE MATERIALIZED VIEW IF NOT EXISTS player_statistics AS
SELECT 
    p.login,
    COUNT(DISTINCT p.id_game) as games_played,
    COUNT(CASE WHEN p.tokens >= 2 THEN 1 END) as games_won,
    CASE 
        WHEN COUNT(DISTINCT p.id_game) > 0 
        THEN ROUND((COUNT(CASE WHEN p.tokens >= 2 THEN 1 END)::NUMERIC / COUNT(DISTINCT p.id_game)) * 100, 2)
        ELSE 0 
    END as win_rate,
    MAX(p.id_game) as last_game_id
FROM Players p
GROUP BY p.login;

-- Индекс для материализованного представления
CREATE INDEX IF NOT EXISTS idx_player_stats_login ON player_statistics(login);
CREATE INDEX IF NOT EXISTS idx_player_stats_games_played ON player_statistics(games_played);
CREATE INDEX IF NOT EXISTS idx_player_stats_win_rate ON player_statistics(win_rate);

-- Функция для обновления материализованного представления
CREATE OR REPLACE FUNCTION refresh_player_statistics()
RETURNS VOID AS $$
BEGIN
    REFRESH MATERIALIZED VIEW player_statistics;
END;
$$ LANGUAGE plpgsql;

-- 6. Настройки производительности PostgreSQL

-- Увеличиваем shared_buffers для лучшей производительности
-- (требует перезапуска PostgreSQL)
-- ALTER SYSTEM SET shared_buffers = '256MB';

-- Увеличиваем work_mem для сложных запросов
-- ALTER SYSTEM SET work_mem = '16MB';

-- Настраиваем эффективное планирование запросов
-- ALTER SYSTEM SET effective_cache_size = '1GB';

-- 7. Мониторинг производительности

-- Представление для мониторинга медленных запросов
CREATE OR REPLACE VIEW slow_queries AS
SELECT 
    query,
    calls,
    total_time,
    mean_time,
    rows
FROM pg_stat_statements
WHERE mean_time > 1000  -- Запросы дольше 1 секунды
ORDER BY mean_time DESC;

-- Представление для мониторинга использования индексов
CREATE OR REPLACE VIEW index_usage AS
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
ORDER BY idx_tup_read DESC;

-- 8. Резервное копирование и восстановление

-- Функция для создания бэкапа игровых данных
CREATE OR REPLACE FUNCTION backup_game_data()
RETURNS TEXT AS $$
DECLARE
    backup_file TEXT;
BEGIN
    backup_file := 'game_backup_' || to_char(NOW(), 'YYYY_MM_DD_HH24_MI_SS') || '.sql';
    
    -- Экспорт данных (требует pg_dump в PATH)
    PERFORM pg_dump(
        'host=localhost dbname=mydatabase user=admin password=1',
        '--data-only',
        '--file=' || backup_file,
        '--table=Games',
        '--table=Players',
        '--table=Users'
    );
    
    RETURN backup_file;
END;
$$ LANGUAGE plpgsql;

-- 9. Анализ и статистика

-- Функция для получения статистики базы данных
CREATE OR REPLACE FUNCTION get_database_stats()
RETURNS TABLE(
    table_name TEXT,
    row_count BIGINT,
    table_size TEXT,
    index_size TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        t.table_name::TEXT,
        t.n_tup_ins - t.n_tup_del as row_count,
        pg_size_pretty(pg_total_relation_size(c.oid)) as table_size,
        pg_size_pretty(pg_indexes_size(c.oid)) as index_size
    FROM pg_stat_user_tables t
    JOIN pg_class c ON c.relname = t.relname
    ORDER BY pg_total_relation_size(c.oid) DESC;
END;
$$ LANGUAGE plpgsql;
