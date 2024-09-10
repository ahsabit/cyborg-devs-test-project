<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $clientId;
    protected $data_con;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->clientId = [];
        $this->data_con = mysqli_connect("localhost", "cyborg", "testpass", "cyborg");

        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            exit();
        }
    }

    private function getAuthDetails(ConnectionInterface $conn) {
        $url = $conn->httpRequest->getUri();
        // Extract query parameters from URL
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        
        // Safely retrieve 'user_id' and 'username' from query parameters
        $userId = $queryParams['user_id'] ?? null;
        $username = $queryParams['username'] ?? null;
        
        return [$userId, $username];
    }
    
    public function onOpen(ConnectionInterface $conn) {
        // Get authentication details for the user
        [$userId, $username] = $this->getAuthDetails($conn);
    
        if (!empty($userId)) {
            // Attach the connection if the userId is valid
            $this->clients->attach($conn);
            $this->clientId[$userId] = $conn;
    
            try {
                // Notify other clients about the new user
                foreach ($this->clientId as $client => $connection) {
                    if ($connection !== $conn) {
                        $connection->send(json_encode([
                            'new_user' => $username,
                            'user_id' => $userId
                        ]));
                    }
                }
            } catch (\Throwable $th) {
                // Log error if message sending fails
                echo "Error sending message: " . $th->getMessage() . "\n";
            }
    
            echo "Connection opened for user: " . $userId . "\n";
        } else {
            // Handle invalid user scenario
            echo "Invalid user, closing connection.\n";
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msgData = json_decode($msg, true);

        if (!empty($msgData['msg']) && !empty($msgData['to']) && !empty($msgData['from'])) {
            $main = trim($msgData['msg']);
            $stmt = $this->data_con->prepare("INSERT INTO msg (msg, recipient, sender) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $main, $msgData['to'], $msgData['from']);

            if ($stmt->execute()) {
                echo "Data inserted successfully.\n";
            } else {
                echo "Query failed: " . $this->data_con->error . "\n";
            }

            $stmt->close();

            if (isset($this->clientId[$msgData['to']]) && $this->clientId[$msgData['to']] !== $from) {
                try {
                    $this->clientId[$msgData['to']]->send(json_encode($msgData));
                } catch (\Throwable $th) {
                    echo "Error sending message: " . $th->getMessage() . "\n";
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $user_id = null;

        foreach ($this->clientId as $client => $value) {
            if ($value->resourceId === $conn->resourceId) {
                $user_id = $client;
                break;
            }
        }

        if ($user_id !== null) {
            unset($this->clientId[$user_id]);
            echo "Connection closed for user: $user_id\n";
        }

        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();