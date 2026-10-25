/**
 * F06 - TRANG THÀNH VIÊN
 *
 * Danh sách người tham gia dự án, kèm khu vực mời thêm (chỉ hiện với chủ dự án).
 */
class MembersView extends View {

    render() {
        const host = this.el('memberList');
        if (!host) return;

        const members = this.store.members;
        const isOwner = this.store.isOwner();

        const badge = this.el('memberCountBadge');
        if (badge) badge.textContent = members.length + ' người';

        // Khu vực mời chỉ hiện với chủ dự án.
        const inviteCard = this.el('inviteCard');
        if (inviteCard) inviteCard.classList.toggle('hidden', !isOwner);

        if (members.length === 0) {
            host.innerHTML = Ui.emptyState('Chưa có thành viên', 'Hãy chọn một dự án ở thanh bên trái.');
            return;
        }

        host.innerHTML = members.map(member => this.rowHtml(member, isOwner)).join('');

        host.querySelectorAll('[data-remove-member]').forEach(button => {
            button.addEventListener('click', () => this.remove(parseInt(button.dataset.removeMember, 10)));
        });
    }

    rowHtml(member, viewerIsOwner) {
        const isSelf      = member.id === this.app.currentUser.id;
        const isOwnerRole = member.role === 'owner';

        const removeButton = (viewerIsOwner && !isOwnerRole)
            ? `<button class="icon-btn" data-remove-member="${member.id}" title="Xoá khỏi dự án">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
               </button>`
            : '';

        return `
            <div class="member-row">
                ${Formatter.avatar(member.full_name, member.avatar_color, 'avatar-lg')}
                <div class="mb-body">
                    <div class="mb-name">
                        ${Formatter.escape(member.full_name)}
                        ${isSelf ? '<span class="badge badge-accent" style="margin-left:6px">Bạn</span>' : ''}
                        ${isOwnerRole ? '<span class="badge" style="margin-left:6px">Chủ dự án</span>' : ''}
                    </div>
                    <div class="mb-mail">${Formatter.escape(member.email)}</div>
                </div>
                <div class="mb-stat">
                    <div><strong>${member.open_tasks}</strong> việc đang mở</div>
                    <div>${member.done_tasks} việc đã xong</div>
                </div>
                ${removeButton}
            </div>`;
    }

    // -----------------------------------------------------------------------

    async remove(userId) {
        const member = this.store.members.find(item => item.id === userId);
        if (!member) return;

        const agreed = await this.ui.confirm(
            `Xoá ${member.full_name} khỏi dự án? Các công việc đang giao cho họ sẽ chuyển về “chưa phân công”.`,
            'Xoá thành viên?',
            'Xoá thành viên'
        );
        if (!agreed) return;

        try {
            const result = await this.api.removeMember({
                project_id: this.store.currentProjectId,
                user_id:    userId
            });

            this.ui.toast(result.message, 'success');

            await this.store.loadMembers();
            await this.store.loadTasks();
            this.app.refreshAssigneeOptions();
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }
    }

    async invite() {
        const input      = this.el('inviteInput');
        const identifier = input.value.trim();

        if (!identifier) {
            this.ui.toast('Hãy nhập tên đăng nhập hoặc email.', 'warn');
            input.focus();
            return;
        }

        const button = this.el('inviteBtn');
        button.disabled = true;

        try {
            const result = await this.api.inviteMember({
                project_id: this.store.currentProjectId,
                identifier
            });

            this.ui.toast(result.message, 'success');
            input.value = '';
            this.el('inviteSuggestions').innerHTML = '';

            await this.store.loadMembers();
            await this.store.loadProjects();
            this.app.refreshAssigneeOptions();
        } catch (err) {
            this.ui.toast(err.message, 'error');
        }

        button.disabled = false;
    }

    /** Gợi ý người dùng ngay khi gõ, để đỡ phải nhớ chính xác tên đăng nhập. */
    async suggest(keyword) {
        const host = this.el('inviteSuggestions');
        if (!host) return;

        if (keyword.trim().length < 2) {
            host.innerHTML = '';
            return;
        }

        try {
            const data = await this.api.searchUsers(this.store.currentProjectId, keyword.trim());

            if (data.users.length === 0) {
                host.innerHTML = '<p class="muted small" style="margin-bottom:13px">Không tìm thấy người dùng phù hợp.</p>';
                return;
            }

            host.innerHTML = '<div style="margin-bottom:13px">' + data.users.map(user => `
                <div class="member-row" style="padding:9px 0;cursor:pointer" data-pick-user="${Formatter.escape(user.username)}">
                    ${Formatter.avatar(user.full_name, user.avatar_color, 'avatar-sm')}
                    <div class="mb-body">
                        <div class="mb-name" style="font-size:13px">${Formatter.escape(user.full_name)}</div>
                        <div class="mb-mail">${Formatter.escape(user.username)} · ${Formatter.escape(user.email)}</div>
                    </div>
                </div>`).join('') + '</div>';

            host.querySelectorAll('[data-pick-user]').forEach(row => {
                row.addEventListener('click', () => {
                    this.el('inviteInput').value = row.dataset.pickUser;
                    host.innerHTML = '';
                });
            });
        } catch (err) {
            host.innerHTML = '';
        }
    }
}
