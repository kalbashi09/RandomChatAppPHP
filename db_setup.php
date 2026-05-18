<?php
require_once 'api/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS freedom_notes (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    content VARCHAR(300) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pos_x INT DEFAULT 0,
    pos_y INT DEFAULT 0
);";
    $pdo->exec($sql);
    echo "Table 'freedom_notes' created successfully or already exists.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
