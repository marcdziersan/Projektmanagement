<?php
require_once __DIR__ . '/Database.php';

class Logger {
    public static function log(?int $userId, string $actionType, ?int $entityId = null, $oldValue = null, $newValue = null): void {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action_type, entity_id, old_value, new_value) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            strtoupper(trim($actionType)),
            $entityId,
            self::stringify($oldValue),
            self::stringify($newValue),
        ]);
    }

    private static function stringify($value): ?string {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
