/**
 * KHO DỮ LIỆU TRUNG TÂM + CƠ CHẾ ĐỒNG BỘ NGẦM (F09)
 *
 * Mọi dữ liệu lấy từ máy chủ được giữ tại đây; các góc nhìn (Kanban, Danh sách,
 * Lịch) chỉ đọc từ kho này. Nhờ vậy chuyển đổi góc nhìn không cần gọi lại API.
 *
 * Khi dữ liệu thay đổi, kho gọi thẳng hàm tương ứng của App (this.app.onTasksChanged()...)
 * để App vẽ lại đúng phần giao diện cần thiết.
 */
class AppStore {

    static PROJECT_STORAGE_KEY = 'nv7_project_id';

    constructor(api, { pollInterval = 15000 } = {}) {
        this.api          = api;
        this.pollInterval = pollInterval;

        /** Bộ điều phối App, được App gắn vào lúc khởi tạo. */
        this.app = null;

        // --- Dữ liệu ---
        this.projects      = [];
        this.tasks         = [];
        this.members       = [];
        this.dashboard     = null;
        this.notifications = [];

        this.currentProjectId = null;

        /** Chữ ký của tập công việc lần tải trước — dùng để biết dữ liệu có đổi không. */
        this.taskSignature = null;

        // --- Trạng thái giao diện ---
        this.filters    = { search: '', assignee: '', priority: '' };
        this.logChannel = '';

        // --- Đồng bộ ---
        this.pollTimer   = null;
        this.isSyncing   = false;
        this.isDragging  = false;   // tạm ngưng vẽ lại khi người dùng đang kéo thẻ
        this.lastSyncAt  = null;
        this.lastCronRun = null;

        /** Hàm cho biết có nên hoãn nhịp đồng bộ hay không (App gắn vào). */
        this.shouldPauseSync = () => false;

        /** Hàm cho biết trang nào đang mở, để chỉ tải đúng dữ liệu cần thiết. */
        this.activePage = () => 'dashboard';
    }

    // -----------------------------------------------------------------------
    // TRUY XUẤT
    // -----------------------------------------------------------------------

    currentProject() {
        return this.projects.find(project => project.id === this.currentProjectId) || null;
    }

    isOwner() {
        const project = this.currentProject();
        return project ? project.my_role === 'owner' : false;
    }

    /** Ngưỡng "sắp tới hạn" của dự án đang xem (phút). */
    remindBefore() {
        const project = this.currentProject();
        return project ? project.remind_before_minutes : 120;
    }

    hasActiveFilter() {
        return Boolean(this.filters.search || this.filters.assignee || this.filters.priority);
    }

    /**
     * Danh sách công việc sau khi áp bộ lọc trên thanh công cụ, đồng thời tính
     * lại mức khẩn cấp theo thời điểm hiện tại.
     */
    filteredTasks() {
        const keyword  = this.filters.search.trim().toLowerCase();
        const assignee = this.filters.assignee;
        const priority = this.filters.priority;
        const remind   = this.remindBefore();

        return this.tasks
            .filter(task => {
                if (keyword && !task.title.toLowerCase().includes(keyword)) return false;
                if (assignee && String(task.assignee_id || '') !== assignee) return false;
                if (priority && task.priority !== priority) return false;
                return true;
            })
            .map(task => ({
                ...task,
                due_state: Formatter.dueState(task.due_at, task.status, remind),
                due_human: Formatter.humanize(task.due_at)
            }));
    }

    /** Nhóm công việc theo trạng thái để dựng các cột Kanban. */
    tasksByStatus() {
        const groups = { todo: [], in_progress: [], review: [], done: [] };

        this.filteredTasks().forEach(task => {
            if (groups[task.status]) groups[task.status].push(task);
        });

        Object.values(groups).forEach(list => list.sort((a, b) => a.position - b.position));

        return groups;
    }

    findTask(taskId) {
        return this.tasks.find(task => task.id === taskId) || null;
    }

    findProject(projectId) {
        return this.projects.find(project => project.id === projectId) || null;
    }

    /** Số công việc quá hạn của dự án đang xem (hiện lên huy hiệu ở sidebar). */
    overdueCount() {
        const remind = this.remindBefore();

        return this.tasks.filter(
            task => Formatter.dueState(task.due_at, task.status, remind) === 'overdue'
        ).length;
    }

    // -----------------------------------------------------------------------
    // TẢI DỮ LIỆU
    // -----------------------------------------------------------------------

    async loadProjects() {
        const data = await this.api.projects();
        this.projects = data.projects;

        // Khôi phục dự án đã xem lần trước, nếu nó vẫn còn tồn tại.
        const remembered = parseInt(localStorage.getItem(AppStore.PROJECT_STORAGE_KEY) || '0', 10);

        if (!this.currentProjectId || !this.projects.some(p => p.id === this.currentProjectId)) {
            const match = this.projects.find(p => p.id === remembered);
            this.currentProjectId = match ? match.id : (this.projects[0] ? this.projects[0].id : null);
        }

        this.app.renderProjectPicker();
        return this.projects;
    }

    setProject(projectId) {
        if (projectId === this.currentProjectId) return;

        this.currentProjectId = projectId;
        this.taskSignature    = null;               // buộc vẽ lại toàn bộ
        localStorage.setItem(AppStore.PROJECT_STORAGE_KEY, String(projectId));

        this.app.onProjectChanged();
    }

    /**
     * Tải danh sách công việc.
     * Nếu chữ ký dữ liệu không đổi so với lần trước thì không báo App vẽ lại,
     * tránh vẽ lại giao diện một cách vô ích ở mỗi nhịp đồng bộ.
     */
    async loadTasks({ silent = false } = {}) {
        if (!this.currentProjectId) {
            this.tasks = [];
            this.app.onTasksChanged();
            return this.tasks;
        }

        const data    = await this.api.tasks({ project_id: this.currentProjectId });
        const changed = data.signature !== this.taskSignature;

        this.tasks         = data.tasks;
        this.taskSignature = data.signature;

        if (changed || !silent) {
            this.app.onTasksChanged();
        }

        return this.tasks;
    }

    async loadMembers() {
        if (!this.currentProjectId) {
            this.members = [];
            this.app.onMembersChanged();
            return this.members;
        }

        const data = await this.api.members(this.currentProjectId);
        this.members = data.members;
        this.app.onMembersChanged();

        return this.members;
    }

    async loadDashboard() {
        const data = await this.api.dashboard(null);       // tổng hợp mọi dự án

        this.dashboard   = data;
        this.lastCronRun = data.last_cron_run;
        this.app.onDashboardChanged(data);

        return data;
    }

    async loadNotifications() {
        const data = await this.api.notifications({
            project_id: this.currentProjectId,
            channel:    this.logChannel
        });

        this.notifications = data.notifications;
        this.app.onNotificationsChanged(data);

        return data;
    }

    /** Buộc lần tải công việc kế tiếp phải vẽ lại giao diện. */
    invalidateTasks() {
        this.taskSignature = null;
    }

    // -----------------------------------------------------------------------
    // F09 - VÒNG LẶP ĐỒNG BỘ NGẦM
    // -----------------------------------------------------------------------

    /**
     * Một nhịp đồng bộ gồm hai việc:
     *   1. Gọi reminder/scan để máy chủ quét deadline (nếu đã tới chu kỳ)
     *   2. Tải lại dữ liệu của trang đang mở
     */
    async syncTick({ manual = false } = {}) {
        // Đang kéo thẻ hoặc đang mở hộp thoại thì hoãn lại — tránh giật giao diện.
        if (!manual && (this.isDragging || this.shouldPauseSync())) return;
        if (this.isSyncing) return;

        this.isSyncing = true;
        this.app.updateSyncPill('syncing');

        try {
            // Bước 1: nhịp quét ngầm
            const cron = await this.api.cronTick();
            if (cron.last_run) this.lastCronRun = cron.last_run;

            // Nếu lượt quét vừa sinh ra thông báo mới thì báo cho người dùng biết.
            if (cron.ran && cron.stats && cron.stats.logged > 0) {
                this.app.onNewReminders(cron.stats);
            }

            // Bước 2: làm mới dữ liệu của trang hiện tại
            await this.refreshCurrentPage();

            this.lastSyncAt = new Date();
            this.app.updateSyncPill('ok');
        } catch (err) {
            console.warn('Đồng bộ thất bại:', err.message);
            this.app.updateSyncPill('error');
        } finally {
            this.isSyncing = false;
        }
    }

    /** Tải lại đúng phần dữ liệu mà trang đang mở cần tới. */
    async refreshCurrentPage() {
        const page = this.activePage();

        // Danh sách dự án luôn được làm mới để các con số ở sidebar luôn đúng.
        await this.loadProjects();

        if (page === 'dashboard')    await this.loadDashboard();
        else if (page === 'tasks')   await this.loadTasks({ silent: true });
        else if (page === 'members') await this.loadMembers();
        else if (page === 'log')     await this.loadNotifications();

        // Huy hiệu cảnh báo ở sidebar cần dữ liệu công việc mới nhất.
        if (page !== 'tasks') {
            await this.loadTasks({ silent: true });
        }

        this.app.updateBadges();
    }

    startPolling() {
        this.stopPolling();
        this.pollTimer = setInterval(() => this.syncTick(), this.pollInterval);
    }

    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }
}
