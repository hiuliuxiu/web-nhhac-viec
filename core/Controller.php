<?php
/**
 * Lớp cha của mọi Controller.
 * Chứa các hàm dùng chung: đọc dữ liệu gửi lên, kiểm tra đăng nhập, CSRF,
 * phân quyền theo dự án, trả JSON hoặc hiện trang HTML.
 * Các hàm ở đây là protected/private nên không gọi được trực tiếp từ đường dẫn.
 */
class Controller
{
    /** Dữ liệu trình duyệt gửi lên: tham số trên URL, dữ liệu form và JSON gộp lại. */
    protected $data = [];

    public function __construct()
    {
        $this->data = $this->readRequestData();
    }

    // =====================================================================
    // ĐỌC DỮ LIỆU GỬI LÊN
    // =====================================================================

    private function readRequestData()
    {
        $json = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // JavaScript (fetch) gửi dữ liệu dạng JSON nên PHP phải tự đọc và giải mã
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if ($raw !== '') {
                $json = json_decode($raw, true);
                if (!is_array($json)) {
                    $this->fail('Dữ liệu JSON không hợp lệ.');
                }
            }
        }

        return array_merge($_GET, $_POST, $json);
    }

    /** Trình duyệt có gửi trường $key lên hay không. */
    protected function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /** Lấy một chuỗi đã bỏ khoảng trắng hai đầu; không có thì trả về $default. */
    protected function input($key, $default = '')
    {
        if (!isset($this->data[$key]) || !is_scalar($this->data[$key])) {
            return $default;
        }
        return trim((string) $this->data[$key]);
    }

    /** Lấy chuỗi nguyên văn, không bỏ khoảng trắng (dùng cho mật khẩu). */
    protected function inputRaw($key)
    {
        if (!isset($this->data[$key]) || !is_scalar($this->data[$key])) {
            return '';
        }
        return (string) $this->data[$key];
    }

    /** Lấy một số nguyên; không có hoặc không phải số thì trả về $default. */
    protected function inputInt($key, $default = 0)
    {
        $value = $this->data[$key] ?? null;
        return is_numeric($value) ? (int) $value : $default;
    }

    /** Lấy giá trị đúng/sai (checkbox, công tắc). Các giá trị 1, true, on, yes là đúng. */
    protected function inputBool($key, $default = false)
    {
        if (!$this->has($key)) {
            return $default;
        }
        $value = $this->data[$key];
        return is_scalar($value) && in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    /** Lấy một mảng, ví dụ danh sách id các thẻ sau khi kéo thả. */
    protected function inputArray($key)
    {
        $value = $this->data[$key] ?? [];
        return is_array($value) ? $value : [];
    }

    // =====================================================================
    // PHƯƠNG THỨC HTTP
    // =====================================================================

    protected function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Các chức năng thay đổi dữ liệu chỉ nhận POST. */
    protected function requirePost()
    {
        if (!$this->isPost()) {
            $this->fail('Chức năng này chỉ nhận yêu cầu POST.', 405);
        }
    }

    // =====================================================================
    // ĐĂNG NHẬP
    // =====================================================================

    /** Người dùng đang đăng nhập, hoặc null nếu chưa đăng nhập. */
    protected function currentUser()
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $userModel = new UserModel();
        $user = $userModel->findById((int) $_SESSION['user_id']);

        if (!$user) {
            // Tài khoản đã bị xoá khỏi CSDL nhưng phiên vẫn còn
            $this->logoutSession();
            return null;
        }

        $user['id']         = (int) $user['id'];
        $user['created_at'] = Helper::toIso($user['created_at']);
        return $user;
    }

    /** Bắt buộc đã đăng nhập; trả về thông tin người dùng. */
    protected function requireLogin()
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->fail('Bạn cần đăng nhập để thực hiện thao tác này.', 401);
        }
        return $user;
    }

    /** Ghi nhớ người dùng vào session sau khi đăng nhập hoặc đăng ký thành công. */
    protected function loginSession($userId)
    {
        session_regenerate_id(true);   // đổi mã phiên mới, chống chiếm phiên (session fixation)
        $_SESSION['user_id'] = $userId;
    }

    /** Xoá toàn bộ dữ liệu phiên và cookie phiên. */
    protected function logoutSession()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    // =====================================================================
    // CSRF: chống trang web lạ gửi yêu cầu giả mạo thay người dùng
    // =====================================================================

    /** Mã CSRF lưu trong session; trang web của mình gửi kèm mã này ở mọi yêu cầu POST. */
    protected function csrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** So mã gửi lên (header X-CSRF-Token hoặc trường csrf_token) với mã trong session. */
    protected function verifyCsrf()
    {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token');

        if ($sent === '' || !hash_equals($this->csrfToken(), $sent)) {
            $this->fail('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 403);
        }
    }

    // =====================================================================
    // PHÂN QUYỀN THEO DỰ ÁN
    // =====================================================================

    /**
     * Bắt buộc người dùng là thành viên của dự án; trả về thông tin dự án.
     * $ownerOnly = true với thao tác chỉ chủ dự án được làm (cấu hình, mời/xoá thành viên...).
     */
    protected function requireProjectAccess($projectId, array $user, $ownerOnly = false)
    {
        if ($projectId <= 0) {
            $this->fail('Thiếu mã dự án.');
        }

        $projectModel = new ProjectModel();
        $role = $projectModel->getRole($projectId, $user['id']);

        if ($role === null) {
            $this->fail('Bạn không có quyền truy cập dự án này.', 403);
        }
        if ($ownerOnly && $role !== 'owner') {
            $this->fail('Chỉ chủ dự án mới được thực hiện thao tác này.', 403);
        }

        $project = $projectModel->find($projectId);
        if (!$project) {
            $this->fail('Không tìm thấy dự án.', 404);
        }

        $project['id']       = (int) $project['id'];
        $project['owner_id'] = (int) $project['owner_id'];
        $project['my_role']  = $role;
        return $project;
    }

    // =====================================================================
    // TRẢ KẾT QUẢ
    // =====================================================================

    /** Dừng xử lý và báo lỗi; Router sẽ bắt lỗi này để trả thông báo cho người dùng. */
    protected function fail($message, $status = 400)
    {
        throw new AppException($message, $status);
    }

    /** Trả dữ liệu dạng JSON cho JavaScript, luôn kèm ok = true. */
    protected function json(array $data)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Hiện một trang HTML: nạp views/$page.php rồi đặt vào bên trong views/$layout.php.
     * Mỗi phần tử của $data trở thành một biến trong View, ví dụ $data['user'] thành $user.
     */
    protected function view($page, array $data = [], $layout = 'layout')
    {
        $appName = APP_NAME;
        extract($data);

        ob_start();
        include ROOT_PATH . '/views/' . $page . '.php';
        $content = ob_get_clean();

        include ROOT_PATH . '/views/' . $layout . '.php';
    }

    /** Chuyển trình duyệt sang trang khác. */
    protected function redirect($route)
    {
        header('Location: ' . url($route));
    }
}
