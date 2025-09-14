<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Неверный идентификатор комнаты']);
    exit;
}

try {
    // Обновляем время последней активности комнаты
    $stmt = $conn->prepare("
        INSERT INTO room_activity (id_game, last_activity, created_at) 
        VALUES (:room_id, NOW(), NOW())
        ON CONFLICT (id_game) DO UPDATE SET last_activity = NOW()
    ");
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Активность комнаты обновлена']);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
