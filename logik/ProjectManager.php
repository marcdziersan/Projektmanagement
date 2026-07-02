<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

class ProjectManager {
    private const VALID_STATUSES = ['active', 'completed', 'deleted'];

    public function getAllProjects(bool $includeDeleted = false): array {
        $pdo = Database::getInstance();
        $sql = 'SELECT p.*, u.username AS created_by_name
                FROM projects p
                LEFT JOIN users u ON u.id = p.created_by';
        if (!$includeDeleted) {
            $sql .= " WHERE p.status <> 'deleted'";
        }
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';

        return $pdo->query($sql)->fetchAll();
    }

    public function createProject(string $name, ?string $description, ?string $color): int {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        $name = $this->normalizeName($name);
        $color = $this->normalizeColor($color);

        $stmt = $pdo->prepare("INSERT INTO projects (name, description, color, created_by, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $description, $color, $userId]);
        $projectId = (int)$pdo->lastInsertId();

        Logger::log($userId, 'CREATE_PROJECT', $projectId, null, ['name' => $name, 'color' => $color]);
        return $projectId;
    }

    public function updateProject(int $id, string $name, ?string $description, ?string $color, string $status = 'active'): void {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        $name = $this->normalizeName($name);
        $color = $this->normalizeColor($color);
        $status = $this->normalizeStatus($status);

        $old = $this->findProject($id, true);
        if (!$old) {
            throw new InvalidArgumentException('Projekt nicht gefunden.');
        }

        $stmt = $pdo->prepare('UPDATE projects SET name = ?, description = ?, color = ?, status = ? WHERE id = ?');
        $stmt->execute([$name, $description, $color, $status, $id]);

        Logger::log($userId, 'UPDATE_PROJECT', $id, $old, compact('name', 'description', 'color', 'status'));
    }

    public function deleteProject(int $id): void {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        $old = $this->findProject($id, true);
        if (!$old) {
            throw new InvalidArgumentException('Projekt nicht gefunden.');
        }

        try {
            $pdo->beginTransaction();

            $stopTimers = $pdo->prepare(
                'UPDATE time_logs tl
                 INNER JOIN tasks t ON t.id = tl.task_id
                 SET tl.end_time = NOW()
                 WHERE t.project_id = ? AND tl.end_time IS NULL'
            );
            $stopTimers->execute([$id]);

            $deleteTasks = $pdo->prepare("UPDATE tasks SET status = 'deleted' WHERE project_id = ? AND status <> 'deleted'");
            $deleteTasks->execute([$id]);

            $stmt = $pdo->prepare("UPDATE projects SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        Logger::log($userId, 'DELETE_PROJECT', $id, $old, ['status' => 'deleted', 'tasks' => 'soft_deleted']);
    }

    public function findProject(int $id, bool $includeDeleted = false): ?array {
        $pdo = Database::getInstance();
        $sql = 'SELECT * FROM projects WHERE id = ?';
        if (!$includeDeleted) {
            $sql .= " AND status <> 'deleted'";
        }
        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        return $project ?: null;
    }

    private function normalizeName(string $name): string {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Projektname fehlt.');
        }
        return substr($name, 0, 100);
    }

    private function normalizeColor(?string $color): string {
        $color = trim((string)$color);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#3498db';
    }

    private function normalizeStatus(string $status): string {
        $status = trim($status);
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException('Ungültiger Projektstatus.');
        }
        return $status;
    }
}
