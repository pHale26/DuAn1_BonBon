<?php
require_once 'configs/env.php';

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
    
    // Check if column exists first
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'views'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "Column 'views' already exists in 'posts' table.\n";
    } else {
        $sql = "ALTER TABLE posts ADD COLUMN views INT DEFAULT 0";
        $pdo->exec($sql);
        echo "Successfully added 'views' column to 'posts' table.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
