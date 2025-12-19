<?php
require_once 'logik/Database.php';
require_once 'logik/TaskManager.php';
require_once 'logik/Auth.php'; // Only if needed by TaskManager

// Mock Auth if needed or just ensure TaskManager works
// TaskManager usually calls Auth::getCurrentUserId(). 
// We might need to mock that or bypass it if we can.
// Actually TaskManager.getTasks doesn't strictly need Auth unless it filters by user?
// Looking at TaskManager.php: "LEFT JOIN users u ON t.assignee_id = u.id". 
// It doesn't seem to force user filter unless $assigneeId is passed (wait, getTasks signature).
// getTasks($start, $end, $id, $projectId) - no user filter.

// But wait, updateTask uses Auth. getTasks usually doesn't.
// Let's check TaskManager.php content above... 
// It does `Auth::getCurrentUserId()` inside `updateTask`, but `getTasks` looks clean.

$pdo = Database::getInstance();
// Ensure project and tasks exist
$pdo->exec("INSERT IGNORE INTO projects (id, name, color, status) VALUES (1, 'Filter Test Project', '#ff0000', 'active')");
$pdo->exec("INSERT INTO tasks (title, project_id, status, start_date, due_date) VALUES ('Task for Project 1', 1, 'todo', NOW(), NOW())");
$pdo->exec("INSERT INTO tasks (title, project_id, status, start_date, due_date) VALUES ('Task for other project', 99, 'todo', NOW(), NOW())");

$tm = new TaskManager();
// Test filtering
$tasksPro1 = $tm->getTasks(null, null, null, 1);

echo "Tasks for Project 1:\n";
foreach ($tasksPro1 as $t) {
    echo "- " . $t['title'] . " (Project ID: " . $t['project_id'] . ")\n";
}

$allCurrentTasks = $tm->getTasks(null, null, null, null);
// Filter for our test tasks just to report
$count = 0;
foreach($allCurrentTasks as $t) {
    if ($t['title'] == 'Task for Project 1' || $t['title'] == 'Task for other project') {
        $count++;
    }
}
echo "\nTotal relevant tasks in DB: " . $count . "\n";
