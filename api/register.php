<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/Database.php';

$data = readJsonBody();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    apiError('Benutzername und Passwort erforderlich.', 400);
}
if (strlen($username) < 3 || strlen($username) > 50) {
    apiError('Benutzername muss zwischen 3 und 50 Zeichen haben.', 400);
}
if (strlen($password) < 6) {
    apiError('Passwort muss mindestens 6 Zeichen haben.', 400);
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        apiError('Benutzername bereits vergeben.', 409);
    }

    $passHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
    $stmt->execute([$username, $passHash]);
    apiJson(['success' => true, 'id' => (int)$pdo->lastInsertId()], 201);
} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
