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
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    try {
        
        $conn->beginTransaction();

        
        $stmt = $conn->prepare("DELETE FROM Players WHERE login = :login AND id_game = :id_game");
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':id_game', $room_id);
        if ($stmt->execute()) {
           
            $stmt = $conn->prepare("SELECT COUNT(*) AS player_count FROM Players WHERE id_game = :id_game");
            $stmt->bindParam(':id_game', $room_id);
            $stmt->execute();
            $player_count = $stmt->fetch(PDO::FETCH_ASSOC)['player_count'];

            if ($player_count == 0) {
                
                $stmt = $conn->prepare("DELETE FROM Games WHERE id_game = :id_game");
                $stmt->bindParam(':id_game', $room_id);
                $stmt->execute();
            }

            
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            
            $conn->rollBack();
            logError('Ошибка при выходе из комнаты: ' . $stmt->errorInfo()[2]);
            echo json_encode(['success' => false, 'message' => 'Ошибка при выходе из комнаты']);
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