<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$id = $input['id'] ?? null;
$x = $input['pos_x'] ?? null;
$y = $input['pos_y'] ?? null;

if ($id === null || $x === null || $y === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id, pos_x, or pos_y']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE freedom_notes SET pos_x = :x, pos_y = :y WHERE id = :id");
    $stmt->execute([
        ':x' => $x,
        ':y' => $y,
        ':id' => $id
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update position: ' . $e->getMessage()]);
}
?>
