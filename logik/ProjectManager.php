<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

class ProjectManager {
    public function getAllProjects() {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function createProject($name, $description, $color) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, color, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $color, $userId]);
        $projectId = $pdo->lastInsertId();

        Logger::log($userId, 'CREATE_PROJECT', $projectId, null, "Name: $name");
        return $projectId;
    }

    public function updateProject($id, $name, $description, $color, $status) {
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        
        // Fetch old for logging
        $stmtOld = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmtOld->execute([$id]);
        $old = $stmtOld->fetch();

        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, color = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $description, $color, $status, $id]);

        Logger::log($userId, 'UPDATE_PROJECT', $id, json_encode($old), json_encode(compact('name', 'description', 'color', 'status')));
    }

    public function deleteProject($id) {
        // Soft delete
        $this->updateProject($id, null, null, null, 'deleted'); // Needs refinement to keep old values?
        // Actually, let's just update status to deleted
        $pdo = Database::getInstance();
        $userId = Auth::getCurrentUserId();
        
        $stmt = $pdo->prepare("UPDATE projects SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);
        
        Logger::log($userId, 'DELETE_PROJECT', $id);
    }
}
