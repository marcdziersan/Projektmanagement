const Calendar = {
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth(),

    init() {
        this.render();
    },

    nextMonth() {
        this.currentMonth++;
        if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear++;
        }
        this.render();
    },

    prevMonth() {
        this.currentMonth--;
        if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear--;
        }
        this.render();
    },

    gotoToday() {
        const now = new Date();
        this.currentYear = now.getFullYear();
        this.currentMonth = now.getMonth();
        this.render();
    },

    async render() {
        const grid = document.getElementById('calendarGrid');
        const label = document.getElementById('currentPeriodLabel');
        if (!grid || !label) return;

        grid.innerHTML = '';
        const date = new Date(this.currentYear, this.currentMonth, 1);
        const monthName = date.toLocaleString('de-DE', { month: 'long' });
        label.textContent = `${monthName} ${this.currentYear}`;

        const firstDayIdx = (date.getDay() + 6) % 7;
        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        const startDateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-01`;
        const endDateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${daysInMonth}`;
        const tasksResponse = await API.getTasks(`${startDateStr} 00:00:00`, `${endDateStr} 23:59:59`);
        const tasks = Array.isArray(tasksResponse) ? tasksResponse : [];

        for (let i = 0; i < firstDayIdx; i++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day other-month';
            grid.appendChild(cell);
        }

        const today = new Date();

        for (let d = 1; d <= daysInMonth; d++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day';

            const dayNum = document.createElement('div');
            dayNum.className = 'day-number';
            dayNum.textContent = d;

            if (today.getDate() === d && today.getMonth() === this.currentMonth && today.getFullYear() === this.currentYear) {
                dayNum.classList.add('today');
            }

            cell.appendChild(dayNum);
            cell.addEventListener('click', (e) => {
                if (e.target === cell || e.target === dayNum) {
                    App.openTaskModal(null, new Date(this.currentYear, this.currentMonth, d, 9, 0));
                }
            });

            const currentDayStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayTasks = tasks.filter(t => String(t.start_date || '').startsWith(currentDayStr));

            dayTasks.forEach(task => {
                const card = document.createElement('div');
                card.className = `task-card status-${task.status || 'new'}`;

                if (Number(task.is_timer_running) > 0) {
                    card.classList.add('timer-running');
                }

                const title = document.createElement('span');
                title.textContent = task.title || 'Ohne Titel';
                card.appendChild(title);

                if (Number(task.is_timer_running) > 0) {
                    const icon = document.createElement('i');
                    icon.className = 'fa-solid fa-clock fa-spin';
                    icon.style.color = '#ef4444';
                    icon.style.marginLeft = '0.35rem';
                    card.appendChild(icon);
                }

                card.title = `${task.title || 'Ohne Titel'}\nStatus: ${App.formatStatus(task.status)}\nZugewiesen: ${task.assignee_name || 'Nicht zugewiesen'}`;
                card.addEventListener('click', (e) => {
                    e.stopPropagation();
                    App.openTaskModal(task);
                });

                cell.appendChild(card);
            });

            grid.appendChild(cell);
        }
    }
};
