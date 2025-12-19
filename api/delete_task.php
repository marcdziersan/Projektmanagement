<?php
require_once 'api_header.php';
require_once '../logik/TaskManager.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if ($id) {
    $tm = new TaskManager();
    $tm->deleteTask($id);
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
}
