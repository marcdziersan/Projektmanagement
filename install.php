<?php
require_once __DIR__ . '/logik/Installer.php';

Installer::writeSampleConfigIfMissing();

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function posted(string $key, string $fallback = ''): string {
    return isset($_POST[$key]) ? (string)$_POST[$key] : $fallback;
}

$existingConfig = Installer::loadConfig();
$existingDb = $existingConfig['db'] ?? [];
$existingApp = $existingConfig['app'] ?? [];

$defaults = [
    'app_name' => (string)($existingApp['name'] ?? 'IT Projektmanagement'),
    'db_host' => (string)($existingDb['host'] ?? '127.0.0.1'),
    'db_port' => (string)($existingDb['port'] ?? '3306'),
    'db_name' => (string)($existingDb['name'] ?? 'projektmanagement'),
    'db_user' => (string)($existingDb['user'] ?? 'root'),
    'db_pass' => (string)($existingDb['pass'] ?? ''),
    'db_charset' => (string)($existingDb['charset'] ?? 'utf8mb4'),
    'admin_username' => 'admin',
    'admin_password' => '',
    'admin_password_confirm' => '',
];

$error = null;
$success = null;
$details = [];
$isInstalled = Installer::isFullyInstalled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isInstalled = false;

    try {
        $settings = Installer::normalizeSettings($_POST);
        $pdo = Installer::createPdo($settings, true, true);
        $details[] = 'Datenbankverbindung erfolgreich.';

        Installer::runMigrations($pdo);
        $details[] = 'Tabellen wurden erstellt beziehungsweise migriert.';

        $userCount = Installer::usersCount($pdo);
        if ($userCount === 0) {
            $adminUser = posted('admin_username');
            $adminPass = posted('admin_password');
            $adminPassConfirm = posted('admin_password_confirm');

            if ($adminPass !== $adminPassConfirm) {
                throw new InvalidArgumentException('Die Admin-Passwörter stimmen nicht überein.');
            }

            Installer::createFirstAdmin($pdo, $adminUser, $adminPass);
            $details[] = 'Erster Admin-Nutzer wurde angelegt.';
        } else {
            $details[] = 'Es existieren bereits Nutzer. Es wurde kein neuer Startnutzer angelegt.';
        }

        Installer::writeConfig($settings);
        $details[] = 'config.php wurde geschrieben.';
        $success = 'Installation abgeschlossen.';
        $isInstalled = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$values = [];
foreach ($defaults as $key => $value) {
    $values[$key] = posted($key, $value);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First-Run-Installer | IT Projektmanagement</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: rgba(15, 23, 42, 0.82);
            --line: rgba(148, 163, 184, 0.24);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --primary: #38bdf8;
            --success: #22c55e;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 34rem),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.14), transparent 30rem),
                var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem 1rem;
        }
        main {
            width: min(920px, 100%);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.35);
            padding: clamp(1.25rem, 3vw, 2rem);
            backdrop-filter: blur(16px);
        }
        h1 { margin: 0 0 .5rem; font-size: clamp(1.8rem, 4vw, 2.6rem); }
        h2 { margin: 2rem 0 1rem; font-size: 1.15rem; color: var(--primary); }
        p { color: var(--muted); line-height: 1.55; }
        form { margin-top: 1.5rem; }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: .4rem; color: #cbd5e1; font-size: .92rem; }
        input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(2, 6, 23, .55);
            color: var(--text);
            padding: .85rem .95rem;
            font-size: 1rem;
            outline: none;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(56,189,248,.18); }
        .hint { margin-top: .35rem; color: var(--muted); font-size: .82rem; }
        .box {
            border: 1px solid var(--line);
            background: rgba(2, 6, 23, .35);
            border-radius: 16px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .alert {
            border-radius: 14px;
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid var(--line);
        }
        .alert.error { border-color: rgba(239, 68, 68, .55); background: rgba(239, 68, 68, .12); color: #fecaca; }
        .alert.success { border-color: rgba(34, 197, 94, .55); background: rgba(34, 197, 94, .12); color: #bbf7d0; }
        ul { margin: .5rem 0 0; padding-left: 1.2rem; }
        li { margin: .35rem 0; }
        .actions { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
        button, .button {
            border: 0;
            border-radius: 999px;
            padding: .9rem 1.25rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        button { background: var(--primary); color: #082f49; }
        .button { background: rgba(148, 163, 184, .16); color: var(--text); border: 1px solid var(--line); }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1px solid var(--line);
            color: var(--muted);
            padding: .45rem .8rem;
            border-radius: 999px;
            font-size: .88rem;
        }
        code { color: #bae6fd; }
        @media (max-width: 720px) {
            body { padding: .75rem; }
            .grid { grid-template-columns: 1fr; }
            main { border-radius: 16px; }
        }
    </style>
</head>
<body>
<main>
    <span class="status-pill">First-Run-Installer</span>
    <h1>IT Projektmanagement installieren</h1>
    <p>Hier richtest du die Datenbankverbindung ein, lässt die Tabellen erstellen und legst beim ersten Start den Admin-Nutzer an.</p>

    <?php if ($success): ?>
        <div class="alert success">
            <strong><?= h($success) ?></strong>
            <?php if ($details): ?>
                <ul>
                    <?php foreach ($details as $detail): ?>
                        <li><?= h($detail) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="actions">
            <a class="button" href="index.php">Zur Anwendung</a>
        </div>
    <?php elseif ($isInstalled): ?>
        <div class="alert success">
            <strong>System ist bereits installiert.</strong>
            <p>Die Datei <code>config.php</code> existiert, die Datenbank ist erreichbar und mindestens ein Nutzer ist vorhanden.</p>
        </div>
        <div class="actions">
            <a class="button" href="index.php">Zur Anwendung</a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert error"><strong>Installation fehlgeschlagen:</strong> <?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <h2>1. Anwendung</h2>
            <div class="grid">
                <div class="full">
                    <label for="app_name">Anwendungsname</label>
                    <input id="app_name" name="app_name" value="<?= h($values['app_name']) ?>" required>
                </div>
            </div>

            <h2>2. Datenbank</h2>
            <div class="grid">
                <div>
                    <label for="db_host">Host</label>
                    <input id="db_host" name="db_host" value="<?= h($values['db_host']) ?>" required>
                    <div class="hint">XAMPP lokal meistens <code>127.0.0.1</code>.</div>
                </div>
                <div>
                    <label for="db_port">Port</label>
                    <input id="db_port" name="db_port" value="<?= h($values['db_port']) ?>" required inputmode="numeric">
                </div>
                <div>
                    <label for="db_name">Datenbankname</label>
                    <input id="db_name" name="db_name" value="<?= h($values['db_name']) ?>" required>
                    <div class="hint">Wird erstellt, wenn der DB-Nutzer die Rechte dazu hat.</div>
                </div>
                <div>
                    <label for="db_charset">Charset</label>
                    <input id="db_charset" name="db_charset" value="<?= h($values['db_charset']) ?>" required>
                </div>
                <div>
                    <label for="db_user">DB-Benutzer</label>
                    <input id="db_user" name="db_user" value="<?= h($values['db_user']) ?>" required>
                </div>
                <div>
                    <label for="db_pass">DB-Passwort</label>
                    <input id="db_pass" name="db_pass" type="password" value="<?= h($values['db_pass']) ?>">
                </div>
            </div>

            <h2>3. Erster Admin</h2>
            <div class="box">
                <p>Diese Daten werden nur verwendet, wenn in der Datenbank noch kein Nutzer existiert. Gibt es bereits Nutzer, überspringt der Installer diesen Schritt automatisch.</p>
                <div class="grid">
                    <div>
                        <label for="admin_username">Admin-Benutzername</label>
                        <input id="admin_username" name="admin_username" value="<?= h($values['admin_username']) ?>">
                    </div>
                    <div>
                        <label for="admin_password">Admin-Passwort</label>
                        <input id="admin_password" name="admin_password" type="password" value="<?= h($values['admin_password']) ?>">
                        <div class="hint">Mindestens 8 Zeichen.</div>
                    </div>
                    <div>
                        <label for="admin_password_confirm">Admin-Passwort wiederholen</label>
                        <input id="admin_password_confirm" name="admin_password_confirm" type="password" value="<?= h($values['admin_password_confirm']) ?>">
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="submit">Installation starten</button>
                <a class="button" href="config.sample.php">config.sample.php ansehen</a>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
