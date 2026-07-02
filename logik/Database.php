<?php
require_once __DIR__ . '/Installer.php';

/**
 * Zentraler PDO-Zugriff.
 *
 * Reihenfolge der Konfiguration:
 * 1. config.php aus dem First-Run-Installer
 * 2. Umgebungsvariablen PM_DB_* als Fallback für Deployments
 */
class Database {
    private static ?PDO $pdo = null;
    private static ?array $config = null;

    private static function loadConfig(): array {
        if (self::$config !== null) {
            return self::$config;
        }

        $fileConfig = Installer::loadConfig();
        $dbConfig = $fileConfig['db'] ?? [];

        self::$config = [
            'db' => [
                'host' => self::envOrConfig('PM_DB_HOST', $dbConfig['host'] ?? '127.0.0.1'),
                'port' => (int)self::envOrConfig('PM_DB_PORT', (string)($dbConfig['port'] ?? 3306)),
                'name' => self::envOrConfig('PM_DB_NAME', $dbConfig['name'] ?? 'projektmanagement'),
                'user' => self::envOrConfig('PM_DB_USER', $dbConfig['user'] ?? 'root'),
                'pass' => self::envOrConfig('PM_DB_PASS', $dbConfig['pass'] ?? ''),
                'charset' => self::envOrConfig('PM_DB_CHARSET', $dbConfig['charset'] ?? 'utf8mb4'),
            ],
        ];

        return self::$config;
    }

    private static function envOrConfig(string $key, string $default): string {
        $value = getenv($key);
        return $value !== false && $value !== '' ? $value : $default;
    }

    public static function getInstance(): PDO {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        if (!Installer::isConfigPresent() && getenv('PM_DB_HOST') === false) {
            throw new RuntimeException('System noch nicht installiert. Bitte install.php aufrufen.');
        }

        self::$pdo = Installer::createPdo(self::loadConfig(), true, true);
        return self::$pdo;
    }
}
