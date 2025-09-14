<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login']) && isset($_POST['password'])) {
        try {
            $login = trim($_POST['login']);
            $password = $_POST['password'];

            if (empty($login) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Пожалуйста, заполните все поля'
                ]);
                exit;
            }

            if (strlen($login) < 3) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Логин должен содержать минимум 3 символа'
                ]);
                exit;
            }

            if (strlen($password) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Пароль должен содержать минимум 6 символов'
                ]);
                exit;
            }

            // Проверяем, существует ли пользователь
            $stmt = $conn->prepare("SELECT * FROM users WHERE login = :login");
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Пользователь с таким логином уже существует'
                ]);
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (login, password) VALUES (:login, :password)");
                $stmt->bindParam(':login', $login);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->execute();

                echo json_encode([
                    'success' => true,
                    'message' => 'Регистрация успешна! Теперь вы можете войти в систему.',
                    'redirect' => 'login.html'
                ]);
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка подключения к базе данных'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Данные формы не получены'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный метод запроса'
    ]);
}
?>