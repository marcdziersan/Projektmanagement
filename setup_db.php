<?php
require_once __DIR__ . '/logik/Database.php';
require_once __DIR__ . '/logik/Installer.php';

try {
    $pdo = Database::getInstance();
    Installer::runMigrations($pdo);
    Installer::writeSampleConfigIfMissing();

    $userCount = Installer::usersCount($pdo);
    echo 'Tabellen wurden initialisiert und Migrationen ausgeführt.<br>';
    if ($userCount === 0) {
        echo 'Es existiert noch kein Nutzer. Bitte install.php öffnen und den ersten Admin anlegen.';
    } else {
        echo 'Vorhandene Nutzer: ' . (int)$userCount;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Setup fehlgeschlagen: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
