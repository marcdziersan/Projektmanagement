<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

class TaskManager {
    public function getTasks($start = null, $end = null, $id = null, $projectId = null) {
        $pdo = Database::getInstance();
        $sql = "SELECT t.*, p.name as project_name, p.color as project_color, u.username as assignee_name,
                (SELECT SUM(TIMESTAMPDIFF(SECOND, tl.start_time, IFNULL(tl.end_time, NOW()))) 
                 FROM time_logs tl WHERE tl.task_id = t.id) as total_time_seconds,
                (SELECT COUNT(*) FROM time_logs tl WHERE tl.task_id = t.id AND tl.end_time IS NULL) as is_timer_running
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assignee_id = u.id";
        
        $params = [];
        $conditions = [];

        if ($id) {
            $conditions[] = "t.id = ?";
            $params[] = $id;
        }

        if ($projectId) {
            $conditions[] = "t.project_id = ?";
            $params[] = $projectId;
        }

        if ($start && $end) {
            $conditions[] = "( (t.start_date BETWEEN ? AND ?) OR (t.due_date BETWEEN ? AND ?) )";
            $params[] = $start;
            $params[] = $end;
            $params[] = $start;
            $params[] = $end;
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY t.start_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createTask($projectId, $title, $description, $assigneeId, $start, $due) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();

        $stmt = $pdo->prepare("INSERT INTO tasks (project_id, title, description, assignee_id, start_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?, 'new')");
        $stmt->execute([$projectId, $title, $description, $assigneeId, $start, $due]);
        $taskId = $pdo->lastInsertId();

        Logger::log($userId, 'CREATE_TASK', $taskId, null, $title);
        return $taskId;
    }

    public function updateTask($id, $title, $description, $assigneeId, $start, $due, $status) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();

        $completedAt = null;
        if (strpos($status, 'completed') === 0) {
            $completedAt = date('Y-m-d H:i:s');
        } else {
            // If status is not completed, ensure completed_at is null
            $completedAt = null;
        }

        // Fetch current task to check if it was already completed
        $currentTaskStmt = $pdo->prepare("SELECT status, completed_at FROM tasks WHERE id = ?");
        $currentTaskStmt->execute([$id]);
        $currentTask = $currentTaskStmt->fetch(PDO::FETCH_ASSOC);

        $sql = "UPDATE tasks SET title = ?, description = ?, assignee_id = ?, start_date = ?, due_date = ?, status = ?, completed_at = ? WHERE id = ?";
        $params = [$title, $description, $assigneeId, $start, $due, $status];

        if ($completedAt !== null) {
            // If the new status is completed, set completed_at to current time
            $params[] = $completedAt;
        } elseif ($currentTask && strpos($currentTask['status'], 'completed') === 0 && strpos($status, 'completed') !== 0) {
            // If the task was completed but is no longer, set completed_at to NULL
            $params[] = null;
        } else {
            // Otherwise, keep the existing completed_at value or null if it was never completed
            $params[] = $currentTask['completed_at'];
        }
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        Logger::log($userId, 'UPDATE_TASK', $id, null, "Status: $status");
    }

    public function deleteTask($id) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);

        Logger::log($userId, 'DELETE_TASK', $id);
    }
}
