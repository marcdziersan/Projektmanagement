const API = {
    async request(url, method = 'GET', body = null) {
        const options = {
            method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' }
        };
        if (body !== null) {
            options.body = JSON.stringify(body);
        }

        try {
            const res = await fetch(url, options);
            const text = await res.text();
            const data = text ? JSON.parse(text) : {};

            if (!res.ok) {
                if (res.status === 401) window.location.reload();
                return data || { success: false, error: 'API-Fehler' };
            }
            return data;
        } catch (err) {
            console.error(err);
            return { success: false, error: err.message || 'Netzwerkfehler' };
        }
    },

    query(params) {
        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                query.set(key, value);
            }
        });
        const result = query.toString();
        return result ? `?${result}` : '';
    },

    login: (username, password) => API.request('api/login.php', 'POST', { username, password }),
    register: (username, password) => API.request('api/register.php', 'POST', { username, password }),
    logout: () => API.request('api/logout.php', 'POST'),
    getTasks: (start, end) => API.request(`api/get_tasks.php${API.query({ start, end })}`),
    getTask: (id) => API.request(`api/get_tasks.php${API.query({ id })}`),
    getTasksByProject: (projectId) => API.request(`api/get_tasks.php${API.query({ project_id: projectId })}`),
    saveTask: (task) => API.request('api/save_task.php', 'POST', task),
    deleteTask: (id) => API.request('api/delete_task.php', 'POST', { id }),
    getProjects: () => API.request('api/get_projects.php'),
    saveProject: (project) => API.request('api/save_project.php', 'POST', project),
    deleteProject: (id) => API.request('api/delete_project.php', 'POST', { id }),
    getUsers: () => API.request('api/get_users.php'),
    timeAction: (action, taskId) => API.request('api/time_action.php', 'POST', { action, task_id: taskId }),
    getAuditLogs: () => API.request('api/get_audit_logs.php')
};
