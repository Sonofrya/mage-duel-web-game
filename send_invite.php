<?php
session_start();
include 'db.php';

function logError($message) {
    file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];
    $invited_login = $_POST['invited_login'];
    $inviter_login = $_SESSION['login'];

    if (!$inviter_login) {
        logError('Пользователь не аутентифицирован');
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    
    $stmt = $conn->prepare("SELECT * FROM Users WHERE login = :login");
    $stmt->bindParam(':login', $invited_login);
    $stmt->execute();
    $invited_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invited_user) {
        logError('Пользователь не найден: ' . $invited_login);
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }

    
    $stmt = $conn->prepare("INSERT INTO Invitations (room_id, inviter_login, invited_login) VALUES (:room_id, :inviter_login, :invited_login)");
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':inviter_login', $inviter_login);
    $stmt->bindParam(':invited_login', $invited_login);
    $stmt->execute();

    logError('Приглашение отправлено: ' . $inviter_login . ' -> ' . $invited_login . ' для комнаты ' . $room_id);
    echo json_encode(['success' => true]);
} else {
    logError('Неверный метод запроса');
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>