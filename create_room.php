<?php
session_start();
include 'db.php';

function logError($message) {
    file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_SESSION['login'];

    if (!$login) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Создание комнаты с установкой времени начала раунда
        $stmt = $conn->prepare("INSERT INTO Games (creator_login, time_start_round) VALUES (:login, NOW())");
        $stmt->bindParam(':login', $login);
        if ($stmt->execute()) {
            $room_id = $conn->lastInsertId();

            // Добавление игрока в комнату
            $stmt = $conn->prepare("INSERT INTO Players (login, id_game) VALUES (:login, :id_game)");
            $stmt->bindParam(':login', $login);
            $stmt->bindParam(':id_game', $room_id);
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'room_id' => $room_id]);
            } else {
                $conn->rollBack();
                logError('Ошибка при добавлении игрока в комнату: ' . $stmt->errorInfo()[2]);
                echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении игрока в комнату']);
            }
        } else {
            $conn->rollBack();
            logError('Ошибка при создании комнаты: ' . $stmt->errorInfo()[2]);
            echo json_encode(['success' => false, 'message' => 'Ошибка при создании комнаты']);
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Ошибка базы данных: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
