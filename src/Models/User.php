<?php

class User {
    private $conn;
    
    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }
    
    /**
     * Создает нового пользователя
     */
    public function create(string $login, string $password): array {
        try {
            // Проверяем существование пользователя
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE login = :login");
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Пользователь уже существует'];
            }
            
            // Создаем пользователя
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (login, password, created_at) VALUES (:login, :password, NOW())");
            $stmt->bindParam(':login', $login);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->execute();
            
            Logger::info("User created", ['login' => $login]);
            
            return ['success' => true, 'message' => 'Пользователь создан успешно'];
            
        } catch (PDOException $e) {
            Logger::error("User creation failed", ['login' => $login, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ошибка создания пользователя'];
        }
    }
    
    /**
     * Аутентифицирует пользователя
     */
    public function authenticate(string $login, string $password): array {
        try {
            $stmt = $this->conn->prepare("SELECT id, login, password FROM users WHERE login = :login");
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                Logger::warning("Failed login attempt", ['login' => $login, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                return ['success' => false, 'message' => 'Неверный логин или пароль'];
            }
            
            Logger::info("Successful login", ['login' => $login, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            
            return [
                'success' => true, 
                'user' => [
                    'id' => $user['id'],
                    'login' => $user['login']
                ]
            ];
            
        } catch (PDOException $e) {
            Logger::error("Authentication error", ['login' => $login, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ошибка аутентификации'];
        }
    }
    
    /**
     * Обновляет токен авторизации
     */
    public function updateAuthToken(string $login, string $token): bool {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET auth_token = :token, last_login = NOW() WHERE login = :login");
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':login', $login);
            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error("Token update failed", ['login' => $login, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Проверяет токен авторизации
     */
    public function verifyAuthToken(string $login, string $token): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT id, login FROM users WHERE login = :login AND auth_token = :token");
            $stmt->bindParam(':login', $login);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Token verification failed", ['login' => $login, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Очищает токен авторизации
     */
    public function clearAuthToken(string $login): bool {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET auth_token = NULL WHERE login = :login");
            $stmt->bindParam(':login', $login);
            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error("Token clear failed", ['login' => $login, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Получает информацию о пользователе
     */
    public function getById(int $id): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT id, login, created_at, last_login FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Get user by ID failed", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Получает статистику пользователя
     */
    public function getStats(string $login): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT p.id_game) as games_played,
                    COUNT(CASE WHEN p.tokens >= 2 THEN 1 END) as games_won
                FROM Players p 
                WHERE p.login = :login
            ");
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'games_played' => (int)$stats['games_played'],
                'games_won' => (int)$stats['games_won'],
                'win_rate' => $stats['games_played'] > 0 ? round(($stats['games_won'] / $stats['games_played']) * 100, 2) : 0
            ];
        } catch (PDOException $e) {
            Logger::error("Get user stats failed", ['login' => $login, 'error' => $e->getMessage()]);
            return ['games_played' => 0, 'games_won' => 0, 'win_rate' => 0];
        }
    }
}
?>
