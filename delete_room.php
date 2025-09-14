<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

function logError($message) {
    file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;
    $login = $_SESSION['login'] ?? null;

    if (!$room_id) {
        echo json_encode(['success' => false, 'message' => 'ID комнаты не указан']);
        exit;
    }

    if (!$login) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    try {
        // Проверяем, является ли пользователь создателем комнаты
        $stmt = $conn->prepare("SELECT creator_login FROM Games WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            echo json_encode(['success' => false, 'message' => 'Комната не найдена']);
            exit;
        }

        if ($game['creator_login'] !== $login) {
            echo json_encode(['success' => false, 'message' => 'Только создатель комнаты может её удалить']);
            exit;
        }

        $conn->beginTransaction();

        // Удаляем все связанные данные в правильном порядке (из-за внешних ключей)
        
        // 1. Удаляем карты в руке игроков
        $stmt = $conn->prepare("
            DELETE FROM Cards_in_hand 
            WHERE id_player IN (
                SELECT id_player FROM Players WHERE id_game = :room_id
            )
        ");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        // 2. Удаляем выбранные карты
        $stmt = $conn->prepare("
            DELETE FROM Chosen_cards 
            WHERE id_player IN (
                SELECT id_player FROM Players WHERE id_game = :room_id
            )
        ");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        // 3. Удаляем заклинания
        $stmt = $conn->prepare("
            DELETE FROM Spells 
            WHERE id_player IN (
                SELECT id_player FROM Players WHERE id_game = :room_id
            )
        ");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        // 4. Удаляем приглашения
        $stmt = $conn->prepare("DELETE FROM Invitations WHERE room_id = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        // 5. Удаляем активность комнаты
        $stmt = $conn->prepare("DELETE FROM room_activity WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        // 6. Удаляем игроков
        $stmt = $conn->prepare("DELETE FROM Players WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        // 7. Удаляем саму комнату
        $stmt = $conn->prepare("DELETE FROM Games WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit();

        logError("Room {$room_id} deleted by creator {$login}");

        echo json_encode([
            'success' => true, 
            'message' => 'Комната успешно удалена',
            'room_id' => $room_id
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Ошибка базы данных при удалении комнаты: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollBack();
        logError('Ошибка при удалении комнаты: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
