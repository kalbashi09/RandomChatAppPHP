<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use RandomChat\ChatHandler;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatHandler()
        )
    ),
    8081
);

echo "Server running on port 8081...\n";
$server->run();
