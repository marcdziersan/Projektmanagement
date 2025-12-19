window.App = {
    projects: [],
    users: [],
    currentTask: null,
    timerInterval: null,

    async init() {
        console.log('App initializing...');
        if (document.querySelector('.login-view')) {
            this.bindLogin();
        } else {
            this.bindApp();
            await this.loadDependencies();
            Calendar.init(); // Requires calendar.js loaded
        }
        console.log('App initialized');
    },

    bindLogin() {
        // Toggle forms
        const showRegister = document.getElementById('showRegister');
        if (showRegister) {
            showRegister.addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('loginForm').classList.add('hidden');
                document.getElementById('registerForm').classList.remove('hidden');
            });
        }

        const showLogin = document.getElementById('showLogin');
        if (showLogin) {
            showLogin.addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('registerForm').classList.add('hidden');
                document.getElementById('loginForm').classList.remove('hidden');
            });
        }

        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const user = document.getElementById('loginUser').value;
                const pass = document.getElementById('loginPass').value;
                const res = await API.login(user, pass);
                if (res && res.success) {
                    window.location.reload();
                } else {
                    document.getElementById('loginError').textContent = res.message || 'Login fehlgeschlagen.';
                }
            });
        }

        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const user = document.getElementById('regUser').value;
                const pass = document.getElementById('regPass').value;
                const pass2 = document.getElementById('regPass2').value;
                const err = document.getElementById('regError');

                if (pass !== pass2) {
                    err.textContent = 'Passwörter stimmen nicht überein.';
                    return;
                }

                const res = await API.register(user, pass);
                if (res && res.success) {
                    alert('Registrierung erfolgreich! Bitte anmelden.');
                    document.getElementById('registerForm').classList.add('hidden');
                    document.getElementById('loginForm').classList.remove('hidden');
                } else {
                    err.textContent = res.error || 'Fehler bei der Registrierung.';
                }
            });
        }
    },

    bindApp() {
        // Navigation Logic
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.switchView(btn.dataset.view);
            });
        });

        // Project Sidebar Header Click
        const projHeader = document.querySelector('.projects-list-container .section-header');
        if (projHeader) {
            projHeader.style.cursor = 'pointer';
            projHeader.addEventListener('click', () => this.switchView('projects'));
            projHeader.title = "Klicken für Projektübersicht";
        }

        const btnLogout = document.getElementById('btnLogout');
        if (btnLogout) {
            btnLogout.addEventListener('click', async () => {
                await API.logout();
                window.location.reload();
            });
        }

        // Calendar Controls
        document.getElementById('prevPeriod')?.addEventListener('click', () => Calendar.prevMonth());
        document.getElementById('nextPeriod')?.addEventListener('click', () => Calendar.nextMonth());
        document.getElementById('btnToday')?.addEventListener('click', () => Calendar.gotoToday());

        // Modals
        document.getElementById('btnAddTask')?.addEventListener('click', () => this.openTaskModal());
        document.getElementById('taskForm')?.addEventListener('submit', (e) => this.handleTaskSave(e));

        // Use delegates or ensure elements exist
        document.addEventListener('click', (e) => {
            if (e.target.closest('.close-modal') || e.target.closest('.close-modal-btn')) {
                this.closeTaskModal();
            }
        });

        document.getElementById('btnDeleteTask')?.addEventListener('click', () => this.handleTaskDelete());

        document.getElementById('btnStartTimer')?.addEventListener('click', () => this.handleTimer('start'));
        document.getElementById('btnStopTimer')?.addEventListener('click', () => this.handleTimer('stop'));

        // Completion buttons
        const btnSuccess = document.getElementById('btnCompleteSuccess');
        const btnFail = document.getElementById('btnCompleteFail');

        // Robust binding + logging
        if (btnSuccess) {
            btnSuccess.onclick = () => window.App.completeTask('completed_success');
        }
        if (btnFail) {
            btnFail.onclick = () => window.App.completeTask('completed_fail');
        }

        // Projects
        document.getElementById('btnAddProject')?.addEventListener('click', () => this.openProjectModal());
        document.getElementById('projectForm')?.addEventListener('submit', (e) => this.handleProjectSave(e));
    },

    async loadDependencies() {
        this.projects = await API.getProjects();
        this.users = await API.getUsers();
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
            if (p.status === 'deleted') li.style.textDecoration = 'line-through';
            li.innerHTML = `<span class="dot" style="background-color: ${p.color}"></span> ${p.name}`;
            li.style.cursor = 'pointer';
            li.addEventListener('click', () => {
                this.switchView(`project_${p.id}`);
            });
            list.appendChild(li);
        });
    },

    async switchView(viewName) {
        document.querySelectorAll('.view-container').forEach(el => el.style.display = 'none');
        const controls = document.querySelector('.view-controls');
        if (controls) controls.style.visibility = viewName === 'calendar' ? 'visible' : 'hidden';

        if (viewName === 'calendar') {
            document.getElementById('calendarView').style.display = 'flex';
            Calendar.render();
        } else if (viewName === 'projects') {
            this.showDynamicView('projectsOverview', '<h2>Projekte Übersicht</h2>Loading...', async (container) => {
                container.innerHTML = await this.renderProjectsOverview();
            });
        } else if (viewName === 'audit') {
            this.showDynamicView('auditView', '<h2>Aktivitäten Verlauf</h2>Loading...', async (container) => {
                container.innerHTML = '<h2>Aktivitäten Verlauf</h2><ul class="audit-list">' + await this.renderAuditList() + '</ul>';
            });
        } else if (viewName === 'analytics') {
            this.showDynamicView('analyticsView', '<h2>Jahresauswertung</h2>Loading...', async (container) => {
                const html = await this.renderAnalytics();
                container.innerHTML = '<h2>Jahresauswertung</h2>' + html;
            });
        } else if (viewName.startsWith('project_')) {
            const projectId = viewName.split('_')[1];
            this.showDynamicView('projectView', '<h2>Projekt Aufgaben</h2>Loading...', async (container) => {
                const html = await this.renderProjectTasks(projectId);
                container.innerHTML = html;
            });
        }
    },

    showDynamicView(id, initialContent, populateFunc) {
        let view = document.getElementById(id);
        if (!view) {
            view = document.createElement('div');
            view.id = id;
            view.className = 'view-container glass-panel';
            view.style.padding = '2rem';
            view.style.overflowY = 'auto'; // allow scrolling
            document.querySelector('.main-content').appendChild(view);
        }
        view.innerHTML = initialContent;
        view.style.display = 'block';
        populateFunc(view);
    },

    async renderAuditList() {
        const logs = await API.getAuditLogs();
        if (!logs || logs.length === 0) return '<p>Keine Einträge.</p>';
        return logs.map(l => `
            <li style="margin-bottom: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem; list-style:none;">
                <span style="color: var(--primary); font-weight: bold;">${l.username}</span> 
                <span style="color: #94a3b8; font-size: 0.8em;">(${l.timestamp})</span>: 
                <span style="color: var(--text-color);">${l.action_type}</span> <br>
                <small style="color: #64748b;">${l.new_value ? 'Details: ' + l.new_value.substring(0, 100) : ''}</small>
            </li>
        `).join('');
    },

    async renderProjectTasks(projectId) {
        const tasks = await API.getTasksByProject(projectId);
        const project = this.projects.find(p => p.id == projectId) || { name: 'Unbekannt' };

        let html = `<h2>Aufgaben: ${project.name}</h2>`;
        html += '<div class="task-list">';

        if (tasks.length === 0) {
            html += '<p>Keine Aufgaben in diesem Projekt.</p>';
        } else {
            tasks.forEach(t => {
                let statusIcon = '';
                let borderStyle = 'border-bottom: 1px solid rgba(255,255,255,0.1);';
                let bgStyle = '';

                if (t.status === 'completed_success') {
                    statusIcon = '<i class="fa-solid fa-check-circle" style="color: var(--success);"></i>';
                    bgStyle = 'background: rgba(46, 204, 113, 0.1); border-left: 4px solid var(--success);';
                } else if (t.status === 'completed_fail') {
                    statusIcon = '<i class="fa-solid fa-times-circle" style="color: var(--danger);"></i>';
                    bgStyle = 'background: rgba(231, 76, 60, 0.1); border-left: 4px solid var(--danger);';
                } else if (t.is_timer_running == 1) {
                    statusIcon = '<i class="fa-solid fa-clock fa-spin" style="color: #ef4444;"></i>';
                }

                html += `
                    <div class="task-item" style="padding: 1rem; ${borderStyle} ${bgStyle} display: flex; justify-content: space-between; align-items: center; cursor: pointer; margin-bottom: 0.5rem; border-radius: 4px;" onclick="window.App.openTaskModal({id: ${t.id}})">
                        <div>
                            <strong>${t.title}</strong> ${statusIcon}<br>
                            <small>${t.status}</small>
                        </div>
                        <div>
                            ${t.total_time_seconds ? (parseInt(t.total_time_seconds) / 3600).toFixed(2) + 'h' : '0h'}
                        </div>
                    </div>
                `;
            });
        }
        html += '</div>';
        return html;
    },

    async renderProjectsOverview() {
        const tasks = await API.getTasks(`${Calendar.currentYear}-01-01 00:00:00`, `${Calendar.currentYear}-12-31 23:59:59`);

        let html = '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;"><h2>Alle Projekte</h2><button class="btn-primary" onclick="window.App.openProjectModal()"><i class="fa-solid fa-plus"></i> Neues Projekt</button></div>';
        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">';

        this.projects.forEach(p => {
            const pTasks = tasks.filter(t => t.project_id == p.id);
            const openCount = pTasks.filter(t => t.status !== 'completed_success' && t.status !== 'completed_fail').length;
            const doneCount = pTasks.length - openCount;

            html += `
                <div class="glass-panel" style="padding: 1.5rem; border-left: 5px solid ${p.color}; cursor: pointer; transition: transform 0.2s;" 
                     onclick="window.App.switchView('project_${p.id}')"
                     onmouseover="this.style.transform='translateY(-2px)'" 
                     onmouseout="this.style.transform='translateY(0)'">
                    <h3 style="margin-top: 0;">${p.name}</h3>
                    <p style="color: #94a3b8; font-size: 0.9rem;">${p.description || 'Keine Beschreibung'}</p>
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; font-size: 0.8rem;">
                        <span style="color: var(--warning);"><i class="fa-solid fa-list-check"></i> ${openCount} Offen</span>
                        <span style="color: var(--success);"><i class="fa-solid fa-check"></i> ${doneCount} Fertig</span>
                    </div>
                </div>
             `;
        });

        html += '</div>';
        return html;
    },

    async renderAnalytics() {
        const start = `${Calendar.currentYear}-01-01 00:00:00`;
        const end = `${Calendar.currentYear}-12-31 23:59:59`;
        const tasks = await API.getTasks(start, end);

        const projectStats = {};

        tasks.forEach(t => {
            if (!projectStats[t.project_id]) {
                projectStats[t.project_id] = {
                    name: t.project_name || 'Unbekannt',
                    color: t.project_color || '#ccc',
                    seconds: 0
                };
            }
            projectStats[t.project_id].seconds += (parseInt(t.total_time_seconds) || 0);
        });

        let html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">';

        for (const pid in projectStats) {
            const s = projectStats[pid];
            const hours = (s.seconds / 3600).toFixed(1);
            html += `
                <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; border-left: 4px solid ${s.color}">
                    <h3 style="margin-bottom: 0.5rem">${s.name}</h3>
                    <div style="font-size: 2rem; font-weight: bold;">${hours} h</div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Gesamtzeit ${Calendar.currentYear}</div>
                </div>
            `;
        }
        html += '</div>';

        if (Object.keys(projectStats).length === 0) return '<p>Keine Daten für dieses Jahr.</p>';
        return html;
    },

    populateSelects() {
        const pSelect = document.getElementById('taskProject');
        const uSelect = document.getElementById('taskAssignee');
        if (!pSelect || !uSelect) return;

        pSelect.innerHTML = '<option value="">-- Projekt wählen --</option>';
        this.projects.forEach(p => {
            pSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });

        uSelect.innerHTML = '<option value="">-- Zuweisen an --</option>';
        this.users.forEach(u => {
            uSelect.innerHTML += `<option value="${u.id}">${u.username}</option>`;
        });
    },

    async openTaskModal(task = null, defaultDate = null) {
        if (task) {
            const freshTasks = await API.getTask(task.id);
            if (freshTasks && freshTasks.length > 0) {
                this.currentTask = freshTasks[0];
            } else {
                this.currentTask = task;
            }
        } else {
            this.currentTask = null;
        }

        const modal = document.getElementById('taskModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('taskForm');
        if (!modal) return;

        modal.classList.remove('hidden');
        title.textContent = this.currentTask ? 'Aufgabe bearbeiten' : 'Neue Aufgabe';

        document.getElementById('taskTimerControls').classList.toggle('hidden', this.currentTask === null);
        document.getElementById('btnDeleteTask').classList.toggle('hidden', this.currentTask === null);

        const showCompletion = this.currentTask !== null;
        const btnSuccess = document.getElementById('btnCompleteSuccess');
        const btnFail = document.getElementById('btnCompleteFail');
        if (btnSuccess) btnSuccess.style.display = showCompletion ? 'inline-block' : 'none';
        if (btnFail) btnFail.style.display = showCompletion ? 'inline-block' : 'none';

        if (this.currentTask) {
            const t = this.currentTask;
            document.getElementById('taskId').value = t.id;
            document.getElementById('taskTitle').value = t.title;
            document.getElementById('taskDesc').value = t.description || '';
            document.getElementById('taskProject').value = t.project_id;
            document.getElementById('taskAssignee').value = t.assignee_id;
            document.getElementById('taskStart').value = t.start_date.replace(' ', 'T');
            document.getElementById('taskDue').value = t.due_date ? t.due_date.replace(' ', 'T') : '';

            this.updateTimerUI(t);
        } else {
            form.reset();
            document.getElementById('taskId').value = '';
            if (defaultDate) {
                const offset = defaultDate.getTimezoneOffset() * 60000;
                const localISOTime = (new Date(defaultDate - offset)).toISOString().slice(0, 16);
                document.getElementById('taskStart').value = localISOTime;
                const due = new Date(defaultDate.getTime() + 60 * 60 * 1000);
                const localISODue = (new Date(due - offset)).toISOString().slice(0, 16);
                document.getElementById('taskDue').value = localISODue;
            }
        }
    },

    closeTaskModal() {
        const modal = document.getElementById('taskModal');
        if (modal) modal.classList.add('hidden');
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
            start_date: document.getElementById('taskStart').value.replace('T', ' '),
            due_date: document.getElementById('taskDue').value.replace('T', ' '),
            status: this.currentTask ? this.currentTask.status : 'new'
        };

        const res = await API.saveTask(data);
        if (res && res.success) {
            this.closeTaskModal();
            Calendar.render();
        } else {
            alert('Fehler beim Speichern');
        }
    },

    async handleTaskDelete() {
        if (!this.currentTask || !confirm('Wirklich löschen?')) return;
        await API.deleteTask(this.currentTask.id);
        this.closeTaskModal();
        Calendar.render();
    },

    openProjectModal() {
        document.getElementById('projectModal').classList.remove('hidden');
        document.getElementById('projName').value = '';
        document.getElementById('projColor').value = '#3498db';
        document.getElementById('projName').focus();
    },

    async handleProjectSave(e) {
        e.preventDefault();
        const name = document.getElementById('projName').value;
        const color = document.getElementById('projColor').value;

        if (!name) return;

        const res = await API.saveProject({ name, color, description: '' });
        if (res && res.success) {
            document.getElementById('projectModal').classList.add('hidden');
            await this.loadDependencies();
        } else {
            alert('Fehler beim Speichern des Projekts');
        }
    },

    updateTimerUI(task) {
        const val = document.getElementById('timerValue');
        const startBtn = document.getElementById('btnStartTimer');
        const stopBtn = document.getElementById('btnStopTimer');

        let totalSeconds = task.total_time_seconds ? parseInt(task.total_time_seconds) : 0;
        val.textContent = this.formatTime(totalSeconds);

        clearInterval(this.timerInterval);

        if (task.is_timer_running == 1) {
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
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    },

    async handleTimer(action) {
        if (!this.currentTask) return;
        await API.timeAction(action, this.currentTask.id);

        await Calendar.render();

        const start = `${Calendar.currentYear}-${String(Calendar.currentMonth + 1).padStart(2, '0')}-01 00:00:00`;
        const end = `${Calendar.currentYear}-${String(Calendar.currentMonth + 1).padStart(2, '0')}-31 23:59:59`;
        const tasks = await API.getTasks(start, end);
        const updatedTask = tasks.find(t => t.id == this.currentTask.id);

        if (updatedTask) {
            this.currentTask = updatedTask;
            this.updateTimerUI(this.currentTask);
        }
    },

    async completeTask(status) {
        console.log('completeTask called with', status);
        if (!this.currentTask || !confirm('Status ändern?')) return;

        if (this.currentTask.is_timer_running == 1) {
            await API.timeAction('stop', this.currentTask.id);
        }

        this.currentTask.status = status;
        const res = await API.saveTask(this.currentTask);

        if (res && res.success) {
            this.closeTaskModal();
            Calendar.render();
            const currentView = document.querySelector('.view-container[style*="block"]');
            if (currentView && (currentView.id === 'projectView' || currentView.id === 'projectsOverview')) {
                if (currentView.id === 'projectView' && this.currentTask.project_id) {
                    this.switchView(`project_${this.currentTask.project_id}`);
                } else if (currentView.id === 'projectsOverview') {
                    this.switchView('projects');
                }
            }
        } else {
            alert('Fehler beim Speichern: ' + (res ? res.error : 'Serverfehler'));
            console.error('Save failed', res);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => window.App.init());
