/**
 * ĐỊNH DẠNG DỮ LIỆU VÀ DỰNG HTML AN TOÀN
 *
 * Toàn bộ chuỗi do người dùng nhập đều phải đi qua Formatter.escape() trước khi
 * chèn vào innerHTML — đây là hàng rào chống XSS phía trình duyệt, song song với
 * hàm escape của tầng View bên PHP.
 */
class Formatter {

    // -----------------------------------------------------------------------
    // AN TOÀN HIỂN THỊ
    // -----------------------------------------------------------------------

    static escape(value) {
        if (value === null || value === undefined) return '';

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /** Chữ cái đầu của họ tên để hiển thị trong avatar. */
    static initials(fullName) {
        if (!fullName) return '?';

        const words = String(fullName).trim().split(/\s+/);
        return words[words.length - 1].charAt(0).toUpperCase();
    }

    static avatar(name, color, extraClass = '') {
        if (!name) {
            return `<div class="avatar is-empty ${extraClass}" title="Chưa phân công">–</div>`;
        }

        return `<div class="avatar ${extraClass}" style="background:${Formatter.escape(color || '#5b5bd6')}"
                     title="${Formatter.escape(name)}">${Formatter.escape(Formatter.initials(name))}</div>`;
    }

    /** Nhãn mức độ ưu tiên có thanh màu. */
    static priority(priority) {
        return `<span class="prio prio-${Formatter.escape(priority)}"><span class="prio-bar"></span>`
             + `${Formatter.escape(Catalog.priorityLabel(priority))}</span>`;
    }

    /** Huy hiệu trạng thái. */
    static statusBadge(status) {
        return `<span class="badge"><span class="badge-dot" style="background:${Catalog.statusColor(status)}"></span>`
             + `${Formatter.escape(Catalog.statusLabel(status))}</span>`;
    }

    // -----------------------------------------------------------------------
    // THỜI GIAN
    // -----------------------------------------------------------------------

    /**
     * F07 - Tính mức khẩn cấp ngay tại trình duyệt.
     *
     * Máy chủ cũng trả về due_state, nhưng giữa hai nhịp đồng bộ thì con số đó
     * dần cũ đi. Tính lại phía client giúp đồng hồ đếm ngược luôn đúng và thẻ
     * công việc đổi màu ngay tại thời khắc chạm hạn.
     */
    static dueState(dueAtIso, status, remindBeforeMinutes) {
        if (status === 'done' || !dueAtIso) return 'none';

        const msLeft = new Date(dueAtIso).getTime() - Date.now();

        if (msLeft < 0) return 'overdue';
        if (msLeft <= (remindBeforeMinutes || 120) * 60000) return 'due_soon';
        return 'upcoming';
    }

    /** Mô tả khoảng thời gian còn lại / đã trễ. Ví dụ: "còn 1 giờ 20 phút". */
    static humanize(dueAtIso) {
        if (!dueAtIso) return '';

        let seconds = Math.floor((new Date(dueAtIso).getTime() - Date.now()) / 1000);
        const isLate = seconds < 0;
        seconds = Math.abs(seconds);

        const days    = Math.floor(seconds / 86400);
        const hours   = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        let text;
        if (days > 0)       text = days + ' ngày' + (hours > 0 ? ' ' + hours + ' giờ' : '');
        else if (hours > 0) text = hours + ' giờ' + (minutes > 0 ? ' ' + minutes + ' phút' : '');
        else                text = Math.max(1, minutes) + ' phút';

        return (isLate ? 'trễ ' : 'còn ') + text;
    }

    static pad2(value) {
        return String(value).padStart(2, '0');
    }

    /** Định dạng đầy đủ: "14:30 · 18/08/2026" */
    static dateTime(iso) {
        if (!iso) return '';

        const d = new Date(iso);
        return `${Formatter.pad2(d.getHours())}:${Formatter.pad2(d.getMinutes())} · `
             + `${Formatter.pad2(d.getDate())}/${Formatter.pad2(d.getMonth() + 1)}/${d.getFullYear()}`;
    }

    /** Định dạng ngắn cho thẻ Kanban: "14:30 18/08" (thêm năm nếu khác năm hiện tại). */
    static shortDateTime(iso) {
        if (!iso) return '';

        const d    = new Date(iso);
        const year = d.getFullYear() !== new Date().getFullYear() ? '/' + d.getFullYear() : '';

        return `${Formatter.pad2(d.getHours())}:${Formatter.pad2(d.getMinutes())} `
             + `${Formatter.pad2(d.getDate())}/${Formatter.pad2(d.getMonth() + 1)}${year}`;
    }

    /** Thời gian tương đối cho dòng hoạt động: "3 phút trước". */
    static timeAgo(iso) {
        if (!iso) return '';

        const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);

        if (seconds < 60)     return 'vừa xong';
        if (seconds < 3600)   return Math.floor(seconds / 60) + ' phút trước';
        if (seconds < 86400)  return Math.floor(seconds / 3600) + ' giờ trước';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' ngày trước';

        return Formatter.dateTime(iso);
    }

    /** Đổi chuỗi ISO sang định dạng mà ô <input type="datetime-local"> hiểu được. */
    static toInputValue(iso) {
        if (!iso) return '';

        const d = new Date(iso);
        return `${d.getFullYear()}-${Formatter.pad2(d.getMonth() + 1)}-${Formatter.pad2(d.getDate())}`
             + `T${Formatter.pad2(d.getHours())}:${Formatter.pad2(d.getMinutes())}`;
    }
}
