<?php
session_start();
include 'db.php';

function logError($message) {
    file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}


function getDatabaseTime($conn) {
    $stmt = $conn->query("SELECT NOW() AS db_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return new DateTime($result['db_time'], new DateTimeZone('Europe/Moscow'));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];

    if (!$room_id) {
        echo json_encode(['success' => false, 'message' => 'ID комнаты не указан']);
        exit;
    }

    try {
        
        $current_time = getDatabaseTime($conn);

        
        logError("Current time from database: " . $current_time->format('Y-m-d H:i:s'));

        
        $current_time_formatted = $current_time->format('Y-m-d H:i:s');
        $current_time->f = 0;     
        
        $stmt = $conn->prepare("UPDATE Games SET time_start_round = :current_time WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':current_time', $current_time_formatted);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            logError('Ошибка при обновлении времени начала раунда: ' . $stmt->errorInfo()[2]);
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении времени начала раунда']);
        }
    } catch (PDOException $e) {
        logError('Ошибка базы данных: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
