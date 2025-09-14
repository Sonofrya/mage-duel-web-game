<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

try {
    // Получаем информацию о всех активных комнатах
    $stmt = $conn->prepare("
        SELECT 
            g.id_game,
            g.created_at,
            ra.last_activity,
            ra.created_at as activity_created,
            COUNT(p.id_player) as player_count
        FROM Games g
        LEFT JOIN room_activity ra ON g.id_game = ra.id_game
        LEFT JOIN Players p ON g.id_game = p.id_game
        GROUP BY g.id_game, g.created_at, ra.last_activity, ra.created_at
        ORDER BY ra.last_activity DESC
    ");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Добавляем информацию о статусе активности
    foreach ($rooms as &$room) {
        if ($room['last_activity']) {
            $lastActivity = new DateTime($room['last_activity']);
            $now = new DateTime();
            $diffMinutes = $now->diff($lastActivity)->i + ($now->diff($lastActivity)->h * 60) + ($now->diff($lastActivity)->d * 24 * 60);
            
            $room['minutes_inactive'] = $diffMinutes;
            $room['is_active'] = $diffMinutes < 2;
        } else {
            $room['minutes_inactive'] = null;
            $room['is_active'] = false;
        }
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'total_rooms' => count($rooms),
        'active_rooms' => count(array_filter($rooms, function($room) { return $room['is_active']; }))
    ]);

} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
