<?php
require_once __DIR__ . '/logik/Database.php';
require_once __DIR__ . '/logik/TaskManager.php';

$pdo = Database::getInstance();
$pdo->exec("INSERT INTO projects (id, name, color, status) VALUES (9991, 'Filter Test Project', '#ff0000', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status)");
$pdo->exec("INSERT INTO projects (id, name, color, status) VALUES (9992, 'Deleted Test Project', '#999999', 'deleted') ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status)");
$pdo->exec("INSERT INTO tasks (title, project_id, status, start_date, due_date) VALUES ('Task for active project', 9991, 'new', NOW(), NOW())");
$pdo->exec("INSERT INTO tasks (title, project_id, status, start_date, due_date) VALUES ('Deleted task', 9991, 'deleted', NOW(), NOW())");
$pdo->exec("INSERT INTO tasks (title, project_id, status, start_date, due_date) VALUES ('Task for deleted project', 9992, 'new', NOW(), NOW())");

$tm = new TaskManager();
$tasksPro1 = $tm->getTasks(null, null, null, 9991);

echo "Visible tasks for active project 9991:\n";
foreach ($tasksPro1 as $t) {
    echo '- ' . $t['title'] . ' (Status: ' . $t['status'] . ")\n";
}

$allCurrentTasks = $tm->getTasks(null, null, null, null);
$visible = array_filter($allCurrentTasks, function($t) {
    return in_array($t['title'], ['Task for active project', 'Deleted task', 'Task for deleted project'], true);
});

echo "\nRelevant visible tasks: " . count($visible) . "\n";

$pdo->exec("DELETE FROM tasks WHERE project_id IN (9991, 9992)");
$pdo->exec("DELETE FROM projects WHERE id IN (9991, 9992)");
