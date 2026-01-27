<?php
require_once 'configs/env.php';

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'views'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents('fix_result.txt', print_r($result, true));
    
} catch (Exception $e) {
    file_put_contents('fix_result.txt', "Error: " . $e->getMessage());
}
