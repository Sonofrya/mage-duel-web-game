<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../../src/Utils/Security.php';
require_once __DIR__ . '/../../src/Utils/Logger.php';

class AuthController {
    private $userModel;
    
    public function __construct(PDO $conn) {
        $this->userModel = new User($conn);
    }
    
    /**
     * Регистрация пользователя
     */
    public function register(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
            return;
        }
        
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Проверка CSRF токена
        if (!Security::verifyCSRFToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF токен']);
            return;
        }
        
        // Проверка rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Security::checkRateLimit($ip, 'register', 3, 300)) {
            echo json_encode(['success' => false, 'message' => 'Слишком много попыток регистрации']);
            return;
        }
        
        // Валидация входных данных
        if (empty($login) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
            return;
        }
        
        if (!Security::validateLogin($login)) {
            echo json_encode(['success' => false, 'message' => 'Логин должен содержать 3-20 символов (буквы, цифры, _)']);
            return;
        }
        
        if (!Security::validatePassword($password)) {
            echo json_encode(['success' => false, 'message' => 'Пароль должен содержать 6-100 символов']);
            return;
        }
        
        // Проверка безопасности пароля
        $passwordCheck = Security::isPasswordSecure($password);
        if (!$passwordCheck['is_secure']) {
            echo json_encode(['success' => false, 'message' => implode(', ', $passwordCheck['errors'])]);
            return;
        }
        
        // Создание пользователя
        $result = $this->userModel->create($login, $password);
        
        if ($result['success']) {
            Logger::info("User registration successful", ['login' => $login, 'ip' => $ip]);
        } else {
            Logger::warning("User registration failed", ['login' => $login, 'ip' => $ip, 'reason' => $result['message']]);
        }
        
        echo json_encode($result);
    }
    
    /**
     * Вход пользователя
     */
    public function login(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
            return;
        }
        
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Проверка CSRF токена
        if (!Security::verifyCSRFToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF токен']);
            return;
        }
        
        // Проверка rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Security::checkRateLimit($ip, 'login', 5, 300)) {
            echo json_encode(['success' => false, 'message' => 'Слишком много попыток входа']);
            return;
        }
        
        // Валидация входных данных
        if (empty($login) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
            return;
        }
        
        // Аутентификация
        $result = $this->userModel->authenticate($login, $password);
        
        if ($result['success']) {
            // Создаем сессию
            session_start();
            $_SESSION['login'] = $result['user']['login'];
            $_SESSION['logged_in'] = true;
            
            // Устанавливаем cookies
            setcookie('login', $result['user']['login'], time() + (86400 * 30), "/");
            
            // Генерируем токен авторизации
            $token = bin2hex(random_bytes(16));
            setcookie('auth_token', $token, time() + (86400 * 30), "/");
            
            // Обновляем токен в базе данных
            $this->userModel->updateAuthToken($result['user']['login'], $token);
            
            Logger::info("User login successful", ['login' => $login, 'ip' => $ip]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Вход успешен!',
                'redirect' => 'room.html'
            ]);
        } else {
            Logger::warning("User login failed", ['login' => $login, 'ip' => $ip, 'reason' => $result['message']]);
            echo json_encode($result);
        }
    }
    
    /**
     * Выход пользователя
     */
    public function logout(): void {
        header('Content-Type: application/json');
        
        session_start();
        
        if (isset($_SESSION['login'])) {
            $login = $_SESSION['login'];
            
            // Очищаем токен в базе данных
            $this->userModel->clearAuthToken($login);
            
            // Очищаем сессию
            session_destroy();
            
            // Очищаем cookies
            setcookie('login', '', time() - 3600, '/');
            setcookie('auth_token', '', time() - 3600, '/');
            
            Logger::info("User logout", ['login' => $login]);
            
            echo json_encode(['success' => true, 'message' => 'Выход выполнен успешно']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован']);
        }
    }
    
    /**
     * Проверка авторизации
     */
    public function checkAuth(): void {
        header('Content-Type: application/json');
        
        session_start();
        
        // Проверяем сессию
        if (isset($_SESSION['login']) && isset($_SESSION['logged_in'])) {
            echo json_encode([
                'success' => true,
                'user' => ['login' => $_SESSION['login']]
            ]);
            return;
        }
        
        // Проверяем cookies
        if (isset($_COOKIE['login']) && isset($_COOKIE['auth_token'])) {
            $user = $this->userModel->verifyAuthToken($_COOKIE['login'], $_COOKIE['auth_token']);
            
            if ($user) {
                // Восстанавливаем сессию
                $_SESSION['login'] = $user['login'];
                $_SESSION['logged_in'] = true;
                
                echo json_encode([
                    'success' => true,
                    'user' => ['login' => $user['login']]
                ]);
                return;
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован']);
    }
    
    /**
     * Получение CSRF токена
     */
    public function getCSRFToken(): void {
        header('Content-Type: application/json');
        
        session_start();
        $token = Security::generateCSRFToken();
        
        echo json_encode(['csrf_token' => $token]);
    }
}
?>
