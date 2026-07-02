<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

class TaskManager {
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED_SUCCESS = 'completed_success';
    public const STATUS_COMPLETED_FAIL = 'completed_fail';
    public const STATUS_DELETED = 'deleted';

    private const VALID_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED_SUCCESS,
        self::STATUS_COMPLETED_FAIL,
        self::STATUS_DELETED,
        'completed', // Legacy-Wert aus alten Installationen, wird intern als completed_success behandelt.
    ];

    public function getTasks(?string $start = null, ?string $end = null, ?int $id = null, ?int $projectId = null, bool $includeDeleted = false): array {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId() ?? 0;

        $sql = "SELECT
                    t.*,
                    p.name AS project_name,
                    p.color AS project_color,
                    u.username AS assignee_name,
                    COALESCE((
                        SELECT SUM(TIMESTAMPDIFF(SECOND, tl.start_time, COALESCE(tl.end_time, NOW())))
                        FROM time_logs tl
                        WHERE tl.task_id = t.id
                    ), 0) AS total_time_seconds,
                    COALESCE((
                        SELECT COUNT(*)
                        FROM time_logs tl
                        WHERE tl.task_id = t.id AND tl.user_id = ? AND tl.end_time IS NULL
                    ), 0) AS is_timer_running,
                    COALESCE((
                        SELECT COUNT(*)
                        FROM time_logs tl
                        WHERE tl.task_id = t.id AND tl.end_time IS NULL
                    ), 0) AS has_running_timer
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assignee_id = u.id";

        $params = [$userId];
        $conditions = [];

        if (!$includeDeleted) {
            $conditions[] = "t.status <> 'deleted'";
            $conditions[] = "(p.id IS NULL OR p.status <> 'deleted')";
        }

        if ($id !== null && $id > 0) {
            $conditions[] = 't.id = ?';
            $params[] = $id;
        }

        if ($projectId !== null && $projectId > 0) {
            $conditions[] = 't.project_id = ?';
            $params[] = $projectId;
        }

        if ($start && $end) {
            $conditions[] = '(t.start_date IS NOT NULL AND t.start_date <= ? AND COALESCE(t.due_date, t.start_date) >= ?)';
            $params[] = $this->normalizeDateTime($end, true);
            $params[] = $this->normalizeDateTime($start, true);
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY COALESCE(t.start_date, t.created_at) ASC, t.id ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createTask(?int $projectId, string $title, ?string $description, ?int $assigneeId, ?string $start, ?string $due): int {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        $title = $this->normalizeTitle($title);
        $projectId = $this->normalizeOptionalId($projectId);
        $assigneeId = $this->normalizeOptionalId($assigneeId);
        $start = $this->normalizeDateTime($start);
        $due = $this->normalizeDateTime($due);
        $this->assertDateOrder($start, $due);

        $stmt = $pdo->prepare(
            'INSERT INTO tasks (project_id, title, description, assignee_id, start_date, due_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$projectId, $title, $description, $assigneeId, $start, $due, self::STATUS_NEW]);
        $taskId = (int)$pdo->lastInsertId();

        Logger::log($userId, 'CREATE_TASK', $taskId, null, ['title' => $title, 'project_id' => $projectId]);
        return $taskId;
    }

    public function updateTask(int $id, ?int $projectId, string $title, ?string $description, ?int $assigneeId, ?string $start, ?string $due, string $status): void {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        $title = $this->normalizeTitle($title);
        $projectId = $this->normalizeOptionalId($projectId);
        $assigneeId = $this->normalizeOptionalId($assigneeId);
        $start = $this->normalizeDateTime($start);
        $due = $this->normalizeDateTime($due);
        $status = $this->normalizeStatus($status);
        $this->assertDateOrder($start, $due);

        $current = $this->findTask($id, true);
        if (!$current) {
            throw new InvalidArgumentException('Aufgabe nicht gefunden.');
        }

        $completedAt = $current['completed_at'] ?? null;
        $wasCompleted = $this->isCompletedStatus((string)$current['status']);
        $isCompleted = $this->isCompletedStatus($status);

        if ($isCompleted && !$wasCompleted) {
            $completedAt = date('Y-m-d H:i:s');
        } elseif (!$isCompleted) {
            $completedAt = null;
        }

        $stmt = $pdo->prepare(
            'UPDATE tasks
             SET project_id = ?, title = ?, description = ?, assignee_id = ?, start_date = ?, due_date = ?, status = ?, completed_at = ?
             WHERE id = ?'
        );
        $stmt->execute([$projectId, $title, $description, $assigneeId, $start, $due, $status, $completedAt, $id]);

        if ($isCompleted || $status === self::STATUS_DELETED) {
            $this->stopOpenTimers($id);
        }

        Logger::log($userId, 'UPDATE_TASK', $id, $current, [
            'project_id' => $projectId,
            'title' => $title,
            'assignee_id' => $assigneeId,
            'start_date' => $start,
            'due_date' => $due,
            'status' => $status,
            'completed_at' => $completedAt,
        ]);
    }

    public function deleteTask(int $id): void {
        $current = $this->findTask($id, true);
        if (!$current) {
            throw new InvalidArgumentException('Aufgabe nicht gefunden.');
        }

        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);
        $this->stopOpenTimers($id);

        Logger::log($userId, 'DELETE_TASK', $id, $current, ['status' => self::STATUS_DELETED]);
    }

    public function findTask(int $id, bool $includeDeleted = false): ?array {
        $tasks = $this->getTasks(null, null, $id, null, $includeDeleted);
        return $tasks[0] ?? null;
    }

    public function isCompletedStatus(string $status): bool {
        return in_array($status, [self::STATUS_COMPLETED_SUCCESS, self::STATUS_COMPLETED_FAIL, 'completed'], true);
    }

    private function stopOpenTimers(int $taskId): void {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE time_logs SET end_time = NOW() WHERE task_id = ? AND end_time IS NULL');
        $stmt->execute([$taskId]);
    }

    private function normalizeStatus(string $status): string {
        $status = trim($status) ?: self::STATUS_NEW;
        if ($status === 'completed') {
            return self::STATUS_COMPLETED_SUCCESS;
        }
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException('Ungültiger Aufgabenstatus.');
        }
        return $status;
    }

    private function normalizeTitle(string $title): string {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException('Aufgabentitel fehlt.');
        }
        return substr($title, 0, 255);
    }

    private function normalizeOptionalId($value): ?int {
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    private function normalizeDateTime(?string $value, bool $required = false): ?string {
        $value = trim((string)$value);
        if ($value === '') {
            if ($required) {
                throw new InvalidArgumentException('Datum fehlt.');
            }
            return null;
        }

        $value = str_replace('T', ' ', $value);
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new InvalidArgumentException('Ungültiges Datum.');
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function assertDateOrder(?string $start, ?string $due): void {
        if ($start && $due && strtotime($due) < strtotime($start)) {
            throw new InvalidArgumentException('Fälligkeitsdatum liegt vor dem Startdatum.');
        }
    }
}
