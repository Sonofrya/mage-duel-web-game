<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;
$card_id = isset($_POST['card_id']) ? intval($_POST['card_id']) : null;
$card_position = isset($_POST['card_position']) ? intval($_POST['card_position']) : null;
$player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : null;

if (!$room_id || !$card_id || !$card_position || !$player_id) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные запроса']);
    exit;
}

try {
    
    $stmt = $conn->prepare("SELECT cardtype, lead, heal, damage FROM Cards WHERE id_card = :card_id");
    $stmt->bindParam(':card_id', $card_id, PDO::PARAM_INT);
    $stmt->execute();
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        throw new Exception('Карта не найдена');
    }

  
    $stmt = $conn->prepare("SELECT id_player, lives, tokens FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $player_killed = false;
    $killer_id = null;

    foreach ($players as $player) {
        if ($player['id_player'] != $player_id) {
            
            $new_lives = max(0, $player['lives'] - $card['damage']);
            $stmt = $conn->prepare("UPDATE Players SET lives = :lives WHERE id_player = :id_player");
            $stmt->bindParam(':lives', $new_lives, PDO::PARAM_INT);
            $stmt->bindParam(':id_player', $player['id_player'], PDO::PARAM_INT);
            $stmt->execute();

            if ($new_lives == 0) {
                $player_killed = true;
                $killer_id = $player_id;
            }
        } else {
            
            $new_lives = $player['lives'] + $card['heal'];
            $stmt = $conn->prepare("UPDATE Players SET lives = :lives WHERE id_player = :id_player");
            $stmt->bindParam(':lives', $new_lives, PDO::PARAM_INT);
            $stmt->bindParam(':id_player', $player['id_player'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    if ($player_killed) {
        
        foreach ($players as $player) {
            $stmt = $conn->prepare("UPDATE Players SET lives = 20 WHERE id_player = :id_player");
            $stmt->bindParam(':id_player', $player['id_player'], PDO::PARAM_INT);
            $stmt->execute();
        }

        
        if ($killer_id !== null) {
            $stmt = $conn->prepare("UPDATE Players SET tokens = tokens + 1 WHERE id_player = :id_player");
            $stmt->bindParam(':id_player', $killer_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
