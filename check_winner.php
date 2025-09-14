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
    
    $stmt = $conn->prepare("SELECT id_player, tokens FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $winner = null;
    foreach ($players as $player) {
        if ($player['tokens'] >= 2) {
            $winner = $player['id_player'];
            break;
        }
    }

    if ($winner) {
        echo json_encode(['success' => true, 'winner' => $winner]);
    } else {
        echo json_encode(['success' => true, 'winner' => null]);
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
