/**
 * TIỆN ÍCH GIAO DIỆN: THÔNG BÁO NỔI VÀ HỘP THOẠI
 *
 * Được tạo một lần trong bootstrap.js rồi truyền vào mọi View.
 */
class Ui {

    static TOAST_ICONS = { success: '✓', error: '✕', warn: '!', info: 'i' };

    constructor(document) {
        this.document = document;
    }

    el(id) {
        return this.document.getElementById(id);
    }

    // -----------------------------------------------------------------------
    // THÔNG BÁO NỔI
    // -----------------------------------------------------------------------

    toast(message, type = 'success', duration = 3800) {
        const stack = this.el('toastStack');
        if (!stack) return;

        const item = this.document.createElement('div');
        item.className = 'toast toast-' + type;
        item.innerHTML = `<span class="toast-icon">${Ui.TOAST_ICONS[type] || 'i'}</span>
                          <span class="toast-text">${Formatter.escape(message)}</span>`;

        stack.appendChild(item);

        setTimeout(() => {
            item.classList.add('is-leaving');
            setTimeout(() => item.remove(), 300);
        }, duration);
    }

    // -----------------------------------------------------------------------
    // HỘP THOẠI
    // -----------------------------------------------------------------------

    openModal(id) {
        const modal = this.el(id);
        if (!modal) return;

        modal.classList.add('is-open');
        this.document.body.style.overflow = 'hidden';

        // Đưa con trỏ vào ô nhập đầu tiên cho thao tác bàn phím được liền mạch.
        setTimeout(() => {
            const firstInput = modal.querySelector('input:not([type=hidden]), textarea');
            if (firstInput) firstInput.focus();
        }, 120);
    }

    closeModal(id) {
        const modal = this.el(id);
        if (!modal) return;

        modal.classList.remove('is-open');
        this.document.body.style.overflow = '';
    }

    closeAllModals() {
        this.document.querySelectorAll('.modal-backdrop.is-open')
            .forEach(modal => modal.classList.remove('is-open'));

        this.document.body.style.overflow = '';
    }

    hasOpenModal() {
        return this.document.querySelector('.modal-backdrop.is-open') !== null;
    }

    /** Hộp thoại xác nhận. Trả về Promise<boolean>. */
    confirm(message, title = 'Xác nhận', okLabel = 'Đồng ý') {
        return new Promise(resolve => {
            this.el('confirmTitle').textContent   = title;
            this.el('confirmMessage').textContent = message;

            const okButton = this.el('confirmOkBtn');
            okButton.textContent = okLabel;

            // Thay nút bằng bản sao để gỡ sạch listener của lần mở trước.
            const freshButton = okButton.cloneNode(true);
            okButton.replaceWith(freshButton);

            const finish = (result) => {
                this.closeModal('confirmModal');
                resolve(result);
            };

            freshButton.addEventListener('click', () => finish(true), { once: true });

            this.el('confirmModal').querySelectorAll('[data-close-modal]').forEach(button => {
                button.addEventListener('click', () => finish(false), { once: true });
            });

            this.openModal('confirmModal');
        });
    }

    // -----------------------------------------------------------------------
    // KHỐI HIỂN THỊ KHI KHÔNG CÓ DỮ LIỆU
    // -----------------------------------------------------------------------

    static emptyState(title, description, actionHtml = '') {
        return `
            <div class="empty-state">
                <div class="es-glyph">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>
                    </svg>
                </div>
                <h3>${Formatter.escape(title)}</h3>
                <p>${Formatter.escape(description)}</p>
                ${actionHtml}
            </div>`;
    }

    /** Hoãn thực thi cho tới khi người dùng ngừng gõ (dùng cho ô tìm kiếm). */
    static debounce(fn, wait = 280) {
        let timer;

        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), wait);
        };
    }
}
