<?php

namespace RandomChat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RandomChat\Models\UserRequest;

class ChatHandler implements MessageComponentInterface {
    protected $clients;
    protected $waitingQueue = []; // [groupSize => [UserRequest, ...]]
    protected $groupMap = [];     // [resourceId => groupId]
    protected $groupMembers = []; // [groupId => [resourceId => name, ...]]
    protected $nicknameMap = [];  // [resourceId => chatterName]

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "Chat Server Started...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Received message from {$from->resourceId}: {$msg}\n";
        $data = json_decode($msg, true);
        if (!$data || !isset($data['action'])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid JSON or missing action']));
            return;
        }

        switch ($data['action']) {
            case 'find':
                $this->handleFind($from, $data);
                break;
            case 'send':
                $this->handleSend($from, $data);
                break;
            case 'leave':
                $this->handleLeave($from);
                break;
            default:
                $from->send(json_encode(['type' => 'error', 'message' => 'Unknown action type']));
        }
    }

    protected function handleFind(ConnectionInterface $conn, $data) {
        $groupSize = (int)($data['groupSize'] ?? 2);
        $name = trim($data['chatterName'] ?? $data['name'] ?? '');

        if (empty($name)) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Name cannot be empty']));
            return;
        }

        if ($groupSize < 2 || $groupSize > 6) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Group size must be between 2 and 6']));
            return;
        }

        $request = new UserRequest($conn->resourceId, $groupSize, $name);
        $this->waitingQueue[$groupSize][] = $request;
        $this->nicknameMap[$conn->resourceId] = $name;

        echo "User {$name} ({$conn->resourceId}) is looking for a group of {$groupSize}\n";

        $this->tryMatch($groupSize);
    }

    protected function tryMatch($groupSize) {
        if (!isset($this->waitingQueue[$groupSize]) || count($this->waitingQueue[$groupSize]) < $groupSize) {
            return;
        }

        $groupId = uniqid('group_');
        $matchedUsers = array_splice($this->waitingQueue[$groupSize], 0, $groupSize);
        
        $members = [];
        foreach ($matchedUsers as $user) {
            $this->groupMap[$user->resourceId] = $groupId;
            $members[$user->resourceId] = $user->chatterName;
        }
        $this->groupMembers[$groupId] = $members;

        echo "Group created: {$groupId} with " . count($members) . " members\n";

        foreach ($matchedUsers as $user) {
            $conn = $this->getConnectionByResourceId($user->resourceId);
            if ($conn) {
                $conn->send(json_encode([
                    'type' => 'matched',
                    'groupId' => $groupId,
                    'members' => $members
                ]));
            }
        }

        // Notify group members that the group is now active
        $this->broadcastToGroup($groupId, [
            'type' => 'system',
            'text' => "You have been matched into a group! Say hello!"
        ]);
    }

    protected function handleSend(ConnectionInterface $from, $data) {
        $resourceId = $from->resourceId;
        if (!isset($this->groupMap[$resourceId])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'You are not in a group']));
            return;
        }

        $groupId = $this->groupMap[$resourceId];
        $name = $this->nicknameMap[$resourceId] ?? 'Anonymous';
        $text = $data['message'] ?? $data['text'] ?? '';

        $this->broadcastToGroup($groupId, [
            'type' => 'message',
            'sender' => $name,
            'text' => $text
        ]);
    }

    protected function handleLeave(ConnectionInterface $conn) {
        $resourceId = $conn->resourceId;
        if (isset($this->groupMap[$resourceId])) {
            $groupId = $this->groupMap[$resourceId];
            $name = $this->nicknameMap[$resourceId] ?? 'Anonymous';

            unset($this->groupMembers[$groupId][$resourceId]);
            unset($this->groupMap[$resourceId]);

            $this->broadcastToGroup($groupId, [
                'type' => 'system',
                'text' => "User {$name} has left the chat"
            ]);

            if (empty($this->groupMembers[$groupId])) {
                unset($this->groupMembers[$groupId]);
            }
        }

        // Remove from waiting queue if present
        foreach ($this->waitingQueue as $size => $queue) {
            foreach ($queue as $index => $request) {
                if ($request->resourceId == $resourceId) {
                    unset($this->waitingQueue[$size][$index]);
                }
            }
        }
        
        unset($this->nicknameMap[$resourceId]);
    }

    protected function broadcastToGroup($groupId, $data) {
        if (!isset($this->groupMembers[$groupId])) return;

        $payload = json_encode($data);
        foreach ($this->groupMembers[$groupId] as $resourceId => $name) {
            $conn = $this->getConnectionByResourceId($resourceId);
            if ($conn) {
                $conn->send($payload);
            }
        }
    }

    protected function getConnectionByResourceId($resourceId) {
        foreach ($this->clients as $conn) {
            if ($conn->resourceId == $resourceId) {
                return $conn;
            }
        }
        return null;
    }

    public function onClose(ConnectionInterface $conn) {
        $this->handleLeave($conn);
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
