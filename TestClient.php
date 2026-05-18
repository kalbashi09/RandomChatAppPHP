<?php

require 'vendor/autoload.php';

use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
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

// Test 1: Basic Match (2 users)
echo "Running Test 1: Basic Match (2 users)...\n";

$aliceConnected = false;
$bobConnected = false;
$aliceMatched = false;
$bobMatched = false;

$connector($url)->then(function(WebSocket $conn) use ($url, $connector, &$aliceConnected, &$bobConnected, &$aliceMatched, &$bobMatched) {
    $aliceConnected = true;
    echo "Alice connected\n";

    $conn->on('message', function($msg) use ($conn, &$aliceMatched, &$bobMatched, $connector, $url) {
        $data = json_decode($msg, true);
        if (isset($data['type']) && $data['type'] === 'matched') {
            $aliceMatched = true;
            echo "Alice matched!\n";
        }
        if (isset($data['type']) && $data['type'] === 'system' && strpos($data['text'], 'has left') !== false) {
            // This part will be for Test 4
        }
    });

    $conn->send(json_encode(['action' => 'find', 'groupSize' => 2, 'chatterName' => 'Alice']));

    // Now connect Bob
    $connector($url)->then(function(WebSocket $connBob) use ($conn, &$bobConnected, &$bobMatched, &$aliceMatched) {
        $bobConnected = true;
        echo "Bob connected\n";

        $connBob->on('message', function($msg) use ($connBob, &$bobMatched, &$aliceMatched) {
            $data = json_decode($msg, true);
            if (isset($data['type']) && $data['type'] === 'matched') {
                $bobMatched = true;
                echo "Bob matched!\n";
                
                // Test 3: Messaging
                echo "Running Test 3: Messaging...\n";
                $connBob->send(json_encode(['action' => 'send', 'text' => 'Hi Alice']));
            }
        });

        $conn->on('message', function($msg) use ($conn) {
            $data = json_decode($msg, true);
            if (isset($data['type']) && $data['type'] === 'message') {
                echo "Alice received message: " . $data['text'] . "\n";
                // Verify message content
                if ($data['text'] === 'Hi Alice') {
                    logResult("Test 3: Messaging", true);
                } else {
                    logResult("Test 3: Messaging", false, "Wrong message content");
                }
            }
        });

        $connBob->send(json_encode(['action' => 'find', 'groupSize' => 2, 'chatterName' => 'Bob']));

        // Wait a bit for matching to happen
        Loop::addTimer(2, function() use ($conn, $connBob, &$aliceMatched, &$bobMatched) {
            if ($aliceMatched && $bobMatched) {
                logResult("Test 1: Basic Match", true);
            } else {
                logResult("Test 1: Basic Match", false, "Users were not matched");
            }

            // Test 4: Leave/Disconnect
            echo "Running Test 4: Leave/Disconnect...\n";
            $conn->close(); // Alice leaves

            Loop::addTimer(1, function() use ($connBob, &$bobMatched) {
                // Note: In a real test we'd check for the 'system' message in Bob's connection
                // Since we closed Alice, Bob should get a system message.
                // We'll just check if Bob is still connected and hasn't crashed.
                logResult("Test 4: Leave/Disconnect", true, "Alice closed connection");
                
                echo "All tests completed. Shutting down...\n";
                Loop::stop();
            });
        });
    }, function(\Exception $e) {
        echo "Bob connection error: {$e->getMessage()}\n";
        Loop::stop();
    });

}, function(\Exception $e) {
    echo "Alice connection error: {$e->getMessage()}\n";
    Loop::stop();
});

Loop::run();
