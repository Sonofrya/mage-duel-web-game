<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные запроса']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT phase FROM Games WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        throw new Exception('Игра не найдена');
    }

    $phase = $game['phase'];

    echo json_encode(['success' => true, 'phase' => $phase]);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
