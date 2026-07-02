<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/TaskManager.php';

class TimeTracker {
    public function startTimer(int $taskId): int {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        if (!$userId) {
            throw new RuntimeException('Nicht angemeldet.');
        }

        $tm = new TaskManager();
        $task = $tm->findTask($taskId);
        if (!$task) {
            throw new InvalidArgumentException('Aufgabe nicht gefunden oder gelöscht.');
        }
        if ($tm->isCompletedStatus((string)$task['status'])) {
            throw new RuntimeException('Abgeschlossene Aufgaben können nicht erneut getrackt werden.');
        }

        $pdo->beginTransaction();
        try {
            // Pro Nutzer ist genau ein aktiver Timer erlaubt.
            $stopOther = $pdo->prepare('UPDATE time_logs SET end_time = NOW() WHERE user_id = ? AND end_time IS NULL');
            $stopOther->execute([$userId]);

            $stmt = $pdo->prepare('INSERT INTO time_logs (task_id, user_id, start_time) VALUES (?, ?, NOW())');
            $stmt->execute([$taskId, $userId]);
            $logId = (int)$pdo->lastInsertId();

            $pdo->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ? AND status = 'new'")->execute([$taskId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Logger::log($userId, 'START_TIMER', $taskId, null, ['log_id' => $logId]);
        return $logId;
    }

    public function stopTimer(int $taskId): bool {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        if (!$userId) {
            throw new RuntimeException('Nicht angemeldet.');
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM time_logs WHERE task_id = ? AND user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1'
        );
        $stmt->execute([$taskId, $userId]);
        $log = $stmt->fetch();

        if (!$log) {
            return false;
        }

        $stmtUpd = $pdo->prepare('UPDATE time_logs SET end_time = NOW() WHERE id = ?');
        $stmtUpd->execute([(int)$log['id']]);
        Logger::log($userId, 'STOP_TIMER', $taskId, null, ['log_id' => (int)$log['id']]);
        return true;
    }

    public function getLogsForTask(int $taskId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT tl.*, u.username
             FROM time_logs tl
             LEFT JOIN users u ON tl.user_id = u.id
             WHERE tl.task_id = ?
             ORDER BY tl.start_time DESC, tl.id DESC'
        );
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    public function getTotalTimeForTask(int $taskId): int {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))), 0) AS total_seconds
             FROM time_logs
             WHERE task_id = ?'
        );
        $stmt->execute([$taskId]);
        return (int)$stmt->fetchColumn();
    }
}
