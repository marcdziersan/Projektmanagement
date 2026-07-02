<?php
require_once __DIR__ . '/api_header.php';

$data = readJsonBody();
$username = (string)($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

if (Auth::login($username, $password)) {
    apiJson(['success' => true, 'user' => Auth::getCurrentUser()]);
}

apiError('Benutzername oder Passwort falsch.', 401);
