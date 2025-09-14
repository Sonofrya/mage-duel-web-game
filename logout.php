<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

try {
    if (isset($_SESSION['login'])) {
        $login = $_SESSION['login'];
        
        // Очищаем токен в базе данных
        $stmt = $conn->prepare("UPDATE users SET auth_token = NULL WHERE login = :login");
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        
        // Очищаем сессию
        session_destroy();
        
        // Очищаем cookie
        setcookie('login', '', time() - 3600, '/');
        setcookie('auth_token', '', time() - 3600, '/');
        
        echo json_encode([
            'success' => true,
            'message' => 'Выход выполнен успешно'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь не авторизован'
        ]);
    }
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при выходе'
    ]);
}
?>
