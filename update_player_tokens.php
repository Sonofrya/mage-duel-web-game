<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$id_player = isset($_POST['id_player']) ? intval($_POST['id_player']) : null;
$tokens = isset($_POST['tokens']) ? intval($_POST['tokens']) : null;

error_log("update_player_tokens.php called with id_player=$id_player, tokens=$tokens");

if (!$id_player || !is_numeric($tokens)) {
    error_log("Invalid request data: id_player=$id_player, tokens=$tokens");
    echo json_encode(['success' => false, 'message' => 'Неверные данные запроса']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE Players SET tokens = :tokens WHERE id_player = :id_player");
    $stmt->bindParam(':tokens', $tokens, PDO::PARAM_INT);
    $stmt->bindParam(':id_player', $id_player, PDO::PARAM_INT);
    if ($stmt->execute()) {
        error_log("Player tokens updated successfully: id_player=$id_player, tokens=$tokens");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка обновления токенов игрока');
    }
} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
