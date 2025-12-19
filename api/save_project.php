<?php
require_once 'api_header.php';
require_once '../logik/ProjectManager.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$pm = new ProjectManager();

if (isset($data['id']) && !empty($data['id'])) {
    $pm->updateProject($data['id'], $data['name'], $data['description'], $data['color'], $data['status']);
    echo json_encode(['success' => true]);
} else {
    $id = $pm->createProject($data['name'], $data['description'], $data['color']);
    echo json_encode(['success' => true, 'id' => $id]);
}
