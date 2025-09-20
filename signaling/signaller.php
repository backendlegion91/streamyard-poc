<?php
require __DIR__ . '/SignalingServer.php';
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

$port = 8080;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SignalingServer()
        )
    ),
    $port
);
echo "Listening on ws://0.0.0.0:$port\n";
$server->run();
