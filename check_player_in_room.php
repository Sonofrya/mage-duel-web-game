<?php
session_start();
include 'db.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];
    $login = $_SESSION['login'];

    $stmt = $conn->prepare("SELECT * FROM Players WHERE id_game = :id_game AND login = :login");
    $stmt->bindParam(':id_game', $room_id);
    $stmt->bindParam(':login', $login);
    $stmt->execute();
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) AS player_count FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id);
    $stmt->execute();
    $player_count = $stmt->fetch(PDO::FETCH_ASSOC)['player_count'];

    echo json_encode(['is_player_in_room' => !empty($player), 'player_count' => $player_count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
}
?>