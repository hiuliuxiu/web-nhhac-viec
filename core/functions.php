<?php
// Mấy hàm ngắn dùng trong View (các tệp HTML) cho gọn: e(), url(), asset(), to_js().

/** In chuỗi ra HTML an toàn, chống chèn mã (XSS). */
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Thư mục chứa index.php trên web, ví dụ "/webnhacviec-v7". */
function base_path()
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(dirname($script), '/.');
}

/** Đường dẫn tới một trang, ví dụ url('auth/login') => /webnhacviec-v7/index.php?url=auth/login */
function url($route = '')
{
    $route = trim($route, '/');
    return base_path() . '/index.php' . ($route === '' ? '' : '?url=' . $route);
}

/** Đường dẫn tới tệp tĩnh như CSS, JS. */
function asset($path)
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Đổi giá trị PHP thành JSON để đặt an toàn bên trong thẻ <script>. */
function to_js($value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
