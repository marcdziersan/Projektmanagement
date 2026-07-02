<?php
/**
 * First-Run-Installer und Datenbank-Migrationen.
 *
 * Dieses Projekt soll ohne harte Zugangsdaten im Code ausgeliefert werden.
 * Der Installer erzeugt deshalb beim ersten Lauf eine lokale config.php und
 * initialisiert die MySQL/MariaDB-Tabellen.
 */
class Installer {
    public const CONFIG_FILE = __DIR__ . '/../config.php';
    public const SAMPLE_CONFIG_FILE = __DIR__ . '/../config.sample.php';

    public static function isConfigPresent(): bool {
        return is_file(self::CONFIG_FILE);
    }

    public static function loadConfig(): array {
        if (!self::isConfigPresent()) {
            return [];
        }

        $config = require self::CONFIG_FILE;
        return is_array($config) ? $config : [];
    }

    public static function normalizeSettings(array $input): array {
        $host = trim((string)($input['db_host'] ?? '127.0.0.1'));
        $port = trim((string)($input['db_port'] ?? '3306'));
        $name = trim((string)($input['db_name'] ?? 'projektmanagement'));
        $user = trim((string)($input['db_user'] ?? 'root'));
        $pass = (string)($input['db_pass'] ?? '');
        $charset = trim((string)($input['db_charset'] ?? 'utf8mb4'));
        $appName = trim((string)($input['app_name'] ?? 'IT Projektmanagement'));

        if ($host === '') {
            throw new InvalidArgumentException('Datenbank-Host fehlt.');
        }
        if (!preg_match('/^[0-9]{1,5}$/', $port) || (int)$port < 1 || (int)$port > 65535) {
            throw new InvalidArgumentException('Datenbank-Port ist ungültig.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Datenbankname darf nur Buchstaben, Zahlen und Unterstriche enthalten.');
        }
        if ($user === '') {
            throw new InvalidArgumentException('Datenbank-Benutzer fehlt.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
            throw new InvalidArgumentException('Charset ist ungültig.');
        }
        if ($appName === '') {
            $appName = 'IT Projektmanagement';
        }

        return [
            'app' => [
                'name' => $appName,
                'installed_at' => date('c'),
            ],
            'db' => [
                'host' => $host,
                'port' => (int)$port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass,
                'charset' => $charset,
            ],
        ];
    }

    public static function createPdo(array $settings, bool $withDatabase = true, bool $createDatabase = true): PDO {
        $db = $settings['db'] ?? [];
        $host = (string)($db['host'] ?? '127.0.0.1');
        $port = (int)($db['port'] ?? 3306);
        $name = (string)($db['name'] ?? 'projektmanagement');
        $user = (string)($db['user'] ?? 'root');
        $pass = (string)($db['pass'] ?? '');
        $charset = (string)($db['charset'] ?? 'utf8mb4');

        $baseDsn = "mysql:host={$host};port={$port};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (!$withDatabase) {
            return new PDO($baseDsn, $user, $pass, $options);
        }

        $serverPdo = new PDO($baseDsn, $user, $pass, $options);
        if ($createDatabase) {
            $serverPdo->exec(
                'CREATE DATABASE IF NOT EXISTS ' . self::quoteIdentifier($name) .
                ' CHARACTER SET ' . self::safeIdentifier($charset) .
                ' COLLATE ' . self::safeIdentifier($charset . '_unicode_ci')
            );
        }

        return new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", $user, $pass, $options);
    }

    public static function writeConfig(array $settings): void {
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * Lokale Konfiguration.\n";
        $content .= " * Diese Datei wird vom First-Run-Installer erzeugt und sollte nicht veröffentlicht werden.\n";
        $content .= " */\n";
        $content .= 'return ' . var_export($settings, true) . ";\n";

        $result = @file_put_contents(self::CONFIG_FILE, $content, LOCK_EX);
        if ($result === false) {
            throw new RuntimeException('config.php konnte nicht geschrieben werden. Prüfe die Schreibrechte im Projektordner.');
        }
    }

    public static function writeSampleConfigIfMissing(): void {
        if (is_file(self::SAMPLE_CONFIG_FILE)) {
            return;
        }

        $sample = [
            'app' => [
                'name' => 'IT Projektmanagement',
                'installed_at' => null,
            ],
            'db' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'name' => 'projektmanagement',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4',
            ],
        ];

        $content = "<?php\nreturn " . var_export($sample, true) . ";\n";
        @file_put_contents(self::SAMPLE_CONFIG_FILE, $content, LOCK_EX);
    }

    public static function runMigrations(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            color VARCHAR(7) DEFAULT '#3498db',
            status ENUM('active', 'completed', 'deleted') DEFAULT 'active',
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_projects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            status ENUM('new', 'in_progress', 'completed', 'completed_success', 'completed_fail', 'deleted') DEFAULT 'new',
            assignee_id INT NULL,
            start_date DATETIME NULL,
            due_date DATETIME NULL,
            completed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
            CONSTRAINT fk_tasks_assignee FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS time_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NULL,
            CONSTRAINT fk_time_logs_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            CONSTRAINT fk_time_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action_type VARCHAR(50) NOT NULL,
            entity_id INT NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::migrateLegacySchema($pdo);
        self::addIndexIfMissing($pdo, 'projects', 'idx_projects_status', 'CREATE INDEX idx_projects_status ON projects(status)');
        self::addIndexIfMissing($pdo, 'tasks', 'idx_tasks_project_status', 'CREATE INDEX idx_tasks_project_status ON tasks(project_id, status)');
        self::addIndexIfMissing($pdo, 'tasks', 'idx_tasks_dates', 'CREATE INDEX idx_tasks_dates ON tasks(start_date, due_date)');
        self::addIndexIfMissing($pdo, 'time_logs', 'idx_time_logs_running', 'CREATE INDEX idx_time_logs_running ON time_logs(user_id, task_id, end_time)');
        self::addIndexIfMissing($pdo, 'audit_logs', 'idx_audit_logs_timestamp', 'CREATE INDEX idx_audit_logs_timestamp ON audit_logs(timestamp)');
    }

    public static function createFirstAdmin(PDO $pdo, string $username, string $password): int {
        $username = trim($username);
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new InvalidArgumentException('Admin-Benutzername muss zwischen 3 und 50 Zeichen haben.');
        }
        if (!preg_match('/^[a-zA-Z0-9_.@-]+$/', $username)) {
            throw new InvalidArgumentException('Admin-Benutzername enthält ungültige Zeichen.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Admin-Passwort muss mindestens 8 Zeichen haben.');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Dieser Benutzername existiert bereits.');
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT)]);
        return (int)$pdo->lastInsertId();
    }

    public static function usersCount(PDO $pdo): int {
        if (!self::tableExists($pdo, 'users')) {
            return 0;
        }
        return (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function isFullyInstalled(): bool {
        if (!self::isConfigPresent()) {
            return false;
        }

        try {
            require_once __DIR__ . '/Database.php';
            $pdo = Database::getInstance();
            return self::tableExists($pdo, 'users') && self::usersCount($pdo) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function migrateLegacySchema(PDO $pdo): void {
        if (!self::columnExists($pdo, 'tasks', 'completed_at')) {
            $pdo->exec('ALTER TABLE tasks ADD COLUMN completed_at DATETIME NULL AFTER due_date');
        }

        // Status-Enum korrigieren: Frontend nutzt completed_success/completed_fail.
        $pdo->exec("ALTER TABLE tasks MODIFY status ENUM('new', 'in_progress', 'completed', 'completed_success', 'completed_fail', 'deleted') DEFAULT 'new'");
        $pdo->exec("UPDATE tasks SET status = 'completed_success' WHERE status = 'completed'");
    }

    private static function tableExists(PDO $pdo, string $table): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool {
        if (!self::tableExists($pdo, $table)) {
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function indexExists(PDO $pdo, string $table, string $index): bool {
        if (!self::tableExists($pdo, $table)) {
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function addIndexIfMissing(PDO $pdo, string $table, string $index, string $sql): void {
        if (!self::indexExists($pdo, $table, $index)) {
            $pdo->exec($sql);
        }
    }

    private static function quoteIdentifier(string $identifier): string {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private static function safeIdentifier(string $identifier): string {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Ungültiger SQL-Identifier.');
        }
        return $identifier;
    }
}
