/**
 * LỚP GIAO TIẾP HTTP
 *
 * Mọi lời gọi tới máy chủ đều đi qua đây, nhờ vậy phần xử lý chung (gắn mã CSRF,
 * bắt lỗi mạng, xử lý phiên hết hạn) chỉ phải viết một lần.
 */
class HttpClient {

    /**
     * @param {object}   options
     * @param {string}   options.baseUrl            Ví dụ: /webnhacviec-v7/index.php?url=
     * @param {string}   options.csrfToken
     * @param {Function} options.onUnauthorized     Gọi khi phiên đăng nhập hết hạn
     */
    constructor({ baseUrl, csrfToken, onUnauthorized }) {
        this.baseUrl        = baseUrl;
        this.csrfToken      = csrfToken;
        this.onUnauthorized = onUnauthorized || (() => {});
    }

    /** Ghép đường dẫn đầy đủ, ví dụ url('task/list', { project_id: 1 }) → index.php?url=task/list&project_id=1 */
    url(path, params = {}) {
        const url = new URL(this.baseUrl + String(path).replace(/^\/+/, ''), window.location.origin);

        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, value);
            }
        });

        return url.toString();
    }

    /**
     * Gửi một yêu cầu và trả về phần dữ liệu JSON.
     * Ném lỗi khi máy chủ báo thất bại, để nơi gọi dùng try/catch.
     */
    async request(path, { method = 'GET', params = {}, body = null } = {}) {
        const options = {
            method,
            // X-Requested-With cho máy chủ biết đây là lời gọi từ JavaScript, lỗi thì trả JSON
            headers:     { 'X-CSRF-Token': this.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        };

        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        let response;
        try {
            response = await fetch(this.url(path, params), options);
        } catch (networkError) {
            throw new Error('Mất kết nối tới máy chủ. Hãy kiểm tra Apache trong XAMPP.');
        }

        let data;
        try {
            data = await response.json();
        } catch (parseError) {
            throw new Error('Máy chủ trả về dữ liệu không hợp lệ (HTTP ' + response.status + ').');
        }

        // Phiên đăng nhập đã hết hạn → đưa người dùng về trang đăng nhập.
        if (response.status === 401 && data.auth_required) {
            this.onUnauthorized();
            throw new Error('Phiên làm việc đã hết hạn.');
        }

        if (!data.ok) {
            throw new Error(data.error || 'Đã xảy ra lỗi không xác định.');
        }

        return data;
    }

    get(path, params = {}) {
        return this.request(path, { method: 'GET', params });
    }

    post(path, body = {}, params = {}) {
        return this.request(path, { method: 'POST', params, body });
    }
}
