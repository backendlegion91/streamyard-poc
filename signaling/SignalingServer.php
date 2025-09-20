<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

class SignalingServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms; // room_id => array of connections

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        echo "Signaling server started\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->room = null;
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) return;

        switch ($data['type'] ?? '') {
            case 'join':
                $room = $data['room'];
                $from->room = $room;
                if (!isset($this->rooms[$room])) $this->rooms[$room] = [];
                $this->rooms[$room][$from->resourceId] = $from;
                // notify others in room
                foreach ($this->rooms[$room] as $rid => $conn) {
                    if ($conn === $from) continue;
                    $conn->send(json_encode(['type'=>'peer-joined','id'=>$from->resourceId]));
                }
                break;
            case 'signal':
                // forward to target (resourceId) or broadcast
                $target = $data['target'] ?? null;
                $room = $from->room;
                if ($target && isset($this->rooms[$room][$target])) {
                    $this->rooms[$room][$target]->send(json_encode([
                        'type' => 'signal',
                        'from' => $from->resourceId,
                        'payload' => $data['payload']
                    ]));
                } else {
                    // broadcast
                    if (isset($this->rooms[$room])) {
                        foreach ($this->rooms[$room] as $rid => $conn) {
                            if ($conn === $from) continue;
                            $conn->send(json_encode([
                                'type' => 'signal',
                                'from' => $from->resourceId,
                                'payload' => $data['payload']
                            ]));
                        }
                    }
                }
                break;
            default:
                // ignore
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $room = $conn->room;
        if ($room && isset($this->rooms[$room][$conn->resourceId])) {
            unset($this->rooms[$room][$conn->resourceId]);
            foreach ($this->rooms[$room] as $rid => $c) {
                $c->send(json_encode(['type'=>'peer-left','id'=>$conn->resourceId]));
            }
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

