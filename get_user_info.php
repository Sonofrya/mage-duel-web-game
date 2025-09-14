<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

try {
    // Проверяем, авторизован ли пользователь
    if (isset($_SESSION['login']) && $_SESSION['logged_in']) {
        echo json_encode([
            'success' => true,
            'login' => $_SESSION['login']
        ]);
    } else {
        // Проверяем cookie
        if (isset($_COOKIE['login']) && isset($_COOKIE['auth_token'])) {
            $login = $_COOKIE['login'];
            $token = $_COOKIE['auth_token'];
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE login = :login AND auth_token = :token");
            $stmt->bindParam(':login', $login);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                // Восстанавливаем сессию
                $_SESSION['login'] = $user['login'];
                $_SESSION['logged_in'] = true;
                
                echo json_encode([
                    'success' => true,
                    'login' => $user['login']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Недействительная сессия'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Пользователь не авторизован'
            ]);
        }
    }
} catch (Exception $e) {
    error_log("Get user info error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера'
    ]);
}
?>
