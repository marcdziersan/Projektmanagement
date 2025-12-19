const API = {
    async request(url, method = 'GET', body = null) {
        const options = {
            method,
            headers: { 'Content-Type': 'application/json' }
        };
        if (body) options.body = JSON.stringify(body);

        try {
            const res = await fetch(url, options);
            if (!res.ok) {
                if (res.status === 401) window.location.reload(); // Force relogin
                throw new Error('API Request failed');
            }
            return await res.json();
        } catch (err) {
            console.error(err);
            return null;
        }
    },

    login: (username, password) => API.request('api/login.php', 'POST', { username, password }),
    register: (username, password) => API.request('api/register.php', 'POST', { username, password }),
    logout: () => API.request('api/logout.php', 'POST'),
    getTasks: (start, end) => API.request(`api/get_tasks.php?start=${start}&end=${end}`),
    getTask: (id) => API.request(`api/get_tasks.php?id=${id}`),
    getTasksByProject: (projectId) => API.request(`api/get_tasks.php?project_id=${projectId}`),
    saveTask: (task) => API.request('api/save_task.php', 'POST', task),
    deleteTask: (id) => API.request('api/delete_task.php', 'POST', { id }),
    getProjects: () => API.request('api/get_projects.php'),
    saveProject: (project) => API.request('api/save_project.php', 'POST', project),
    getUsers: () => API.request('api/get_users.php'),
    timeAction: (action, taskId) => API.request('api/time_action.php', 'POST', { action, task_id: taskId }),
    getAuditLogs: () => API.request('api/get_audit_logs.php'),
};
