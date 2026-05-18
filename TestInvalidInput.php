<?php

require 'vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Loop;

$connector = new Connector(Loop::get());
$url = 'ws://127.0.0.1:8081';

$testResults = [
    'passed' => [],
    'failed' => [],
];

function logResult($testName, $success, $message = '') {
    global $testResults;
    if ($success) {
        $testResults['passed'][] = "$testName: OK";
    } else {
        $testResults['failed'][] = "$testName: FAIL ($message)";
    }
    echo ($success ? "[PASS] " : "[FAIL] ") . "$testName " . ($message ? "($message)" : "") . "\n";
}

echo "Running Test 5: Invalid Inputs...\n";

$invalidTests = [
    ['name' => '', 'size' => 2, 'desc' => 'Empty Name'],
    ['name' => 'Alice', 'size' => 1, 'desc' => 'Size too small'],
    ['name' => 'Alice', 'size' => 7, 'desc' => 'Size too large'],
];

$completed = 0;

foreach ($invalidTests as $test) {
    $connector($url)->then(function($conn) use ($test, &$completed, &$invalidTests) {
        $conn->on('message', function($msg) use ($conn, $test, &$completed) {
            $data = json_decode($msg, true);
            if (isset($data['type']) && $data['type'] === 'error') {
                logResult("Test 5: " . $test['desc'], true);
            } else {
                logResult("Test 5: " . $test['desc'], false, "Did not receive error for invalid input");
            }
            $conn->close();
            $completed++;
        });

        $conn->send(json_encode([
            'action' => 'find',
            'groupSize' => $test['size'],
            'chatterName' => $test['name']
        ]));
    }, function($e) use ($test, &$completed) {
        logResult("Test 5: " . $test['desc'], false, "Connection error: " . $e->getMessage());
        $completed++;
    });
}

Loop::addPeriodicTimer(0.1, function() use (&$completed, &$invalidTests) {
    if ($completed === count($invalidTests)) {
        echo "All invalid input tests completed.\n";
        Loop::stop();
    }
});

Loop::run();
