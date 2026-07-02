<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/Database.php';

requireLogin();

$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT al.*, COALESCE(u.username, 'System') AS username
                     FROM audit_logs al
                     LEFT JOIN users u ON al.user_id = u.id
                     ORDER BY al.timestamp DESC, al.id DESC
                     LIMIT 100");
apiJson($stmt->fetchAll());
