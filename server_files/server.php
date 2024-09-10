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
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        return $queryParams['user_id'] ?? '';
    }

    public function onOpen(ConnectionInterface $conn) {
        $user_id = $this->getAuthDetails($conn);

        if (!empty($user_id)) {
            $this->clients->attach($conn);
            $this->clientId[$user_id] = $conn;
            echo "Connection opened for user: $user_id\n";
        } else {
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