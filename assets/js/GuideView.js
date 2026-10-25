/**
 * TRANG HƯỚNG DẪN SỬ DỤNG
 *
 * Toàn bộ nội dung chỉ dẫn của hệ thống được gom về một nơi duy nhất tại đây,
 * để các màn hình làm việc (Kanban, Danh sách, Cấu hình…) giữ được sự gọn gàng.
 */
class GuideView extends View {

    /** Mục lục — dùng chung cho cả thanh mục lục bên trái và nội dung bên phải. */
    static SECTIONS = [
        { id: 'batdau',    title: 'Bắt đầu' },
        { id: 'gocnhin',   title: 'Ba góc nhìn công việc' },
        { id: 'congviec',  title: 'Quản lý công việc' },
        { id: 'canhbao',   title: 'Cảnh báo hạn chót' },
        { id: 'thanhvien', title: 'Thành viên và phân quyền' },
        { id: 'discord',   title: 'Kết nối Discord' },
        { id: 'chaynen',   title: 'Cơ chế nhắc việc nền' },
        { id: 'kientruc',  title: 'Kiến trúc mã nguồn' },
        { id: 'suco',      title: 'Xử lý sự cố' }
    ];

    constructor(context) {
        super(context);
        this.rendered = false;
    }

    /** Nội dung tĩnh nên chỉ cần dựng một lần cho cả phiên làm việc. */
    render() {
        const host = this.el('guideContent');
        if (!host || this.rendered) return;

        host.innerHTML = `
            <div class="guide-layout">
                <nav class="guide-toc" aria-label="Mục lục hướng dẫn">
                    <div class="card-title" style="margin-bottom:12px">Nội dung</div>
                    ${GuideView.SECTIONS.map((section, index) => `
                        <a class="toc-link${index === 0 ? ' is-active' : ''}"
                           href="#guide-${section.id}" data-toc="${section.id}">
                            <span class="toc-num">${String(index + 1).padStart(2, '0')}</span>
                            <span>${Formatter.escape(section.title)}</span>
                        </a>`).join('')}
                </nav>

                <div class="guide-body">
                    ${this.sectionBatDau()}
                    ${this.sectionGocNhin()}
                    ${this.sectionCongViec()}
                    ${this.sectionCanhBao()}
                    ${this.sectionThanhVien()}
                    ${this.sectionDiscord()}
                    ${this.sectionChayNen()}
                    ${this.sectionKienTruc()}
                    ${this.sectionSuCo()}
                </div>
            </div>`;

        this.bindEvents();
        this.rendered = true;
    }

    // -----------------------------------------------------------------------
    // KHUNG CHUNG
    // -----------------------------------------------------------------------

    block(id, index, title, lead, body) {
        return `
            <section class="guide-section" id="guide-${id}">
                <div class="guide-head">
                    <span class="guide-index">${String(index).padStart(2, '0')}</span>
                    <div>
                        <h2>${Formatter.escape(title)}</h2>
                        ${lead ? `<p class="guide-lead">${lead}</p>` : ''}
                    </div>
                </div>
                ${body}
            </section>`;
    }

    steps(items) {
        return `<ol class="guide-steps">${items.map(text => `<li>${text}</li>`).join('')}</ol>`;
    }

    // -----------------------------------------------------------------------
    sectionBatDau() {
        return this.block('batdau', 1, 'Bắt đầu',
            'Làm quen với bố cục màn hình và cách chuyển giữa các khu vực làm việc.',
            `
            <div class="guide-grid">
                <div class="guide-card">
                    <h3>Bố cục màn hình</h3>
                    <ul class="guide-list">
                        <li><strong>Thanh trái</strong> — chọn dự án đang xem và chuyển giữa các khu vực.</li>
                        <li><strong>Thanh trên</strong> — tên khu vực, trạng thái đồng bộ và nút thêm công việc.</li>
                        <li><strong>Vùng giữa</strong> — nội dung của khu vực đang mở.</li>
                    </ul>
                </div>

                <div class="guide-card">
                    <h3>Sáu khu vực chính</h3>
                    <ul class="guide-list">
                        <li><strong>Tổng quan</strong> — số liệu thống kê và tiến độ từng dự án.</li>
                        <li><strong>Công việc</strong> — nơi làm việc chính, có ba góc nhìn.</li>
                        <li><strong>Thành viên</strong> — danh sách người tham gia dự án.</li>
                        <li><strong>Thông báo &amp; Webhook</strong> — cấu hình nhắc việc.</li>
                        <li><strong>Nhật ký nhắc việc</strong> — lịch sử cảnh báo đã phát ra.</li>
                        <li><strong>Hướng dẫn sử dụng</strong> — trang bạn đang xem.</li>
                    </ul>
                </div>
            </div>

            <h3 class="guide-subhead">Đổi dự án đang xem</h3>
            <p class="guide-text">
                Dùng ô chọn ở đầu thanh trái. Mọi khu vực đều bám theo dự án đang chọn.
                Chọn <em>+ Tạo dự án mới…</em> ở cuối danh sách để lập một dự án khác.
            </p>`
        );
    }

    // -----------------------------------------------------------------------
    sectionGocNhin() {
        return this.block('gocnhin', 2, 'Ba góc nhìn công việc',
            'Cùng một tập dữ liệu, ba cách trình bày. Chuyển qua lại tức thì, không tải lại trang.',
            `
            <div class="guide-grid guide-grid-3">
                <div class="guide-card">
                    <div class="guide-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="9.5" y="4" width="5" height="11" rx="1.5"/><rect x="16" y="4" width="5" height="14" rx="1.5"/>
                        </svg>
                    </div>
                    <h3>Kanban</h3>
                    <p class="guide-text">Bốn cột theo trạng thái. Kéo thẻ giữa các cột để đổi trạng thái công việc.</p>
                </div>

                <div class="guide-card">
                    <div class="guide-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </div>
                    <h3>Danh sách</h3>
                    <p class="guide-text">Dạng bảng. Bấm vào tiêu đề cột để sắp xếp, bấm lần nữa để đảo chiều.</p>
                </div>

                <div class="guide-card">
                    <div class="guide-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    </div>
                    <h3>Lịch</h3>
                    <p class="guide-text">Trải công việc lên lịch theo hạn chót. Xem theo tháng, tuần hoặc danh sách.</p>
                </div>
            </div>

            <h3 class="guide-subhead">Mẹo dùng nhanh</h3>
            <ul class="guide-list">
                <li>Ở góc nhìn <strong>Danh sách</strong>, cột <em>Hạn chót</em> được sắp sẵn từ gấp nhất tới thong thả nhất. Công việc đã hoàn thành luôn nằm cuối bảng.</li>
                <li>Ở góc nhìn <strong>Lịch</strong>, bấm vào một ô ngày trống để tạo công việc có hạn chót đúng ngày đó.</li>
                <li>Ô tìm kiếm và hai bộ lọc trên thanh công cụ có tác dụng với cả ba góc nhìn.</li>
            </ul>`
        );
    }

    // -----------------------------------------------------------------------
    sectionCongViec() {
        return this.block('congviec', 3, 'Quản lý công việc',
            'Thêm, sửa, phân công và chuyển trạng thái công việc.',
            `
            <h3 class="guide-subhead">Thêm công việc</h3>
            ${this.steps([
                'Bấm <strong>Công việc mới</strong> ở góc trên bên phải — hoặc bấm dấu <strong>+</strong> trên đầu một cột Kanban để thêm thẳng vào cột đó.',
                'Nhập tiêu đề. Đây là trường bắt buộc duy nhất.',
                'Chọn người thực hiện, mức ưu tiên và hạn chót. Hạn chót đặt được tới từng phút.',
                'Bấm <strong>Lưu công việc</strong>.'
            ])}

            <h3 class="guide-subhead">Sửa hoặc xoá</h3>
            <p class="guide-text">
                Bấm vào một thẻ trên bảng Kanban, một dòng trong bảng danh sách, hoặc một sự kiện trên lịch —
                hộp thoại chi tiết sẽ mở ra. Nút <em>Xoá công việc</em> nằm ở góc dưới bên trái hộp thoại.
            </p>

            <h3 class="guide-subhead">Chuyển trạng thái bằng kéo thả</h3>
            <p class="guide-text">
                Giữ chuột trên một thẻ rồi kéo sang cột khác. Vị trí mới được lưu ngay, các thành viên khác
                sẽ thấy thay đổi ở lần đồng bộ kế tiếp.
            </p>

            <div class="guide-note">
                Khi đang bật bộ lọc hoặc tìm kiếm, hệ thống chỉ đổi trạng thái mà không ghi lại thứ tự thẻ —
                vì danh sách đang nhìn thấy không đầy đủ, ghi thứ tự sẽ làm xáo trộn những thẻ bị ẩn.
            </div>

            <h3 class="guide-subhead">Phím tắt</h3>
            <div class="guide-keys">
                <div class="key-row"><kbd>N</kbd><span>Mở hộp thoại thêm công việc mới</span></div>
                <div class="key-row"><kbd>Esc</kbd><span>Đóng hộp thoại đang mở</span></div>
                <div class="key-row"><kbd>Enter</kbd><span>Lưu ngay khi con trỏ đang ở ô tiêu đề</span></div>
            </div>`
        );
    }

    // -----------------------------------------------------------------------
    sectionCanhBao() {
        return this.block('canhbao', 4, 'Cảnh báo hạn chót',
            'Hệ thống tự đổi màu thẻ công việc theo thời gian còn lại.',
            `
            <div class="guide-legend">
                <div class="legend-card">
                    <div class="task-card is-overdue legend-demo">
                        <div class="tc-title">Sửa lỗi hiển thị giá</div>
                        <div class="tc-foot">
                            <span class="spacer"></span>
                            <span class="due-chip is-overdue">trễ 5 giờ</span>
                        </div>
                    </div>
                    <div class="legend-text">
                        <strong>Quá hạn</strong>
                        Viền đỏ kèm hiệu ứng nhấp nháy. Hạn chót đã trôi qua mà công việc chưa hoàn thành.
                    </div>
                </div>

                <div class="legend-card">
                    <div class="task-card is-due_soon legend-demo">
                        <div class="tc-title">Kiểm thử thanh toán</div>
                        <div class="tc-foot">
                            <span class="spacer"></span>
                            <span class="due-chip is-due_soon">còn 45 phút</span>
                        </div>
                    </div>
                    <div class="legend-text">
                        <strong>Sắp tới hạn</strong>
                        Viền cam. Thời gian còn lại đã lọt vào ngưỡng cấu hình của dự án (mặc định 2 giờ).
                    </div>
                </div>

                <div class="legend-card">
                    <div class="task-card legend-demo">
                        <div class="tc-title">Viết tài liệu đặc tả</div>
                        <div class="tc-foot">
                            <span class="spacer"></span>
                            <span class="due-chip">còn 4 ngày</span>
                        </div>
                    </div>
                    <div class="legend-text">
                        <strong>Còn hạn</strong>
                        Hiển thị bình thường. Nhãn thời gian tự đếm ngược mà không cần tải lại trang.
                    </div>
                </div>
            </div>

            <h3 class="guide-subhead">Đổi ngưỡng cảnh báo</h3>
            <p class="guide-text">
                Vào <strong>Thông báo &amp; Webhook</strong> → mục <em>Coi là “sắp tới hạn” khi còn</em>.
                Ngưỡng này áp dụng riêng cho từng dự án, quyết định cả thời điểm thẻ đổi màu lẫn thời điểm
                hệ thống gửi thông báo.
            </p>`
        );
    }

    // -----------------------------------------------------------------------
    sectionThanhVien() {
        return this.block('thanhvien', 5, 'Thành viên và phân quyền',
            'Mỗi dự án có một chủ dự án và nhiều thành viên, với quyền hạn khác nhau.',
            `
            <div class="guide-table-wrap">
                <table class="guide-table">
                    <thead>
                        <tr><th>Thao tác</th><th>Chủ dự án</th><th>Thành viên</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Xem và tìm kiếm công việc</td><td class="yes">Có</td><td class="yes">Có</td></tr>
                        <tr><td>Thêm, sửa, xoá, kéo thả công việc</td><td class="yes">Có</td><td class="yes">Có</td></tr>
                        <tr><td>Xem cấu hình nhắc việc</td><td class="yes">Có</td><td class="yes">Có</td></tr>
                        <tr><td>Đọc đường dẫn webhook đầy đủ</td><td class="yes">Có</td><td class="no">Không</td></tr>
                        <tr><td>Sửa cấu hình và webhook</td><td class="yes">Có</td><td class="no">Không</td></tr>
                        <tr><td>Thêm hoặc xoá thành viên</td><td class="yes">Có</td><td class="no">Không</td></tr>
                        <tr><td>Đổi thông tin hoặc xoá dự án</td><td class="yes">Có</td><td class="no">Không</td></tr>
                    </tbody>
                </table>
            </div>

            <h3 class="guide-subhead">Thêm thành viên</h3>
            ${this.steps([
                'Mở khu vực <strong>Thành viên</strong>.',
                'Gõ tên đăng nhập hoặc email vào ô bên phải — hệ thống gợi ý ngay từ ký tự thứ hai.',
                'Chọn đúng người trong danh sách gợi ý rồi bấm <strong>Thêm vào dự án</strong>.'
            ])}

            <div class="guide-note">
                Người được thêm phải có sẵn tài khoản trên hệ thống. Nếu chưa có, họ cần tự đăng ký trước.
                Công việc chỉ phân công được cho người đã nằm trong danh sách thành viên của dự án.
            </div>`
        );
    }

    // -----------------------------------------------------------------------
    sectionDiscord() {
        return this.block('discord', 6, 'Kết nối Discord',
            'Đẩy thông báo nhắc việc thẳng vào kênh chat của nhóm.',
            `
            <h3 class="guide-subhead">Lấy đường dẫn webhook</h3>
            ${this.steps([
                'Mở Discord, bấm chuột phải vào kênh muốn nhận thông báo, chọn <strong>Chỉnh sửa kênh</strong>.',
                'Vào mục <strong>Tích hợp</strong> → <strong>Webhook</strong> → <strong>Webhook mới</strong>.',
                'Bấm <strong>Sao chép URL Webhook</strong>.',
                'Quay lại hệ thống, vào <strong>Thông báo &amp; Webhook</strong>, dán vào ô <em>Đường dẫn Webhook</em>.',
                'Bấm <strong>Gửi tin nhắn thử</strong> để xác nhận kết nối, sau đó bấm <strong>Lưu cấu hình</strong>.'
            ])}

            <div class="guide-note is-warn">
                Đường dẫn webhook là thông tin bí mật — bất kỳ ai có nó đều gửi được tin nhắn vào kênh của bạn.
                Thành viên thường chỉ nhìn thấy bản đã che. Đừng đăng đường dẫn này lên nơi công khai.
            </div>

            <h3 class="guide-subhead">Ba loại thông báo</h3>
            <ul class="guide-list">
                <li><strong>Sắp tới hạn</strong> — gửi khi công việc lọt vào ngưỡng cảnh báo của dự án.</li>
                <li><strong>Quá hạn</strong> — gửi khi hạn chót đã trôi qua mà công việc chưa xong.</li>
                <li><strong>Vừa hoàn thành</strong> — gửi ngay khi có người kéo thẻ sang cột <em>Hoàn thành</em>.</li>
            </ul>

            <p class="guide-text">
                Mỗi công việc chỉ được nhắc <strong>đúng một lần</strong> cho mỗi loại sự kiện, dù hệ thống
                có quét qua nó bao nhiêu lượt đi nữa. Khi bạn đổi hạn chót hoặc mở lại một việc đã hoàn thành,
                hệ thống tự cho phép nhắc lại từ đầu.
            </p>

            <h3 class="guide-subhead">Chế độ giả lập</h3>
            <p class="guide-text">
                Bật công tắc <em>Chế độ giả lập</em> khi máy không có kết nối Internet. Hệ thống vẫn dựng đủ
                nội dung tin nhắn và ghi vào nhật ký, nhưng không gọi ra Discord thật.
            </p>`
        );
    }

    // -----------------------------------------------------------------------
    sectionChayNen() {
        return this.block('chaynen', 7, 'Cơ chế nhắc việc nền',
            'Cách hệ thống phát hiện công việc tới hạn khi bạn không thao tác gì.',
            `
            <div class="guide-grid">
                <div class="guide-card">
                    <h3>Khi có người mở web</h3>
                    <p class="guide-text">
                        Giao diện tự gọi tiến trình quét mỗi <strong>15 giây</strong>. Nhưng máy chủ chỉ thực sự
                        quét deadline <strong>60 giây một lần</strong> — dù cả nhóm cùng mở web thì tải lên cơ sở
                        dữ liệu vẫn không đổi.
                    </p>
                </div>

                <div class="guide-card">
                    <h3>Khi không ai mở web</h3>
                    <p class="guide-text">
                        Chạy tiến trình nền độc lập bằng dòng lệnh. Nó dùng chung nhịp quét với giao diện nên
                        không bao giờ quét trùng.
                    </p>
                    <div class="guide-code">cd C:\\xampp\\htdocs\\webnhacviec-v7
C:\\xampp\\php\\php.exe worker.php</div>
                    <p class="guide-text">Nhấn <kbd>Ctrl</kbd> + <kbd>C</kbd> trong cửa sổ đó để dừng.</p>
                </div>
            </div>

            <h3 class="guide-subhead">Quét thủ công</h3>
            <p class="guide-text">
                Vào <strong>Nhật ký nhắc việc</strong> và bấm <strong>Quét deadline ngay</strong> để chạy một lượt
                mà không phải chờ hết chu kỳ. Hệ thống sẽ báo lại số công việc đã quét và số tin nhắn đã gửi.
            </p>

            <h3 class="guide-subhead">Trạng thái đồng bộ</h3>
            <p class="guide-text">
                Chỉ báo ở thanh trên cùng cho biết lần làm mới dữ liệu gần nhất. Việc đồng bộ tự tạm dừng khi
                bạn đang kéo thẻ, đang mở hộp thoại, hoặc đã chuyển sang tab khác — và tiếp tục ngay khi bạn quay lại.
            </p>`
        );
    }

    // -----------------------------------------------------------------------
    sectionKienTruc() {
        return this.block('kientruc', 8, 'Kiến trúc MVC hướng đối tượng',
            'Mỗi Controller và Model là một class, kế thừa lớp cha trong thư mục core/.',
            `<div class="guide-grid guide-grid-3">
                <div class="guide-card"><h3>Model</h3><p class="guide-text">
                <code>models/</code>: chỉ chạy câu SQL. Ví dụ: <code>TaskModel</code> có các hàm
                <code>find()</code>, <code>insert()</code>, <code>update()</code>, <code>delete()</code>.</p></div>
                <div class="guide-card"><h3>View</h3><p class="guide-text">
                <code>views/</code>: HTML và các lệnh PHP in dữ liệu.
                CSS và JavaScript nằm trong <code>assets/</code>.</p></div>
                <div class="guide-card"><h3>Controller</h3><p class="guide-text">
                <code>controllers/</code>: kiểm tra đăng nhập, quyền và dữ liệu rồi gọi Model.</p></div>
            </div>
            <h3 class="guide-subhead">Ví dụ: thêm công việc</h3>
            <p class="guide-text"><code>index.php?url=task/create</code>
                → <code>Router</code> tạo <code>TaskController</code> và gọi <code>create()</code>
                → kiểm tra dữ liệu → <code>TaskModel::insert()</code> → MySQL → trả JSON cho giao diện.</p>
            <div class="guide-note">Dữ liệu dùng mảng: <code>$task['title']</code>.
                Thứ tự đọc: index.php → core/Router.php → Controller → Model.
                Nghiệp vụ nhắc việc và gửi Discord nằm trong <code>services/</code>.</div>`);
    }

    sectionSuCo() {
        return this.block('suco', 9, 'Xử lý sự cố',
            'Những tình huống thường gặp và cách khắc phục.',
            `
            <div class="guide-table-wrap">
                <table class="guide-table">
                    <thead><tr><th>Hiện tượng</th><th>Cách xử lý</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>Chỉ báo đồng bộ hiện <em>Mất kết nối</em></td>
                            <td>Kiểm tra Apache và MySQL trong XAMPP Control Panel đã ở trạng thái chạy chưa.</td>
                        </tr>
                        <tr>
                            <td>Kéo thả, lịch hoặc biểu đồ không hoạt động</td>
                            <td>Ba thư viện này nạp từ Internet. Kiểm tra kết nối mạng rồi tải lại trang.</td>
                        </tr>
                        <tr>
                            <td>Gửi thử Discord báo lỗi 401 hoặc 404</td>
                            <td>Webhook đã bị xoá bên Discord. Tạo webhook mới rồi dán lại đường dẫn.</td>
                        </tr>
                        <tr>
                            <td>Công việc quá hạn nhưng không thấy tin nhắn</td>
                            <td>Mỗi việc chỉ được nhắc một lần. Vào <em>Thông báo &amp; Webhook</em> → <em>Xoá nhật ký nhắc việc</em> để hệ thống nhắc lại.</td>
                        </tr>
                        <tr>
                            <td>Không sửa được cấu hình dự án</td>
                            <td>Bạn không phải chủ dự án. Nhờ chủ dự án chỉnh giúp hoặc nâng quyền cho bạn.</td>
                        </tr>
                        <tr>
                            <td>Đóng trình duyệt thì ngừng nhắc việc</td>
                            <td>Đó là hành vi mặc định. Chạy tiến trình nền bằng dòng lệnh như hướng dẫn ở mục 07.</td>
                        </tr>
                        <tr>
                            <td>Trang báo <em>Không tìm thấy trang</em></td>
                            <td>Đường dẫn phải có dạng <code>index.php?url=controller/action</code>, ví dụ <code>index.php?url=task/list</code>. Ứng dụng không cần bật <code>mod_rewrite</code>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>`
        );
    }

    // -----------------------------------------------------------------------
    bindEvents() {
        const links = document.querySelectorAll('.toc-link');

        links.forEach(link => {
            link.addEventListener('click', (event) => {
                event.preventDefault();

                const target = document.getElementById('guide-' + link.dataset.toc);
                if (!target) return;

                // Trừ đi chiều cao thanh trên cùng để tiêu đề không bị che khuất.
                window.scrollTo({
                    top:      target.getBoundingClientRect().top + window.scrollY - 96,
                    behavior: 'smooth'
                });
            });
        });

        // Tô sáng mục đang đọc trong mục lục theo vị trí cuộn.
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;

                const id = entry.target.id.replace('guide-', '');
                links.forEach(link => link.classList.toggle('is-active', link.dataset.toc === id));
            });
        }, { rootMargin: '-100px 0px -65% 0px' });

        document.querySelectorAll('.guide-section').forEach(section => observer.observe(section));
    }
}
