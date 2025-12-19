<?php
require_once 'api_header.php';
require_once '../logik/TimeTracker.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$taskId = $data['task_id'] ?? null;

if (!$taskId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing Task ID']);
    exit;
}

$tt = new TimeTracker();

if ($action === 'start') {
    $logId = $tt->startTimer($taskId);
    echo json_encode(['success' => true, 'log_id' => $logId]);
} elseif ($action === 'stop') {
    if ($tt->stopTimer($taskId)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No running timer found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Action']);
}
