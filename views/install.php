<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<div class="install-wrap">
  <div class="shell">
    <div class="core install-card">

      <div class="install-head">
        <?php include __DIR__ . '/brand-mark.php'; ?>
        <div>
          <h1>Cài đặt <?= e($appName) ?></h1>
          <p class="muted">Tạo cơ sở dữ liệu <code><?= e($databaseName) ?></code> trên MySQL của XAMPP.</p>
        </div>
      </div>

      <?php if ($fatal !== null): ?>
        <div class="alert alert-error"><?= e($fatal) ?></div>
      <?php endif; ?>

      <?php if ($steps !== []): ?>
        <ul class="install-steps">
          <?php foreach ($steps as $step): ?>
            <li class="<?= $step['ok'] ? 'is-ok' : 'is-fail' ?>">
              <span class="step-icon"><?= $step['ok'] ? '✓' : '✕' ?></span>
              <div>
                <strong><?= e($step['label']) ?></strong>
                <?php if (!empty($step['detail'])): ?>
                  <div class="muted small"><?= e($step['detail']) ?></div>
                <?php endif; ?>
                <?php if (!$step['ok']): ?>
                  <div class="small error-text"><?= e($step['error']) ?></div>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($allOk): ?>
        <div class="alert alert-success">
          <strong>Cài đặt thành công!</strong>
          <?php if ($withSeed): ?>
            Đăng nhập bằng <code>an</code> / <code>123456</code> để xem dữ liệu mẫu.
          <?php endif; ?>
        </div>
        <a class="btn btn-primary btn-block" href="<?= e(url('auth/login')) ?>">
          Tới trang đăng nhập
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </span>
        </a>
      <?php else: ?>
        <form method="post" action="<?= e(url('install')) ?>" class="install-form">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <div class="callout">
            <strong>Trước khi bấm cài đặt:</strong>
            <ol>
              <li>Mở <em>XAMPP Control Panel</em>, bấm <em>Start</em> ở dòng <strong>MySQL</strong>.</li>
              <li>Thao tác này sẽ <strong>xoá và tạo lại</strong> cơ sở dữ liệu <code><?= e($databaseName) ?></code> nếu nó đã tồn tại.</li>
            </ol>
          </div>

          <label class="check-row">
            <input type="checkbox" name="with_seed" value="1" checked>
            <span>
              <strong>Nạp dữ liệu mẫu</strong>
              <span class="muted small">8 tài khoản, 4 dự án và 83 công việc, có sẵn việc quá hạn và sắp tới hạn để xem ngay hiệu ứng cảnh báo.</span>
            </span>
          </label>

          <button type="submit" class="btn btn-primary btn-block">
            Bắt đầu cài đặt
            <span class="btn-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
          </button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>
