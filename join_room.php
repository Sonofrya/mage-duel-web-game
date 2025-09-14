<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];
    $login = $_SESSION['login'];

    if (!$login) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Проверяем существование комнаты
        $stmt = $conn->prepare("SELECT id_game FROM Games WHERE id_game = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();
        $room_exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room_exists) {
            throw new Exception('Комната не найдена');
        }

        // Добавляем игрока в комнату (с обработкой дублирования)
        $stmt = $conn->prepare("
            INSERT INTO Players (login, id_game) 
            VALUES (:login, :id_game)
            ON CONFLICT (id_game, login) DO NOTHING
        ");
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':id_game', $room_id);
        $stmt->execute();

        
        $stmt = $conn->prepare("UPDATE Invitations SET status = 'accepted' WHERE invited_login = :login AND room_id = :room_id");
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>