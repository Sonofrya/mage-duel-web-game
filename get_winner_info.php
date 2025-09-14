<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$winner_id = isset($_GET['winner_id']) ? intval($_GET['winner_id']) : null;

if (!$winner_id) {
    echo json_encode(['success' => false, 'message' => 'ID победителя не указан']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT login FROM Players WHERE id_player = :winner_id");
    $stmt->bindParam(':winner_id', $winner_id, PDO::PARAM_INT);
    $stmt->execute();
    $winner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($winner) {
        echo json_encode(['success' => true, 'winner_name' => $winner['login']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Победитель не найден']);
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
