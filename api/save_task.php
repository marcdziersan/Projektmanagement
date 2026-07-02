<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/TaskManager.php';

requireLogin();
$data = readJsonBody();
$tm = new TaskManager();

try {
    if (!empty($data['id'])) {
        $tm->updateTask(
            (int)$data['id'],
            optionalInt($data['project_id'] ?? null),
            (string)($data['title'] ?? ''),
            $data['description'] ?? null,
            optionalInt($data['assignee_id'] ?? null),
            $data['start_date'] ?? null,
            $data['due_date'] ?? null,
            (string)($data['status'] ?? TaskManager::STATUS_NEW)
        );
        apiJson(['success' => true]);
    }

    $id = $tm->createTask(
        optionalInt($data['project_id'] ?? null),
        (string)($data['title'] ?? ''),
        $data['description'] ?? null,
        optionalInt($data['assignee_id'] ?? null),
        $data['start_date'] ?? null,
        $data['due_date'] ?? null
    );
    apiJson(['success' => true, 'id' => $id], 201);
} catch (Throwable $e) {
    apiError($e->getMessage(), 400);
}
