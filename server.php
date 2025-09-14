<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;

class GameServer implements MessageComponentInterface {
    protected $clients;
    protected $playersReady;
    protected $roomConnections;
    protected $playerRooms;
    protected $loop;

    public function __construct($loop = null) {
        $this->clients = new \SplObjectStorage;
        $this->playersReady = [];
        $this->roomConnections = [];
        $this->playerRooms = [];
        $this->loop = $loop;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
    
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            echo "Invalid message format from connection {$from->resourceId}\n";
            return;
        }
        
        switch ($data['type']) {
            case 'joinRoom':
                $this->handleJoinRoom($from, $data);
                break;
                
            case 'leaveRoom':
                $this->handleLeaveRoom($from, $data);
                break;
                
            case 'startPhase2':
                $this->handleStartPhase2($from, $data);
                break;
                
            case 'timer':
                $this->handleTimer($from, $data);
                break;
                
            case 'checkPlayersReady':
                $this->handleCheckPlayersReady($from, $data);
                break;
                
            case 'checkPhase2':
                $this->handleCheckPhase2($from, $data);
                break;
                
            case 'playerReady':
                $this->handlePlayerReady($from, $data);
                break;
                
            case 'gameAction':
                $this->handleGameAction($from, $data);
                break;
                
            case 'gameEnd':
                $this->handleGameEnd($from, $data);
                break;
                
            case 'gameUpdate':
                $this->handleGameUpdate($from, $data);
                break;
                
            case 'playerAction':
                $this->handlePlayerAction($from, $data);
                break;
                
            default:
                $this->broadcastToRoom($from, $msg, $data['room_id'] ?? null);
                break;
        }
    }
    
    private function handleJoinRoom(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        $login = $data['login'] ?? null;
        
        if ($room_id && $login) {
            $this->playerRooms[$from->resourceId] = $room_id;
            
            if (!isset($this->roomConnections[$room_id])) {
                $this->roomConnections[$room_id] = [];
            }
            $this->roomConnections[$room_id][] = $from;
            
            echo "Player {$login} joined room {$room_id}\n";
            
            // Уведомляем других игроков в комнате
            $this->broadcastToRoom($from, json_encode([
                'type' => 'playerJoined',
                'room_id' => $room_id,
                'login' => $login
            ]), $room_id);
        }
    }
    
    private function handleLeaveRoom(ConnectionInterface $from, $data) {
        $room_id = $this->playerRooms[$from->resourceId] ?? null;
        
        if ($room_id) {
            // Удаляем игрока из комнаты
            if (isset($this->roomConnections[$room_id])) {
                $this->roomConnections[$room_id] = array_filter(
                    $this->roomConnections[$room_id],
                    function($conn) use ($from) {
                        return $conn !== $from;
                    }
                );
                
                if (empty($this->roomConnections[$room_id])) {
                    unset($this->roomConnections[$room_id]);
                }
            }
            
            unset($this->playerRooms[$from->resourceId]);
            
            echo "Player left room {$room_id}\n";
            
            // Уведомляем других игроков
            $this->broadcastToRoom($from, json_encode([
                'type' => 'playerLeft',
                'room_id' => $room_id
            ]), $room_id);
        }
    }
    
    private function handleStartPhase2(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            $this->broadcastToRoom($from, json_encode([
                'type' => 'startPhase2',
                'room_id' => $room_id
            ]), $room_id);
            echo "Sent startPhase2 message to room {$room_id}\n";
        }
    }
    
    private function handleTimer(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            $this->broadcastToRoom($from, json_encode([
                'type' => 'timer',
                'timeLeft' => $data['timeLeft'] ?? 0,
                'room_id' => $room_id
            ]), $room_id);
        }
    }
    
    private function handleCheckPlayersReady(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            if (!isset($this->playersReady[$room_id])) {
                $this->playersReady[$room_id] = 0;
            }
            $this->playersReady[$room_id]++;
            
            $allPlayersReady = $this->playersReady[$room_id] >= 2;
            
            $this->broadcastToRoom($from, json_encode([
                'type' => 'checkPlayersReady',
                'allPlayersReady' => $allPlayersReady,
                'room_id' => $room_id
            ]), $room_id);
        }
    }
    
    private function handleCheckPhase2(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            $this->broadcastToRoom($from, json_encode([
                'type' => 'checkPhase2',
                'room_id' => $room_id
            ]), $room_id);
            echo "Sent checkPhase2 message to room {$room_id}\n";
        }
    }
    
    private function handlePlayerReady(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        $login = $data['login'] ?? null;
        
        if ($room_id && $login) {
            $this->broadcastToRoom($from, json_encode([
                'type' => 'playerReady',
                'room_id' => $room_id,
                'login' => $login
            ]), $room_id);
            echo "Sent playerReady message to room {$room_id}\n";
        }
    }
    
    private function handleGameAction(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            $this->broadcastToRoom($from, json_encode($data), $room_id);
        }
    }
    
    private function handleGameEnd(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            // Отправляем сообщение о завершении игры всем игрокам в комнате
            $this->broadcastToRoom($from, json_encode([
                'type' => 'gameEnd',
                'room_id' => $room_id,
                'winner' => $data['winner'] ?? null,
                'winnerName' => $data['winnerName'] ?? 'Неизвестный игрок'
            ]), $room_id);
            
            echo "Game ended in room {$room_id}, winner: " . ($data['winnerName'] ?? 'Unknown') . "\n";
            
            // Через 5 секунд закрываем все соединения в этой комнате
            $this->scheduleRoomCleanup($room_id);
        }
    }
    
    private function handleGameUpdate(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            // Отправляем обновление игры всем игрокам в комнате
            $this->broadcastToRoom($from, json_encode([
                'type' => 'gameUpdate',
                'room_id' => $room_id,
                'updateType' => $data['updateType'] ?? 'general',
                'data' => $data['data'] ?? []
            ]), $room_id);
            
            echo "Game update sent to room {$room_id}\n";
        }
    }
    
    private function handlePlayerAction(ConnectionInterface $from, $data) {
        $room_id = $data['room_id'] ?? null;
        if ($room_id) {
            // Отправляем информацию о действии игрока всем в комнате
            $this->broadcastToRoom($from, json_encode([
                'type' => 'playerAction',
                'room_id' => $room_id,
                'player' => $data['player'] ?? null,
                'action' => $data['action'] ?? null,
                'data' => $data['data'] ?? []
            ]), $room_id);
            
            echo "Player action broadcasted to room {$room_id}\n";
        }
    }
    
    private function scheduleRoomCleanup($room_id) {
        if ($this->loop) {
            // Через 5 секунд закрываем все соединения в комнате
            $this->loop->addTimer(5.0, function() use ($room_id) {
                if (isset($this->roomConnections[$room_id])) {
                    foreach ($this->roomConnections[$room_id] as $client) {
                        $client->send(json_encode([
                            'type' => 'roomCleanup',
                            'message' => 'Комната удалена'
                        ]));
                        $client->close();
                    }
                    unset($this->roomConnections[$room_id]);
                    echo "Room {$room_id} cleaned up\n";
                }
            });
        }
    }
    
    private function broadcastToRoom(ConnectionInterface $from, $msg, $room_id = null) {
        if ($room_id && isset($this->roomConnections[$room_id])) {
            foreach ($this->roomConnections[$room_id] as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                }
            }
        } else {
            // Если комната не указана, отправляем всем
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                }
            }
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $room_id = $this->playerRooms[$conn->resourceId] ?? null;
        
        if ($room_id) {
            // Уведомляем других игроков о выходе
            $this->broadcastToRoom($conn, json_encode([
                'type' => 'playerDisconnected',
                'room_id' => $room_id
            ]), $room_id);
            
            // Очищаем данные о комнате
            if (isset($this->roomConnections[$room_id])) {
                $this->roomConnections[$room_id] = array_filter(
                    $this->roomConnections[$room_id],
                    function($client) use ($conn) {
                        return $client !== $conn;
                    }
                );
                
                if (empty($this->roomConnections[$room_id])) {
                    unset($this->roomConnections[$room_id]);
                }
            }
            
            unset($this->playerRooms[$conn->resourceId]);
        }
        
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        
        // Уведомляем о ошибке в комнате
        $room_id = $this->playerRooms[$conn->resourceId] ?? null;
        if ($room_id) {
            $this->broadcastToRoom($conn, json_encode([
                'type' => 'connectionError',
                'room_id' => $room_id,
                'error' => $e->getMessage()
            ]), $room_id);
        }
        
        $conn->close();
    }
}

// Конфигурация сервера
$config = [
    'host' => '0.0.0.0',
    'port' => 8080,
    'max_connections' => 1000
];

echo "Starting WebSocket server on {$config['host']}:{$config['port']}\n";
echo "Max connections: {$config['max_connections']}\n";

$loop = Factory::create();
$webSock = new Server("{$config['host']}:{$config['port']}", $loop);

$gameServer = new GameServer($loop);

$webServer = new IoServer(
    new HttpServer(
        new WsServer($gameServer)
    ),
    $webSock,
    $loop
);

// Обработка сигналов для корректного завершения (только для Unix-систем)
if (PHP_OS_FAMILY !== 'Windows') {
    if (defined('SIGTERM')) {
        $loop->addSignal(SIGTERM, function () use ($webServer, $loop) {
            echo "Received SIGTERM, shutting down gracefully...\n";
            $webServer->getLoop()->stop();
        });
    }
    
    if (defined('SIGINT')) {
        $loop->addSignal(SIGINT, function () use ($webServer, $loop) {
            echo "Received SIGINT, shutting down gracefully...\n";
            $webServer->getLoop()->stop();
        });
    }
} else {
    echo "Running on Windows - signal handling disabled\n";
    echo "Press Ctrl+C to stop the server\n";
    
    // Альтернативная обработка для Windows
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGINT, function() use ($webServer, $loop) {
            echo "\nReceived interrupt signal, shutting down gracefully...\n";
            $webServer->getLoop()->stop();
        });
    }
}

echo "WebSocket server is running...\n";
echo "Server will run until manually stopped\n";

try {
    $loop->run();
} catch (Exception $e) {
    echo "Server error: " . $e->getMessage() . "\n";
} finally {
    echo "WebSocket server stopped.\n";
}
