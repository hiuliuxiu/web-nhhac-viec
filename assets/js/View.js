/**
 * LỚP CHA CỦA MỌI GÓC NHÌN
 *
 * Mỗi View nhận đúng ba thứ qua hàm khởi tạo: kho dữ liệu, lớp gọi API và tiện
 * ích giao diện, nên không View nào phải dùng biến toàn cục.
 */
class View {

    /**
     * @param {object}    context
     * @param {AppStore}  context.store
     * @param {ApiClient} context.api
     * @param {Ui}        context.ui
     */
    constructor({ store, api, ui }) {
        this.store = store;
        this.api   = api;
        this.ui    = ui;

        /** Bộ điều phối ứng dụng — được App gắn vào sau khi khởi tạo xong. */
        this.app = null;
    }

    attach(app) {
        this.app = app;
        return this;
    }

    el(id) {
        return document.getElementById(id);
    }

    /** Vẽ lại nội dung của góc nhìn. Lớp con bắt buộc cài đặt. */
    render() {
    }
}
