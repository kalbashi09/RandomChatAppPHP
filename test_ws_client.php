<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Factory;

$loop = React\EventLoop\Loop::get();
$connector = new Connector($loop);

$connections = [];

$createClient = function($name) use ($connector, $loop, &$connections) {
    return $connector('ws://localhost:8081')->then(function($conn) use ($name, $loop, &$connections) {
        echo "[$name] Connected!\n";
        $connections[] = $conn;

        $conn->on('message', function($msg) use ($name) {
            echo "[$name] Received: $msg\n";
        });

        $conn->on('close', function() use ($name, $loop) {
            echo "[$name] Closed.\n";
            $loop->stop();
        });

        // Send find request
        $payload = json_encode([
            'action' => 'find',
            'groupSize' => 2,
            'chatterName' => $name
        ]);
        echo "[$name] Sending: $payload\n";
        $conn->send($payload);
        
        return $conn;
    });
};

$createClient('User_A')->then(function() use ($createClient) {
    return $createClient('User_B');
})->then(function() use ($loop) {
    // Give them a moment to match
    $loop->addTimer(2, function() use ($loop) {
        echo "Test completed. Stopping loop.\n";
        $loop->stop();
    });
}, function(\Exception $e) use ($loop) {
    echo "Error: {$e->getMessage()}\n";
    $loop->stop();
});

$loop->run();
