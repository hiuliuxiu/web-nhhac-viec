<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<div class="auth-wrap">
  <div class="shell">
    <div class="core auth-card">

      <div class="auth-head">
        <?php include __DIR__ . '/brand-mark.php'; ?>
        <h1>Chào mừng trở lại</h1>
        <p>Đăng nhập để theo dõi tiến độ công việc của nhóm</p>
      </div>

      <?php if ($needsInstall): ?>
        <div class="alert alert-warn">
          <strong>Chưa cài đặt cơ sở dữ liệu.</strong><br>
          Hãy mở <a href="<?= e(url('install')) ?>"><u>trang cài đặt</u></a> để tạo CSDL trước khi đăng nhập.
        </div>
      <?php endif; ?>

      <div id="formError" class="alert alert-error hidden"></div>

      <form id="loginForm" novalidate>
        <div class="field">
          <label for="username">Tên đăng nhập hoặc email</label>
          <input type="text" id="username" name="username" autocomplete="username"
                 placeholder="ví dụ: an" required autofocus>
        </div>

        <div class="field">
          <label for="password">Mật khẩu</label>
          <input type="password" id="password" name="password" autocomplete="current-password"
                 placeholder="Nhập mật khẩu" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
          <span class="btn-label">Đăng nhập</span>
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </span>
        </button>
      </form>

      <div class="auth-foot">
        Chưa có tài khoản? <a href="<?= e(url('auth/register')) ?>">Đăng ký ngay</a>
      </div>

    </div>
  </div>
</div>

<script src="<?= e(asset('assets/js/auth-form.js')) ?>"></script>
<script>
new AuthForm({
    formId:      'loginForm',
    endpoint:    <?= to_js(url('auth/login')) ?>,
    redirectTo:  <?= to_js(url('')) ?>,
    csrfToken:   <?= to_js($csrfToken) ?>,
    fields:      ['username', 'password'],
    busyLabel:   'Đang đăng nhập…',
    idleLabel:   'Đăng nhập',
    validate(values) {
        if (!values.username || !values.password) {
            return 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
        }
        return null;
    }
}).mount();
</script>
