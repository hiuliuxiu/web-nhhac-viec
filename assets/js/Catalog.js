/** Nhãn và màu của các trạng thái dùng trên giao diện. */
class Catalog {

    static STATUSES = ['todo', 'in_progress', 'review', 'done'];

    static STATUS_LABELS = {
        todo:        'Cần làm',
        in_progress: 'Đang làm',
        review:      'Chờ duyệt',
        done:        'Hoàn thành'
    };

    static STATUS_COLORS = {
        todo:        '#94a3b8',
        in_progress: '#3b82f6',
        review:      '#a855f7',
        done:        '#10b981'
    };

    static PRIORITY_LABELS = {
        low:    'Thấp',
        medium: 'Trung bình',
        high:   'Cao',
        urgent: 'Khẩn cấp'
    };

    /** Thứ tự sắp xếp: khẩn cấp lên đầu. */
    static PRIORITY_WEIGHT = { urgent: 0, high: 1, medium: 2, low: 3 };

    static statusLabel(status) {
        return Catalog.STATUS_LABELS[status] || status;
    }

    static statusColor(status) {
        return Catalog.STATUS_COLORS[status] || '#94a3b8';
    }

    static statusIndex(status) {
        return Catalog.STATUSES.indexOf(status);
    }

    static priorityLabel(priority) {
        return Catalog.PRIORITY_LABELS[priority] || priority;
    }

    static priorityWeight(priority) {
        const weight = Catalog.PRIORITY_WEIGHT[priority];
        return weight === undefined ? 99 : weight;
    }
}
