<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <?php include __DIR__ . '/brand-mark.php'; ?>
    <div>
      <div class="brand-name"><?= e($appName) ?></div>
      <div class="brand-sub">Theo dõi tiến độ</div>
    </div>
  </div>

  <div class="side-label">Dự án đang xem</div>
  <div class="project-picker">
    <select id="projectPicker" aria-label="Chọn dự án"></select>
  </div>

  <nav class="sidebar-nav">
    <div class="side-label">Điều hướng</div>

    <button class="nav-item is-active" data-page="dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7.5" height="8.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="5" rx="2"/>
        <rect x="13.5" y="10.5" width="7.5" height="10.5" rx="2"/><rect x="3" y="14" width="7.5" height="7" rx="2"/>
      </svg>
      Tổng quan
    </button>

    <button class="nav-item" data-page="tasks">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 5h10M9 12h10M9 19h10"/><path d="m3.5 5 1.2 1.2L7 4"/><path d="m3.5 12 1.2 1.2L7 11"/><path d="m3.5 19 1.2 1.2L7 18"/>
      </svg>
      Công việc
      <span class="nav-badge" id="navBadgeTasks"></span>
    </button>

    <button class="nav-item" data-page="members">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/>
        <path d="M16.5 5.4a3.2 3.2 0 0 1 0 5.2M17.8 14.2A6.5 6.5 0 0 1 21.5 20"/>
      </svg>
      Thành viên
    </button>

    <div class="side-label">Nhắc việc</div>

    <button class="nav-item" data-page="settings">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9v0a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.55 1Z"/>
      </svg>
      Cài đặt
    </button>

    <button class="nav-item" data-page="log">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 3a6 6 0 0 0-6 6c0 3-1.2 4.6-2 5.4a.7.7 0 0 0 .5 1.2h15a.7.7 0 0 0 .5-1.2c-.8-.8-2-2.4-2-5.4a6 6 0 0 0-6-6Z"/>
        <path d="M10 19a2 2 0 0 0 4 0"/>
      </svg>
      Thông báo
      <span class="nav-badge is-quiet" id="navBadgeLog"></span>
    </button>

    <!-- Trang Hướng dẫn sử dụng đang hoàn thiện nên tạm ẩn; bỏ class "hidden" ở 2 dòng dưới để hiện lại -->
    <div class="side-label hidden">Trợ giúp</div>

    <button class="nav-item hidden" data-page="guide">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10a2.5 2.5 0 0 1 2 1 2.5 2.5 0 0 1 2-1h4.5A1.5 1.5 0 0 1 20 5.5v12a1.5 1.5 0 0 1-1.5 1.5H14a2.5 2.5 0 0 0-2 1 2.5 2.5 0 0 0-2-1H5.5A1.5 1.5 0 0 1 4 17.5Z"/>
        <path d="M12 6.5v13"/>
      </svg>
      Hướng dẫn sử dụng
    </button>
  </nav>

  <div class="sidebar-user">
    <div class="avatar" style="background: <?= e($user['avatar_color']) ?>"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
    <div>
      <div class="u-name"><?= e($user['full_name']) ?></div>
      <div class="u-mail"><?= e($user['email']) ?></div>
    </div>
    <button class="icon-btn" id="logoutBtn" title="Đăng xuất" aria-label="Đăng xuất">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 17v1.5a2.5 2.5 0 0 1-2.5 2.5h-6A2.5 2.5 0 0 1 4 18.5v-13A2.5 2.5 0 0 1 6.5 3h6A2.5 2.5 0 0 1 15 5.5V7"/>
        <path d="M10 12h11M18 8.5l3.5 3.5-3.5 3.5"/>
      </svg>
    </button>
  </div>

</aside>
