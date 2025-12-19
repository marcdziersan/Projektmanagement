<?php
require_once 'api_header.php';
require_once '../logik/TaskManager.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tm = new TaskManager();

try {
    if (isset($data['id']) && !empty($data['id'])) {
        $tm->updateTask(
            $data['id'],
            $data['title'],
            $data['description'],
            $data['assignee_id'],
            $data['start_date'],
            $data['due_date'],
            $data['status']
        );
        echo json_encode(['success' => true]);
    } else {
        $id = $tm->createTask(
            $data['project_id'],
            $data['title'],
            $data['description'],
            $data['assignee_id'],
            $data['start_date'],
            $data['due_date']
        );
        echo json_encode(['success' => true, 'id' => $id]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
