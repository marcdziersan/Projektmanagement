<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/TimeTracker.php';

requireLogin();
$data = readJsonBody();
$action = (string)($data['action'] ?? '');
$taskId = optionalInt($data['task_id'] ?? null);

if (!$taskId) {
    apiError('Missing Task ID', 400);
}

try {
    $tt = new TimeTracker();
    if ($action === 'start') {
        apiJson(['success' => true, 'log_id' => $tt->startTimer($taskId)], 201);
    }
    if ($action === 'stop') {
        $stopped = $tt->stopTimer($taskId);
        apiJson(['success' => $stopped, 'error' => $stopped ? null : 'No running timer found']);
    }
    apiError('Invalid Action', 400);
} catch (Throwable $e) {
    apiError($e->getMessage(), 400);
}
