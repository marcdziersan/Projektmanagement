<?php
require_once 'logik/Database.php';
require_once 'logik/TaskManager.php';

$pdo = Database::getInstance();
// 1. Ensure a test project and task exist
$pdo->exec("INSERT INTO projects (id, name, color, status) VALUES (999, 'Test Overview Proj', '#ff00ff', 'active') ON DUPLICATE KEY UPDATE name=name");
$pdo->exec("INSERT INTO tasks (title, project_id, status) VALUES ('Active Task', 999, 'in_progress')");
$pdo->exec("INSERT INTO tasks (title, project_id, status, completed_at) VALUES ('Done Task', 999, 'completed_success', NOW())");

// 2. Fetch tasks for overview logic (broad range)
$tm = new TaskManager();
// Assuming app.js uses full year range
$start = date('Y') . '-01-01 00:00:00';
$end = date('Y') . '-12-31 23:59:59';
$tasks = $tm->getTasks($start, $end);

$pTasks = array_filter($tasks, function($t) { return $t['project_id'] == 999; });
$openCount = 0;
$doneCount = 0;
foreach($pTasks as $t) {
    if ($t['status'] == 'completed_success' || $t['status'] == 'completed_fail') $doneCount++;
    else $openCount++;
}

echo "Project 999 Stats: Open=$openCount, Done=$doneCount\n";

// 3. Test Completion Logic (Update Task)
// Create a temp task
$pdo->exec("INSERT INTO tasks (title, status) VALUES ('Completion Test', 'new')");
$id = $pdo->lastInsertId();
$tm->updateTask($id, 'Completion Test Updated', '', null, null, null, 'completed_success');

// Verify completed_at is set
$stmt = $pdo->prepare("SELECT status, completed_at FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Task $id: Status=" . $res['status'] . ", CompletedAt=" . ($res['completed_at'] ? 'SET' : 'NULL') . "\n";

// Cleanup
$pdo->exec("DELETE FROM tasks WHERE project_id = 999");
$pdo->exec("DELETE FROM projects WHERE id = 999");
$pdo->exec("DELETE FROM tasks WHERE id = $id");
