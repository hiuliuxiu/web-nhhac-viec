/**
 * BỘ ĐIỀU PHỐI ỨNG DỤNG (Controller phía trình duyệt)
 *
 * Nối các mảnh lại với nhau: điều hướng giữa các khu vực, chuyển góc nhìn (F03),
 * hộp thoại thêm/sửa công việc (F04), và vòng lặp đồng bộ ngầm (F09).
 *
 * App không tự dựng HTML — việc đó là của các View. Nó chỉ quyết định
 * "khi nào thì View nào được vẽ".
 */
class App {

    /** Tiêu đề hiển thị trên thanh trên cùng cho từng khu vực. */
    static PAGE_META = {
        dashboard: { title: 'Tổng quan',           subtitle: 'Toàn cảnh tiến độ công việc của bạn' },
        tasks:     { title: 'Công việc',           subtitle: 'Kéo thả thẻ để chuyển trạng thái' },
        members:   { title: 'Thành viên',          subtitle: 'Quản lý người tham gia dự án' },
        settings:  { title: 'Cài đặt',             subtitle: 'Cấu hình nhắc việc và Discord cho dự án này' },
        log:       { title: 'Thông báo',           subtitle: 'Lịch sử nhắc việc trong hệ thống và trên Discord' },
        guide:     { title: 'Hướng dẫn sử dụng',   subtitle: 'Tra cứu cách dùng từng chức năng của hệ thống' }
    };

    /** Chu kỳ vẽ lại đồng hồ đếm ngược (mili-giây) — không gọi mạng. */
    static COUNTDOWN_INTERVAL = 20000;

    constructor({ store, api, ui, views, config }) {
        this.store  = store;
        this.api    = api;
        this.ui     = ui;
        this.views  = views;
        this.config = config;

        this.currentUser = config.user;
        this.currentPage = 'dashboard';
        this.currentView = 'board';

        // Mỗi View cần biết bộ điều phối để yêu cầu chuyển trang, mở hộp thoại…
        Object.values(this.views).forEach(view => view.attach(this));

        // Kho dữ liệu cần biết App để báo khi dữ liệu đổi (gọi this.app.onTasksChanged()...)
        this.store.app = this;

        // Kho dữ liệu hỏi ngược lại App hai điều này ở mỗi nhịp đồng bộ.
        this.store.activePage      = () => this.currentPage;
        this.store.shouldPauseSync = () => this.ui.hasOpenModal();
    }

    // =======================================================================
    // KHỞI ĐỘNG
    // =======================================================================

    async init() {
        this.bindNavigation();
        this.bindViewSwitch();
        this.bindToolbar();
        this.bindTaskModal();
        this.bindProjectModal();
        this.bindMembersPage();
        this.bindLogPage();
        this.bindGlobalShortcuts();

        try {
            await this.store.loadProjects();
            this.renderProjectPicker();

            await Promise.all([
                this.store.loadTasks(),
                this.store.loadDashboard(),
                this.store.loadMembers()
            ]);

            this.refreshAssigneeOptions();
            this.updateBadges();
        } catch (err) {
            this.ui.toast('Không tải được dữ liệu: ' + err.message, 'error', 6000);
        }

        // F09 - bắt đầu vòng lặp đồng bộ ngầm
        this.store.startPolling();
        this.startCountdownTicker();
        this.updateSyncPill('ok');

        // Tạm ngưng đồng bộ khi người dùng chuyển sang tab khác, đồng bộ lại ngay
        // khi họ quay về — vừa tiết kiệm tài nguyên vừa thấy dữ liệu mới liền.
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.store.stopPolling();
            } else {
                this.store.startPolling();
                this.store.syncTick();
            }
        });
    }

    el(id) {
        return document.getElementById(id);
    }

    // =======================================================================
    // ĐIỀU HƯỚNG
    // =======================================================================

    bindNavigation() {
        document.querySelectorAll('.nav-item[data-page]').forEach(button => {
            button.addEventListener('click', () => this.goToPage(button.dataset.page));
        });

        document.querySelectorAll('[data-goto-page]').forEach(button => {
            button.addEventListener('click', () => this.goToPage(button.dataset.gotoPage));
        });

        // Thanh điều hướng dạng trượt trên màn hình nhỏ
        const sidebar = this.el('sidebar');
        const scrim   = this.el('sidebarScrim');

        this.el('menuToggle')?.addEventListener('click', () => {
            sidebar.classList.add('is-open');
            scrim.classList.add('is-open');
        });

        scrim?.addEventListener('click', () => {
            sidebar.classList.remove('is-open');
            scrim.classList.remove('is-open');
        });

        // Đổi dự án
        this.el('projectPicker')?.addEventListener('change', (event) => {
            if (event.target.value === '__new__') {
                this.renderProjectPicker();          // trả ô chọn về dự án cũ
                this.openProjectModal(null);
                return;
            }

            this.store.setProject(parseInt(event.target.value, 10));
        });

        this.el('logoutBtn')?.addEventListener('click', () => this.logout());
    }

    async logout() {
        const agreed = await this.ui.confirm(
            'Bạn có chắc muốn đăng xuất khỏi hệ thống?',
            'Đăng xuất',
            'Đăng xuất'
        );
        if (!agreed) return;

        try {
            await this.api.logout();
        } catch (err) {
            /* dù lỗi vẫn chuyển về trang đăng nhập */
        }

        window.location.href = this.config.loginUrl;
    }

    /**
     * @param {string}      page    Mã khu vực cần mở
     * @param {string|null} anchor  Mã mục cần cuộn tới (chỉ dùng cho trang Hướng dẫn)
     */
    goToPage(page, anchor = null) {
        this.currentPage = page;

        document.querySelectorAll('.page').forEach(section => {
            section.classList.toggle('is-active', section.id === 'page-' + page);
        });

        document.querySelectorAll('.nav-item[data-page]').forEach(button => {
            button.classList.toggle('is-active', button.dataset.page === page);
        });

        // Cập nhật tiêu đề
        const meta    = App.PAGE_META[page] || App.PAGE_META.dashboard;
        const project = this.store.currentProject();

        this.el('pageTitle').textContent    = (page === 'tasks' && project) ? project.name : meta.title;
        this.el('pageSubtitle').textContent = meta.subtitle;

        // Nút thêm công việc không có ý nghĩa ở trang tra cứu.
        this.el('newTaskBtn')?.classList.toggle('hidden', page === 'guide');

        // Đóng thanh điều hướng trên màn hình nhỏ
        this.el('sidebar')?.classList.remove('is-open');
        this.el('sidebarScrim')?.classList.remove('is-open');

        this.renderCurrentPage();

        // Mở trang Hướng dẫn từ một liên kết cụ thể thì cuộn thẳng tới mục đó.
        if (page === 'guide' && anchor) {
            requestAnimationFrame(() => {
                const target = this.el('guide-' + anchor);
                if (!target) return;

                window.scrollTo({
                    top:      target.getBoundingClientRect().top + window.scrollY - 96,
                    behavior: 'smooth'
                });
            });
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    /** Vẽ lại nội dung của khu vực đang mở bằng dữ liệu hiện có trong kho. */
    renderCurrentPage() {
        switch (this.currentPage) {
            case 'dashboard':
                this.views.dashboard.render();
                this.store.loadDashboard().catch(() => {});
                break;

            case 'tasks':
                this.renderCurrentView();
                break;

            case 'members':
                this.views.members.render();
                this.store.loadMembers().catch(() => {});
                break;

            case 'settings':
                this.views.settings.render();
                break;

            case 'log':
                this.views.log.render();
                this.store.loadNotifications().catch(() => {});
                break;

            case 'guide':
                this.views.guide.render();
                break;
        }
    }

    // =======================================================================
    // F03 - CHUYỂN ĐỔI GÓC NHÌN
    // =======================================================================

    bindViewSwitch() {
        document.querySelectorAll('.view-tab[data-view]').forEach(tab => {
            tab.addEventListener('click', () => this.setView(tab.dataset.view));
        });
    }

    setView(view) {
        this.currentView = view;

        document.querySelectorAll('.view-tab[data-view]').forEach(tab => {
            tab.classList.toggle('is-active', tab.dataset.view === view);
        });

        document.querySelectorAll('.view-panel').forEach(panel => {
            panel.classList.toggle('is-active', panel.id === 'view-' + view);
        });

        // Không gọi lại API — cả ba góc nhìn dùng chung dữ liệu đã có trong kho.
        this.renderCurrentView();
    }

    renderCurrentView() {
        if (this.currentView === 'board')         this.views.board.render();
        else if (this.currentView === 'list')     this.views.list.render();
        else if (this.currentView === 'calendar') this.views.calendar.render();
    }

    // =======================================================================
    // THANH CÔNG CỤ (tìm kiếm và bộ lọc)
    // =======================================================================

    bindToolbar() {
        this.el('taskSearch')?.addEventListener('input', Ui.debounce((event) => {
            this.store.filters.search = event.target.value;
            this.renderCurrentView();
        }, 240));

        this.el('filterAssignee')?.addEventListener('change', (event) => {
            this.store.filters.assignee = event.target.value;
            this.renderCurrentView();
        });

        this.el('filterPriority')?.addEventListener('change', (event) => {
            this.store.filters.priority = event.target.value;
            this.renderCurrentView();
        });

        // Sắp xếp cột ở góc nhìn Danh sách
        document.querySelectorAll('#taskTable th.sortable').forEach(th => {
            th.addEventListener('click', () => this.views.list.setSort(th.dataset.sort));
        });

        // Bấm vào một dòng trong bảng thì mở hộp thoại sửa
        this.el('taskTableBody')?.addEventListener('click', (event) => {
            const row = event.target.closest('tr[data-task-id]');
            if (row) this.openTaskModal(parseInt(row.dataset.taskId, 10));
        });

        // Bấm vào một thẻ trên bảng Kanban thì mở hộp thoại sửa
        this.el('kanbanBoard')?.addEventListener('click', (event) => {
            const addButton = event.target.closest('[data-add-status]');
            if (addButton) {
                this.openTaskModal(null, { status: addButton.dataset.addStatus });
                return;
            }

            const card = event.target.closest('.task-card');
            if (card) this.openTaskModal(parseInt(card.dataset.taskId, 10));
        });

        this.el('newTaskBtn')?.addEventListener('click', () => this.openTaskModal(null));
    }

    // =======================================================================
    // F04 - HỘP THOẠI THÊM / SỬA CÔNG VIỆC
    // =======================================================================

    bindTaskModal() {
        document.querySelectorAll('[data-close-modal]').forEach(button => {
            button.addEventListener('click', () => this.ui.closeAllModals());
        });

        // Bấm ra vùng nền tối để đóng
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) this.ui.closeAllModals();
            });
        });

        this.el('saveTaskBtn')?.addEventListener('click', () => this.saveTask());
        this.el('deleteTaskBtn')?.addEventListener('click', () => this.deleteTask());

        // Enter trong ô tiêu đề = lưu luôn
        this.el('taskTitle')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.saveTask();
            }
        });
    }

    /**
     * @param {number|null} taskId   null = tạo mới
     * @param {object}      options  { status, dueDate } khi tạo từ cột Kanban hoặc từ Lịch
     */
    openTaskModal(taskId = null, options = {}) {
        if (!this.store.currentProjectId) {
            this.ui.toast('Hãy tạo hoặc chọn một dự án trước đã.', 'warn');
            return;
        }

        this.el('taskFormError').classList.add('hidden');
        this.refreshAssigneeOptions();

        const task = taskId ? this.store.findTask(taskId) : null;

        this.el('taskModalTitle').textContent = task ? 'Chi tiết công việc' : 'Công việc mới';
        this.el('taskId').value               = task ? task.id : '';
        this.el('taskTitle').value            = task ? task.title : '';
        this.el('taskDescription').value      = task ? (task.description || '') : '';
        this.el('taskAssignee').value         = task && task.assignee_id ? String(task.assignee_id) : '';
        this.el('taskPriority').value         = task ? task.priority : 'medium';
        this.el('taskStatus').value           = task ? task.status : (options.status || 'todo');

        let dueValue = '';
        if (task && task.due_at) {
            dueValue = Formatter.toInputValue(task.due_at);
        } else if (options.dueDate) {
            // Bấm vào ô ngày trên Lịch: mặc định hạn chót là 17:00 ngày hôm đó.
            dueValue = options.dueDate.length === 10
                ? options.dueDate + 'T17:00'
                : Formatter.toInputValue(options.dueDate);
        }
        const dueInput = this.el('taskDueAt');
        if (task) {
            // Công việc cũ có thể đã quá hạn; vẫn cho sửa các thông tin khác.
            dueInput.removeAttribute('min');
        } else {
            // datetime-local không có múi giờ, nên tính mốc theo giờ địa phương.
            const minimumDue = new Date();
            minimumDue.setSeconds(0, 0);
            minimumDue.setMinutes(minimumDue.getMinutes() + 1);
            dueInput.min = Formatter.toInputValue(minimumDue.toISOString());
        }
        dueInput.value = dueValue;

        // Nút xoá chỉ có nghĩa khi đang sửa một công việc đã tồn tại.
        this.el('deleteTaskBtn').classList.toggle('hidden', !task);

        this.ui.openModal('taskModal');
    }

    async saveTask() {
        const errorBox = this.el('taskFormError');
        errorBox.classList.add('hidden');

        const taskId = this.el('taskId').value;
        const title  = this.el('taskTitle').value.trim();
        const dueAt  = this.el('taskDueAt').value;

        if (!title) {
            errorBox.textContent = 'Tiêu đề công việc không được để trống.';
            errorBox.classList.remove('hidden');
            this.el('taskTitle').focus();
            return;
        }

        if (!taskId && dueAt && new Date(dueAt).getTime() < Date.now()) {
            errorBox.textContent = 'Hạn chót phải là thời điểm hiện tại hoặc trong tương lai.';
            errorBox.classList.remove('hidden');
            this.el('taskDueAt').focus();
            return;
        }

        const payload = {
            project_id:  this.store.currentProjectId,
            title,
            description: this.el('taskDescription').value.trim(),
            assignee_id: this.el('taskAssignee').value || null,
            priority:    this.el('taskPriority').value,
            status:      this.el('taskStatus').value,
            due_at:      dueAt
        };

        const saveButton = this.el('saveTaskBtn');
        const label      = saveButton.querySelector('.btn-label');

        saveButton.disabled = true;
        label.textContent   = 'Đang lưu…';

        try {
            if (taskId) {
                payload.task_id = parseInt(taskId, 10);
                await this.api.updateTask(payload);
            } else {
                await this.api.createTask(payload);
            }

            this.ui.closeModal('taskModal');

            this.store.invalidateTasks();
            await this.store.loadTasks();
            await this.store.loadProjects();

            this.renderCurrentView();
            this.updateBadges();

            this.ui.toast(taskId ? 'Đã cập nhật công việc.' : 'Đã thêm công việc mới.', 'success');
        } catch (err) {
            errorBox.textContent = err.message;
            errorBox.classList.remove('hidden');
        }

        saveButton.disabled = false;
        label.textContent   = 'Lưu công việc';
    }

    async deleteTask() {
        const taskId = parseInt(this.el('taskId').value, 10);
        if (!taskId) return;

        const task = this.store.findTask(taskId);

        const agreed = await this.ui.confirm(
            `Xoá vĩnh viễn công việc “${task ? task.title : ''}”? Thao tác này không thể hoàn tác.`,
            'Xoá công việc?',
            'Xoá công việc'
        );
        if (!agreed) return;

        try {
            await this.api.deleteTask(taskId);

            this.ui.closeAllModals();

            this.store.invalidateTasks();
            await this.store.loadTasks();
            await this.store.loadProjects();

            this.renderCurrentView();
            this.updateBadges();

            this.ui.toast('Đã xoá công việc.', 'success');
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }
    }

    /** Đổ danh sách thành viên vào ô chọn "Người thực hiện" và ô lọc. */
    refreshAssigneeOptions() {
        const options = this.store.members
            .map(member => `<option value="${member.id}">${Formatter.escape(member.full_name)}</option>`)
            .join('');

        const assigneeSelect = this.el('taskAssignee');
        if (assigneeSelect) {
            const keep = assigneeSelect.value;
            assigneeSelect.innerHTML = '<option value="">Chưa phân công</option>' + options;
            assigneeSelect.value = keep;
        }

        const filterSelect = this.el('filterAssignee');
        if (filterSelect) {
            const keep = filterSelect.value;
            filterSelect.innerHTML = '<option value="">Mọi người</option>' + options;
            filterSelect.value = keep;
        }
    }

    // =======================================================================
    // F10 - HỘP THOẠI DỰ ÁN
    // =======================================================================

    bindProjectModal() {
        this.el('saveProjectBtn')?.addEventListener('click', () => this.saveProject());

        this.el('deleteProjectBtn')?.addEventListener('click', () => {
            const id      = parseInt(this.el('projectFormId').value, 10);
            const project = this.store.findProject(id);

            if (project) this.deleteProject(project.id, project.name);
        });
    }

    openProjectModal(projectId = null) {
        const project = projectId ? this.store.findProject(projectId) : null;

        this.el('projectFormError').classList.add('hidden');
        this.el('projectModalTitle').textContent = project ? 'Sửa dự án' : 'Dự án mới';
        this.el('projectFormId').value      = project ? project.id : '';
        this.el('projectName').value        = project ? project.name : '';
        this.el('projectDescription').value = project ? (project.description || '') : '';
        this.el('projectColor').value       = project ? project.color : '#5b5bd6';

        this.el('deleteProjectBtn').classList.toggle('hidden', !project);

        this.ui.openModal('projectModal');
    }

    async saveProject() {
        const errorBox = this.el('projectFormError');
        errorBox.classList.add('hidden');

        const projectId = this.el('projectFormId').value;
        const name      = this.el('projectName').value.trim();

        if (!name) {
            errorBox.textContent = 'Tên dự án không được để trống.';
            errorBox.classList.remove('hidden');
            return;
        }

        const payload = {
            name,
            description: this.el('projectDescription').value.trim(),
            color:       this.el('projectColor').value
        };

        const button = this.el('saveProjectBtn');
        button.disabled = true;

        try {
            const result = projectId
                ? await this.api.updateProject({ ...payload, project_id: parseInt(projectId, 10) })
                : await this.api.createProject(payload);

            this.ui.closeModal('projectModal');

            await this.store.loadProjects();

            // Vừa tạo xong thì chuyển luôn sang dự án mới.
            if (!projectId && result.project) {
                this.store.setProject(result.project.id);
            }

            this.renderProjectPicker();
            this.renderCurrentPage();

            this.ui.toast(result.message, 'success');
        } catch (err) {
            errorBox.textContent = err.message;
            errorBox.classList.remove('hidden');
        }

        button.disabled = false;
    }

    async deleteProject(projectId, projectName) {
        const agreed = await this.ui.confirm(
            `Xoá dự án “${projectName}”? Toàn bộ công việc, thành viên và thông báo bên trong sẽ bị xoá vĩnh viễn.`,
            'Xoá dự án?',
            'Xoá vĩnh viễn'
        );
        if (!agreed) return;

        try {
            await this.api.deleteProject(projectId);

            this.ui.closeAllModals();

            this.store.currentProjectId = null;
            await this.store.loadProjects();
            this.renderProjectPicker();

            await Promise.all([
                this.store.loadTasks(),
                this.store.loadMembers(),
                this.store.loadDashboard()
            ]);

            this.refreshAssigneeOptions();
            this.goToPage('dashboard');

            this.ui.toast('Đã xoá dự án.', 'success');
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }
    }

    renderProjectPicker() {
        const picker = this.el('projectPicker');
        if (!picker) return;

        const options = this.store.projects
            .map(project => `<option value="${project.id}">${Formatter.escape(project.name)}</option>`)
            .join('');

        picker.innerHTML = options + '<option value="__new__">+ Tạo dự án mới…</option>';

        if (this.store.currentProjectId) {
            picker.value = String(this.store.currentProjectId);
        }
    }

    // =======================================================================
    // TRANG THÀNH VIÊN VÀ NHẬT KÝ
    // =======================================================================

    bindMembersPage() {
        this.el('inviteBtn')?.addEventListener('click', () => this.views.members.invite());

        const input = this.el('inviteInput');

        input?.addEventListener('input', Ui.debounce((event) => {
            this.views.members.suggest(event.target.value);
        }, 320));

        input?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.views.members.invite();
            }
        });
    }

    bindLogPage() {
        document.querySelectorAll('.view-tab[data-log-channel]').forEach(tab => {
            tab.addEventListener('click', async () => {
                document.querySelectorAll('.view-tab[data-log-channel]')
                    .forEach(item => item.classList.remove('is-active'));

                tab.classList.add('is-active');

                this.store.logChannel = tab.dataset.logChannel;
                await this.store.loadNotifications();
            });
        });

        this.el('forceScanBtn')?.addEventListener('click', (event) => {
            this.views.log.forceScan(event.currentTarget);
        });
    }

    // =======================================================================
    // PHẢN ỨNG VỚI THAY ĐỔI TRONG KHO DỮ LIỆU
    // AppStore gọi các hàm dưới đây mỗi khi tải xong dữ liệu mới.
    // =======================================================================

    /** Người dùng vừa chọn dự án khác: tải lại công việc, thành viên rồi vẽ lại trang. */
    async onProjectChanged() {
        this.renderProjectPicker();

        try {
            await Promise.all([this.store.loadTasks(), this.store.loadMembers()]);

            this.refreshAssigneeOptions();
            this.renderCurrentPage();
            this.updateBadges();

            const project = this.store.currentProject();
            if (project && this.currentPage === 'tasks') {
                this.el('pageTitle').textContent = project.name;
            }
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }
    }

    onTasksChanged() {
        if (this.currentPage === 'tasks') this.renderCurrentView();
        this.updateBadges();
    }

    onMembersChanged() {
        if (this.currentPage === 'members') this.views.members.render();
    }

    onDashboardChanged(data) {
        if (this.currentPage === 'dashboard') this.views.dashboard.render(data);
    }

    onNotificationsChanged(payload) {
        if (this.currentPage === 'log') this.views.log.render(payload);
        this.updateBadges(payload);
    }

    /** F07 - lượt quét ngầm vừa phát hiện việc mới tới ngưỡng nhắc. */
    onNewReminders(stats) {
        const parts = [];
        if (stats.overdue > 0)  parts.push(stats.overdue + ' việc quá hạn');
        if (stats.due_soon > 0) parts.push(stats.due_soon + ' việc sắp tới hạn');

        if (parts.length > 0) {
            this.ui.toast('Hệ thống vừa phát hiện ' + parts.join(' và ') + '.', 'warn', 5200);
        }
    }

    /** Huy hiệu số ở thanh điều hướng bên trái. */
    updateBadges(notificationPayload = null) {
        const overdue   = this.store.overdueCount();
        const taskBadge = this.el('navBadgeTasks');

        if (taskBadge) {
            taskBadge.textContent = overdue > 0 ? String(overdue) : '';
            taskBadge.title       = overdue + ' công việc đã quá hạn';
        }

        const logBadge = this.el('navBadgeLog');
        if (logBadge) {
            const count = notificationPayload
                ? notificationPayload.notifications.length
                : this.store.notifications.length;

            logBadge.textContent = count > 0 ? String(count) : '';
        }
    }

    /**
     * F09 - chỉ báo trạng thái đồng bộ trên thanh trên cùng.
     * Chỉ đổi màu chấm; chữ mô tả nằm trong chú thích hiện ra khi rê chuột.
     */
    updateSyncPill(state) {
        const pill = this.el('syncPill');
        if (!pill) return;

        pill.classList.remove('is-syncing', 'is-error');

        let text;
        if (state === 'syncing') {
            pill.classList.add('is-syncing');
            text = 'Đang đồng bộ…';
        } else if (state === 'error') {
            pill.classList.add('is-error');
            text = 'Mất kết nối';
        } else {
            text = this.store.lastSyncAt
                ? 'Đã đồng bộ ' + Formatter.timeAgo(this.store.lastSyncAt.toISOString())
                : 'Đã đồng bộ';
        }

        pill.title = text;
        pill.setAttribute('aria-label', text);
    }

    /**
     * F07 - đồng hồ đếm ngược.
     *
     * Dữ liệu chỉ được tải lại mỗi 15 giây, nhưng nhãn "còn 3 phút" và thời khắc
     * thẻ chuyển sang màu đỏ thì phải chính xác hơn thế. Bộ đếm này vẽ lại phần
     * hiển thị mà không hề gọi mạng.
     */
    startCountdownTicker() {
        setInterval(() => {
            if (this.store.isDragging) return;
            if (this.ui.hasOpenModal()) return;
            if (document.hidden) return;

            if (this.currentPage === 'tasks' && this.currentView !== 'calendar') {
                this.renderCurrentView();
            }

            this.updateSyncPill('ok');
            this.updateBadges();
        }, App.COUNTDOWN_INTERVAL);
    }

    bindGlobalShortcuts() {
        document.addEventListener('keydown', (event) => {
            // Esc: đóng mọi hộp thoại
            if (event.key === 'Escape') {
                this.ui.closeAllModals();
                return;
            }

            // Bỏ qua khi con trỏ đang ở trong ô nhập liệu
            const tag = document.activeElement?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

            // N: tạo công việc mới
            if (event.key === 'n' || event.key === 'N') {
                event.preventDefault();
                this.openTaskModal(null);
            }
        });
    }
}
