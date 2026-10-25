<?php
/**
 * Kết nối CSDL theo mẫu Singleton: cả chương trình chỉ tạo đúng một kết nối PDO,
 * mọi Model đều dùng chung kết nối đó.
 */
class Database
{
    private static $connection = null;

    /** Trả về kết nối PDO; lần gọi đầu tiên mới thực sự kết nối tới MySQL. */
    public static function getConnection()
    {
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lỗi SQL sẽ ném ra exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // mỗi dòng kết quả là một mảng
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Cho MySQL dùng cùng múi giờ với PHP, để NOW() và date() khớp nhau
            self::$connection->exec("SET time_zone = '" . date('P') . "'");
        }

        return self::$connection;
    }

    /** Kiểm tra CSDL đã được cài đặt (đã có bảng users) hay chưa. */
    public static function isInstalled()
    {
        try {
            self::getConnection()->query('SELECT 1 FROM users LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
