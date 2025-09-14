<?php
session_start();
include 'db.php';

function logError($message) {
    file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];
    $login = $_SESSION['login'];

    if (!$login) {
        logError('Пользователь не аутентифицирован');
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    try {
        // Удаляем приглашение из базы данных
        $stmt = $conn->prepare("DELETE FROM Invitations WHERE invited_login = :login AND room_id = :room_id");
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->execute();

        logError('Приглашение удалено: ' . $login . ' для комнаты ' . $room_id);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        logError('Ошибка при удалении приглашения: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    logError('Неверный метод запроса');
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
