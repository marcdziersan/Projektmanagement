<?php
require_once __DIR__ . '/Database.php';

class Auth {
    private static function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Lädt die Nutzerdaten aus der Datenbank nach, falls noch eine alte Session
     * nur mit user_id existiert. Das verhindert leere Headerdaten nach Updates.
     */
    private static function refreshSessionUser(): bool {
        self::ensureSession();

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            self::clearSessionUser();
            return false;
        }

        if (!empty($_SESSION['username']) && !empty($_SESSION['role'])) {
            return true;
        }

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            self::clearSessionUser();
            return false;
        }

        if (!$user) {
            self::clearSessionUser();
            return false;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = (string)$user['username'];
        $_SESSION['role'] = (string)($user['role'] ?: 'user');

        return true;
    }

    private static function clearSessionUser(): void {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    }

    public static function login(string $username, string $password): bool {
        self::ensureSession();

        $username = trim($username);
        if ($username === '' || $password === '') {
            return false;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = (string)$user['username'];
        $_SESSION['role'] = (string)($user['role'] ?: 'user');

        return true;
    }

    public static function logout(): void {
        self::ensureSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return self::refreshSessionUser();
    }

    public static function getCurrentUserId(): ?int {
        return self::isLoggedIn() ? (int)$_SESSION['user_id'] : null;
    }

    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => (int)$_SESSION['user_id'],
            'username' => (string)$_SESSION['username'],
            'role' => (string)$_SESSION['role'],
        ];
    }

    public static function isAdmin(): bool {
        return self::isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }
}
