/**
 * F03 + F05 - GÓC NHÌN KANBAN
 *
 * Bốn cột theo trạng thái, kéo thả thẻ để đổi trạng thái công việc.
 */
class BoardView extends View {

    static CLOCK_ICON = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>`;

    constructor(context) {
        super(context);
        this.sortables = [];
    }

    render() {
        const host = this.el('kanbanBoard');
        if (!host) return;

        // Gỡ các phiên bản SortableJS cũ trước khi thay nội dung, tránh rò rỉ bộ nhớ.
        this.destroySortables();

        const groups = this.store.tasksByStatus();

        host.innerHTML = Catalog.STATUSES
            .map(status => this.columnHtml(status, groups[status]))
            .join('');

        this.initDragDrop();
    }

    // -----------------------------------------------------------------------
    // DỰNG HTML
    // -----------------------------------------------------------------------

    columnHtml(status, tasks) {
        const cards = tasks.length
            ? tasks.map(task => this.cardHtml(task)).join('')
            : '<div class="col-empty">Chưa có công việc nào</div>';

        return `
            <div class="column">
                <div class="column-head">
                    <span class="col-dot" style="background:${Catalog.statusColor(status)}"></span>
                    <span class="col-name">${Formatter.escape(Catalog.statusLabel(status))}</span>
                    <span class="col-count">${tasks.length}</span>
                    <button class="icon-btn col-add" data-add-status="${status}" title="Thêm vào cột này">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>
                <div class="column-body" data-status="${status}">${cards}</div>
            </div>`;
    }

    cardHtml(task) {
        const state = task.due_state;

        // Nhãn hạn chót đổi màu theo mức khẩn cấp (F07)
        const dueChip = task.due_at
            ? `<span class="due-chip is-${state}" title="${Formatter.escape(Formatter.dateTime(task.due_at))}">
                   ${BoardView.CLOCK_ICON}${Formatter.escape(
                       task.status === 'done' ? Formatter.shortDateTime(task.due_at) : task.due_human
                   )}
               </span>`
            : '';

        const stateClass = (state === 'overdue' || state === 'due_soon') ? ' is-' + state : '';
        const doneClass  = task.status === 'done' ? ' is-done' : '';

        return `
            <div class="task-card${stateClass}${doneClass}" data-task-id="${task.id}">
                <div class="tc-top">
                    <div class="tc-title">${Formatter.escape(task.title)}</div>
                </div>
                <div class="tc-foot">
                    ${Formatter.priority(task.priority)}
                    <span class="spacer"></span>
                    ${dueChip}
                    ${Formatter.avatar(task.assignee_name, task.assignee_color, 'avatar-sm')}
                </div>
            </div>`;
    }

    // -----------------------------------------------------------------------
    // F05 - KÉO THẢ
    // -----------------------------------------------------------------------

    destroySortables() {
        this.sortables.forEach(instance => instance.destroy());
        this.sortables = [];
    }

    initDragDrop() {
        if (typeof Sortable === 'undefined') return;

        document.querySelectorAll('#kanbanBoard .column-body').forEach(column => {
            const instance = Sortable.create(column, {
                group:         'kanban',
                animation:     190,
                easing:        'cubic-bezier(.32,.72,0,1)',
                ghostClass:    'task-ghost',
                chosenClass:   'task-chosen',
                dragClass:     'task-drag',
                draggable:     '.task-card',
                filter:        '.col-empty',
                forceFallback: false,

                // Tạm dừng đồng bộ ngầm trong lúc kéo, để giao diện không bị vẽ lại giữa chừng.
                onStart: () => { this.store.isDragging = true; },

                onMove: (evt) => {
                    evt.to.classList.add('is-drop-target');
                    return true;
                },

                onEnd: (evt) => {
                    document.querySelectorAll('.column-body')
                        .forEach(col => col.classList.remove('is-drop-target'));

                    this.store.isDragging = false;
                    this.handleDrop(evt);
                }
            });

            this.sortables.push(instance);
        });
    }

    async handleDrop(evt) {
        const taskId    = parseInt(evt.item.dataset.taskId, 10);
        const newStatus = evt.to.dataset.status;
        const oldStatus = evt.from.dataset.status;

        // Thả lại đúng chỗ cũ thì không cần gọi máy chủ.
        if (newStatus === oldStatus && evt.oldIndex === evt.newIndex) return;

        const task = this.store.findTask(taskId);
        if (!task) return;

        const previousStatus = task.status;

        // Cập nhật ngay tại chỗ để giao diện phản hồi tức thì (optimistic update).
        task.status = newStatus;

        // Chỉ ghi lại thứ tự khi không bật bộ lọc — nếu đang lọc thì danh sách thẻ
        // nhìn thấy không đầy đủ, ghi vị trí sẽ làm xáo trộn các thẻ bị ẩn.
        const orderedIds = this.store.hasActiveFilter()
            ? []
            : Array.from(evt.to.querySelectorAll('.task-card'))
                   .map(el => parseInt(el.dataset.taskId, 10));

        // Cập nhật con số trên đầu mỗi cột mà không phải vẽ lại cả bảng.
        this.refreshCounts();

        try {
            const result = await this.api.moveTask({
                task_id:     taskId,
                status:      newStatus,
                ordered_ids: orderedIds
            });

            // Đồng bộ lại bản ghi trong kho bằng dữ liệu chuẩn từ máy chủ.
            Object.assign(task, result.task);
            this.store.invalidateTasks();

            let message = result.message;
            if (result.notified && result.notified.discord_sent > 0) {
                message += ' Đã báo lên Discord.';
            }
            this.ui.toast(message, newStatus === 'done' ? 'success' : 'info', 2600);

            this.render();
            this.app.updateBadges();
        } catch (err) {
            // Thất bại: trả thẻ về trạng thái cũ rồi vẽ lại.
            task.status = previousStatus;
            this.ui.toast(err.message, 'error');
            this.render();
        }
    }

    /** Cập nhật nhanh số lượng thẻ hiển thị trên đầu mỗi cột. */
    refreshCounts() {
        document.querySelectorAll('#kanbanBoard .column').forEach(column => {
            column.querySelector('.col-count').textContent =
                column.querySelectorAll('.task-card').length;
        });
    }
}
