<?php
/**
 * Bộ định tuyến: đọc tham số url rồi gọi đúng Controller và hàm (action).
 *
 *   index.php?url=task/create  =>  new TaskController(), gọi create()
 *   index.php?url=install      =>  new InstallController(), gọi index()
 *   index.php                  =>  new HomeController(), gọi index()
 */
class Router
{
    public function run()
    {
        try {
            $this->dispatch();
        } catch (AppException $e) {
            $this->showError($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            // Lỗi ngoài dự kiến: ghi vào log của PHP, chỉ báo chung chung cho người dùng
            error_log($e->getMessage());
            $this->showError(500, 'Không xử lý được yêu cầu. Hãy kiểm tra MySQL và cài đặt CSDL.');
        }
    }

    private function dispatch()
    {
        $url = isset($_GET['url']) && is_string($_GET['url']) ? trim($_GET['url'], '/') : '';
        if ($url === '') {
            $url = 'home/index';
        }

        $parts  = explode('/', $url);
        $name   = $parts[0];
        $action = $parts[1] ?? 'index';

        // Tên controller chỉ gồm chữ thường, tên action chỉ gồm chữ cái
        if (count($parts) > 2 || !preg_match('/^[a-z]+$/', $name) || !preg_match('/^[a-zA-Z]+$/', $action)) {
            throw new AppException('Không tìm thấy trang.', 404);
        }

        $class = ucfirst($name) . 'Controller';
        $file  = ROOT_PATH . '/controllers/' . $class . '.php';
        if (!file_exists($file)) {
            throw new AppException('Không tìm thấy trang.', 404);
        }

        require_once $file;
        $controller = new $class();

        // Chỉ cho gọi hàm public do chính Controller con khai báo,
        // không cho gọi các hàm dùng chung của lớp cha như requireLogin(), json()...
        if (!method_exists($controller, $action) || !is_callable([$controller, $action]) || method_exists('Controller', $action)) {
            throw new AppException('Không tìm thấy trang.', 404);
        }

        $controller->$action();
    }

    /** Báo lỗi: JavaScript gọi thì trả JSON, còn mở bằng trình duyệt thì hiện trang lỗi. */
    private function showError($status, $message)
    {
        http_response_code($status);

        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo json_encode([
                'ok'            => false,
                'error'         => $message,
                'auth_required' => $status === 401,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $appName   = APP_NAME;
        $pageTitle = 'Lỗi ' . $status;

        ob_start();
        include ROOT_PATH . '/views/error.php';
        $content = ob_get_clean();

        include ROOT_PATH . '/views/auth-layout.php';
    }

    /** JavaScript của trang (fetch) luôn gửi kèm header X-Requested-With: XMLHttpRequest. */
    private function isAjax()
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
