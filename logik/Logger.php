<?php
require_once __DIR__ . '/Database.php';

class Logger {
    public static function log($userId, $actionType, $entityId = null, $oldValue = null, $newValue = null) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action_type, entity_id, old_value, new_value) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $actionType, $entityId, $oldValue, $newValue]);
    }
}
