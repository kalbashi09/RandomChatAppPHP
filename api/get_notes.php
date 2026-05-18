<?php
require_once 'config.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit <= 0) $limit = 50;

try {
    $stmt = $pdo->prepare("SELECT id, username, content, created_at, pos_x, pos_y FROM freedom_notes ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($notes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch notes: ' . $e->getMessage()]);
}
?>
