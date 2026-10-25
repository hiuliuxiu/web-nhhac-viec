<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<!-- =========================================================================
     HỘP THOẠI: THÊM / SỬA CÔNG VIỆC (F04)
     ========================================================================= -->
<div class="modal-backdrop" id="taskModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
    <div class="modal-head">
      <h2 id="taskModalTitle">Công việc mới</h2>
      <button class="icon-btn" data-close-modal aria-label="Đóng">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>

    <div class="modal-body">
      <div id="taskFormError" class="alert alert-error hidden"></div>

      <form id="taskForm" novalidate>
        <input type="hidden" id="taskId">

        <div class="field">
          <label for="taskTitle">Tiêu đề <span class="error-text">*</span></label>
          <input type="text" id="taskTitle" placeholder="Ví dụ: Hoàn thiện báo cáo tuần" required maxlength="255">
        </div>

        <div class="field">
          <label for="taskDescription">Mô tả</label>
          <textarea id="taskDescription" placeholder="Mô tả chi tiết công việc cần làm…" maxlength="2000"></textarea>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="taskAssignee">Người thực hiện</label>
            <select id="taskAssignee"><option value="">Chưa phân công</option></select>
          </div>
          <div class="field">
            <label for="taskPriority">Mức độ ưu tiên</label>
            <select id="taskPriority">
              <option value="low">Thấp</option>
              <option value="medium" selected>Trung bình</option>
              <option value="high">Cao</option>
              <option value="urgent">Khẩn cấp</option>
            </select>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="taskStatus">Trạng thái</label>
            <select id="taskStatus">
              <option value="todo" selected>Cần làm</option>
              <option value="in_progress">Đang làm</option>
              <option value="review">Chờ duyệt</option>
              <option value="done">Hoàn thành</option>
            </select>
          </div>
          <div class="field">
            <label for="taskDueAt">Hạn chót (có giờ/phút)</label>
            <input type="datetime-local" id="taskDueAt">
          </div>
        </div>
      </form>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" id="deleteTaskBtn" style="color:var(--danger)">Xoá công việc</button>
      <div class="spacer"></div>
      <button class="btn" data-close-modal>Huỷ</button>
      <button class="btn btn-primary" id="saveTaskBtn">
        <span class="btn-label">Lưu công việc</span>
      </button>
    </div>
  </div>
</div>

<!-- =========================================================================
     HỘP THOẠI: DỰ ÁN (F10)
     ========================================================================= -->
<div class="modal-backdrop" id="projectModal">
  <div class="modal modal-sm" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2 id="projectModalTitle">Dự án mới</h2>
      <button class="icon-btn" data-close-modal aria-label="Đóng">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>

    <div class="modal-body">
      <div id="projectFormError" class="alert alert-error hidden"></div>
      <form id="projectForm" novalidate>
        <input type="hidden" id="projectFormId">
        <div class="field">
          <label for="projectName">Tên dự án <span class="error-text">*</span></label>
          <input type="text" id="projectName" placeholder="Ví dụ: Đồ án tốt nghiệp" required maxlength="150">
        </div>
        <div class="field">
          <label for="projectDescription">Mô tả</label>
          <textarea id="projectDescription" placeholder="Dự án này nhằm mục đích…" maxlength="1000"></textarea>
        </div>
        <div class="field" style="margin-bottom:0">
          <label for="projectColor">Màu nhận diện</label>
          <input type="color" id="projectColor" value="#5b5bd6" style="height:42px;padding:4px;cursor:pointer">
        </div>
      </form>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" id="deleteProjectBtn" style="color:var(--danger)">Xoá dự án</button>
      <div class="spacer"></div>
      <button class="btn" data-close-modal>Huỷ</button>
      <button class="btn btn-primary" id="saveProjectBtn"><span class="btn-label">Lưu dự án</span></button>
    </div>
  </div>
</div>

<!-- =========================================================================
     HỘP THOẠI XÁC NHẬN
     ========================================================================= -->
<div class="modal-backdrop" id="confirmModal">
  <div class="modal modal-sm" role="dialog" aria-modal="true">
    <div class="modal-head"><h2 id="confirmTitle">Xác nhận</h2></div>
    <div class="modal-body"><p id="confirmMessage" style="font-size:13.5px;line-height:1.6"></p></div>
    <div class="modal-foot">
      <div class="spacer"></div>
      <button class="btn" data-close-modal>Huỷ</button>
      <button class="btn btn-danger" id="confirmOkBtn">Đồng ý</button>
    </div>
  </div>
</div>

<div class="toast-stack" id="toastStack" aria-live="polite"></div>
