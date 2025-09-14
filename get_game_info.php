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
    // Обновляем активность комнаты
    $stmt = $conn->prepare("
        INSERT INTO room_activity (id_game, last_activity, created_at) 
        VALUES (:room_id, NOW(), NOW())
        ON CONFLICT (id_game) DO UPDATE SET last_activity = NOW()
    ");
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT p.login, u.character, p.lives, p.tokens, p.id_player, p.cards_chosen, p.ready FROM Players p JOIN Users u ON p.login = u.login WHERE p.login = :login AND p.id_game = :id_game");
    $stmt->bindParam(':login', $_SESSION['login']);
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $currentPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentPlayer) {
        throw new Exception('Игрок не найден в указанной комнате');
    }

    $stmt = $conn->prepare("SELECT p.login, u.character, p.lives, p.tokens, p.id_player, p.cards_chosen, p.ready FROM Players p JOIN Users u ON p.login = u.login WHERE p.id_game = :id_game");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT c.id_card, c.png, c.descr, c.cardtype FROM Cards c JOIN Cards_in_hand ch ON c.id_card = ch.id_card WHERE ch.id_player = :id_player");
    $stmt->bindParam(':id_player', $currentPlayer['id_player'], PDO::PARAM_INT);
    $stmt->execute();
    $my_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allPlayersReady = true;
    foreach ($players as $player) {
        error_log("Checking player: " . $player['login'] . ", cards_chosen: " . ($player['cards_chosen'] ? 'true' : 'false'));
        if (!$player['cards_chosen']) {
            $allPlayersReady = false;
            break;
        }
    }
    error_log("All players ready: " . ($allPlayersReady ? 'true' : 'false'));

    $phase = 1;
    if ($allPlayersReady) {
        $phase = 2;
    }

    echo json_encode([
        'success' => true,
        'my_login' => $_SESSION['login'],
        'players' => $players,
        'my_cards' => $my_cards,
        'all_players_ready' => $allPlayersReady,
        'phase' => $phase
    ]);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
