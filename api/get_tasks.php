<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/TaskManager.php';

requireLogin();

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$projectId = optionalInt($_GET['project_id'] ?? null);
$taskId = optionalInt($_GET['id'] ?? null);
$includeDeleted = isset($_GET['include_deleted']) && $_GET['include_deleted'] === '1';

try {
    $tm = new TaskManager();
    apiJson($tm->getTasks($start, $end, $taskId, $projectId, $includeDeleted));
} catch (Throwable $e) {
    apiError($e->getMessage(), 400);
}
