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
    $stmt = $conn->prepare("SELECT c.id_card, c.cardtype, c.lead, c.heal, c.damage, c.descr, c.png, s.card_position, s.id_player FROM Spells s JOIN Cards c ON s.id_card = c.id_card WHERE s.id_player IN (SELECT id_player FROM Players WHERE id_game = :id_game) ORDER BY s.card_position");
    $stmt->bindParam(':id_game', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $chosen_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Chosen cards: " . print_r($chosen_cards, true));

    if (empty($chosen_cards)) {
        error_log("No chosen cards found for room_id $room_id");
    }

    echo json_encode(['success' => true, 'chosen_cards' => $chosen_cards, 'my_login' => $_SESSION['login']]);
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
