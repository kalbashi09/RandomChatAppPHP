<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$username = trim($input['username'] ?? '');
$content = trim($input['content'] ?? '');

if (empty($username) || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required and must be under 50 characters']);
    exit;
}

if (empty($content) || strlen($content) > 300) {
    http_response_code(400);
    echo json_encode(['error' => 'Content is required and must be under 300 characters']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO freedom_notes (username, content, pos_x, pos_y) VALUES (:username, :content, :x, :y)");
    $stmt->execute([
        ':username' => $username,
        ':content' => $content,
        ':x' => 20,
        ':y' => 20
    ]);
    
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add note: ' . $e->getMessage()]);
}
?>
