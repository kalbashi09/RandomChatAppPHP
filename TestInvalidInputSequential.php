<?php

require 'vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Loop;

$connector = new Connector(Loop::get());
$url = 'ws://127.0.0.1:8081';

function runTest($connector, $url, $test) {
    $deferred = new \React\Promise\Deferred();

    $connector($url)->then(function($conn) use ($test, $deferred) {
        $conn->on('message', function($msg) use ($conn, $test, $deferred) {
            $data = json_decode($msg, true);
            if (isset($data['type']) && $data['type'] === 'error') {
                $deferred->resolve(true);
            } else {
                $deferred->resolve(false);
            }
            $conn->close();
        });

        $conn->on('error', function($e) use ($deferred) {
            $deferred->resolve(false);
        });

        $conn->send(json_encode([
            'action' => 'find',
            'groupSize' => $test['size'],
            'chatterName' => $test['name']
        ]));
    }, function($e) use ($deferred) {
        $deferred->reject($e);
    });

    return $deferred->promise();
}

echo "Running Sequential Invalid Input Tests...\n";

$invalidTests = [
    ['name' => '', 'size' => 2, 'desc' => 'Empty Name'],
    ['name' => 'Alice', 'size' => 1, 'desc' => 'Size too small'],
    ['name' => 'Alice', 'size' => 7, 'desc' => 'Size too large'],
];

$runNext = function($index) use (&$runNext, $connector, $url, $invalidTests) {
    if ($index >= count($invalidTests)) {
        echo "All tests completed.\n";
        Loop::stop();
        return;
    }

    $test = $invalidTests[$index];
    echo "Testing: " . $test['desc'] . "... ";

    runTest($connector, $url, $test)->then(function($success) use ($test, $runNext, $index) {
        if ($success) {
            echo "PASSED\n";
        } else {
            echo "FAILED\n";
        }
        $runNext($index + 1);
    }, function($e) use ($test, $runNext, $index) {
        echo "FAILED (Error: " . $e->getMessage() . ")\n";
        $runNext($index + 1);
    });
};

$runNext(0);

Loop::run();
