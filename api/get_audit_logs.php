<?php
require_once 'api_header.php';
require_once '../logik/Database.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT al.*, u.username 
                     FROM audit_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     ORDER BY al.timestamp DESC LIMIT 100");
echo json_encode($stmt->fetchAll());
