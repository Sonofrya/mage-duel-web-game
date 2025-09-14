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
    // Удаляем игру из базы данных
    $stmt = $conn->prepare("DELETE FROM Games WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    // Удаляем игроков из базы данных
    $stmt = $conn->prepare("DELETE FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Игра очищена']);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
