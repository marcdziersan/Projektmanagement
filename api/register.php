<?php
require_once 'api_header.php';
require_once '../logik/Database.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Benutzername und Passwort erforderlich']);
    exit;
}

$pdo = Database::getInstance();

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    http_response_code(409); // Conflict
    echo json_encode(['success' => false, 'error' => 'Benutzername bereits vergeben']);
    exit;
}

// Create user
$passHash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
if ($stmt->execute([$username, $passHash])) {
    // Auto-login? Or just success.
    // Let's just return success and let frontend login.
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
}
