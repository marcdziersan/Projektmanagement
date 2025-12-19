<?php
require_once __DIR__ . '/Database.php';

class Auth {
    public static function login($username, $password) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (session_status() == PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function logout() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        session_destroy();
    }

    public static function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }

    public static function getCurrentUserId() {
        if (self::isLoggedIn()) {
            return $_SESSION['user_id'];
        }
        return null;
    }
}
