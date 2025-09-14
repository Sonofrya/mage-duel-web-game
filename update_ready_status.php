<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];
    $login = $_SESSION['login'];
    $ready_status = $_POST['ready_status'];

    if (!$login) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE Players SET ready = :ready_status WHERE login = :login AND id_game = :id_game");
        $stmt->bindParam(':ready_status', $ready_status);
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':id_game', $room_id);
        $stmt->execute();

        $stmt = $conn->prepare("SELECT COUNT(*) AS ready_count FROM Players WHERE id_game = :id_game AND ready = TRUE");
        $stmt->bindParam(':id_game', $room_id);
        $stmt->execute();
        $ready_count = $stmt->fetch(PDO::FETCH_ASSOC)['ready_count'];

        $stmt = $conn->prepare("SELECT COUNT(*) AS player_count FROM Players WHERE id_game = :id_game");
        $stmt->bindParam(':id_game', $room_id);
        $stmt->execute();
        $player_count = $stmt->fetch(PDO::FETCH_ASSOC)['player_count'];

        if ($ready_count >= 2 && $ready_count == $player_count) {
            $stmt = $conn->prepare("UPDATE Games SET status = 1, time_start_round = NOW() WHERE id_game = :id_game");
            $stmt->bindParam(':id_game', $room_id);
            $stmt->execute();

            echo json_encode(['success' => true, 'redirect' => 'game.html?room_id=' . $room_id]);
        } else {
            echo json_encode(['success' => true]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
