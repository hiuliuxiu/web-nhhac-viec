<?php
/** Đăng nhập, đăng ký và đăng xuất. */
class AuthController extends Controller
{
    /** Màu ảnh đại diện, chọn ngẫu nhiên khi đăng ký. */
    const AVATAR_COLORS = ['#6366f1', '#ec4899', '#10b981', '#f59e0b'];

    /** ?url=auth/login — GET: hiện trang đăng nhập; POST: kiểm tra tài khoản rồi đăng nhập. */
    public function login()
    {
        if (!$this->isPost()) {
            if ($this->currentUser() !== null) {
                $this->redirect('');
                return;
            }
            $this->view('login', [
                'pageTitle'    => 'Đăng nhập',
                'needsInstall' => !Database::isInstalled(),
                'csrfToken'    => $this->csrfToken(),
            ], 'auth-layout');
            return;
        }

        $this->verifyCsrf();

        $identifier = strtolower($this->input('username'));
        $password   = $this->inputRaw('password');

        if ($identifier === '' || $password === '') {
            $this->fail('Vui lòng nhập tên đăng nhập và mật khẩu.');
        }

        $userModel = new UserModel();
        $user = $userModel->findByLogin($identifier);

        // Không có tài khoản hay sai mật khẩu đều báo chung một câu
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->fail('Tên đăng nhập hoặc mật khẩu không đúng.', 401);
        }

        $this->loginSession((int) $user['id']);

        $this->json([
            'user'       => $this->currentUser(),
            'message'    => 'Đăng nhập thành công.',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /** ?url=auth/register — GET: hiện trang đăng ký; POST: tạo tài khoản mới. */
    public function register()
    {
        if (!$this->isPost()) {
            if ($this->currentUser() !== null) {
                $this->redirect('');
                return;
            }
            $this->view('register', [
                'pageTitle' => 'Đăng ký',
                'csrfToken' => $this->csrfToken(),
            ], 'auth-layout');
            return;
        }

        $this->verifyCsrf();

        $username = strtolower($this->input('username'));
        $email    = strtolower($this->input('email'));
        $password = $this->inputRaw('password');
        $fullName = $this->input('full_name');

        // --- Kiểm tra dữ liệu ---
        if ($username === '' || $email === '' || $password === '' || $fullName === '') {
            $this->fail('Vui lòng điền đầy đủ tất cả các trường.');
        }
        if (!preg_match('/^[a-z0-9_.]{3,50}$/', $username)) {
            $this->fail('Tên đăng nhập chỉ gồm chữ thường, số, dấu chấm hoặc gạch dưới, dài 3-50 ký tự.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Địa chỉ email không hợp lệ.');
        }
        if (mb_strlen($password) < 6) {
            $this->fail('Mật khẩu phải có ít nhất 6 ký tự.');
        }
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
            $this->fail('Họ tên phải dài từ 2 đến 120 ký tự.');
        }

        $userModel = new UserModel();
        $existing  = $userModel->findByUsernameOrEmail($username, $email);
        if ($existing) {
            if ($existing['username'] === $username) {
                $this->fail('Tên đăng nhập này đã có người sử dụng.');
            }
            $this->fail('Email này đã được đăng ký.');
        }

        // --- Tạo tài khoản và một dự án khởi đầu (để không nhìn vào màn hình trống) ---
        // Ba câu INSERT phải cùng thành công, nên gói trong một transaction
        $projectModel = new ProjectModel();
        $memberModel  = new MemberModel();

        $userModel->beginTransaction();
        try {
            $userId = $userModel->create(
                $username,
                $email,
                password_hash($password, PASSWORD_BCRYPT),
                $fullName,
                $this->randomColor()
            );

            $projectId = $projectModel->create(
                'Dự án của ' . $fullName,
                'Dự án đầu tiên được tạo tự động. Bạn có thể đổi tên hoặc xoá đi.',
                $this->randomColor(),
                $userId
            );

            $memberModel->add($projectId, $userId, 'owner');

            $userModel->commit();
        } catch (PDOException $e) {
            $userModel->rollBack();
            $this->fail('Không tạo được tài khoản. Vui lòng thử lại.', 500);
        }

        $this->loginSession($userId);

        $this->json([
            'user'       => $this->currentUser(),
            'message'    => 'Tạo tài khoản thành công. Chào mừng bạn!',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /** ?url=auth/logout — GET (mở bằng đường dẫn): đăng xuất rồi về trang đăng nhập; POST (JavaScript): trả JSON. */
    public function logout()
    {
        if (!$this->isPost()) {
            $this->logoutSession();
            $this->redirect('auth/login');
            return;
        }

        $this->verifyCsrf();
        $this->logoutSession();
        $this->json(['message' => 'Đã đăng xuất.']);
    }

    /** GET ?url=auth/me — thông tin người đang đăng nhập. */
    public function me()
    {
        $user = $this->currentUser();

        $this->json([
            'authenticated' => $user !== null,
            'user'          => $user,
            'csrf_token'    => $this->csrfToken(),
        ]);
    }

    private function randomColor()
    {
        return self::AVATAR_COLORS[array_rand(self::AVATAR_COLORS)];
    }
}
