/**
 * F03 - GÓC NHÌN DANH SÁCH
 *
 * Dạng bảng, bấm vào tiêu đề cột để sắp xếp và bấm lần nữa để đảo chiều.
 */
class ListView extends View {

    constructor(context) {
        super(context);

        this.sortKey = 'due';        // mặc định sắp theo hạn chót — đúng tinh thần "nhắc việc"
        this.sortDir = 'asc';
    }

    render() {
        const tbody = this.el('taskTableBody');
        if (!tbody) return;

        const tasks = this.sortTasks(this.store.filteredTasks());

        if (tasks.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">${Ui.emptyState(
                'Không có công việc nào',
                'Thử bỏ bớt bộ lọc, hoặc bấm “Công việc mới” để thêm việc đầu tiên.'
            )}</td></tr>`;
            return;
        }

        tbody.innerHTML = tasks.map(task => this.rowHtml(task)).join('');
        this.updateSortIndicators();
    }

    rowHtml(task) {
        const state = task.due_state;

        // Việc đã xong thì không còn khái niệm "trễ" — hiện mốc hoàn thành thay vì đếm ngược.
        let dueCell;
        if (!task.due_at) {
            dueCell = '<span class="muted">Chưa đặt hạn</span>';
        } else if (task.status === 'done') {
            dueCell = `<div>${Formatter.escape(Formatter.dateTime(task.due_at))}</div>
                       <div class="small muted">Xong ${Formatter.escape(
                           Formatter.timeAgo(task.completed_at || task.updated_at)
                       )}</div>`;
        } else {
            dueCell = `<div>${Formatter.escape(Formatter.dateTime(task.due_at))}</div>
                       <div class="small ${state === 'overdue' ? 'error-text' : 'muted'}">${
                           Formatter.escape(task.due_human)
                       }</div>`;
        }

        const rowClass = (state === 'overdue' || state === 'due_soon') ? ` class="row-${state}"` : '';

        return `
            <tr${rowClass} data-task-id="${task.id}">
                <td class="cell-title">${Formatter.escape(task.title)}</td>
                <td>${Formatter.statusBadge(task.status)}</td>
                <td>${Formatter.priority(task.priority)}</td>
                <td>
                    <div class="cell-people">
                        ${Formatter.avatar(task.assignee_name, task.assignee_color, 'avatar-sm')}
                        <span>${Formatter.escape(task.assignee_name || 'Chưa phân công')}</span>
                    </div>
                </td>
                <td class="tnum">${dueCell}</td>
            </tr>`;
    }

    /**
     * Sắp xếp theo cột đang chọn.
     *
     * Khi sắp theo hạn chót, việc đã hoàn thành và việc chưa đặt hạn luôn bị đẩy
     * xuống cuối — bảng này để trả lời "sắp tới phải làm gì", nên một việc xong
     * từ tuần trước không có lý do gì nằm ở dòng đầu tiên.
     */
    sortTasks(tasks) {
        const direction = this.sortDir === 'asc' ? 1 : -1;

        return [...tasks].sort((a, b) => {
            switch (this.sortKey) {
                case 'due': {
                    const doneA = a.status === 'done' ? 1 : 0;
                    const doneB = b.status === 'done' ? 1 : 0;
                    if (doneA !== doneB) return doneA - doneB;

                    const timeA = a.due_at ? new Date(a.due_at).getTime() : Infinity;
                    const timeB = b.due_at ? new Date(b.due_at).getTime() : Infinity;

                    if (timeA === timeB)    return 0;
                    if (timeA === Infinity) return 1;
                    if (timeB === Infinity) return -1;

                    return (timeA - timeB) * direction;
                }

                case 'priority':
                    return (Catalog.priorityWeight(a.priority) - Catalog.priorityWeight(b.priority)) * direction;

                case 'status':
                    return (Catalog.statusIndex(a.status) - Catalog.statusIndex(b.status)) * direction;

                case 'assignee':
                    return String(a.assignee_name || 'zzz')
                        .localeCompare(String(b.assignee_name || 'zzz'), 'vi') * direction;

                case 'title':
                default:
                    return a.title.localeCompare(b.title, 'vi') * direction;
            }
        });
    }

    setSort(key) {
        if (this.sortKey === key) {
            this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortKey = key;
            this.sortDir = 'asc';
        }

        this.render();
    }

    updateSortIndicators() {
        document.querySelectorAll('#taskTable th.sortable').forEach(th => {
            const isActive = th.dataset.sort === this.sortKey;

            th.classList.toggle('is-sorted', isActive);
            th.classList.toggle('desc', isActive && this.sortDir === 'desc');
        });
    }
}
