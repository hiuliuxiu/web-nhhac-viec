<?php
/**
 * Lớp cha của mọi Model.
 * Model chỉ làm việc với CSDL: chạy câu SQL và trả kết quả về cho Controller.
 */
class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
     * Transaction: gom nhiều câu SQL thành một khối, hoặc thành công hết, hoặc huỷ hết.
     * Vì mọi Model dùng chung một kết nối (Singleton) nên một transaction
     * bao được cả các câu SQL của nhiều Model khác nhau.
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollBack()
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    /** Tạo chuỗi "?,?,?" cho câu IN (...), số dấu ? bằng số phần tử của mảng. */
    protected function placeholders(array $values)
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
