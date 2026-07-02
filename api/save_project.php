<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/ProjectManager.php';

requireLogin();
$data = readJsonBody();
$pm = new ProjectManager();

try {
    if (!empty($data['id'])) {
        $pm->updateProject(
            (int)$data['id'],
            (string)($data['name'] ?? ''),
            $data['description'] ?? null,
            $data['color'] ?? null,
            (string)($data['status'] ?? 'active')
        );
        apiJson(['success' => true]);
    }

    $id = $pm->createProject(
        (string)($data['name'] ?? ''),
        $data['description'] ?? null,
        $data['color'] ?? null
    );
    apiJson(['success' => true, 'id' => $id], 201);
} catch (Throwable $e) {
    apiError($e->getMessage(), 400);
}
