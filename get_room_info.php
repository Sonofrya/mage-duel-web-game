<?php
include 'db.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    $stmt = $conn->prepare("SELECT * FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id);
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['room_id' => $room_id, 'player_count' => count($players), 'players' => $players]);
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
}
?>