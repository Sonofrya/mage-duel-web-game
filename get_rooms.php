<?php
include 'db.php';

$stmt = $conn->prepare("SELECT g.id_game AS room_id, p.login AS creator, COUNT(p2.id_player) AS player_count
                       FROM Games g
                       JOIN Players p ON g.id_game = p.id_game
                       LEFT JOIN Players p2 ON g.id_game = p2.id_game
                       WHERE p.login = (SELECT login FROM Players WHERE id_game = g.id_game LIMIT 1)
                       GROUP BY g.id_game, p.login
                       HAVING COUNT(p2.id_player) > 0");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['rooms' => $rooms]);
?>