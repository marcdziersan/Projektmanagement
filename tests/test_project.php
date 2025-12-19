<?php
require_once __DIR__ . '/../logik/Auth.php';
require_once __DIR__ . '/../logik/ProjectManager.php';
require_once __DIR__ . '/../logik/Database.php';

// Mock Auth
if (session_status() == PHP_SESSION_NONE) @session_start();
// We need a valid user ID. 
// Get admin user id.
$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT id FROM users WHERE username = 'admin'");
$adminId = $stmt->fetchColumn();

if (!$adminId) {
    die("Admin user not found for test.");
}
$_SESSION['user_id'] = $adminId;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "User ID set to: $adminId\n";

$pm = new ProjectManager();

try {
    echo "Creating Project 'Test Project'...\n";
    $id = $pm->createProject('Test Project', 'Test Description', '#ff0000');
    echo "Result ID: $id\n";
    
    if (is_numeric($id) && $id > 0) {
        echo "SUCCESS: Project created.\n";
    } else {
        echo "FAILURE: ID invalid.\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
