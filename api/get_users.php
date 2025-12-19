<?php
require_once 'api_header.php';
require_once '../logik/Database.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
echo json_encode($stmt->fetchAll());
