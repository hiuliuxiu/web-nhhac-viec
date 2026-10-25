/**
 * ĐIỂM KHỞI TẠO CỦA FRONTEND
 *
 * Nơi duy nhất tạo các đối tượng và nối chúng với nhau. Mọi lớp khác chỉ nhận
 * những gì cần dùng qua hàm khởi tạo, nên không lớp nào phải dùng biến toàn cục.
 */
document.addEventListener('DOMContentLoaded', () => {
    const config = window.APP_CONFIG;

    // --- Hạ tầng ------------------------------------------------------------
    const http = new HttpClient({
        baseUrl:        config.apiUrl,
        csrfToken:      config.csrfToken,
        onUnauthorized: () => { window.location.href = config.loginUrl; }
    });

    const api = new ApiClient(http);
    const ui  = new Ui(document);

    // --- Kho dữ liệu --------------------------------------------------------
    const store = new AppStore(api, { pollInterval: config.pollInterval });

    // --- Các góc nhìn -------------------------------------------------------
    const context = { store, api, ui };

    const views = {
        board:     new BoardView(context),
        list:      new ListView(context),
        calendar:  new CalendarView(context),
        dashboard: new DashboardView(context),
        members:   new MembersView(context),
        settings:  new SettingsView(context),
        log:       new LogView(context),
        guide:     new GuideView(context)
    };

    // --- Bộ điều phối -------------------------------------------------------
    const app = new App({ store, api, ui, views, config });

    window.app = app;      // tiện cho việc gỡ lỗi trong Console
    app.init();
});
