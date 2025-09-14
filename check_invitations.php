<?php
session_start();
include 'db.php';

function logError($message) {
    file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}

$login = $_SESSION['login'];

if (!$login) {
    logError('Пользователь не аутентифицирован');
    echo json_encode(['success' => false, 'message' => 'Пользователь не аутентифицирован']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM Invitations WHERE invited_login = :login AND status = 'pending'");
$stmt->bindParam(':login', $login);
$stmt->execute();
$invitation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($invitation) {
    logError('Приглашение получено: ' . $invitation['inviter_login'] . ' -> ' . $login . ' для комнаты ' . $invitation['room_id']);
    echo json_encode(['inviter_login' => $invitation['inviter_login'], 'room_id' => $invitation['room_id']]);
} else {
    echo json_encode(['inviter_login' => null, 'room_id' => null]);
}
?>