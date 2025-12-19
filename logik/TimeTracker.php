<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

class TimeTracker {
    public function startTimer($taskId) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        
        // Check if user already has a running timer? Maybe allow multiple? No, usually one at a time.
        // Let's stop any running timer for this user first? Or just allow it. For simplicity, assume one.
        // But better is to auto-stop others. 
        // For this MVP, let's just start one.

        $stmt = $pdo->prepare("INSERT INTO time_logs (task_id, user_id, start_time) VALUES (?, ?, NOW())");
        $stmt->execute([$taskId, $userId]);
        $logId = $pdo->lastInsertId();
        
        // Update task status to in_progress if not already
        $pdo->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?")->execute([$taskId]);

        Logger::log($userId, 'START_TIMER', $taskId, null, "LogId: $logId");
        return $logId;
    }

    public function stopTimer($taskId) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();

        // Find running timer for this task and user
        $stmt = $pdo->prepare("SELECT id FROM time_logs WHERE task_id = ? AND user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
        $stmt->execute([$taskId, $userId]);
        $log = $stmt->fetch();

        if ($log) {
            $stmtUpd = $pdo->prepare("UPDATE time_logs SET end_time = NOW() WHERE id = ?");
            $stmtUpd->execute([$log['id']]);
            Logger::log($userId, 'STOP_TIMER', $taskId, null, "LogId: " . $log['id']);
            return true;
        }
        return false;
    }

    public function getLogsForTask($taskId) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT tl.*, u.username FROM time_logs tl LEFT JOIN users u ON tl.user_id = u.id WHERE task_id = ? ORDER BY start_time DESC");
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }
    
    public function getTotalTimeForTask($taskId) {
        // Calculate sum in seconds in DB or PHP? DB is faster.
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT SUM(TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW()))) as total_seconds FROM time_logs WHERE task_id = ?");
        $stmt->execute([$taskId]);
        return $stmt->fetchColumn();
    }
}
