<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Неверный идентификатор комнаты']);
    exit;
}

try {
   
    $stmt = $conn->prepare("DELETE FROM Spells WHERE id_player IN (SELECT id_player FROM Players WHERE id_game = :id_game)");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    
    $stmt = $conn->prepare("DELETE FROM Chosen_cards WHERE id_player IN (SELECT id_player FROM Players WHERE id_game = :id_game)");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    
    $stmt = $conn->prepare("UPDATE Players SET cards_chosen = FALSE WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка обновления состояния cards_chosen');
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
