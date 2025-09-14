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

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $room_id = $_GET['room_id'];


    logError("Received room_id: " . $room_id);

    if (!$room_id || !is_numeric($room_id)) {
        echo json_encode(['success' => false, 'message' => 'ID комнаты не указан или не является числом']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT time_start_round FROM Games WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $time_start_round = new DateTime($result['time_start_round'], new DateTimeZone('Europe/Moscow'));
                $current_time = getDatabaseTime($conn);

                $interval = $time_start_round->diff($current_time);
                $interval->f = 0;

                if ($interval->invert) {
                    
                    $time_left = 120;
                } else {
                    $seconds_elapsed = ($interval->days * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
                    $time_left = 120 - $seconds_elapsed;
                    if ($time_left < 0) {
                        $time_left = 0;
                    }
                }
                echo json_encode(['success' => true, 'time_left' => $time_left]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Комната не найдена']);
            }
        } else {
            logError('Ошибка при получении времени начала раунда: ' . $stmt->errorInfo()[2]);
            echo json_encode(['success' => false, 'message' => 'Ошибка при получении времени начала раунда']);
        }
    } catch (PDOException $e) {
        logError('Ошибка базы данных: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
