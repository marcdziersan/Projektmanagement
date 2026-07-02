<?php
require_once __DIR__ . '/logik/Database.php';
require_once __DIR__ . '/logik/TaskManager.php';
require_once __DIR__ . '/logik/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT id, username, role FROM users WHERE username = 'admin' LIMIT 1");
$admin = $stmt->fetch();
if ($admin) {
    $_SESSION['user_id'] = (int)$admin['id'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['role'] = $admin['role'];
}

$pdo->exec("INSERT INTO projects (id, name, color, status) VALUES (999, 'Test Overview Proj', '#ff00ff', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status)");
$pdo->exec("INSERT INTO tasks (title, project_id, status, start_date, due_date) VALUES ('Active Task', 999, 'in_progress', NOW(), NOW())");
$pdo->exec("INSERT INTO tasks (title, project_id, status, completed_at, start_date, due_date) VALUES ('Done Task', 999, 'completed_success', NOW(), NOW(), NOW())");

$tm = new TaskManager();
$start = date('Y') . '-01-01 00:00:00';
$end = date('Y') . '-12-31 23:59:59';
$tasks = $tm->getTasks($start, $end);
$pTasks = array_filter($tasks, fn($t) => (int)$t['project_id'] === 999);

$openCount = 0;
$doneCount = 0;
foreach ($pTasks as $t) {
    if (in_array($t['status'], ['completed_success', 'completed_fail', 'completed'], true)) {
        $doneCount++;
    } else {
        $openCount++;
    }
}
echo "Project 999 Stats: Open={$openCount}, Done={$doneCount}\n";

$id = $tm->createTask(999, 'Completion Test', '', null, null, null);
$tm->updateTask($id, 999, 'Completion Test Updated', '', null, null, null, 'completed_success');
$stmt = $pdo->prepare('SELECT status, completed_at FROM tasks WHERE id = ?');
$stmt->execute([$id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'Task ' . $id . ': Status=' . $res['status'] . ', CompletedAt=' . ($res['completed_at'] ? 'SET' : 'NULL') . "\n";

$pdo->exec('DELETE FROM tasks WHERE project_id = 999');
$pdo->exec('DELETE FROM projects WHERE id = 999');
