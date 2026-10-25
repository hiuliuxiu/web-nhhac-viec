-- ============================================================================
--  WEB NHẮC VIỆC - Dữ liệu mẫu để demo ngay
--  Tệp: seed.sql  |  Chạy SAU schema.sql
--
--  Mật khẩu của TẤT CẢ tài khoản demo: 123456
--
--  Ghi chú quan trọng: mọi mốc thời gian được tính theo NGÀY DEMO (@ngay):
--    - nạp trước 20 giờ      → ngày demo là hôm nay
--    - nạp từ 20 giờ trở đi  → ngày demo là ngày mai (nạp từ tối hôm trước)
--  Trong ngày demo, dự án 1 có công việc đến hạn cách nhau 1 giờ 45 phút,
--  từ 07:00 đến 21:00. Ngưỡng "sắp tới hạn" là 2 giờ, nên mở web vào lúc nào
--  trong ngày cũng có sẵn việc quá hạn (đỏ) và việc sắp tới hạn (cam) (F07).
-- ============================================================================

USE `webnhacviec_v7`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `reminders`;
TRUNCATE TABLE `tasks`;
TRUNCATE TABLE `project_members`;
TRUNCATE TABLE `projects`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Ngày demo lúc 00:00. Các mốc bên dưới viết dạng ADDTIME(<ngày>, '<giờ>').
SET @ngay = TIMESTAMP(IF(HOUR(NOW()) >= 20, CURDATE() + INTERVAL 1 DAY, CURDATE()));

-- ---------------------------------------------------------------------------
-- NGƯỜI DÙNG (mật khẩu: 123456)
-- ---------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `avatar_color`) VALUES
(1, 'an',    'annv@gmail.com',    '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Nguyễn Văn An',   '#6366f1'),
(2, 'binh',  'binhtt@gmail.com',  '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Trần Thị Bình',   '#ec4899'),
(3, 'cuong', 'cuonglm@gmail.com', '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Lê Minh Cường',   '#10b981'),
(4, 'dung',  'dungpt@gmail.com',  '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Phạm Thùy Dung',  '#f59e0b'),
(5, 'giang', 'giangdh@gmail.com', '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Đỗ Hương Giang',  '#06b6d4'),
(6, 'hai',   'haivd@gmail.com',   '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Vũ Đức Hải',      '#8b5cf6'),
(7, 'khanh', 'khanhnb@gmail.com', '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Ngô Bảo Khánh',   '#64748b'),
(8, 'linh',  'linhdt@gmail.com',  '$2y$10$Vx2mCYeuWIUxNQpryeEHhePknhCqiqkQqW7FUX0dEuLKr840k1Eky', 'Đặng Thùy Linh',  '#14b8a6');

-- ---------------------------------------------------------------------------
-- DỰ ÁN
-- Dự án 1: webhook Discord được điền sẵn nhưng CÔNG TẮC ĐANG TẮT
--   (discord_enabled = 0) để không bắn hàng loạt tin nhắn ngay khi vừa cài đặt.
--   Vào trang "Nhắc việc & Webhook" bật lên khi bạn sẵn sàng demo.
-- Dự án 3: BẬT Discord ở CHẾ ĐỘ GIẢ LẬP (mock_mode = 1) — tin nhắn chỉ được
--   dựng và ghi nhật ký với trạng thái "Giả lập", không gọi mạng thật. URL là
--   địa chỉ giả để qua bước kiểm tra định dạng. Ngưỡng nhắc 4 giờ để minh hoạ
--   mỗi dự án có cấu hình riêng (F10).
-- ---------------------------------------------------------------------------
INSERT INTO `projects`
  (`id`, `name`, `description`, `color`, `owner_id`,
   `discord_webhook_url`, `discord_enabled`, `mock_mode`,
   `notify_due_soon`, `notify_overdue`, `notify_completed`, `remind_before_minutes`, `created_at`)
VALUES
(1, 'Website Thương mại điện tử',
    'Xây dựng website bán hàng trực tuyến: giỏ hàng, thanh toán, quản trị sản phẩm.',
    '#6366f1', 1,
    'https://discord.com/api/webhooks/0000000000000000000/your-discord-webhook-url',
    0, 0, 1, 1, 1, 120, ADDTIME(@ngay - INTERVAL 30 DAY, '09:00')),

(2, 'Ứng dụng Mobile Đặt món',
    'Ứng dụng đặt món ăn trực tuyến trên nền tảng di động, có theo dõi tài xế giao hàng.',
    '#10b981', 2,
    NULL, 0, 0, 1, 1, 0, 120, ADDTIME(@ngay - INTERVAL 28 DAY, '14:00')),

(3, 'Hệ thống Quản lý Thư viện số',
    'Phần mềm quản lý mượn trả sách, độc giả và kho sách cho thư viện trường.',
    '#f59e0b', 1,
    'https://discord.com/api/webhooks/0000000000000000000/webhook-gia-lap-de-demo',
    1, 1, 1, 1, 1, 240, ADDTIME(@ngay - INTERVAL 24 DAY, '10:00')),

(4, 'Hội thảo Công nghệ cho Sinh viên',
    'Tổ chức hội thảo chia sẻ kinh nghiệm học tập và việc làm ngành CNTT cho sinh viên toàn khoa.',
    '#ec4899', 3,
    NULL, 0, 0, 1, 1, 0, 120, ADDTIME(@ngay - INTERVAL 20 DAY, '15:00'));

-- ---------------------------------------------------------------------------
-- THÀNH VIÊN DỰ ÁN
-- Tài khoản "an" có mặt ở cả 4 dự án: chủ dự án 1, 3 và thành viên dự án 2, 4.
-- ---------------------------------------------------------------------------
INSERT INTO `project_members` (`project_id`, `user_id`, `role`) VALUES
(1, 1, 'owner'), (1, 2, 'member'), (1, 3, 'member'), (1, 4, 'member'), (1, 5, 'member'),
(2, 2, 'owner'), (2, 1, 'member'), (2, 3, 'member'), (2, 6, 'member'), (2, 7, 'member'),
(3, 1, 'owner'), (3, 4, 'member'), (3, 6, 'member'), (3, 7, 'member'), (3, 8, 'member'),
(4, 3, 'owner'), (4, 1, 'member'), (4, 5, 'member'), (4, 6, 'member'), (4, 8, 'member');

-- ---------------------------------------------------------------------------
-- CÔNG VIỆC - DỰ ÁN 1: Website Thương mại điện tử
-- Chín việc đến hạn trong ngày demo (07:00 → 21:00) để luôn có việc sắp tới hạn.
-- ---------------------------------------------------------------------------
INSERT INTO `tasks`
  (`project_id`, `title`, `description`, `assignee_id`, `created_by`, `priority`, `status`, `due_at`, `position`, `completed_at`)
VALUES
-- === CẦN LÀM ===
(1, 'Thiết kế email xác nhận đơn hàng',
    'Mẫu email gửi khách sau khi đặt hàng: mã đơn, danh sách sản phẩm, tổng tiền, địa chỉ giao.',
    5, 1, 'medium', 'todo', ADDTIME(@ngay, '19:15'), 0, NULL),
(1, 'Tích hợp mã giảm giá (voucher)',
    'Nhập mã ở bước thanh toán, kiểm tra hạn dùng, giá trị đơn tối thiểu và số lượt còn lại.',
    2, 1, 'high', 'todo', ADDTIME(@ngay, '21:00'), 1, NULL),
(1, 'Thiết kế wireframe trang chủ',
    'Phác thảo bố cục trang chủ: banner, danh mục nổi bật, sản phẩm bán chạy, footer.',
    4, 1, 'high', 'todo', ADDTIME(@ngay + INTERVAL 3 DAY, '17:00'), 2, NULL),
(1, 'Viết tài liệu đặc tả API',
    'Mô tả endpoint, tham số đầu vào/ra và mã lỗi cho toàn bộ API sản phẩm và đơn hàng.',
    3, 1, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 5 DAY, '17:00'), 3, NULL),
(1, 'Chuẩn bị dữ liệu mẫu sản phẩm',
    'Nhập 200 sản phẩm mẫu kèm ảnh và mô tả để phục vụ kiểm thử.',
    2, 1, 'low', 'todo', ADDTIME(@ngay + INTERVAL 7 DAY, '17:00'), 4, NULL),
(1, 'Cấu hình sao lưu CSDL tự động',
    'Lên lịch sao lưu cơ sở dữ liệu hằng đêm và giữ lại bản sao của 7 ngày gần nhất.',
    3, 1, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 8 DAY, '17:00'), 5, NULL),
(1, 'Nghiên cứu cổng thanh toán VNPay',
    'So sánh VNPay, MoMo, ZaloPay về phí giao dịch và độ phức tạp khi tích hợp.',
    1, 1, 'low', 'todo', ADDTIME(@ngay + INTERVAL 10 DAY, '17:00'), 6, NULL),
(1, 'Tối ưu SEO trang danh mục',
    'Rà soát thẻ tiêu đề, mô tả meta và cấu trúc đường dẫn của các trang danh mục.',
    NULL, 1, 'medium', 'todo', NULL, 7, NULL),

-- === ĐANG LÀM ===
(1, 'Sửa lỗi hiển thị giá khuyến mãi',
    'Giá sau giảm bị làm tròn sai ở trang danh sách. Cần kiểm tra lại hàm tính giá.',
    1, 1, 'high', 'in_progress', ADDTIME(@ngay - INTERVAL 1 DAY, '16:00'), 0, NULL),
(1, 'Xây dựng chức năng giỏ hàng',
    'Thêm/xoá/cập nhật số lượng sản phẩm, lưu giỏ hàng vào session và đồng bộ khi đăng nhập.',
    2, 1, 'urgent', 'in_progress', ADDTIME(@ngay, '10:30'), 1, NULL),
(1, 'Trang quản trị đơn hàng',
    'Danh sách đơn, lọc theo trạng thái, cập nhật trạng thái giao hàng và in hoá đơn.',
    5, 1, 'high', 'in_progress', ADDTIME(@ngay, '14:00'), 2, NULL),
(1, 'Lọc sản phẩm theo thương hiệu',
    'Bộ lọc nhiều lựa chọn ở trang danh mục, giữ nguyên bộ lọc khi chuyển trang.',
    1, 1, 'medium', 'in_progress', ADDTIME(@ngay, '17:30'), 3, NULL),
(1, 'Tích hợp đăng nhập Google OAuth',
    'Cho phép người dùng đăng nhập nhanh bằng tài khoản Google.',
    3, 1, 'high', 'in_progress', ADDTIME(@ngay + INTERVAL 2 DAY, '17:00'), 4, NULL),
(1, 'Trang chi tiết sản phẩm',
    'Thư viện ảnh, chọn biến thể (màu/size), đánh giá của khách hàng.',
    4, 1, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 4 DAY, '17:00'), 5, NULL),
(1, 'Tối ưu tốc độ tải ảnh',
    'Nén ảnh sản phẩm, chuyển sang định dạng WebP và chỉ tải ảnh khi cuộn tới (lazy-load).',
    3, 1, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 6 DAY, '12:00'), 6, NULL),

-- === CHỜ DUYỆT ===
(1, 'Soát lại giao diện responsive',
    'Kiểm tra hiển thị trên điện thoại, máy tính bảng và màn hình lớn.',
    4, 1, 'medium', 'review', ADDTIME(@ngay, '07:00'), 0, NULL),
(1, 'Kiểm thử luồng thanh toán',
    'Chạy đủ các kịch bản: thanh toán thành công, thất bại, huỷ giữa chừng, timeout.',
    3, 1, 'urgent', 'review', ADDTIME(@ngay, '08:45'), 1, NULL),
(1, 'Rà soát bảo mật form đăng ký',
    'Chống SQL Injection, XSS và kiểm tra độ mạnh mật khẩu.',
    1, 1, 'high', 'review', ADDTIME(@ngay, '12:15'), 2, NULL),
(1, 'Chức năng đánh giá sản phẩm',
    'Khách đã mua mới được đánh giá; chấm sao, viết nhận xét và đính kèm ảnh.',
    2, 1, 'medium', 'review', ADDTIME(@ngay, '15:45'), 3, NULL),

-- === HOÀN THÀNH ===
(1, 'Khởi tạo dự án & cấu hình môi trường',
    'Cài đặt XAMPP, tạo repository, thống nhất quy ước đặt tên.',
    1, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 14 DAY, '17:00'), 0, ADDTIME(@ngay - INTERVAL 14 DAY, '11:00')),
(1, 'Thiết kế cơ sở dữ liệu',
    'Vẽ sơ đồ ERD và viết script tạo bảng.',
    3, 1, 'high', 'done', ADDTIME(@ngay - INTERVAL 11 DAY, '17:00'), 1, ADDTIME(@ngay - INTERVAL 11 DAY, '15:30')),
(1, 'Dựng bộ khung giao diện',
    'Layout chung: header, sidebar, footer và hệ thống lưới.',
    4, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 8 DAY, '17:00'), 2, ADDTIME(@ngay - INTERVAL 8 DAY, '10:15')),
(1, 'Chức năng tìm kiếm sản phẩm',
    'Tìm theo tên, lọc theo danh mục và khoảng giá.',
    2, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 5 DAY, '17:00'), 3, ADDTIME(@ngay - INTERVAL 5 DAY, '16:40')),
(1, 'Đăng ký / đăng nhập tài khoản khách hàng',
    'Đăng ký bằng email, đăng nhập, quên mật khẩu và đổi mật khẩu.',
    1, 1, 'high', 'done', ADDTIME(@ngay - INTERVAL 3 DAY, '17:00'), 4, ADDTIME(@ngay - INTERVAL 3 DAY, '14:20')),
(1, 'Trang danh mục sản phẩm',
    'Hiển thị sản phẩm theo danh mục, phân trang và sắp xếp theo giá, mới nhất.',
    5, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 1 DAY, '12:00'), 5, ADDTIME(@ngay - INTERVAL 1 DAY, '11:05'));

-- ---------------------------------------------------------------------------
-- CÔNG VIỆC - DỰ ÁN 2: Ứng dụng Mobile Đặt món
-- ---------------------------------------------------------------------------
INSERT INTO `tasks`
  (`project_id`, `title`, `description`, `assignee_id`, `created_by`, `priority`, `status`, `due_at`, `position`, `completed_at`)
VALUES
-- === CẦN LÀM ===
(2, 'Thiết kế màn hình onboarding',
    'Ba màn hình giới thiệu tính năng chính khi mở ứng dụng lần đầu.',
    2, 2, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 6 DAY, '17:00'), 0, NULL),
(2, 'Tích hợp bản đồ chọn địa chỉ',
    'Cho phép ghim vị trí giao hàng trên bản đồ và lưu địa chỉ thường dùng.',
    1, 2, 'high', 'todo', ADDTIME(@ngay + INTERVAL 8 DAY, '17:00'), 1, NULL),
(2, 'Chức năng đánh giá tài xế',
    'Khách chấm sao và để lại nhận xét cho tài xế sau khi nhận món.',
    7, 2, 'low', 'todo', ADDTIME(@ngay + INTERVAL 9 DAY, '17:00'), 2, NULL),
(2, 'Xây dựng màn hình lịch sử đơn',
    'Danh sách đơn đã đặt, lọc theo trạng thái, xem lại chi tiết và đặt lại.',
    3, 2, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 12 DAY, '17:00'), 3, NULL),
(2, 'Màn hình ví & điểm thưởng',
    'Hiển thị số dư ví, lịch sử tích điểm và đổi điểm lấy mã giảm giá.',
    6, 2, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 14 DAY, '17:00'), 4, NULL),
(2, 'Hỗ trợ chế độ tối (dark mode)',
    'Bộ màu cho giao diện tối, tự đổi theo cài đặt của điện thoại.',
    NULL, 2, 'low', 'todo', NULL, 5, NULL),

-- === ĐANG LÀM ===
(2, 'API danh sách nhà hàng',
    'Trả về nhà hàng gần nhất kèm đánh giá, thời gian giao dự kiến và trạng thái mở cửa.',
    1, 2, 'high', 'in_progress', ADDTIME(@ngay, '09:30'), 0, NULL),
(2, 'Chức năng đặt món & giỏ hàng',
    'Thêm món vào giỏ, ghi chú cho nhà bếp, tính phí giao hàng theo khoảng cách.',
    2, 2, 'urgent', 'in_progress', ADDTIME(@ngay, '20:00'), 1, NULL),
(2, 'Theo dõi vị trí tài xế theo thời gian thực',
    'Hiển thị tài xế trên bản đồ, cập nhật vị trí mỗi 5 giây và thời gian đến dự kiến.',
    6, 2, 'urgent', 'in_progress', ADDTIME(@ngay + INTERVAL 1 DAY, '15:00'), 2, NULL),
(2, 'Màn hình chi tiết món ăn',
    'Ảnh món, mô tả, tuỳ chọn thêm topping và số lượng.',
    2, 2, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 2 DAY, '12:00'), 3, NULL),
(2, 'Tích hợp thanh toán MoMo',
    'Thanh toán đơn bằng ví MoMo, xử lý kết quả trả về và hoàn tiền khi huỷ đơn.',
    7, 2, 'high', 'in_progress', ADDTIME(@ngay + INTERVAL 3 DAY, '17:00'), 4, NULL),
(2, 'Push notification khi tài xế nhận đơn',
    'Gửi thông báo đẩy tới khách hàng ngay khi có tài xế nhận đơn.',
    3, 2, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 5 DAY, '17:00'), 5, NULL),

-- === CHỜ DUYỆT ===
(2, 'Giao diện trang chủ ứng dụng',
    'Thanh tìm kiếm, danh mục món, nhà hàng nổi bật và khuyến mãi trong ngày.',
    6, 2, 'medium', 'review', ADDTIME(@ngay + INTERVAL 1 DAY, '10:00'), 0, NULL),
(2, 'Kiểm thử API đăng nhập OTP',
    'Kiểm tra giới hạn số lần gửi lại mã và thời gian hết hạn của OTP.',
    1, 2, 'high', 'review', ADDTIME(@ngay + INTERVAL 2 DAY, '17:00'), 1, NULL),
(2, 'Bộ lọc món theo khẩu vị',
    'Lọc món chay, món cay, món ít đường và theo khoảng giá.',
    7, 2, 'low', 'review', ADDTIME(@ngay + INTERVAL 4 DAY, '17:00'), 2, NULL),

-- === HOÀN THÀNH ===
(2, 'Phân tích yêu cầu nghiệp vụ',
    'Phỏng vấn khách hàng và chốt phạm vi chức năng cho phiên bản đầu tiên.',
    2, 2, 'medium', 'done', ADDTIME(@ngay - INTERVAL 13 DAY, '17:00'), 0, ADDTIME(@ngay - INTERVAL 13 DAY, '10:30')),
(2, 'Vẽ sơ đồ use-case',
    'Mô hình hoá tác nhân khách hàng, nhà hàng và tài xế.',
    3, 2, 'low', 'done', ADDTIME(@ngay - INTERVAL 12 DAY, '17:00'), 1, ADDTIME(@ngay - INTERVAL 12 DAY, '16:10')),
(2, 'Thiết lập repository & CI',
    'Tạo repo, cấu hình nhánh và pipeline build tự động.',
    1, 2, 'medium', 'done', ADDTIME(@ngay - INTERVAL 10 DAY, '17:00'), 2, ADDTIME(@ngay - INTERVAL 10 DAY, '09:45')),
(2, 'Thiết kế UI kit',
    'Bảng màu, kiểu chữ, bộ icon và các thành phần giao diện dùng chung.',
    6, 2, 'medium', 'done', ADDTIME(@ngay - INTERVAL 7 DAY, '17:00'), 3, ADDTIME(@ngay - INTERVAL 7 DAY, '15:00')),
(2, 'API đăng ký bằng số điện thoại',
    'Đăng ký tài khoản bằng số điện thoại, xác thực qua mã OTP.',
    7, 2, 'high', 'done', ADDTIME(@ngay - INTERVAL 4 DAY, '17:00'), 4, ADDTIME(@ngay - INTERVAL 4 DAY, '17:35')),
(2, 'Màn hình đăng nhập',
    'Đăng nhập bằng số điện thoại hoặc tài khoản Google.',
    2, 2, 'medium', 'done', ADDTIME(@ngay - INTERVAL 2 DAY, '17:00'), 5, ADDTIME(@ngay - INTERVAL 2 DAY, '11:20'));

-- ---------------------------------------------------------------------------
-- CÔNG VIỆC - DỰ ÁN 3: Hệ thống Quản lý Thư viện số
-- ---------------------------------------------------------------------------
INSERT INTO `tasks`
  (`project_id`, `title`, `description`, `assignee_id`, `created_by`, `priority`, `status`, `due_at`, `position`, `completed_at`)
VALUES
-- === CẦN LÀM ===
(3, 'Nhập danh mục sách từ file Excel',
    'Đọc file .xlsx, kiểm tra trùng mã ISBN và báo lỗi theo từng dòng.',
    6, 1, 'high', 'todo', ADDTIME(@ngay, '18:30'), 0, NULL),
(3, 'Chức năng gia hạn sách trực tuyến',
    'Độc giả tự gia hạn tối đa 2 lần nếu sách chưa có người đặt trước.',
    8, 1, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 4 DAY, '17:00'), 1, NULL),
(3, 'In thẻ thư viện có mã QR',
    'Sinh mã QR theo mã độc giả và xuất file PDF để in thẻ.',
    4, 1, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 6 DAY, '17:00'), 2, NULL),
(3, 'Thống kê sách mượn nhiều nhất',
    'Biểu đồ top 10 đầu sách theo tháng và theo khoa.',
    7, 1, 'low', 'todo', ADDTIME(@ngay + INTERVAL 9 DAY, '17:00'), 3, NULL),
(3, 'Gửi email nhắc trả sách',
    'Tự gửi email cho độc giả trước hạn trả 1 ngày và khi đã quá hạn.',
    1, 1, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 11 DAY, '17:00'), 4, NULL),
(3, 'Phân quyền thủ thư và độc giả',
    'Tách menu và quyền thao tác giữa thủ thư, quản trị viên và độc giả.',
    NULL, 1, 'high', 'todo', NULL, 5, NULL),

-- === ĐANG LÀM ===
(3, 'Quét mã vạch khi mượn/trả sách',
    'Dùng máy quét hoặc camera để đọc mã vạch sách, tự điền vào phiếu mượn.',
    6, 1, 'urgent', 'in_progress', ADDTIME(@ngay - INTERVAL 1 DAY, '15:00'), 0, NULL),
(3, 'Tìm kiếm sách theo tác giả, thể loại',
    'Tìm gần đúng theo tên sách, tác giả; lọc theo thể loại và năm xuất bản.',
    8, 1, 'high', 'in_progress', ADDTIME(@ngay, '12:00'), 1, NULL),
(3, 'Quản lý độc giả',
    'Thêm/sửa/khoá thẻ độc giả, xem lịch sử mượn trả.',
    4, 1, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 2 DAY, '17:00'), 2, NULL),
(3, 'Tính tiền phạt trả sách trễ',
    'Tính theo số ngày trễ, cho phép thủ thư miễn giảm và in biên lai.',
    7, 1, 'high', 'in_progress', ADDTIME(@ngay + INTERVAL 3 DAY, '12:00'), 3, NULL),
(3, 'Trang đọc thử sách điện tử',
    'Xem trước 10 trang đầu của sách điện tử định dạng PDF ngay trên trình duyệt.',
    1, 1, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 7 DAY, '17:00'), 4, NULL),

-- === CHỜ DUYỆT ===
(3, 'Kiểm thử chức năng mượn sách',
    'Mượn quá số lượng cho phép, mượn sách đang được mượn, thẻ hết hạn.',
    7, 1, 'high', 'review', ADDTIME(@ngay + INTERVAL 1 DAY, '15:00'), 0, NULL),
(3, 'Giao diện trang tra cứu cho độc giả',
    'Trang tra cứu công khai, hiển thị tình trạng còn/hết sách và vị trí kệ.',
    8, 1, 'medium', 'review', ADDTIME(@ngay + INTERVAL 2 DAY, '10:00'), 1, NULL),
(3, 'Báo cáo tồn kho sách',
    'Xuất báo cáo số lượng sách theo kho, sách hỏng và sách thất lạc.',
    4, 1, 'low', 'review', ADDTIME(@ngay + INTERVAL 5 DAY, '17:00'), 2, NULL),

-- === HOÀN THÀNH ===
(3, 'Khảo sát quy trình mượn trả tại thư viện',
    'Phỏng vấn thủ thư, ghi lại quy trình hiện tại và các vấn đề gặp phải.',
    1, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 12 DAY, '17:00'), 0, ADDTIME(@ngay - INTERVAL 12 DAY, '15:00')),
(3, 'Thiết kế CSDL sách, độc giả, phiếu mượn',
    'Sơ đồ ERD, các bảng chính và ràng buộc khoá ngoại.',
    6, 1, 'high', 'done', ADDTIME(@ngay - INTERVAL 9 DAY, '17:00'), 1, ADDTIME(@ngay - INTERVAL 9 DAY, '10:40')),
(3, 'Chức năng thêm/sửa/xoá đầu sách',
    'Quản lý thông tin sách: ISBN, tác giả, nhà xuất bản, số lượng.',
    4, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 6 DAY, '17:00'), 2, ADDTIME(@ngay - INTERVAL 6 DAY, '16:15')),
(3, 'Đăng nhập cho thủ thư',
    'Đăng nhập, đăng xuất và ghi nhật ký phiên làm việc của thủ thư.',
    8, 1, 'medium', 'done', ADDTIME(@ngay - INTERVAL 4 DAY, '12:00'), 3, ADDTIME(@ngay - INTERVAL 4 DAY, '09:30')),
(3, 'Upload ảnh bìa sách',
    'Tải ảnh bìa, tự cắt về đúng tỉ lệ và tạo ảnh thu nhỏ.',
    7, 1, 'low', 'done', ADDTIME(@ngay - INTERVAL 1 DAY, '21:00'), 4, ADDTIME(@ngay - INTERVAL 1 DAY, '20:10'));

-- ---------------------------------------------------------------------------
-- CÔNG VIỆC - DỰ ÁN 4: Hội thảo Công nghệ cho Sinh viên
-- ---------------------------------------------------------------------------
INSERT INTO `tasks`
  (`project_id`, `title`, `description`, `assignee_id`, `created_by`, `priority`, `status`, `due_at`, `position`, `completed_at`)
VALUES
-- === CẦN LÀM ===
(4, 'Thiết kế poster & banner sự kiện',
    'Poster A3 dán bảng tin, banner fanpage và backdrop sân khấu.',
    5, 3, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 2 DAY, '12:00'), 0, NULL),
(4, 'Mời diễn giả khách mời',
    'Gửi thư mời 3 diễn giả từ doanh nghiệp, xác nhận lịch và chủ đề trình bày.',
    1, 3, 'high', 'todo', ADDTIME(@ngay + INTERVAL 3 DAY, '17:00'), 1, NULL),
(4, 'Đặt hội trường và thiết bị âm thanh',
    'Đăng ký hội trường lớn, máy chiếu, micro không dây và loa.',
    3, 3, 'high', 'todo', ADDTIME(@ngay + INTERVAL 5 DAY, '10:00'), 2, NULL),
(4, 'Lên kịch bản dẫn chương trình',
    'Kịch bản chi tiết theo từng phút, lời dẫn cho MC và phần hỏi đáp.',
    6, 3, 'medium', 'todo', ADDTIME(@ngay + INTERVAL 10 DAY, '17:00'), 3, NULL),
(4, 'Chuẩn bị quà tặng cho người tham dự',
    'Sổ tay, móc khoá in logo sự kiện và giấy chứng nhận tham dự.',
    8, 3, 'low', 'todo', ADDTIME(@ngay + INTERVAL 12 DAY, '17:00'), 4, NULL),
(4, 'Tìm nhà tài trợ',
    'Liên hệ doanh nghiệp đối tác của khoa để xin tài trợ hiện vật hoặc kinh phí.',
    NULL, 3, 'medium', 'todo', NULL, 5, NULL),

-- === ĐANG LÀM ===
(4, 'Mở form đăng ký tham dự',
    'Form đăng ký trực tuyến, giới hạn 300 chỗ và gửi email xác nhận.',
    5, 3, 'urgent', 'in_progress', ADDTIME(@ngay - INTERVAL 1 DAY, '12:00'), 0, NULL),
(4, 'Viết bài truyền thông trên fanpage',
    'Chuỗi 3 bài giới thiệu diễn giả, đăng cách nhau 2 ngày.',
    8, 3, 'medium', 'in_progress', ADDTIME(@ngay, '17:00'), 1, NULL),
(4, 'Dự trù kinh phí sự kiện',
    'Bảng dự trù chi phí in ấn, quà tặng, nước uống và thuê thiết bị.',
    3, 3, 'high', 'in_progress', ADDTIME(@ngay + INTERVAL 2 DAY, '17:00'), 2, NULL),
(4, 'Liên hệ câu lạc bộ các khoa',
    'Mời các câu lạc bộ học thuật cùng truyền thông và cử tình nguyện viên.',
    6, 3, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 4 DAY, '17:00'), 3, NULL),
(4, 'Làm video teaser giới thiệu',
    'Video 30 giây giới thiệu chủ đề, diễn giả và thời gian tổ chức.',
    1, 3, 'medium', 'in_progress', ADDTIME(@ngay + INTERVAL 6 DAY, '17:00'), 4, NULL),

-- === CHỜ DUYỆT ===
(4, 'Duyệt nội dung slide chương trình',
    'Thống nhất mẫu slide, kiểm tra chính tả và logo đơn vị tổ chức.',
    1, 3, 'high', 'review', ADDTIME(@ngay + INTERVAL 1 DAY, '16:00'), 0, NULL),
(4, 'Danh sách tình nguyện viên',
    'Chốt 20 tình nguyện viên, phân công đón khách, check-in và hậu cần.',
    8, 3, 'low', 'review', ADDTIME(@ngay + INTERVAL 3 DAY, '12:00'), 1, NULL),

-- === HOÀN THÀNH ===
(4, 'Chốt chủ đề hội thảo',
    'Chọn chủ đề "Từ giảng đường đến doanh nghiệp" sau khi khảo sát sinh viên.',
    3, 3, 'high', 'done', ADDTIME(@ngay - INTERVAL 11 DAY, '17:00'), 0, ADDTIME(@ngay - INTERVAL 11 DAY, '14:00')),
(4, 'Lập kế hoạch tổng thể',
    'Mốc thời gian, phân công ban nội dung, truyền thông và hậu cần.',
    3, 3, 'medium', 'done', ADDTIME(@ngay - INTERVAL 9 DAY, '17:00'), 1, ADDTIME(@ngay - INTERVAL 9 DAY, '10:00')),
(4, 'Xin phép phòng Công tác sinh viên',
    'Nộp kế hoạch và nhận văn bản đồng ý tổ chức sự kiện.',
    1, 3, 'high', 'done', ADDTIME(@ngay - INTERVAL 6 DAY, '17:00'), 2, ADDTIME(@ngay - INTERVAL 6 DAY, '15:30')),
(4, 'Thiết kế logo sự kiện',
    'Logo nhận diện dùng cho poster, slide và quà tặng.',
    5, 3, 'low', 'done', ADDTIME(@ngay - INTERVAL 3 DAY, '20:00'), 3, ADDTIME(@ngay - INTERVAL 3 DAY, '19:45')),
(4, 'Tạo nhóm chat ban tổ chức',
    'Nhóm chat chung và các nhóm nhỏ theo từng ban.',
    6, 3, 'low', 'done', ADDTIME(@ngay - INTERVAL 2 DAY, '12:00'), 4, ADDTIME(@ngay - INTERVAL 2 DAY, '08:50'));

-- ---------------------------------------------------------------------------
-- NGÀY TẠO / NGÀY THAM GIA
-- Đặt lùi về trước để không có việc nào "được tạo sau khi đã hoàn thành".
-- ---------------------------------------------------------------------------
UPDATE `users` SET `created_at` = ADDTIME(@ngay - INTERVAL (45 - `id`) DAY, '20:00');

UPDATE `project_members` pm
INNER JOIN `projects` p ON p.`id` = pm.`project_id`
   SET pm.`joined_at` = IF(pm.`role` = 'owner', p.`created_at`,
                           p.`created_at` + INTERVAL (1 + pm.`user_id` MOD 3) DAY);

UPDATE `tasks` t
INNER JOIN `projects` p ON p.`id` = t.`project_id`
   SET t.`created_at` = p.`created_at` + INTERVAL (1 + t.`id` MOD 5) DAY + INTERVAL (t.`id` MOD 7) HOUR,
       t.`updated_at` = COALESCE(t.`completed_at`, NOW());

-- ---------------------------------------------------------------------------
-- NHẬT KÝ NHẮC VIỆC ĐÃ CÓ TỪ TRƯỚC
-- Giả lập những gì hệ thống đã ghi trong các ngày qua, để trang Nhật ký có sẵn
-- lịch sử. Các việc đến hạn trong ngày demo KHÔNG có sẵn nhật ký: chúng sẽ được
-- ghi trực tiếp ở các lượt quét khi mở web (F07, F09).
-- ---------------------------------------------------------------------------

-- Mỗi việc đã hoàn thành có một dòng "Hoàn thành".
INSERT INTO `reminders` (`task_id`, `project_id`, `type`, `channel`, `status`, `message`, `created_at`)
SELECT t.`id`, t.`project_id`, 'completed', 'system', 'sent',
       CONCAT('Đã hoàn thành: «', t.`title`, '» (', COALESCE(u.`full_name`, 'chưa phân công'), ')'),
       t.`completed_at`
FROM        `tasks` t
LEFT  JOIN  `users` u ON u.`id` = t.`assignee_id`
WHERE t.`status` = 'done';

-- Việc đã trễ từ hôm trước: được nhắc "Sắp tới hạn" khi vào ngưỡng, rồi "Quá hạn".
INSERT INTO `reminders` (`task_id`, `project_id`, `type`, `channel`, `status`, `message`, `created_at`)
SELECT t.`id`, t.`project_id`, 'due_soon', 'system', 'sent',
       CONCAT('Sắp tới hạn: «', t.`title`, '» (', COALESCE(u.`full_name`, 'chưa phân công'), ') — còn ',
              p.`remind_before_minutes` DIV 60 - 1, ' giờ 59 phút'),
       t.`due_at` - INTERVAL (p.`remind_before_minutes` - 1) MINUTE
FROM        `tasks`    t
INNER JOIN  `projects` p ON p.`id` = t.`project_id`
LEFT  JOIN  `users`    u ON u.`id` = t.`assignee_id`
WHERE t.`status` <> 'done' AND t.`due_at` < @ngay;

INSERT INTO `reminders` (`task_id`, `project_id`, `type`, `channel`, `status`, `message`, `created_at`)
SELECT t.`id`, t.`project_id`, 'overdue', 'system', 'sent',
       CONCAT('Đã quá hạn: «', t.`title`, '» (', COALESCE(u.`full_name`, 'chưa phân công'), ') — trễ 1 phút'),
       t.`due_at` + INTERVAL 1 MINUTE
FROM        `tasks` t
LEFT  JOIN  `users` u ON u.`id` = t.`assignee_id`
WHERE t.`status` <> 'done' AND t.`due_at` < @ngay;

-- Dự án 3 bật Discord giả lập: mỗi sự kiện trên có thêm một dòng kênh Discord.
INSERT INTO `reminders` (`task_id`, `project_id`, `type`, `channel`, `status`, `message`, `created_at`)
SELECT r.`task_id`, r.`project_id`, r.`type`, 'discord', 'mocked',
       CONCAT(CASE r.`type`
                  WHEN 'completed' THEN '✅ CÔNG VIỆC ĐÃ HOÀN THÀNH'
                  WHEN 'due_soon'  THEN '⏰ CÔNG VIỆC SẮP TỚI HẠN'
                  ELSE                  '🔴 CÔNG VIỆC ĐÃ QUÁ HẠN'
              END,
              ' — ', t.`title`, ' | ', t.`description`),
       r.`created_at`
FROM        `reminders` r
INNER JOIN  `tasks`     t ON t.`id` = r.`task_id`
WHERE r.`project_id` = 3 AND r.`channel` = 'system';
