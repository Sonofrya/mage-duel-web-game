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
    $stmt = $conn->prepare("UPDATE Games SET time_start_round = NOW() WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Время начала раунда обновлено']);
    } else {
        throw new Exception('Ошибка обновления времени начала раунда');
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
