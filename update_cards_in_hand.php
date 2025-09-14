<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

function updateCardsInHand($conn, $player_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS card_count FROM Cards_in_hand WHERE id_player = :id_player");
    $stmt->bindParam(':id_player', $player_id, PDO::PARAM_INT);
    $stmt->execute();
    $card_count = $stmt->fetch(PDO::FETCH_ASSOC)['card_count'];
    if ($card_count < 5) {
        $needed_cards = 5 - $card_count;
        $stmt = $conn->prepare("SELECT id_card FROM Cards WHERE id_card != 1 ORDER BY RANDOM() LIMIT :needed_cards");
        $stmt->bindParam(':needed_cards', $needed_cards, PDO::PARAM_INT);
        $stmt->execute();
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cards as $card) {
            $stmt = $conn->prepare("INSERT INTO Cards_in_hand (id_player, id_card) VALUES (:id_player, :id_card)");
            $stmt->bindParam(':id_player', $player_id, PDO::PARAM_INT);
            $stmt->bindParam(':id_card', $card['id_card'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
$login = $_SESSION['login'];

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Неверный идентификатор комнаты']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id_player FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($players as $player) {
        updateCardsInHand($conn, $player['id_player']);
    }

    $stmt = $conn->prepare("SELECT id_player FROM Players WHERE login = :login AND id_game = :id_game");
    $stmt->bindParam(':login', $login);
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $currentPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentPlayer) {
        throw new Exception('Игрок не найден');
    }

    
    $stmt = $conn->prepare("SELECT c.id_card, c.png, c.descr, c.cardtype FROM Cards c JOIN Cards_in_hand ch ON c.id_card = ch.id_card WHERE ch.id_player = :id_player");
    $stmt->bindParam(':id_player', $currentPlayer['id_player'], PDO::PARAM_INT);
    $stmt->execute();
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'cards' => $cards]);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
