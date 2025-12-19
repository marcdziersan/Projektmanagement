<?php
require_once 'logik/Auth.php';
$isLoggedIn = Auth::isLoggedIn();
$user = $isLoggedIn ? ['username' => $_SESSION['username'], 'role' => $_SESSION['role']] : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Projektmanagement</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?= $isLoggedIn ? 'app-view' : 'login-view' ?>">

<?php if (!$isLoggedIn): ?>
    <div class="login-container glass-panel">
        <h1><i class="fa-solid fa-layer-group"></i> IT Management</h1>
        
        <!-- Login Form -->
        <form id="loginForm">
            <h2 style="font-size: 1.2rem; margin-bottom: 1rem; color: #94a3b8;">Anmelden</h2>
            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" id="loginUser" required placeholder="admin">
            </div>
            <div class="form-group">
                <label>Passwort</label>
                <input type="password" id="loginPass" required placeholder="******">
            </div>
            <button type="submit" class="btn-primary">Anmelden</button>
            <p id="loginError" class="error-msg"></p>
            <p style="margin-top: 1rem; font-size: 0.9rem;">Noch kein Konto? <a href="#" id="showRegister" style="color: var(--primary);">Registrieren</a></p>
        </form>

        <!-- Register Form (Hidden) -->
        <form id="registerForm" class="hidden">
            <h2 style="font-size: 1.2rem; margin-bottom: 1rem; color: #94a3b8;">Registrieren</h2>
            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" id="regUser" required>
            </div>
            <div class="form-group">
                <label>Passwort</label>
                <input type="password" id="regPass" required>
            </div>
             <div class="form-group">
                <label>Passwort wiederholen</label>
                <input type="password" id="regPass2" required>
            </div>
            <button type="submit" class="btn-success">Registrieren</button>
            <p id="regError" class="error-msg"></p>
            <p style="margin-top: 1rem; font-size: 0.9rem;">Bereits ein Konto? <a href="#" id="showLogin" style="color: var(--primary);">Anmelden</a></p>
        </form>
    </div>
<?php else: ?>
    <!-- App Layout -->
    <div class="app-layout">
        <aside class="sidebar glass-panel">
            <div class="brand">
                <i class="fa-solid fa-layer-group"></i> IT Manager
            </div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                <div class="user-info">
                    <span class="name"><?= htmlspecialchars($user['username']) ?></span>
                    <span class="role"><?= htmlspecialchars($user['role']) ?></span>
                </div>
            </div>
            
            <nav class="main-nav">
                <button class="nav-btn active" data-view="calendar"><i class="fa-solid fa-calendar-days"></i> Kalender</button>
                <button class="nav-btn" data-view="projects"><i class="fa-solid fa-folder-open"></i> Projekte</button>
                <button class="nav-btn" data-view="analytics"><i class="fa-solid fa-chart-pie"></i> Auswertung</button>
                <button class="nav-btn" data-view="audit"><i class="fa-solid fa-clock-rotate-left"></i> Verlauf</button>
            </nav>

            <div class="projects-list-container">
                <div class="section-header">
                    <span>Projekte</span>
                    <button id="btnAddProject" class="btn-icon"><i class="fa-solid fa-plus"></i></button>
                </div>
                <ul id="sidebarProjectList" class="project-list">
                    <!-- Loaded via JS -->
                </ul>
            </div>

            <button id="btnLogout" class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Abmelden</button>
        </aside>

        <main class="main-content">
            <header class="top-bar glass-panel">
                <div class="view-controls">
                    <button class="btn-secondary" id="prevPeriod"><i class="fa-solid fa-chevron-left"></i></button>
                    <h2 id="currentPeriodLabel">Dezember 2025</h2>
                    <button class="btn-secondary" id="nextPeriod"><i class="fa-solid fa-chevron-right"></i></button>
                    <button class="btn-text" id="btnToday">Heute</button>
                </div>
                <div class="actions">
                    <button class="btn-primary" id="btnAddTask"><i class="fa-solid fa-plus"></i> Neue Aufgabe</button>
                </div>
            </header>

            <div id="calendarView" class="view-container active">
                <div class="calendar-header">
                    <!-- Weekdays -->
                    <div>Mo</div><div>Di</div><div>Mi</div><div>Do</div><div>Fr</div><div>Sa</div><div>So</div>
                </div>
                <div id="calendarGrid" class="calendar-grid">
                    <!-- JS generated -->
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="taskModal" class="modal hidden">
        <div class="modal-content glass-panel">
            <span class="close-modal">&times;</span>
            <h2 id="modalTitle">Aufgabe erstellen</h2>
            <form id="taskForm">
                <input type="hidden" id="taskId">
                <div class="form-group">
                    <label>Titel</label>
                    <input type="text" id="taskTitle" required>
                </div>
                <div class="form-group">
                    <label>Projekt</label>
                    <select id="taskProject" required></select>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Start</label>
                        <input type="datetime-local" id="taskStart" required>
                    </div>
                    <div class="form-group">
                        <label>Fällig</label>
                        <input type="datetime-local" id="taskDue" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Zugewiesen an</label>
                    <select id="taskAssignee"></select>
                </div>
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea id="taskDesc" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-danger hidden" id="btnDeleteTask">Löschen</button>
                    <!-- Completion Buttons -->
                    <button type="button" id="btnCompleteSuccess" class="btn-primary" style="background: var(--success); display: none;">Erfolg</button>
                    <button type="button" id="btnCompleteFail" class="btn-danger" style="display: none;">Gescheitert</button>
                    
                    <button type="button" class="btn-secondary close-modal-btn">Abbrechen</button>
                    <button type="submit" class="btn-primary">Speichern</button>
                </div>
            </form>
             <!-- Task Actions (Timer) -->
            <div id="taskTimerControls" class="task-timer-controls hidden">
                <hr>
                <div class="timer-display">
                    <span id="timerValue">00:00:00</span>
                    <button id="btnStartTimer" class="btn-success"><i class="fa-solid fa-play"></i> Start</button>
                    <button id="btnStopTimer" class="btn-danger hidden"><i class="fa-solid fa-stop"></i> Stopp</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Modal -->
    <div id="projectModal" class="modal hidden">
        <div class="modal-content glass-panel">
            <span class="close-modal">&times;</span>
            <h2>Neues Projekt</h2>
            <form id="projectForm">
                <div class="form-group">
                    <label>Projektname</label>
                    <input type="text" id="projName" required>
                </div>
                <div class="form-group">
                    <label>Farbe</label>
                    <input type="color" id="projColor" value="#3498db" style="width: 100%; height: 40px; border: none; background: none;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary close-modal-btn">Abbrechen</button>
                    <button type="submit" class="btn-primary">Erstellen</button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>
    <script src="js/api.js"></script>
    <script src="js/calendar.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
