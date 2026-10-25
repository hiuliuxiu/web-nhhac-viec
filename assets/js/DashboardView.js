/**
 * F01 + F02 - BẢNG ĐIỀU KHIỂN
 *
 * Thẻ số liệu, thanh tiến độ từng dự án, biểu đồ tròn phân bố trạng thái,
 * danh sách việc cần chú ý, khối lượng theo người và hoạt động gần đây.
 */
class DashboardView extends View {

    static ICONS = {
        total:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5h10M9 12h10M9 19h10"/><path d="m3.5 5 1.2 1.2L7 4"/><path d="m3.5 12 1.2 1.2L7 11"/><path d="m3.5 19 1.2 1.2L7 18"/></svg>`,
        running: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>`,
        overdue: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5 2.5 20h19L12 3.5Z"/><path d="M12 10v4M12 17.2v.1"/></svg>`,
        soon:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0-6 6c0 3-1.2 4.6-2 5.4a.7.7 0 0 0 .5 1.2h15a.7.7 0 0 0 .5-1.2c-.8-.8-2-2.4-2-5.4a6 6 0 0 0-6-6Z"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>`,
        done:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.2 12.3 2.6 2.6 5-5.4"/></svg>`
    };

    constructor(context) {
        super(context);
        this.chart = null;
    }

    render(data = null) {
        const payload = data || this.store.dashboard;
        if (!payload) return;

        this.renderStats(payload.totals);
        this.renderProjects(payload.projects);
        this.renderChart(payload.totals);
        this.renderUrgent(payload.urgent_tasks);
        this.renderWorkload(payload.workload);
        this.renderActivity(payload.recently_done);
    }

    // -----------------------------------------------------------------------
    // Thẻ số liệu tổng hợp
    // -----------------------------------------------------------------------

    renderStats(totals) {
        const host = this.el('statGrid');
        if (!host) return;

        const cards = [
            {
                label: 'Tổng công việc',
                value: totals.total,
                hint:  totals.projects + ' dự án đang tham gia',
                tone:  '',
                icon:  DashboardView.ICONS.total
            },
            {
                label: 'Đang thực hiện',
                value: totals.in_progress,
                hint:  totals.review + ' việc đang chờ duyệt',
                tone:  'tone-accent',
                icon:  DashboardView.ICONS.running
            },
            {
                label: 'Đã trễ hạn',
                value: totals.overdue,
                hint:  totals.overdue > 0 ? 'Cần xử lý ngay lập tức' : 'Không có việc nào trễ hạn',
                tone:  totals.overdue > 0 ? 'tone-danger' : 'tone-success',
                icon:  DashboardView.ICONS.overdue
            },
            {
                label: 'Sắp tới hạn',
                value: totals.due_soon,
                hint:  'Trong ngưỡng nhắc việc của dự án',
                tone:  totals.due_soon > 0 ? 'tone-warn' : '',
                icon:  DashboardView.ICONS.soon
            },
            {
                label: 'Đã hoàn thành',
                value: totals.done,
                hint:  'Đạt ' + totals.progress + '% tổng khối lượng',
                tone:  'tone-success',
                icon:  DashboardView.ICONS.done
            }
        ];

        host.innerHTML = cards.map(card => `
            <div class="stat-card ${card.tone}">
                <div class="stat-top">
                    <span class="stat-label">${Formatter.escape(card.label)}</span>
                    <span class="stat-glyph">${card.icon}</span>
                </div>
                <div class="stat-value">${card.value}</div>
                <div class="stat-hint">${Formatter.escape(card.hint)}</div>
            </div>`).join('');
    }

    // -----------------------------------------------------------------------
    // F02 - Thanh tiến độ từng dự án
    // -----------------------------------------------------------------------

    renderProjects(projects) {
        const host = this.el('projectProgressList');
        if (!host) return;

        const badge = this.el('projectCountBadge');
        if (badge) badge.textContent = projects.length + ' dự án';

        if (projects.length === 0) {
            host.innerHTML = Ui.emptyState(
                'Chưa có dự án nào',
                'Tạo dự án đầu tiên từ ô chọn dự án ở thanh bên trái.'
            );
            return;
        }

        host.innerHTML = projects.map(project => `
            <div class="project-row" data-project-id="${project.id}" style="cursor:pointer">
                <div class="pr-head">
                    <span class="pr-dot" style="background:${Formatter.escape(project.color)}"></span>
                    <span class="pr-name">${Formatter.escape(project.name)}</span>
                    <span class="pr-pct" style="color:${Formatter.escape(project.color)}">${project.progress}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width:${project.progress}%;background:${Formatter.escape(project.color)}"></div>
                </div>
                <div class="pr-meta">
                    <span><b>${project.done_tasks}</b>/${project.total_tasks} việc xong</span>
                    <span><b>${project.in_progress_tasks}</b> đang làm</span>
                    ${project.overdue_tasks > 0
                        ? `<span style="color:var(--danger)"><b style="color:var(--danger)">${project.overdue_tasks}</b> trễ hạn</span>`
                        : ''}
                    <span><b>${project.member_count}</b> thành viên</span>
                </div>
            </div>`).join('');

        // Bấm vào một dự án thì chuyển sang dự án đó và mở trang Công việc.
        host.querySelectorAll('.project-row').forEach(row => {
            row.addEventListener('click', () => {
                this.store.setProject(parseInt(row.dataset.projectId, 10));
                this.app.goToPage('tasks');
            });
        });
    }

    // -----------------------------------------------------------------------
    // F02 - Biểu đồ tròn phân bố trạng thái
    // -----------------------------------------------------------------------

    renderChart(totals) {
        const canvas = this.el('statusChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const values = [totals.todo, totals.in_progress, totals.review, totals.done];
        const labels = Catalog.STATUSES.map(status => Catalog.statusLabel(status));
        const colors = Catalog.STATUSES.map(status => Catalog.statusColor(status));

        this.el('chartPercent').textContent = totals.progress + '%';

        // Đã có biểu đồ thì chỉ thay số liệu, không dựng lại từ đầu.
        if (this.chart) {
            this.chart.data.datasets[0].data = values;
            this.chart.update('none');
        } else {
            this.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data:            values,
                        backgroundColor: colors,
                        borderWidth:     3,
                        borderColor:     '#ffffff',
                        hoverOffset:     7
                    }]
                },
                options: {
                    responsive:          true,
                    maintainAspectRatio: false,
                    cutout:              '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#16161a',
                            padding:         11,
                            cornerRadius:    9,
                            displayColors:   false,
                            titleFont: { family: 'Plus Jakarta Sans', size: 12.5, weight: '700' },
                            bodyFont:  { family: 'Plus Jakarta Sans', size: 12 },
                            callbacks: { label: (ctx) => ctx.parsed + ' công việc' }
                        }
                    },
                    animation: { duration: 700, easing: 'easeOutQuart' }
                }
            });
        }

        // Chú giải tự dựng để khớp phong cách chung của giao diện.
        const legend = this.el('chartLegend');
        if (legend) {
            legend.innerHTML = Catalog.STATUSES.map((status, index) => `
                <div class="legend-item">
                    <span class="legend-dot" style="background:${colors[index]}"></span>
                    <span class="lg-name">${Formatter.escape(labels[index])}</span>
                    <span class="lg-val">${values[index]}</span>
                </div>`).join('');
        }
    }

    // -----------------------------------------------------------------------
    // Việc cần chú ý ngay
    // -----------------------------------------------------------------------

    renderUrgent(tasks) {
        const host = this.el('urgentList');
        if (!host) return;

        if (!tasks || tasks.length === 0) {
            host.innerHTML = Ui.emptyState(
                'Không có việc nào gấp',
                'Chưa có công việc nào quá hạn hoặc sắp tới ngưỡng nhắc việc. Rất tốt!'
            );
            return;
        }

        host.innerHTML = tasks.map(task => {
            // Tính lại tại trình duyệt để nhãn thời gian luôn khớp thời điểm hiện tại.
            const state = Formatter.dueState(task.due_at, task.status, task.remind_before_minutes);
            const human = Formatter.humanize(task.due_at);

            return `
                <div class="activity-row" data-task-id="${task.id}" data-project-id="${task.project_id}" style="cursor:pointer">
                    <span class="badge badge-${state === 'overdue' ? 'danger' : 'warn'}" style="align-self:flex-start;margin-top:2px">
                        ${state === 'overdue' ? 'Trễ' : 'Gấp'}
                    </span>
                    <div class="ac-body">
                        <div class="ac-title">${Formatter.escape(task.title)}</div>
                        <div class="ac-meta">
                            <span style="color:${Formatter.escape(task.project_color)}">●</span>
                            ${Formatter.escape(task.project_name)} ·
                            ${Formatter.escape(task.assignee_name || 'Chưa phân công')} ·
                            <span class="${state === 'overdue' ? 'error-text' : ''}">${Formatter.escape(human)}</span>
                        </div>
                    </div>
                    ${Formatter.avatar(task.assignee_name, task.assignee_color, 'avatar-sm')}
                </div>`;
        }).join('');

        host.querySelectorAll('[data-task-id]').forEach(row => {
            row.addEventListener('click', () => {
                this.store.setProject(parseInt(row.dataset.projectId, 10));
                this.app.goToPage('tasks');
                setTimeout(() => this.app.openTaskModal(parseInt(row.dataset.taskId, 10)), 260);
            });
        });
    }

    // -----------------------------------------------------------------------
    // Khối lượng công việc theo thành viên
    // -----------------------------------------------------------------------

    renderWorkload(workload) {
        const host = this.el('workloadList');
        if (!host) return;

        if (!workload || workload.length === 0) {
            host.innerHTML = '<p class="muted small" style="padding:8px 0">Chưa có công việc nào được phân công.</p>';
            return;
        }

        host.innerHTML = workload.map(person => `
            <div class="workload-row">
                ${Formatter.avatar(person.full_name, person.avatar_color)}
                <div class="wl-body">
                    <div class="wl-top">
                        <span class="wl-name">${Formatter.escape(person.full_name)}</span>
                        <span class="wl-num">
                            ${person.done}/${person.total}
                            ${person.overdue > 0 ? `<span class="error-text"> · ${person.overdue} trễ</span>` : ''}
                        </span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:${person.progress}%;background:${Formatter.escape(person.avatar_color)}"></div>
                    </div>
                </div>
            </div>`).join('');
    }

    // -----------------------------------------------------------------------
    // Hoạt động gần đây
    // -----------------------------------------------------------------------

    renderActivity(items) {
        const host = this.el('activityList');
        if (!host) return;

        if (!items || items.length === 0) {
            host.innerHTML = '<p class="muted small" style="padding:8px 0">Chưa có công việc nào được hoàn thành.</p>';
            return;
        }

        host.innerHTML = items.map(item => `
            <div class="activity-row">
                ${Formatter.avatar(item.assignee_name, item.assignee_color, 'avatar-sm')}
                <div class="ac-body">
                    <div class="ac-title">${Formatter.escape(item.title)}</div>
                    <div class="ac-meta">
                        <span style="color:${Formatter.escape(item.project_color)}">●</span>
                        ${Formatter.escape(item.project_name)} · ${Formatter.escape(Formatter.timeAgo(item.completed_at))}
                    </div>
                </div>
                <span class="badge badge-success">Xong</span>
            </div>`).join('');
    }
}
