<?php
require_once 'api_header.php';
require_once '../logik/TaskManager.php';
require_once '../logik/TimeTracker.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$projectId = $_GET['project_id'] ?? null;
$taskId = $_GET['id'] ?? null;

$tm = new TaskManager();
$tasks = $tm->getTasks($start, $end, $taskId, $projectId);

// Enrich tasks with current timer status or total time?
// Maybe total time is good.
$tt = new TimeTracker();
foreach ($tasks as &$task) {
    $task['total_time_seconds'] = $tt->getTotalTimeForTask($task['id']);
    // Check if currently running for this user?
    // The frontend can determine if running based on status maybe?
    // But status 'in_progress' doesn't mean *I* am timing it right now.
    // Let's add a flag 'is_tracking' for current user.
    // We need to check if open log exists.
    // Optimization: do this in query? For now, loop is fine for < 1000 tasks.
}

echo json_encode($tasks);
