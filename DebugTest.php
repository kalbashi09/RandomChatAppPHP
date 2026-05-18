<?php
require 'vendor/autoload.php';
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

$connector = new Connector(Loop::get());
$url = 'ws://127.0.0.1:8081';

$connector($url)->then(function($conn) use ($url) {
    echo "Connected!\n";
    $conn->on('message', function($msg) {
        echo "Received: $msg\n";
    });
    $conn->send(json_encode(['action' => 'find', 'groupSize' => 2, 'chatterName' => '']));
    
    Loop::addTimer(2, function() use ($conn) {
        echo "Closing...\n";
        $conn->close();
    });
}, function($e) {
    echo "Error: " . $e->getMessage() . "\n";
});

Loop::run();
