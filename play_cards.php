<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;
$selected_cards = isset($_POST['selected_cards']) ? json_decode($_POST['selected_cards'], true) : null;
$login = $_SESSION['login'];

if (!$room_id || !$selected_cards) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные запроса']);
    exit;
}

try {
    error_log("room_id: " . $room_id);
    error_log("selected_cards: " . print_r($selected_cards, true));
    error_log("login: " . $login);

    // Обновляем активность комнаты
    $stmt = $conn->prepare("
        INSERT INTO room_activity (id_game, last_activity, created_at) 
        VALUES (:room_id, NOW(), NOW())
        ON CONFLICT (id_game) DO UPDATE SET last_activity = NOW()
    ");
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT * FROM Players WHERE login = :login AND id_game = :id_game");
    $stmt->bindParam(':login', $login);
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $currentPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentPlayer) {
        throw new Exception('Игрок не найден');
    }

    $stmt = $conn->prepare("SELECT * FROM Players WHERE id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($selected_cards as $cardType => $cardId) {
        if ($cardId) {
            error_log("Deleting card: id_player=" . $currentPlayer['id_player'] . ", id_card=" . $cardId);
            $stmt = $conn->prepare("DELETE FROM Cards_in_hand WHERE id_player = :id_player AND id_card = :id_card");
            $stmt->bindParam(':id_player', $currentPlayer['id_player'], PDO::PARAM_INT);
            $stmt->bindParam(':id_card', $cardId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    foreach ($selected_cards as $cardType => $cardId) {
        if ($cardId) {
            error_log("Inserting into Chosen_cards: id_player=" . $currentPlayer['id_player'] . ", id_card=" . $cardId . ", card_position=" . $cardType);
            $stmt = $conn->prepare("INSERT INTO Chosen_cards (id_player, id_card, card_position) VALUES (:id_player, :id_card, :card_position)");
            $stmt->bindParam(':id_player', $currentPlayer['id_player'], PDO::PARAM_INT);
            $stmt->bindParam(':id_card', $cardId, PDO::PARAM_INT);
            $stmt->bindParam(':card_position', $cardType, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    foreach ($selected_cards as $cardType => $cardId) {
        if ($cardId) {
            error_log("Inserting into Spells: id_player=" . $currentPlayer['id_player'] . ", id_card=" . $cardId . ", card_position=" . $cardType);
            $stmt = $conn->prepare("INSERT INTO Spells (id_player, id_card, card_position) VALUES (:id_player, :id_card, :card_position)");
            $stmt->bindParam(':id_player', $currentPlayer['id_player'], PDO::PARAM_INT);
            $stmt->bindParam(':id_card', $cardId, PDO::PARAM_INT);
            $stmt->bindParam(':card_position', $cardType, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    error_log("Updating Players: id_player=" . $currentPlayer['id_player']);
    $stmt = $conn->prepare("UPDATE Players SET cards_chosen = TRUE WHERE id_player = :id_player");
    $stmt->bindParam(':id_player', $currentPlayer['id_player'], PDO::PARAM_INT);
    $stmt->execute();

    $allPlayersReady = true;
    foreach ($players as $player) {
        if (!$player['cards_chosen']) {
            $allPlayersReady = false;
            break;
        }
    }

    echo json_encode(['success' => true, 'players' => $players, 'my_login' => $login, 'all_players_ready' => $allPlayersReady]);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
