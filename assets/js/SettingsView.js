/**
 * F08 + F10 - TRANG CẤU HÌNH NHẮC VIỆC VÀ WEBHOOK DISCORD
 *
 * Chủ dự án thấy biểu mẫu chỉnh sửa; thành viên thường thấy bản chỉ xem —
 * đúng với phân quyền mà máy chủ áp dụng, nên giao diện không hứa hẹn điều gì
 * mà API sẽ từ chối.
 */
class SettingsView extends View {

    /** Các mốc ngưỡng "sắp tới hạn" cho người dùng chọn nhanh. */
    static THRESHOLDS = [
        { value: 15,    label: '15 phút trước hạn' },
        { value: 30,    label: '30 phút trước hạn' },
        { value: 60,    label: '1 giờ trước hạn' },
        { value: 120,   label: '2 giờ trước hạn (mặc định)' },
        { value: 240,   label: '4 giờ trước hạn' },
        { value: 480,   label: '8 giờ trước hạn' },
        { value: 1440,  label: '1 ngày trước hạn' },
        { value: 2880,  label: '2 ngày trước hạn' },
        { value: 10080, label: '7 ngày trước hạn' }
    ];

    render() {
        const host = this.el('settingsContent');
        if (!host) return;

        const project = this.store.currentProject();

        if (!project) {
            host.innerHTML = Ui.emptyState(
                'Chưa chọn dự án',
                'Hãy chọn hoặc tạo một dự án ở thanh bên trái trước.'
            );
            return;
        }

        host.innerHTML = this.store.isOwner()
            ? this.ownerViewHtml(project)
            : this.memberViewHtml(project);

        this.bindEvents(project);
    }

    // -----------------------------------------------------------------------
    // Giao diện dành cho chủ dự án
    // -----------------------------------------------------------------------

    ownerViewHtml(project) {
        const thresholdOptions = SettingsView.THRESHOLDS.map(item =>
            `<option value="${item.value}" ${item.value === project.remind_before_minutes ? 'selected' : ''}>${
                Formatter.escape(item.label)
            }</option>`
        ).join('');

        return `
        <div class="settings-grid stagger">

          <!-- ===== Cột trái: kết nối Discord ===== -->
          <div>
            <div class="card">
              <div class="card-head">
                <div class="card-title">Kết nối Discord</div>
                <span class="badge ${project.discord_enabled ? 'badge-success' : ''}">
                  <span class="badge-dot" style="background:${project.discord_enabled ? 'var(--success)' : 'var(--ink-faint)'}"></span>
                  ${project.discord_enabled ? 'Đang bật' : 'Đang tắt'}
                </span>
              </div>

              <div id="settingsAlert" class="alert alert-error hidden"></div>

              <div class="field">
                <label for="webhookInput">Đường dẫn Webhook</label>
                <input type="text" id="webhookInput" placeholder="https://discord.com/api/webhooks/..."
                       value="${Formatter.escape(project.discord_webhook_url || '')}" autocomplete="off" spellcheck="false">
              </div>

              ${project.has_webhook ? `
                <div class="mono-box" style="margin-bottom:14px">
                  <strong>Đang lưu:</strong><br>${Formatter.escape(project.webhook_masked)}
                </div>` : ''}

              <div style="display:flex;gap:9px;flex-wrap:wrap;align-items:center">
                <button class="btn" id="testWebhookBtn">
                  <span class="btn-label">Gửi tin nhắn thử</span>
                  <span class="btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m4 12 16-8-6 16-2.5-6L4 12Z"/></svg>
                  </span>
                </button>
              </div>
            </div>

            <!-- ===== Loại thông báo ===== -->
            <div class="card section-gap">
              <div class="card-head"><div class="card-title">Khi nào thì gửi thông báo?</div></div>

              ${this.switchRowHtml(
                  'setDiscordEnabled',
                  'Bật gửi nhắc nhở lên Discord',
                  'Công tắc tổng. Tắt công tắc này thì mọi thông báo chỉ hiện trong hệ thống.',
                  project.discord_enabled
              )}
              ${this.switchRowHtml(
                  'setNotifyDueSoon',
                  'Công việc sắp tới hạn',
                  'Nhắc trước khi công việc chạm hạn chót.',
                  project.notify_due_soon
              )}
              ${this.switchRowHtml(
                  'setNotifyOverdue',
                  'Công việc đã quá hạn',
                  'Cảnh báo khi hạn chót đã trôi qua mà việc chưa xong.',
                  project.notify_overdue
              )}
              ${this.switchRowHtml(
                  'setNotifyCompleted',
                  'Công việc vừa hoàn thành',
                  'Báo ngay khi ai đó kéo thẻ sang cột “Hoàn thành”.',
                  project.notify_completed
              )}
              ${this.switchRowHtml(
                  'setMockMode',
                  'Chế độ giả lập',
                  'Không gọi Discord thật, chỉ ghi nội dung tin nhắn vào trang Thông báo. Dùng khi máy không có kết nối Internet.',
                  project.mock_mode
              )}

              <div class="field section-gap" style="margin-bottom:0">
                <label for="setThreshold">Coi là “sắp tới hạn” khi còn</label>
                <select id="setThreshold">${thresholdOptions}</select>
              </div>

              <button class="btn btn-primary btn-block section-gap" id="saveSettingsBtn">
                <span class="btn-label">Lưu cấu hình</span>
                <span class="btn-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                </span>
              </button>
            </div>
          </div>

          <!-- ===== Cột phải: thông tin dự án ===== -->
          <div>
            <div class="card">
              <div class="card-head"><div class="card-title">Thông tin dự án</div></div>

              <div style="display:flex;align-items:center;gap:12px;margin-bottom:15px">
                <span style="width:34px;height:34px;border-radius:11px;flex-shrink:0;background:${Formatter.escape(project.color)}"></span>
                <div style="min-width:0">
                  <div style="font-size:14.5px;font-weight:700">${Formatter.escape(project.name)}</div>
                  <div class="muted small">${project.total_tasks} công việc · ${project.member_count} thành viên</div>
                </div>
              </div>

              ${project.description
                  ? `<p class="muted small" style="margin-bottom:15px;line-height:1.6">${Formatter.escape(project.description)}</p>`
                  : ''}

              <button class="btn btn-block" id="editProjectBtn">Sửa thông tin dự án</button>
            </div>

            <div class="card section-gap">
              <div class="card-head"><div class="card-title">Vùng nguy hiểm</div></div>

              <div class="danger-row">
                <div>
                  <strong>Xoá lịch sử thông báo</strong>
                  <span>Cho phép hệ thống nhắc lại từ đầu những công việc đã từng được thông báo.</span>
                </div>
                <button class="btn btn-sm" id="clearLogBtn">Xoá lịch sử</button>
              </div>

              <div class="danger-row">
                <div>
                  <strong>Xoá dự án này</strong>
                  <span>Xoá vĩnh viễn toàn bộ công việc, thành viên và thông báo bên trong.</span>
                </div>
                <button class="btn btn-danger btn-sm" id="deleteProjectFromSettings">Xoá dự án</button>
              </div>
            </div>
          </div>
        </div>`;
    }

    switchRowHtml(id, title, description, checked) {
        return `
            <div class="switch-row">
              <div class="switch-text">
                <strong>${Formatter.escape(title)}</strong>
                <span>${Formatter.escape(description)}</span>
              </div>
              <label class="switch">
                <input type="checkbox" id="${id}" ${checked ? 'checked' : ''}>
                <span class="switch-track"></span>
              </label>
            </div>`;
    }

    // -----------------------------------------------------------------------
    // Giao diện dành cho thành viên thường (chỉ xem)
    // -----------------------------------------------------------------------

    memberViewHtml(project) {
        const badge = (on, onText = 'Bật', offText = 'Tắt') =>
            `<span class="badge ${on ? 'badge-success' : ''}">${on ? onText : offText}</span>`;

        return `
        <div class="settings-grid stagger">
          <div class="card">
            <div class="card-head">
              <div class="card-title">Cấu hình nhắc việc</div>
              <span class="badge">Chỉ xem</span>
            </div>

            <div class="alert alert-info">
              Chỉ <strong>chủ dự án</strong> mới thay đổi được webhook và các tuỳ chọn thông báo.
              Bạn vẫn nhận đầy đủ cảnh báo trên giao diện.
            </div>

            <div class="switch-row">
              <div class="switch-text"><strong>Gửi nhắc nhở lên Discord</strong><span>Công tắc tổng của dự án</span></div>
              ${badge(project.discord_enabled, 'Đang bật', 'Đang tắt')}
            </div>
            <div class="switch-row">
              <div class="switch-text"><strong>Nhắc việc sắp tới hạn</strong></div>
              ${badge(project.notify_due_soon)}
            </div>
            <div class="switch-row">
              <div class="switch-text"><strong>Cảnh báo việc quá hạn</strong></div>
              ${badge(project.notify_overdue)}
            </div>
            <div class="switch-row">
              <div class="switch-text"><strong>Báo khi hoàn thành</strong></div>
              ${badge(project.notify_completed)}
            </div>
            <div class="switch-row">
              <div class="switch-text"><strong>Ngưỡng “sắp tới hạn”</strong></div>
              <span class="badge badge-accent">${project.remind_before_minutes} phút</span>
            </div>
          </div>

          <div class="card">
            <div class="card-head"><div class="card-title">Thông tin dự án</div></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:15px">
              <span style="width:34px;height:34px;border-radius:11px;flex-shrink:0;background:${Formatter.escape(project.color)}"></span>
              <div style="min-width:0">
                <div style="font-size:14.5px;font-weight:700">${Formatter.escape(project.name)}</div>
                <div class="muted small">Chủ dự án: ${Formatter.escape(project.owner_name)}</div>
              </div>
            </div>
            ${project.description
                ? `<p class="muted small" style="line-height:1.6">${Formatter.escape(project.description)}</p>`
                : ''}
          </div>
        </div>`;
    }

    // -----------------------------------------------------------------------
    // GẮN SỰ KIỆN
    // -----------------------------------------------------------------------

    bindEvents(project) {
        const alertBox = this.el('settingsAlert');

        const showError = (message) => {
            if (!alertBox) { this.ui.toast(message, 'error'); return; }
            alertBox.textContent = message;
            alertBox.classList.remove('hidden');
        };

        const hideError = () => alertBox && alertBox.classList.add('hidden');

        this.bindTestWebhook(project, showError, hideError);
        this.bindSave(project, showError, hideError);

        this.el('editProjectBtn')?.addEventListener('click', () => this.app.openProjectModal(project.id));
        this.el('clearLogBtn')?.addEventListener('click', () => this.clearLog(project));
        this.el('deleteProjectFromSettings')?.addEventListener('click',
            () => this.app.deleteProject(project.id, project.name));
    }

    bindTestWebhook(project, showError, hideError) {
        const button = this.el('testWebhookBtn');
        if (!button) return;

        button.addEventListener('click', async () => {
            hideError();

            const webhookUrl = this.el('webhookInput').value.trim();
            if (!webhookUrl) {
                showError('Hãy dán đường dẫn webhook vào ô phía trên trước khi gửi thử.');
                return;
            }

            const label = button.querySelector('.btn-label');
            button.disabled  = true;
            label.textContent = 'Đang gửi…';

            try {
                const result = await this.api.testWebhook({
                    project_id:          project.id,
                    discord_webhook_url: webhookUrl
                });

                this.ui.toast(result.message, result.mocked ? 'info' : 'success', 5200);
            } catch (err) {
                showError(err.message);
                this.ui.toast('Gửi tin nhắn thử thất bại.', 'error');
            }

            button.disabled   = false;
            label.textContent = 'Gửi tin nhắn thử';
        });
    }

    bindSave(project, showError, hideError) {
        const button = this.el('saveSettingsBtn');
        if (!button) return;

        button.addEventListener('click', async () => {
            hideError();

            const label = button.querySelector('.btn-label');
            button.disabled   = true;
            label.textContent = 'Đang lưu…';

            try {
                await this.api.saveSettings({
                    project_id:            project.id,
                    discord_webhook_url:   this.el('webhookInput').value.trim(),
                    discord_enabled:       this.el('setDiscordEnabled').checked,
                    mock_mode:             this.el('setMockMode').checked,
                    notify_due_soon:       this.el('setNotifyDueSoon').checked,
                    notify_overdue:        this.el('setNotifyOverdue').checked,
                    notify_completed:      this.el('setNotifyCompleted').checked,
                    remind_before_minutes: parseInt(this.el('setThreshold').value, 10)
                });

                await this.store.loadProjects();
                await this.store.loadTasks();

                this.ui.toast('Đã lưu cấu hình nhắc việc.', 'success');
                this.render();
            } catch (err) {
                showError(err.message);
                button.disabled   = false;
                label.textContent = 'Lưu cấu hình';
            }
        });
    }

    async clearLog(project) {
        const agreed = await this.ui.confirm(
            'Toàn bộ lịch sử thông báo của dự án này sẽ bị xoá. Hệ thống sẽ nhắc lại từ đầu ở lượt quét kế tiếp.',
            'Xoá lịch sử thông báo?',
            'Xoá lịch sử'
        );
        if (!agreed) return;

        try {
            const result = await this.api.clearLog(project.id);
            this.ui.toast(result.message, 'success');
            await this.store.loadNotifications();
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }
    }
}
