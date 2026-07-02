<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/ProjectManager.php';

requireLogin();
$data = readJsonBody();
$id = optionalInt($data['id'] ?? null);

if (!$id) {
    apiError('Missing ID', 400);
}

try {
    $pm = new ProjectManager();
    $pm->deleteProject($id);
    apiJson(['success' => true]);
} catch (Throwable $e) {
    apiError($e->getMessage(), 400);
}
