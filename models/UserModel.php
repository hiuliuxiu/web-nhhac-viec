<?php
/** Làm việc với bảng users (tài khoản người dùng). */
class UserModel extends Model
{
    /** Thông tin một người dùng (không kèm mật khẩu), hoặc false nếu không có. */
    public function findById($userId)
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, full_name, avatar_color, created_at
               FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /** Tìm theo tên đăng nhập hoặc email; có kèm mật khẩu đã băm để kiểm tra khi đăng nhập. */
    public function findByLogin($identifier)
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, full_name, password_hash
               FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch();
    }

    /** Tìm tài khoản đã dùng tên đăng nhập hoặc email này (kiểm tra trùng khi đăng ký). */
    public function findByUsernameOrEmail($username, $email)
    {
        $stmt = $this->db->prepare('SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        return $stmt->fetch();
    }

    /** Tạo tài khoản mới; trả về id vừa tạo. */
    public function create($username, $email, $passwordHash, $fullName, $avatarColor)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, avatar_color)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $email, $passwordHash, $fullName, $avatarColor]);

        return (int) $this->db->lastInsertId();
    }
}
