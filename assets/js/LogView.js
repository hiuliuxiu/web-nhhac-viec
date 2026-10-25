/**
 * F07 + F08 - TRANG NHẬT KÝ NHẮC VIỆC
 *
 * Lịch sử các cảnh báo hệ thống đã phát ra, lọc được theo kênh.
 */
class LogView extends View {

    static GLYPHS = {
        overdue:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5 2.5 20h19L12 3.5Z"/><path d="M12 10v4M12 17.2v.1"/></svg>`,
        due_soon:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>`,
        completed: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.2 12.3 2.6 2.6 5-5.4"/></svg>`
    };

    static STATUS_BADGES = {
        sent:    '<span class="badge badge-success">Đã gửi</span>',
        failed:  '<span class="badge badge-danger">Thất bại</span>',
        mocked:  '<span class="badge badge-warn">Giả lập</span>',
        skipped: '<span class="badge">Bỏ qua</span>'
    };

    render(payload = null) {
        const host = this.el('logList');
        if (!host) return;

        const items = payload ? payload.notifications : this.store.notifications;

        if (!items || items.length === 0) {
            host.innerHTML = Ui.emptyState(
                'Chưa có thông báo nào',
                'Khi có công việc chạm ngưỡng nhắc việc, hệ thống sẽ tự ghi lại tại đây. '
                + 'Bạn có thể bấm nút quét (mũi tên tròn) để chạy thử một lượt.'
            );
            return;
        }

        host.innerHTML = items.map(item => this.rowHtml(item)).join('');
    }

    rowHtml(item) {
        const channelBadge = item.channel === 'discord'
            ? '<span class="badge badge-accent">Discord</span>'
            : '<span class="badge">Trong hệ thống</span>';

        const statusBadge = LogView.STATUS_BADGES[item.status] || '';

        return `
            <div class="log-row">
                <span class="lg-glyph type-${Formatter.escape(item.type)}">${LogView.GLYPHS[item.type] || ''}</span>
                <div class="lg-body">
                    <div class="lg-msg">${Formatter.escape(item.message || item.task_title)}</div>
                    <div class="lg-meta">
                        <span style="color:${Formatter.escape(item.project_color)}">●</span>
                        <span>${Formatter.escape(item.project_name)}</span>
                        <span>·</span>
                        ${channelBadge}
                        ${item.channel === 'discord' ? statusBadge : ''}
                        <span>·</span>
                        <span>${Formatter.escape(Formatter.timeAgo(item.created_at))}</span>
                    </div>
                    ${item.error
                        ? `<div class="lg-err"><strong>Lỗi:</strong> ${Formatter.escape(item.error)}</div>`
                        : ''}
                </div>
            </div>`;
    }

    /** Nút tròn "Quét deadline ngay" — ép chạy một lượt quét không cần đợi chu kỳ. */
    async forceScan(button) {
        // Nút chỉ có biểu tượng: khi đang quét thì cho biểu tượng xoay và đổi chú thích
        button.disabled = true;
        button.classList.add('is-spinning');
        button.title = 'Đang quét…';

        try {
            const result = await this.api.cronForce();
            const stats  = result.stats || {};

            let message = `Đã quét ${stats.scanned || 0} công việc tới ngưỡng nhắc `
                        + `(${stats.overdue || 0} quá hạn, ${stats.due_soon || 0} sắp hạn).`;

            if (stats.logged > 0)         message += ` Ghi mới ${stats.logged} thông báo.`;
            if (stats.discord_sent > 0)   message += ` Đã đẩy ${stats.discord_sent} tin lên Discord.`;
            if (stats.discord_mocked > 0) message += ` ${stats.discord_mocked} tin ở chế độ giả lập.`;
            if (stats.discord_failed > 0) message += ` ${stats.discord_failed} tin gửi thất bại.`;

            this.ui.toast(message, stats.discord_failed > 0 ? 'warn' : 'success', 6500);

            await this.store.loadNotifications();
            await this.store.loadTasks();
            this.app.updateBadges();
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }

        button.disabled = false;
        button.classList.remove('is-spinning');
        button.title = 'Quét deadline ngay';
    }
}
