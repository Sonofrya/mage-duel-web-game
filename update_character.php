<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['character'])) {
        $character = $_POST['character'];
        $login = $_SESSION['login'];

        $stmt = $conn->prepare("UPDATE users SET character = :character WHERE login = :login");
        $stmt->bindParam(':character', $character);
        $stmt->bindParam(':login', $login);
        if ($stmt->execute()) {
            echo "Персонаж успешно обновлен!";
        } else {
            echo "Ошибка: " . $stmt->errorInfo()[2];
        }
    } else {
        echo "Ошибка: данные формы не получены";
    }
} else {
    echo "Ошибка: неверный метод запроса";
}