<?php

class Security {
    
    /**
     * Генерирует CSRF токен
     */
    public static function generateCSRFToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Проверяет CSRF токен
     */
    public static function verifyCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Санитизирует выходные данные для предотвращения XSS
     */
    public static function sanitizeOutput(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Валидирует логин
     */
    public static function validateLogin(string $login): bool {
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $login);
    }
    
    /**
     * Валидирует пароль
     */
    public static function validatePassword(string $password): bool {
        return strlen($password) >= 6 && strlen($password) <= 100;
    }
    
    /**
     * Проверяет rate limiting для предотвращения брутфорса
     */
    public static function checkRateLimit(string $ip, string $action, int $maxAttempts = 5, int $timeWindow = 300): bool {
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }
        
        $data = $_SESSION[$key];
        
        // Сброс счетчика если время истекло
        if (time() > $data['reset_time']) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
            return true;
        }
        
        // Проверка лимита
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        // Увеличение счетчика
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Логирует попытку входа
     */
    public static function logLoginAttempt(string $login, string $ip, bool $success): void {
        $status = $success ? 'SUCCESS' : 'FAILED';
        $message = "Login attempt: {$status} - User: {$login} - IP: {$ip}";
        error_log(date('Y-m-d H:i:s') . " [SECURITY] " . $message);
    }
    
    /**
     * Проверяет безопасность пароля
     */
    public static function isPasswordSecure(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Пароль должен содержать минимум 8 символов';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать заглавные буквы';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать строчные буквы';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать цифры';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать специальные символы';
        }
        
        return [
            'is_secure' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
