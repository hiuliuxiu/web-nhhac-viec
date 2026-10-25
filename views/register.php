<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<div class="auth-wrap">
  <div class="shell">
    <div class="core auth-card">

      <div class="auth-head">
        <?php include __DIR__ . '/brand-mark.php'; ?>
        <h1>Tạo tài khoản mới</h1>
        <p>Một dự án khởi đầu sẽ được tạo sẵn cho bạn</p>
      </div>

      <div id="formError" class="alert alert-error hidden"></div>

      <form id="registerForm" novalidate>
        <div class="field">
          <label for="full_name">Họ và tên</label>
          <input type="text" id="full_name" name="full_name" placeholder="Nguyễn Văn A" autocomplete="name" required autofocus>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username" placeholder="nguyenvana" autocomplete="username" required>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="email@example.com" autocomplete="email" required>
          </div>
        </div>

        <div class="field">
          <label for="password">Mật khẩu</label>
          <input type="password" id="password" name="password" placeholder="Tối thiểu 6 ký tự" autocomplete="new-password" required>
        </div>

        <div class="field">
          <label for="password2">Nhập lại mật khẩu</label>
          <input type="password" id="password2" name="password2" placeholder="Gõ lại mật khẩu" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
          <span class="btn-label">Tạo tài khoản</span>
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </span>
        </button>
      </form>

      <div class="auth-foot">
        Đã có tài khoản? <a href="<?= e(url('auth/login')) ?>">Đăng nhập</a>
      </div>

    </div>
  </div>
</div>

<script src="<?= e(asset('assets/js/auth-form.js')) ?>"></script>
<script>
new AuthForm({
    formId:     'registerForm',
    endpoint:   <?= to_js(url('auth/register')) ?>,
    redirectTo: <?= to_js(url('')) ?>,
    csrfToken:  <?= to_js($csrfToken) ?>,
    fields:     ['full_name', 'username', 'email', 'password'],
    extraFields: ['password2'],
    busyLabel:  'Đang tạo tài khoản…',
    idleLabel:  'Tạo tài khoản',
    validate(values) {
        if (!values.full_name || !values.username || !values.email || !values.password) {
            return 'Vui lòng điền đầy đủ tất cả các trường.';
        }
        if (values.password.length < 6) {
            return 'Mật khẩu phải có ít nhất 6 ký tự.';
        }
        if (values.password !== values.password2) {
            return 'Hai lần nhập mật khẩu không khớp nhau.';
        }
        return null;
    }
}).mount();
</script>
