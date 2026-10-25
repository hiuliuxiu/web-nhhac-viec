# Nhắc Việc v7: MVC hướng đối tượng

PHP thuần viết theo **lập trình hướng đối tượng**: mỗi Controller, Model, Service là một class.
Không dùng framework, namespace hay autoload; các tệp được nạp bằng `require_once`.
Giao diện Kanban, danh sách, lịch, thống kê và nhắc việc qua Discord giữ nguyên như v6.

## 1. Cấu trúc

```text
webnhacviec-v7/
├── core/          Lớp nền dùng chung
│   ├── Router.php        Đọc ?url=controller/action rồi gọi đúng Controller
│   ├── Controller.php    Lớp cha của mọi Controller
│   ├── Model.php         Lớp cha của mọi Model
│   ├── Database.php      Kết nối CSDL (Singleton)
│   ├── AppException.php  Lỗi kèm mã HTTP (400, 403, 404...)
│   ├── Helper.php        Xử lý ngày giờ, định dạng dữ liệu trả về
│   └── functions.php     4 hàm ngắn dùng trong View: e(), url(), asset(), to_js()
├── controllers/   Kiểm tra đăng nhập, quyền, dữ liệu rồi gọi Model
├── models/        Chỉ chạy câu SQL
├── services/      Nghiệp vụ nhắc việc và gửi Discord
├── views/         Các trang HTML
├── config/        config.php: thông tin MySQL và các hằng số
├── assets/        CSS và JavaScript (Kanban, lịch, biểu đồ)
├── database/      schema.sql (tạo bảng), seed.sql (dữ liệu mẫu)
├── index.php      Mọi yêu cầu từ trình duyệt đều đi qua đây
├── bootstrap.php  Nạp cấu hình và các class
├── install.php    Cài đặt CSDL bằng dòng lệnh
└── worker.php     Quét deadline chạy nền
```

## 2. Đường đi của một yêu cầu

Ví dụ **thêm công việc**:

```text
JavaScript gửi POST index.php?url=task/create
    ↓ index.php bật session rồi gọi Router
core/Router.php: "task/create" → new TaskController(), gọi create()
    ↓
controllers/TaskController.php → create()
    ↓ requirePost(), requireLogin(), verifyCsrf(), requireProjectAccess()
    ↓ kiểm tra tiêu đề, trạng thái, hạn chót, người được giao
models/TaskModel.php → insert()
    ↓ INSERT INTO tasks ... bằng PDO prepared statement
MySQL → Controller trả JSON {"ok": true, "task": {...}} cho giao diện
```

Mọi chức năng thay đổi dữ liệu đều mở đầu bằng 3 dòng:

```php
$this->requirePost();            // chỉ nhận POST
$user = $this->requireLogin();   // phải đăng nhập
$this->verifyCsrf();             // chống giả mạo yêu cầu (CSRF)
```

Router chỉ gọi được các hàm **public** do chính Controller con khai báo. Các hàm dùng chung
của lớp cha (`requireLogin()`, `json()`...) là `protected` nên không thể gọi từ đường dẫn.

## 3. OOP thể hiện ở đâu

| Khái niệm | Ở đâu |
|---|---|
| Class và đối tượng | Mỗi tệp trong `controllers/`, `models/`, `services/` là một class; `new TaskModel()` tạo đối tượng |
| Kế thừa | `TaskController extends Controller`, `TaskModel extends Model` |
| Đóng gói | `protected $db` trong Model; các hàm phụ `private` trong Controller |
| Singleton | `Database::getConnection()` chỉ tạo một kết nối PDO cho cả chương trình |
| Lớp ngoại lệ riêng | `AppException extends Exception` |
| Hằng số của lớp | `Helper::STATUSES`, `DiscordService::COLORS` |

## 4. Danh sách đường dẫn

| Đường dẫn | Controller → hàm | Ghi chú |
|---|---|---|
| `index.php` | `HomeController::index` | Trang chính |
| `?url=auth/login` | `AuthController::login` | GET hiện trang, POST đăng nhập |
| `?url=auth/register` | `AuthController::register` | GET hiện trang, POST đăng ký |
| `?url=auth/logout` | `AuthController::logout` | Đăng xuất |
| `?url=install` | `InstallController::index` | Cài đặt CSDL |
| `?url=task/list`, `get`, `create`, `update`, `move`, `delete` | `TaskController` | Công việc |
| `?url=project/list`, `get`, `create`, `update`, `delete`, `settings`, `testWebhook`, `clearLog` | `ProjectController` | Dự án, cấu hình nhắc việc |
| `?url=member/list`, `search`, `invite`, `remove`, `setRole` | `MemberController` | Thành viên |
| `?url=dashboard` | `DashboardController::index` | Số liệu trang Tổng quan |
| `?url=reminder/list`, `reminder/scan` | `ReminderController` | Nhật ký, quét deadline |

## 5. Muốn sửa gì thì mở tệp nào

| Muốn sửa | Mở tệp |
|---|---|
| Kiểm tra dữ liệu khi thêm/sửa công việc | `controllers/TaskController.php` |
| Câu SQL của công việc | `models/TaskModel.php` |
| Đăng nhập, đăng ký | `controllers/AuthController.php`, `models/UserModel.php` |
| Phân quyền theo dự án | `core/Controller.php` → `requireProjectAccess()` |
| Quét deadline, chống nhắc trùng | `services/ReminderService.php` |
| Nội dung tin Discord | `services/DiscordService.php` |
| Trang chính, thanh bên, hộp thoại | `views/home.php`, `views/sidebar.php`, `views/modals.php` |
| Kéo thả trên giao diện | `assets/js/BoardView.js` |
| Đường dẫn API phía JavaScript | `assets/js/ApiClient.js` |

## 6. Chạy trên XAMPP

1. Bật Apache và MySQL trong XAMPP Control Panel.
2. Mở **http://localhost/webnhacviec-v7/index.php?url=install**.
3. Chọn nạp dữ liệu mẫu và bấm cài đặt.
4. Đăng nhập **an / 123456**. `binh`, `cuong`, `dung`, `giang`, `hai`, `khanh`, `linh`
   dùng cùng mật khẩu.

CSDL mặc định là `webnhacviec_v7`, tách riêng khỏi v6. Thông tin kết nối sửa trong
`config/config.php`. **Cài lại sẽ xoá và tạo lại các bảng của CSDL này.**

Hạn chót trong dữ liệu mẫu tính theo **ngày demo**: nạp trước 20 giờ là hôm nay,
nạp từ 20 giờ trở đi là ngày mai. Trong ngày demo luôn có việc quá hạn và việc
sắp tới hạn, nên có thể nạp từ tối hôm trước buổi bảo vệ.

SortableJS, FullCalendar và Chart.js tải từ CDN nên cần Internet.

Dòng lệnh, tại thư mục `webnhacviec-v7`:

```powershell
& C:/xampp/php/php.exe install.php
& C:/xampp/php/php.exe worker.php
```

`worker.php` chạy liên tục, dừng bằng `Ctrl+C`; thêm `--once` để chỉ quét một lần.
Trong cấu hình thông báo, bật **Chế độ giả lập** khi demo mà không muốn gửi Discord thật.

## 7. Khác gì so với v6

- Backend chuyển từ hàm sang class; thêm `core/` và `services/`.
- Controller kiểm tra dữ liệu, Model chỉ còn câu SQL.
- Đường dẫn đổi từ `?route=api/tasks/create` sang `?url=task/create`; bỏ `config/routes.php`.
- Cấu hình dùng hằng số `define(...)` thay cho mảng `$config`.
- Chống gửi Discord trùng theo kiểu "kiểm tra rồi mới gửi", vẫn giữ khoá UNIQUE làm lưới an toàn.
- Đăng nhập kiểm tra theo cách quen thuộc; đăng nhập và đăng ký có kiểm tra CSRF.
- JavaScript bỏ EventBus: AppStore gọi thẳng hàm của App khi dữ liệu đổi.
