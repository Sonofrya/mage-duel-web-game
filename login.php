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

            $stmt = $conn->prepare("SELECT * FROM users WHERE login = :login");
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result) {
                if (password_verify($password, $result['password'])) {
                    $_SESSION['login'] = $login;
                    $_SESSION['logged_in'] = true;

                    setcookie('login', $login, time() + (86400 * 30), "/");

                    $token = bin2hex(random_bytes(16));
                    setcookie('auth_token', $token, time() + (86400 * 30), "/");

                    $stmt = $conn->prepare("UPDATE users SET auth_token = :token WHERE login = :login");
                    $stmt->bindParam(':token', $token);
                    $stmt->bindParam(':login', $login);
                    $stmt->execute();

                    echo json_encode([
                        'success' => true,
                        'message' => 'Вход успешен!',
                        'redirect' => 'room.html'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Неправильное имя пользователя или пароль'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Неправильное имя пользователя или пароль'
                ]);
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
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
