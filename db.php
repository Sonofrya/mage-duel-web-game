<?php
// Получаем настройки из переменных окружения или используем значения по умолчанию
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: "localhost";
$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: "mydatabase";
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: "admin";
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: "1";
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: "5432";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>