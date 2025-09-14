<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;
$new_time = isset($_POST['new_time']) ? intval($_POST['new_time']) : null;

if (!$room_id || !$new_time) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные запроса']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE Games SET current_timee = :new_time WHERE id_game = :id_game");
    $stmt->bindParam(':new_time', $new_time, PDO::PARAM_INT);
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Время обновлено']);
    } else {
        throw new Exception('Ошибка обновления времени');
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
