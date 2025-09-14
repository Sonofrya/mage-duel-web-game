<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$id_player = isset($_POST['id_player']) ? intval($_POST['id_player']) : null;
$lives = isset($_POST['lives']) ? intval($_POST['lives']) : null;

error_log("update_player_lives.php called with id_player=$id_player, lives=$lives");

if (!$id_player || !is_numeric($lives)) {
    error_log("Invalid request data: id_player=$id_player, lives=$lives");
    echo json_encode(['success' => false, 'message' => 'Неверные данные запроса']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE Players SET lives = :lives WHERE id_player = :id_player");
    $stmt->bindParam(':lives', $lives, PDO::PARAM_INT);
    $stmt->bindParam(':id_player', $id_player, PDO::PARAM_INT);
    if ($stmt->execute()) {
        error_log("Player lives updated successfully: id_player=$id_player, lives=$lives");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка обновления жизней игрока');
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
