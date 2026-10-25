/**
 * BIỂU MẪU XÁC THỰC (đăng nhập / đăng ký)
 *
 * Hai trang đăng nhập và đăng ký khác nhau ở danh sách trường và luật kiểm tra,
 * còn phần còn lại — thu thập dữ liệu, khoá nút khi đang gửi, hiện lỗi, chuyển
 * trang khi thành công — thì giống hệt. Gói vào một lớp dùng chung, mỗi trang
 * chỉ khai báo phần khác biệt của mình.
 */
class AuthForm {

    /**
     * @param {object}   options
     * @param {string}   options.formId       Id của thẻ <form>
     * @param {string}   options.endpoint     Địa chỉ API nhận dữ liệu
     * @param {string}   options.redirectTo   Nơi chuyển tới khi thành công
     * @param {string}   options.csrfToken    Mã CSRF gửi kèm để máy chủ kiểm tra
     * @param {string[]} options.fields       Các trường gửi lên máy chủ
     * @param {string[]} options.extraFields  Các trường chỉ dùng để kiểm tra tại chỗ
     * @param {string}   options.busyLabel    Chữ trên nút khi đang gửi
     * @param {string}   options.idleLabel    Chữ trên nút lúc bình thường
     * @param {Function} options.validate     Trả về câu lỗi, hoặc null nếu hợp lệ
     */
    constructor(options) {
        this.options     = options;
        this.form        = document.getElementById(options.formId);
        this.errorBox    = document.getElementById('formError');
        this.submitBtn   = document.getElementById('submitBtn');
        this.extraFields = options.extraFields || [];
    }

    mount() {
        this.form.addEventListener('submit', (event) => this.handleSubmit(event));
        return this;
    }

    // -----------------------------------------------------------------------

    collect() {
        const values = {};

        [...this.options.fields, ...this.extraFields].forEach(name => {
            const input = document.getElementById(name);
            values[name] = input ? input.value.trim() : '';
        });

        return values;
    }

    /** Chỉ những trường được khai báo trong `fields` mới đi lên máy chủ. */
    payloadFrom(values) {
        const payload = {};
        this.options.fields.forEach(name => { payload[name] = values[name]; });

        return payload;
    }

    showError(message) {
        this.errorBox.textContent = message;
        this.errorBox.classList.remove('hidden');
        this.errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    setBusy(busy) {
        this.submitBtn.disabled = busy;
        this.submitBtn.querySelector('.btn-label').textContent =
            busy ? this.options.busyLabel : this.options.idleLabel;
    }

    // -----------------------------------------------------------------------

    async handleSubmit(event) {
        event.preventDefault();
        this.errorBox.classList.add('hidden');

        const values = this.collect();
        const error  = this.options.validate ? this.options.validate(values) : null;

        if (error) {
            this.showError(error);
            return;
        }

        this.setBusy(true);

        try {
            const response = await fetch(this.options.endpoint, {
                method:      'POST',
                headers:     {
                    'Content-Type':     'application/json',
                    'X-CSRF-Token':     this.options.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body:        JSON.stringify(this.payloadFrom(values))
            });

            const data = await response.json();

            if (data.ok) {
                window.location.href = this.options.redirectTo;
                return;
            }

            this.showError(data.error || 'Thao tác không thành công.');
        } catch (err) {
            this.showError('Không kết nối được máy chủ. Hãy kiểm tra Apache trong XAMPP đã bật chưa.');
        }

        this.setBusy(false);
    }
}
