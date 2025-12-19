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

        // Calculate start/end for API
        // Get first day of month
        const firstDayIdx = (date.getDay() + 6) % 7; // Mon=0, Sun=6
        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

        // Fetch tasks
        const startDateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-01`;
        const endDateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${daysInMonth}`;

        // Actually, to be safe, fetch a bit more for overlap? 
        // Let's just fetch the whole month range.

        const tasks = await API.getTasks(`${startDateStr} 00:00:00`, `${endDateStr} 23:59:59`);

        // Create blank cells for prev month offset
        for (let i = 0; i < firstDayIdx; i++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day other-month';
            grid.appendChild(cell);
        }

        const today = new Date();

        // Create days
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

            // Interaction: Click empty space to add task
            cell.addEventListener('click', (e) => {
                if (e.target === cell || e.target === dayNum) {
                    App.openTaskModal(null, new Date(this.currentYear, this.currentMonth, d, 9, 0));
                }
            });

            // Find tasks for this day
            // Simple check: start date is this day OR it spans this day.
            // Requirement: "alle aufgaben anzeigt... status dazu"
            // Let's simplified check: Start Date is on this day.
            // Or if spanning, show on all days? For simplicity in monthly view, showing on Start Date is common.
            // But user said "projekte... start drücken...".
            // Let's show on Start Date.

            const currentDayStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

            const dayTasks = tasks.filter(t => t.start_date.startsWith(currentDayStr));

            dayTasks.forEach(task => {
                const card = document.createElement('div');
                card.className = `task-card status-${task.status}`;

                // Active timer indicator
                let timerIcon = '';
                if (task.is_timer_running == 1) {
                    timerIcon = ' <i class="fa-solid fa-clock fa-spin" style="color: #ef4444;"></i>';
                    card.classList.add('timer-running'); // for optional CSS styling
                }

                card.innerHTML = `${task.title}${timerIcon}`;
                card.title = `${task.title}\nStatus: ${task.status}\nAssignee: ${task.assignee_name}`;

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
