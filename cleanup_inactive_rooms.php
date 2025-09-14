<?php
include 'db.php';

// Устанавливаем часовой пояс
date_default_timezone_set('Europe/Moscow');

// Логируем запуск скрипта
error_log("Starting cleanup of inactive rooms at " . date('Y-m-d H:i:s'));

try {
    // Находим комнаты, которые неактивны более 2 минут
    $stmt = $conn->prepare("
        SELECT ra.id_game, ra.last_activity, ra.created_at, g.game_name
        FROM room_activity ra
        JOIN Games g ON ra.id_game = g.id_game
        WHERE ra.last_activity < DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $stmt->execute();
    $inactive_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cleaned_count = 0;
    $errors = [];

    foreach ($inactive_rooms as $room) {
        try {
            $room_id = $room['id_game'];
            $last_activity = $room['last_activity'];
            $game_name = $room['game_name'];
            
            error_log("Cleaning inactive room: ID=$room_id, Name='$game_name', Last activity: $last_activity");

            // Удаляем игроков комнаты
            $stmt = $conn->prepare("DELETE FROM Players WHERE id_game = :room_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем карты в руке
            $stmt = $conn->prepare("DELETE FROM Cards_in_hand WHERE id_game = :room_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем выбранные карты
            $stmt = $conn->prepare("DELETE FROM Cards_chosen WHERE id_game = :room_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем заклинания
            $stmt = $conn->prepare("DELETE FROM Spells WHERE id_game = :room_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем активность комнаты
            $stmt = $conn->prepare("DELETE FROM room_activity WHERE id_game = :room_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем саму игру
            $stmt = $conn->prepare("DELETE FROM Games WHERE id_game = :room_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            $cleaned_count++;
            error_log("Successfully cleaned room ID: $room_id");

        } catch (Exception $e) {
            $error_msg = "Error cleaning room ID {$room['id_game']}: " . $e->getMessage();
            error_log($error_msg);
            $errors[] = $error_msg;
        }
    }

    // Логируем результаты
    $log_message = "Cleanup completed. Cleaned rooms: $cleaned_count";
    if (!empty($errors)) {
        $log_message .= ". Errors: " . count($errors);
        error_log("Cleanup errors: " . implode('; ', $errors));
    }
    
    error_log($log_message);
    
    // Если скрипт запущен через веб-интерфейс, возвращаем JSON
    if (isset($_SERVER['HTTP_HOST'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cleaned_rooms' => $cleaned_count,
            'errors' => $errors,
            'message' => $log_message
        ]);
    }

} catch (PDOException $e) {
    $error_msg = "Database error during cleanup: " . $e->getMessage();
    error_log($error_msg);
    
    if (isset($_SERVER['HTTP_HOST'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error_msg]);
    }
} catch (Exception $e) {
    $error_msg = "General error during cleanup: " . $e->getMessage();
    error_log($error_msg);
    
    if (isset($_SERVER['HTTP_HOST'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error_msg]);
    }
}
?>
