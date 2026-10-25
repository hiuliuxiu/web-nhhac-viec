<?php
/**
 * Cài đặt CSDL: chạy schema.sql (tạo bảng) và seed.sql (dữ liệu mẫu).
 * Lớp này không kế thừa Model, vì lúc cài đặt thì CSDL có thể chưa tồn tại
 * nên phải kết nối thẳng tới MySQL mà không chọn CSDL nào.
 */
class InstallModel
{
    /** Trả về ['steps' => các bước đã chạy, 'fatal' => câu lỗi hoặc null]. */
    public function install($withSeed)
    {
        $steps = [];

        // Tên CSDL được ghép thẳng vào câu SQL nên chỉ cho phép chữ, số và gạch dưới
        if (!preg_match('/\A[a-zA-Z0-9_]+\z/', DB_NAME)) {
            return ['steps' => [], 'fatal' => 'Tên CSDL chỉ được chứa chữ, số và gạch dưới.'];
        }

        $files = ['schema.sql' => 'Tạo cơ sở dữ liệu và các bảng'];
        if ($withSeed) {
            $files['seed.sql'] = 'Nạp dữ liệu mẫu để demo';
        }

        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("SET time_zone = '" . date('P') . "'");

            foreach ($files as $file => $label) {
                $sql = file_get_contents(ROOT_PATH . '/database/' . $file);
                // Tệp SQL viết sẵn tên webnhacviec_v7; đổi sang tên trong config.php nếu khác
                $sql = str_replace('`webnhacviec_v7`', '`' . DB_NAME . '`', $sql);
                $pdo->exec($sql);

                $steps[] = ['label' => $label, 'ok' => true, 'error' => null, 'detail' => null];
            }

            return ['steps' => $steps, 'fatal' => null];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return ['steps' => $steps, 'fatal' => 'Không cài đặt được CSDL. Hãy kiểm tra MySQL và config/config.php.'];
        }
    }
}
