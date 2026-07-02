window.App = {
    projects: [],
    users: [],
    currentTask: null,
    currentProject: null,
    currentView: 'calendar',
    timerInterval: null,

    async init() {
        if (document.querySelector('.login-view')) {
            this.bindLogin();
            return;
        }

        this.bindApp();
        await this.loadDependencies();
        Calendar.init();
    },

    escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    },

    escapeAttr(value) {
        return this.escapeHtml(value);
    },

    formatStatus(status) {
        const labels = {
            new: 'Neu',
            in_progress: 'In Bearbeitung',
            completed_success: 'Erfolgreich abgeschlossen',
            completed_fail: 'Gescheitert',
            completed: 'Abgeschlossen',
            deleted: 'Gelöscht'
        };
        return labels[status] || status || 'Unbekannt';
    },

    formatProjectStatus(status) {
        const labels = {
            active: 'Aktiv',
            completed: 'Abgeschlossen',
            deleted: 'Gelöscht'
        };
        return labels[status] || status || 'Unbekannt';
    },

    formatLocalDate(date) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    },

    formatLocalTime(date) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
        const h = String(date.getHours()).padStart(2, '0');
        const m = String(date.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    },

    splitSqlDateTime(value) {
        if (!value) return { date: '', time: '' };
        const normalized = String(value).trim().replace('T', ' ');
        const parts = normalized.split(/\s+/);
        const date = parts[0] || '';
        let time = parts[1] || '';
        if (time.length >= 5) time = time.slice(0, 5);
        if (!time) time = '00:00';
        return { date, time };
    },

    setTaskDateTimeFields(prefix, value, fallbackDate = null, fallbackTime = '') {
        const dateField = document.getElementById(`${prefix}Date`);
        const timeField = document.getElementById(`${prefix}Time`);
        if (!dateField || !timeField) return;

        if (value) {
            const split = this.splitSqlDateTime(value);
            dateField.value = split.date || '';
            timeField.value = split.time || fallbackTime || '00:00';
            return;
        }

        if (fallbackDate instanceof Date && !Number.isNaN(fallbackDate.getTime())) {
            dateField.value = this.formatLocalDate(fallbackDate);
            timeField.value = this.formatLocalTime(fallbackDate) || fallbackTime || '00:00';
            return;
        }

        dateField.value = '';
        timeField.value = fallbackTime || '00:00';
    },

    getTaskDateTimeValue(prefix) {
        const date = document.getElementById(`${prefix}Date`)?.value || '';
        const time = document.getElementById(`${prefix}Time`)?.value || '';
        if (!date) return null;
        return `${date} ${time || '00:00'}:00`;
    },

    bindLogin() {
        document.getElementById('showRegister')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
        });

        document.getElementById('showLogin')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginForm').classList.remove('hidden');
        });

        document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const user = document.getElementById('loginUser').value;
            const pass = document.getElementById('loginPass').value;
            const res = await API.login(user, pass);
            if (res && res.success) {
                window.location.reload();
            } else {
                document.getElementById('loginError').textContent = res.error || res.message || 'Login fehlgeschlagen.';
            }
        });

        document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const user = document.getElementById('regUser').value;
            const pass = document.getElementById('regPass').value;
            const pass2 = document.getElementById('regPass2').value;
            const err = document.getElementById('regError');

            err.textContent = '';
            if (pass !== pass2) {
                err.textContent = 'Passwörter stimmen nicht überein.';
                return;
            }

            const res = await API.register(user, pass);
            if (res && res.success) {
                alert('Registrierung erfolgreich. Bitte anmelden.');
                document.getElementById('registerForm').classList.add('hidden');
                document.getElementById('loginForm').classList.remove('hidden');
            } else {
                err.textContent = res.error || 'Fehler bei der Registrierung.';
            }
        });
    },

    bindApp() {
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.switchView(btn.dataset.view);
            });
        });

        const projHeader = document.querySelector('.projects-list-container .section-header');
        if (projHeader) {
            projHeader.style.cursor = 'pointer';
            projHeader.title = 'Klicken für Projektübersicht';
            projHeader.addEventListener('click', (e) => {
                if (e.target.closest('#btnAddProject')) return;
                this.switchView('projects');
            });
        }

        document.getElementById('btnLogout')?.addEventListener('click', async () => {
            await API.logout();
            window.location.reload();
        });

        document.getElementById('prevPeriod')?.addEventListener('click', () => Calendar.prevMonth());
        document.getElementById('nextPeriod')?.addEventListener('click', () => Calendar.nextMonth());
        document.getElementById('btnToday')?.addEventListener('click', () => Calendar.gotoToday());

        document.getElementById('btnAddTask')?.addEventListener('click', () => this.openTaskModal());
        document.getElementById('taskForm')?.addEventListener('submit', (e) => this.handleTaskSave(e));
        document.getElementById('btnDeleteTask')?.addEventListener('click', () => this.handleTaskDelete());
        document.getElementById('btnStartTimer')?.addEventListener('click', () => this.handleTimer('start'));
        document.getElementById('btnStopTimer')?.addEventListener('click', () => this.handleTimer('stop'));
        document.getElementById('btnCompleteSuccess')?.addEventListener('click', () => this.completeTask('completed_success'));
        document.getElementById('btnCompleteFail')?.addEventListener('click', () => this.completeTask('completed_fail'));

        document.getElementById('btnAddProject')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.openProjectModal();
        });
        document.getElementById('projectForm')?.addEventListener('submit', (e) => this.handleProjectSave(e));
        document.getElementById('btnDeleteProject')?.addEventListener('click', () => this.handleProjectDelete());

        document.addEventListener('click', (e) => {
            if (e.target.closest('.close-modal') || e.target.closest('.close-modal-btn')) {
                if (e.target.closest('#projectModal')) this.closeProjectModal();
                if (e.target.closest('#taskModal')) this.closeTaskModal();
            }
        });
    },

    async loadDependencies() {
        const [projects, users] = await Promise.all([API.getProjects(), API.getUsers()]);
        this.projects = Array.isArray(projects) ? projects : [];
        this.users = Array.isArray(users) ? users : [];
        this.renderSidebarProjects();
        this.populateSelects();
    },

    renderSidebarProjects() {
        const list = document.getElementById('sidebarProjectList');
        if (!list) return;
        list.innerHTML = '';

        this.projects.forEach(p => {
            const li = document.createElement('li');
            li.className = 'project-item';
            li.innerHTML = `
                <button type="button" class="project-link" title="Projekt öffnen">
                    <span class="dot" style="background-color: ${this.escapeAttr(p.color || '#3498db')}"></span>
                    <span>${this.escapeHtml(p.name)}</span>
                </button>
                <button type="button" class="btn-mini" title="Projekt bearbeiten"><i class="fa-solid fa-pen"></i></button>
            `;
            li.querySelector('.project-link')?.addEventListener('click', () => this.switchView(`project_${p.id}`));
            li.querySelector('.btn-mini')?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openProjectModalById(p.id);
            });
            list.appendChild(li);
        });
    },

    async switchView(viewName) {
        this.currentView = viewName;
        document.querySelectorAll('.view-container').forEach(el => el.style.display = 'none');
        const controls = document.querySelector('.view-controls');
        if (controls) controls.style.visibility = viewName === 'calendar' ? 'visible' : 'hidden';

        if (viewName === 'calendar') {
            document.getElementById('calendarView').style.display = 'flex';
            await Calendar.render();
            return;
        }

        if (viewName === 'projects') {
            this.showDynamicView('projectsOverview', '<h2>Projekte</h2><p>Lade Daten...</p>', async (container) => {
                container.innerHTML = await this.renderProjectsOverview();
            });
            return;
        }

        if (viewName === 'audit') {
            this.showDynamicView('auditView', '<h2>Verlauf</h2><p>Lade Daten...</p>', async (container) => {
                container.innerHTML = '<h2 class="view-title">Verlauf</h2><ul class="audit-list">' + await this.renderAuditList() + '</ul>';
            });
            return;
        }

        if (viewName === 'analytics') {
            this.showDynamicView('analyticsView', '<h2>Auswertung</h2><p>Lade Daten...</p>', async (container) => {
                container.innerHTML = '<h2 class="view-title">Auswertung</h2>' + await this.renderAnalytics();
            });
            return;
        }

        if (viewName.startsWith('project_')) {
            const projectId = viewName.split('_')[1];
            this.showDynamicView('projectView', '<h2>Aufgaben</h2><p>Lade Daten...</p>', async (container) => {
                container.innerHTML = await this.renderProjectTasks(projectId);
            });
        }
    },

    async refreshActiveView() {
        await this.loadDependencies();
        if (this.currentView && this.currentView.startsWith('project_')) {
            const projectId = this.currentView.split('_')[1];
            if (!this.projects.some(p => String(p.id) === String(projectId))) {
                await this.switchView('projects');
                return;
            }
        }
        await this.switchView(this.currentView || 'calendar');
    },

    showDynamicView(id, initialContent, populateFunc) {
        let view = document.getElementById(id);
        if (!view) {
            view = document.createElement('div');
            view.id = id;
            view.className = 'view-container glass-panel dynamic-view';
            document.querySelector('.main-content').appendChild(view);
        }
        view.innerHTML = initialContent;
        view.style.display = 'block';
        populateFunc(view);
    },

    async renderAuditList() {
        const logs = await API.getAuditLogs();
        if (!Array.isArray(logs) || logs.length === 0) return '<p>Keine Einträge.</p>';

        return logs.map(l => `
            <li class="audit-entry">
                <span class="audit-user">${this.escapeHtml(l.username || 'System')}</span>
                <span class="audit-time">(${this.escapeHtml(l.timestamp)})</span>:
                <span>${this.escapeHtml(l.action_type)}</span><br>
                <small>${l.new_value ? 'Details: ' + this.escapeHtml(String(l.new_value).substring(0, 120)) : ''}</small>
            </li>
        `).join('');
    },

    async renderProjectTasks(projectId) {
        const tasks = await API.getTasksByProject(projectId);
        const safeTasks = Array.isArray(tasks) ? tasks : [];
        const project = this.projects.find(p => String(p.id) === String(projectId)) || { id: projectId, name: 'Unbekannt', color: '#3498db' };

        let html = `
            <div class="view-toolbar">
                <div>
                    <h2 class="view-title"><span class="dot" style="background-color: ${this.escapeAttr(project.color || '#3498db')}"></span> ${this.escapeHtml(project.name)}</h2>
                    <p>${this.escapeHtml(project.description || 'Keine Beschreibung')}</p>
                </div>
                <div class="toolbar-actions">
                    <button class="btn-secondary" onclick="window.App.openProjectModalById(${Number(project.id)})"><i class="fa-solid fa-pen"></i> Bearbeiten</button>
                    <button class="btn-primary" onclick="window.App.openTaskModal(null, null, ${Number(project.id)})"><i class="fa-solid fa-plus"></i> Aufgabe</button>
                </div>
            </div>
            <div class="task-list">
        `;

        if (safeTasks.length === 0) {
            html += '<p>Keine Aufgaben in diesem Projekt.</p>';
        } else {
            safeTasks.forEach(t => {
                html += this.renderTaskListItem(t);
            });
        }
        html += '</div>';
        return html;
    },

    renderTaskListItem(t) {
        const isDoneSuccess = t.status === 'completed_success' || t.status === 'completed';
        const isDoneFail = t.status === 'completed_fail';
        const running = Number(t.is_timer_running) > 0;
        const hours = ((Number(t.total_time_seconds) || 0) / 3600).toFixed(2);
        let statusIcon = '';

        if (isDoneSuccess) {
            statusIcon = '<i class="fa-solid fa-check-circle status-success"></i>';
        } else if (isDoneFail) {
            statusIcon = '<i class="fa-solid fa-times-circle status-danger"></i>';
        } else if (running) {
            statusIcon = '<i class="fa-solid fa-clock fa-spin status-danger"></i>';
        }

        return `
            <div class="task-item status-row-${this.escapeAttr(t.status || 'new')}">
                <button type="button" class="task-main" onclick="window.App.openTaskModal({id: ${Number(t.id)}})">
                    <strong>${this.escapeHtml(t.title)}</strong> ${statusIcon}<br>
                    <small>${this.escapeHtml(this.formatStatus(t.status))} · ${this.escapeHtml(t.assignee_name || 'nicht zugewiesen')} · ${hours} h</small>
                </button>
                <div class="task-row-actions">
                    <button type="button" class="btn-secondary" onclick="window.App.openTaskModal({id: ${Number(t.id)}})"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="btn-danger" onclick="window.App.deleteTaskById(${Number(t.id)})"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
    },

    async renderProjectsOverview() {
        const start = `${Calendar.currentYear}-01-01 00:00:00`;
        const end = `${Calendar.currentYear}-12-31 23:59:59`;
        const tasks = await API.getTasks(start, end);
        const safeTasks = Array.isArray(tasks) ? tasks : [];

        let html = `
            <div class="view-toolbar">
                <div>
                    <h2 class="view-title">Projekte</h2>
                    <p>Projekt anlegen, bearbeiten, öffnen oder löschen.</p>
                </div>
                <button class="btn-primary" onclick="window.App.openProjectModal()"><i class="fa-solid fa-plus"></i> Neues Projekt</button>
            </div>
            <div class="project-grid">
        `;

        if (this.projects.length === 0) {
            html += '<p>Noch keine Projekte vorhanden.</p>';
        }

        this.projects.forEach(p => {
            const pTasks = safeTasks.filter(t => String(t.project_id) === String(p.id));
            const doneCount = pTasks.filter(t => t.status === 'completed_success' || t.status === 'completed_fail' || t.status === 'completed').length;
            const openCount = pTasks.length - doneCount;

            html += `
                <article class="project-card glass-panel" style="border-left-color: ${this.escapeAttr(p.color || '#3498db')};" onclick="window.App.switchView('project_${Number(p.id)}')">
                    <div class="project-card-head">
                        <h3>${this.escapeHtml(p.name)}</h3>
                        <span class="status-pill">${this.escapeHtml(this.formatProjectStatus(p.status))}</span>
                    </div>
                    <p>${this.escapeHtml(p.description || 'Keine Beschreibung')}</p>
                    <div class="project-stats">
                        <span><i class="fa-solid fa-list-check"></i> ${openCount} offen</span>
                        <span><i class="fa-solid fa-check"></i> ${doneCount} fertig</span>
                    </div>
                    <div class="project-actions" onclick="event.stopPropagation()">
                        <button class="btn-secondary" onclick="window.App.openProjectModalById(${Number(p.id)})"><i class="fa-solid fa-pen"></i> Bearbeiten</button>
                        <button class="btn-danger" onclick="window.App.deleteProjectById(${Number(p.id)})"><i class="fa-solid fa-trash"></i> Löschen</button>
                    </div>
                </article>
            `;
        });

        html += '</div>';
        return html;
    },

    async renderAnalytics() {
        const start = `${Calendar.currentYear}-01-01 00:00:00`;
        const end = `${Calendar.currentYear}-12-31 23:59:59`;
        const tasks = await API.getTasks(start, end);
        const safeTasks = Array.isArray(tasks) ? tasks : [];
        const projectStats = {};

        safeTasks.forEach(t => {
            const key = t.project_id || 'none';
            if (!projectStats[key]) {
                projectStats[key] = {
                    name: t.project_name || 'Ohne Projekt',
                    color: t.project_color || '#cccccc',
                    seconds: 0
                };
            }
            projectStats[key].seconds += Number(t.total_time_seconds) || 0;
        });

        if (Object.keys(projectStats).length === 0) return '<p>Keine Daten für dieses Jahr.</p>';

        let html = '<div class="stats-grid">';
        Object.values(projectStats).forEach(s => {
            const hours = (s.seconds / 3600).toFixed(1);
            html += `
                <div class="stat-card" style="border-left-color: ${this.escapeAttr(s.color)}">
                    <h3>${this.escapeHtml(s.name)}</h3>
                    <div class="stat-number">${hours} h</div>
                    <div>Gesamtzeit ${Calendar.currentYear}</div>
                </div>
            `;
        });
        html += '</div>';
        return html;
    },

    populateSelects() {
        const pSelect = document.getElementById('taskProject');
        const uSelect = document.getElementById('taskAssignee');
        if (!pSelect || !uSelect) return;

        pSelect.innerHTML = '<option value="">-- Projekt wählen --</option>';
        this.projects.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = p.name;
            pSelect.appendChild(option);
        });

        uSelect.innerHTML = '<option value="">-- Zuweisen an --</option>';
        this.users.forEach(u => {
            const option = document.createElement('option');
            option.value = u.id;
            option.textContent = u.username;
            uSelect.appendChild(option);
        });
    },

    async openTaskModal(task = null, defaultDate = null, defaultProjectId = null) {
        if (task) {
            const freshTasks = await API.getTask(task.id);
            this.currentTask = Array.isArray(freshTasks) && freshTasks.length > 0 ? freshTasks[0] : task;
        } else {
            this.currentTask = null;
        }

        const modal = document.getElementById('taskModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('taskForm');
        if (!modal || !title || !form) return;

        modal.classList.remove('hidden');
        title.textContent = this.currentTask ? 'Aufgabe bearbeiten' : 'Aufgabe anlegen';

        document.getElementById('taskTimerControls').classList.toggle('hidden', this.currentTask === null);
        document.getElementById('btnDeleteTask').classList.toggle('hidden', this.currentTask === null);

        const showCompletion = this.currentTask !== null;
        document.getElementById('btnCompleteSuccess').style.display = showCompletion ? 'inline-block' : 'none';
        document.getElementById('btnCompleteFail').style.display = showCompletion ? 'inline-block' : 'none';

        if (this.currentTask) {
            const t = this.currentTask;
            document.getElementById('taskId').value = t.id || '';
            document.getElementById('taskTitle').value = t.title || '';
            document.getElementById('taskDesc').value = t.description || '';
            document.getElementById('taskProject').value = t.project_id || '';
            document.getElementById('taskAssignee').value = t.assignee_id || '';
            document.getElementById('taskStatus').value = t.status === 'completed' ? 'completed_success' : (t.status || 'new');
            this.setTaskDateTimeFields('taskStart', t.start_date, null, '09:00');
            this.setTaskDateTimeFields('taskDue', t.due_date, null, '10:00');
            this.updateTimerUI(t);
        } else {
            form.reset();
            document.getElementById('taskId').value = '';
            document.getElementById('taskStatus').value = 'new';
            document.getElementById('taskProject').value = defaultProjectId || '';
            clearInterval(this.timerInterval);

            const startBase = defaultDate instanceof Date ? defaultDate : new Date();
            const roundedStart = new Date(startBase);
            roundedStart.setMinutes(Math.ceil((roundedStart.getMinutes() || 1) / 15) * 15, 0, 0);
            if (roundedStart.getMinutes() === 60) {
                roundedStart.setHours(roundedStart.getHours() + 1, 0, 0, 0);
            }
            const roundedDue = new Date(roundedStart.getTime() + 60 * 60 * 1000);
            this.setTaskDateTimeFields('taskStart', null, roundedStart, '09:00');
            this.setTaskDateTimeFields('taskDue', null, roundedDue, '10:00');
        }
    },

    closeTaskModal() {
        document.getElementById('taskModal')?.classList.add('hidden');
        this.currentTask = null;
        clearInterval(this.timerInterval);
    },

    async handleTaskSave(e) {
        e.preventDefault();
        const data = {
            id: document.getElementById('taskId').value,
            title: document.getElementById('taskTitle').value,
            description: document.getElementById('taskDesc').value,
            project_id: document.getElementById('taskProject').value,
            assignee_id: document.getElementById('taskAssignee').value,
            start_date: this.getTaskDateTimeValue('taskStart'),
            due_date: this.getTaskDateTimeValue('taskDue'),
            status: document.getElementById('taskStatus').value || 'new'
        };

        const res = await API.saveTask(data);
        if (res && res.success) {
            this.closeTaskModal();
            await this.refreshActiveView();
            return;
        }
        alert('Fehler beim Speichern: ' + (res?.error || 'Serverfehler'));
    },

    async handleTaskDelete() {
        if (!this.currentTask) return;
        await this.deleteTaskById(this.currentTask.id, true);
    },

    async deleteTaskById(id, closeModal = false) {
        if (!id || !confirm('Aufgabe wirklich löschen?')) return;
        const res = await API.deleteTask(id);
        if (!res?.success) {
            alert('Fehler beim Löschen: ' + (res?.error || 'Serverfehler'));
            return;
        }
        if (closeModal) this.closeTaskModal();
        await this.refreshActiveView();
    },

    openProjectModal(project = null) {
        this.currentProject = project || null;
        const modal = document.getElementById('projectModal');
        if (!modal) return;

        document.getElementById('projectModalTitle').textContent = project ? 'Projekt bearbeiten' : 'Projekt anlegen';
        document.getElementById('btnDeleteProject').classList.toggle('hidden', project === null);
        document.getElementById('projId').value = project?.id || '';
        document.getElementById('projName').value = project?.name || '';
        document.getElementById('projDesc').value = project?.description || '';
        document.getElementById('projColor').value = project?.color || '#3498db';
        document.getElementById('projStatus').value = project?.status && project.status !== 'deleted' ? project.status : 'active';

        modal.classList.remove('hidden');
        document.getElementById('projName').focus();
    },

    openProjectModalById(id) {
        const project = this.projects.find(p => String(p.id) === String(id));
        if (!project) {
            alert('Projekt nicht gefunden.');
            return;
        }
        this.openProjectModal(project);
    },

    closeProjectModal() {
        document.getElementById('projectModal')?.classList.add('hidden');
        this.currentProject = null;
    },

    async handleProjectSave(e) {
        e.preventDefault();
        const data = {
            id: document.getElementById('projId').value,
            name: document.getElementById('projName').value,
            description: document.getElementById('projDesc').value,
            color: document.getElementById('projColor').value,
            status: document.getElementById('projStatus').value || 'active'
        };

        if (!data.name.trim()) return;

        const res = await API.saveProject(data);
        if (res && res.success) {
            this.closeProjectModal();
            await this.refreshActiveView();
            return;
        }
        alert('Fehler beim Speichern des Projekts: ' + (res?.error || 'Serverfehler'));
    },

    async handleProjectDelete() {
        const id = document.getElementById('projId').value;
        if (!id) return;
        await this.deleteProjectById(id, true);
    },

    async deleteProjectById(id, closeModal = false) {
        if (!id || !confirm('Projekt wirklich löschen? Zugehörige Aufgaben werden ebenfalls weich gelöscht und offene Timer gestoppt.')) return;
        const res = await API.deleteProject(id);
        if (!res?.success) {
            alert('Fehler beim Löschen des Projekts: ' + (res?.error || 'Serverfehler'));
            return;
        }
        if (closeModal) this.closeProjectModal();
        if (this.currentView === `project_${id}`) {
            this.currentView = 'projects';
        }
        await this.refreshActiveView();
    },

    updateTimerUI(task) {
        const val = document.getElementById('timerValue');
        const startBtn = document.getElementById('btnStartTimer');
        const stopBtn = document.getElementById('btnStopTimer');
        if (!val || !startBtn || !stopBtn) return;

        let totalSeconds = Number(task.total_time_seconds) || 0;
        val.textContent = this.formatTime(totalSeconds);
        clearInterval(this.timerInterval);

        if (Number(task.is_timer_running) > 0) {
            startBtn.classList.add('hidden');
            stopBtn.classList.remove('hidden');
            this.timerInterval = setInterval(() => {
                totalSeconds++;
                val.textContent = this.formatTime(totalSeconds);
            }, 1000);
        } else {
            startBtn.classList.remove('hidden');
            stopBtn.classList.add('hidden');
        }
    },

    formatTime(seconds) {
        seconds = Number(seconds) || 0;
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    },

    async handleTimer(action) {
        if (!this.currentTask) return;
        const res = await API.timeAction(action, this.currentTask.id);
        if (!res?.success) {
            alert(res?.error || 'Timer-Aktion fehlgeschlagen.');
            return;
        }

        await Calendar.render();
        const freshTasks = await API.getTask(this.currentTask.id);
        if (Array.isArray(freshTasks) && freshTasks.length > 0) {
            this.currentTask = freshTasks[0];
            this.updateTimerUI(this.currentTask);
        }
    },

    async completeTask(status) {
        if (!this.currentTask || !confirm('Status ändern?')) return;

        if (Number(this.currentTask.is_timer_running) > 0) {
            await API.timeAction('stop', this.currentTask.id);
        }

        this.currentTask.status = status;
        document.getElementById('taskStatus').value = status;
        const res = await API.saveTask(this.currentTask);
        if (res && res.success) {
            this.closeTaskModal();
            await this.refreshActiveView();
            return;
        }
        alert('Fehler beim Speichern: ' + (res?.error || 'Serverfehler'));
    }
};

document.addEventListener('DOMContentLoaded', () => window.App.init());
