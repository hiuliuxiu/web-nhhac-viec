<?php
/** Trang chính của ứng dụng. */
class HomeController extends Controller
{
    /** ?url=home/index (mặc định khi mở index.php) — chưa đăng nhập thì chuyển sang trang đăng nhập. */
    public function index()
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->redirect('auth/login');
            return;
        }

        // Cấu hình truyền sang JavaScript qua biến window.APP_CONFIG
        $boot = [
            'apiUrl'       => url() . '?url=',
            'homeUrl'      => url(),
            'loginUrl'     => url('auth/login'),
            'assetUrl'     => asset('assets'),
            'csrfToken'    => $this->csrfToken(),
            'pollInterval' => POLL_INTERVAL_MS,
            'user'         => [
                'id'       => $user['id'],
                'fullName' => $user['full_name'],
                'username' => $user['username'],
                'color'    => $user['avatar_color'],
            ],
        ];

        $this->view('home', ['user' => $user, 'boot' => $boot]);
    }
}
