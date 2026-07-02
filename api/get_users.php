<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/Database.php';

requireLogin();

$pdo = Database::getInstance();
$stmt = $pdo->query('SELECT id, username, role FROM users ORDER BY username ASC');
apiJson($stmt->fetchAll());
