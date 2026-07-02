<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../logik/Installer.php';

function apiJson($payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiError(string $message, int $status = 400): void {
    apiJson(['success' => false, 'error' => $message], $status);
}

if (!Installer::isFullyInstalled()) {
    apiError('System noch nicht installiert. Bitte install.php aufrufen.', 503);
}

require_once __DIR__ . '/../logik/Auth.php';

function requireLogin(): void {
    if (!Auth::isLoggedIn()) {
        apiError('Unauthorized', 401);
    }
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        apiError('Ungültiges JSON.', 400);
    }
    return $data;
}

function optionalInt($value): ?int {
    if ($value === null || $value === '' || (int)$value <= 0) {
        return null;
    }
    return (int)$value;
}
