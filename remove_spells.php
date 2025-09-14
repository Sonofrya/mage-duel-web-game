<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$id_player = isset($_POST['id_player']) ? intval($_POST['id_player']) : null;

if (!$id_player) {
    error_log("Invalid player ID: " . json_encode($_POST));
    echo json_encode(['success' => false, 'message' => 'Неверный идентификатор игрока']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM Spells WHERE id_player = :id_player");
    $stmt->bindParam(':id_player', $id_player, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка удаления карт из таблицы Spells');
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
