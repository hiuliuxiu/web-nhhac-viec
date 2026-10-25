<?php
/**
 * Lỗi "có chủ đích" kèm mã HTTP, ví dụ 400 (dữ liệu sai), 401 (chưa đăng nhập),
 * 403 (không có quyền), 404 (không tìm thấy).
 * Router bắt lỗi này và hiện đúng thông báo cho người dùng.
 * Các lỗi khác (lỗi SQL, lỗi code...) được coi là lỗi hệ thống 500.
 */
class AppException extends Exception
{
}
