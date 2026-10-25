<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<!-- Lớp phủ nền khi mở thanh điều hướng trên màn hình nhỏ -->
<div class="sidebar-scrim" id="sidebarScrim"></div>

<div class="app">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- =====================================================================
       NỘI DUNG CHÍNH
       ===================================================================== -->
  <main class="main">

    <header class="topbar">
      <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Mở menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>

      <div class="topbar-title">
        <h1 id="pageTitle">Tổng quan</h1>
        <p id="pageSubtitle">Toàn cảnh tiến độ công việc của bạn</p>
      </div>

      <div class="topbar-actions">
        <!-- F09 - chỉ báo đồng bộ ngầm: chỉ có chấm màu, rê chuột vào để xem chi tiết -->
        <div class="sync-pill" id="syncPill" title="Đang đồng bộ…" aria-label="Đang đồng bộ…">
          <span class="sync-dot"></span>
        </div>

        <button class="btn btn-primary" id="newTaskBtn">
          <span>Công việc mới</span>
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
          </span>
        </button>
      </div>
    </header>

    <div class="page-body">

      <!-- ============================ TỔNG QUAN (F01 + F02) ============= -->
      <section class="page is-active" id="page-dashboard">
        <div class="stat-grid" id="statGrid"></div>

        <div class="dash-grid stagger">
          <div>
            <div class="card">
              <div class="card-head">
                <div class="card-title">Tiến độ theo dự án</div>
                <span class="badge" id="projectCountBadge"></span>
              </div>
              <div id="projectProgressList"></div>
            </div>

            <div class="card section-gap">
              <div class="card-head">
                <div class="card-title">Việc cần chú ý ngay</div>
                <button class="btn btn-ghost btn-sm" data-goto-page="tasks">Xem tất cả</button>
              </div>
              <div id="urgentList"></div>
            </div>
          </div>

          <div>
            <div class="card">
              <div class="card-head">
                <div class="card-title">Phân bố trạng thái</div>
              </div>
              <div class="chart-box">
                <canvas id="statusChart"></canvas>
                <div class="chart-center">
                  <div class="cc-value" id="chartPercent">0%</div>
                  <div class="cc-label">Hoàn thành</div>
                </div>
              </div>
              <div class="legend" id="chartLegend"></div>
            </div>

            <div class="card section-gap">
              <div class="card-head"><div class="card-title">Khối lượng theo người</div></div>
              <div id="workloadList"></div>
            </div>

            <div class="card section-gap">
              <div class="card-head"><div class="card-title">Hoàn thành gần đây</div></div>
              <div id="activityList"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ============================ CÔNG VIỆC (F03 + F05) ============= -->
      <section class="page" id="page-tasks">

        <div class="toolbar">
          <!-- F03 - chuyển đổi 3 góc nhìn -->
          <div class="view-switch" role="tablist">
            <button class="view-tab is-active" data-view="board" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="9.5" y="4" width="5" height="11" rx="1.5"/><rect x="16" y="4" width="5" height="14" rx="1.5"/>
              </svg>
              Kanban
            </button>
            <button class="view-tab" data-view="list" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
              Danh sách
            </button>
            <button class="view-tab" data-view="calendar" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/>
              </svg>
              Lịch
            </button>
          </div>

          <div class="spacer"></div>

          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>
            <input type="search" id="taskSearch" placeholder="Tìm theo tiêu đề…">
          </div>

          <select class="filter-select" id="filterAssignee" aria-label="Lọc theo người thực hiện">
            <option value="">Mọi người</option>
          </select>

          <select class="filter-select" id="filterPriority" aria-label="Lọc theo ưu tiên">
            <option value="">Mọi mức ưu tiên</option>
            <option value="urgent">Khẩn cấp</option>
            <option value="high">Cao</option>
            <option value="medium">Trung bình</option>
            <option value="low">Thấp</option>
          </select>
        </div>

        <!-- Góc nhìn 1: Kanban -->
        <div class="view-panel is-active" id="view-board">
          <div class="kanban" id="kanbanBoard"></div>
        </div>

        <!-- Góc nhìn 2: Danh sách -->
        <div class="view-panel" id="view-list">
          <div class="shell">
            <div class="core">
              <div class="table-wrap">
                <table class="data" id="taskTable">
                  <thead>
                    <tr>
                      <th class="sortable" data-sort="title">Công việc <span class="sort-arrow">▲</span></th>
                      <th class="sortable" data-sort="status">Trạng thái <span class="sort-arrow">▲</span></th>
                      <th class="sortable" data-sort="priority">Ưu tiên <span class="sort-arrow">▲</span></th>
                      <th class="sortable" data-sort="assignee">Người thực hiện <span class="sort-arrow">▲</span></th>
                      <th class="sortable is-sorted" data-sort="due">Hạn chót <span class="sort-arrow">▲</span></th>
                    </tr>
                  </thead>
                  <tbody id="taskTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Góc nhìn 3: Lịch -->
        <div class="view-panel" id="view-calendar">
          <div class="shell">
            <div class="core calendar-host">
              <div id="calendarHost"></div>
            </div>
          </div>
        </div>

      </section>

      <!-- ============================ THÀNH VIÊN (F06) ================== -->
      <section class="page" id="page-members">
        <div class="settings-grid stagger">
          <div class="card">
            <div class="card-head">
              <div class="card-title">Thành viên dự án</div>
              <span class="badge" id="memberCountBadge"></span>
            </div>
            <div id="memberList"></div>
          </div>

          <div class="card" id="inviteCard">
            <div class="card-head"><div class="card-title">Thêm thành viên</div></div>
            <div class="field">
              <label for="inviteInput">Tên đăng nhập hoặc email</label>
              <input type="text" id="inviteInput" placeholder="Tên đăng nhập hoặc email" autocomplete="off">
            </div>
            <div id="inviteSuggestions"></div>
            <button class="btn btn-primary btn-block" id="inviteBtn">
              <span>Thêm vào dự án</span>
              <span class="btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
              </span>
            </button>
          </div>
        </div>
      </section>

      <!-- ============================ CẤU HÌNH (F08 + F10) ============== -->
      <section class="page" id="page-settings">
        <div id="settingsContent"></div>
      </section>

      <!-- ============================ THÔNG BÁO (F07 + F08) ============= -->
      <section class="page" id="page-log">
        <div class="toolbar">
          <div class="view-switch">
            <button class="view-tab is-active" data-log-channel="">Tất cả</button>
            <button class="view-tab" data-log-channel="system">Trong hệ thống</button>
            <button class="view-tab" data-log-channel="discord">Đã đẩy Discord</button>
          </div>
          <div class="spacer"></div>
          <!-- Nút quét deadline ngay: chỉ có biểu tượng, rê chuột vào để xem chú thích -->
          <button class="round-btn" id="forceScanBtn" title="Quét deadline ngay" aria-label="Quét deadline ngay">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11A8 8 0 1 0 18 16.5"/><path d="M20 6v5h-5"/></svg>
          </button>
        </div>

        <div class="shell">
          <div class="core" style="padding:20px 22px">
            <div id="logList"></div>
          </div>
        </div>
      </section>

      <!-- ============================ HƯỚNG DẪN SỬ DỤNG ================= -->
      <section class="page" id="page-guide">
        <div id="guideContent"></div>
      </section>

    </div>
  </main>
</div>

<?php include __DIR__ . '/modals.php'; ?>
