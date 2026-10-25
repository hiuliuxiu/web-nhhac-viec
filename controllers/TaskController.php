<?php
/** Công việc: xem, thêm, sửa, kéo thả và xoá. */
class TaskController extends Controller
{
    /** GET ?url=task/list&project_id=1 — danh sách công việc của một dự án (hoặc của mọi dự án). */
    public function list()
    {
        $user      = $this->requireLogin();
        $projectId = $this->inputInt('project_id');

        if ($projectId > 0) {
            $this->requireProjectAccess($projectId, $user);
            $projectIds = [$projectId];
        } else {
            $projectModel = new ProjectModel();
            $projectIds   = $projectModel->getProjectIdsOfUser($user['id']);
        }

        // Bộ lọc "Việc của tôi"
        $assigneeId = $this->inputBool('mine_only') ? $user['id'] : null;

        $tasks = [];
        if ($projectIds !== []) {
            $taskModel = new TaskModel();
            foreach ($taskModel->listByProjects($projectIds, $assigneeId) as $row) {
                $tasks[] = Helper::formatTask($row);
            }
        }

        $this->json([
            'tasks'       => $tasks,
            'signature'   => $this->makeSignature($tasks),
            'server_time' => date('c'),
        ]);
    }

    /** GET ?url=task/get&task_id=5 — chi tiết một công việc. */
    public function get()
    {
        $user = $this->requireLogin();
        $task = $this->findTaskForUser($this->inputInt('task_id'), $user);

        $this->json(['task' => Helper::formatTask($task)]);
    }

    /** POST ?url=task/create — thêm công việc mới. */
    public function create()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user);

        // --- Kiểm tra dữ liệu ---
        $title = $this->input('title');
        $this->checkTitle($title);

        $status = $this->input('status', 'todo');
        if (!in_array($status, Helper::STATUSES, true)) {
            $status = 'todo';
        }

        $priority = $this->input('priority', 'medium');
        if (!in_array($priority, Helper::PRIORITIES, true)) {
            $priority = 'medium';
        }

        $dueAt      = $this->readDueAt();
        $assigneeId = $this->checkAssignee($projectId, $this->inputInt('assignee_id'));

        $this->requireCanMoveToStatus($projectId, $assigneeId, $status, $user);

        // --- Lưu vào CSDL ---
        $taskModel = new TaskModel();
        $taskId = $taskModel->insert([
            'project_id'   => $projectId,
            'title'        => $title,
            'description'  => $this->readDescription(),
            'assignee_id'  => $assigneeId,
            'created_by'   => $user['id'],
            'priority'     => $priority,
            'status'       => $status,
            'due_at'       => $dueAt,
            'position'     => $taskModel->nextPosition($projectId, $status),
            'completed_at' => $status === 'done' ? date('Y-m-d H:i:s') : null,
        ]);

        if ($status === 'done') {
            $reminderService = new ReminderService();
            $reminderService->notifyCompleted($taskId);
        }

        $this->json([
            'task'    => Helper::formatTask($taskModel->find($taskId)),
            'message' => 'Đã thêm công việc mới.',
        ]);
    }

    /** POST ?url=task/update — sửa công việc. Trường nào không gửi lên thì giữ nguyên. */
    public function update()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $taskId    = $this->inputInt('task_id');
        $current   = $this->findTaskForUser($taskId, $user);
        $projectId = (int) $current['project_id'];

        // --- Kiểm tra dữ liệu ---
        $title = $this->input('title', $current['title']);
        $this->checkTitle($title);

        $status = $this->input('status', $current['status']);
        if (!in_array($status, Helper::STATUSES, true)) {
            $status = $current['status'];
        }

        $priority = $this->input('priority', $current['priority']);
        if (!in_array($priority, Helper::PRIORITIES, true)) {
            $priority = $current['priority'];
        }

        $dueAt = $current['due_at'];
        if ($this->has('due_at')) {
            $dueAt = $this->readDueAt();
        }

        $assigneeId = $current['assignee_id'] !== null ? (int) $current['assignee_id'] : null;
        if ($this->has('assignee_id')) {
            $assigneeId = $this->checkAssignee($projectId, $this->inputInt('assignee_id'));
            $this->requireCanChangeAssignee($projectId, $current['assignee_id'], $assigneeId, $user);
        }

        $description = $current['description'];
        if ($this->has('description')) {
            $description = $this->readDescription();
        }

        // --- Mốc thời gian hoàn thành ---
        $wasDone     = $current['status'] === 'done';
        $isDone      = $status === 'done';
        if ($current['status'] !== $status) {
            $this->requireCanMoveToStatus($projectId, $current['assignee_id'], $status, $user);
            $this->requireCanMoveToStatus($projectId, $assigneeId, $status, $user);
        }
        $completedAt = $this->completedAt($wasDone, $isDone, $current['completed_at']);

        // --- Lưu vào CSDL ---
        $taskModel = new TaskModel();
        $taskModel->update($taskId, [
            'title'        => $title,
            'description'  => $description,
            'assignee_id'  => $assigneeId,
            'priority'     => $priority,
            'status'       => $status,
            'due_at'       => $dueAt,
            'completed_at' => $completedAt,
        ]);

        // --- Dọn nhật ký nhắc việc để hệ thống được nhắc lại khi cần ---
        $reminderService = new ReminderService();

        if ((string) $current['due_at'] !== (string) $dueAt) {
            // Đổi hạn chót thì các lần nhắc theo hạn cũ không còn ý nghĩa
            $reminderService->resetForTask($taskId, ['due_soon', 'overdue']);
        }
        if ($wasDone && !$isDone) {
            // Mở lại việc đã xong: cho phép nhắc và báo hoàn thành lại từ đầu
            $reminderService->resetForTask($taskId, ['completed', 'due_soon', 'overdue']);
        }
        if (!$wasDone && $isDone) {
            $reminderService->notifyCompleted($taskId);
        }

        $this->json([
            'task'    => Helper::formatTask($taskModel->find($taskId)),
            'message' => 'Đã cập nhật công việc.',
        ]);
    }

    /** POST ?url=task/move — kéo thả thẻ sang cột khác và lưu lại thứ tự các thẻ trong cột. */
    public function move()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $taskId    = $this->inputInt('task_id');
        $current   = $this->findTaskForUser($taskId, $user);
        $projectId = (int) $current['project_id'];

        $status = $this->input('status');
        if (!in_array($status, Helper::STATUSES, true)) {
            $this->fail('Trạng thái không hợp lệ.');
        }

        $wasDone     = $current['status'] === 'done';
        $isDone      = $status === 'done';
        if ($current['status'] !== $status) {
            $this->requireCanMoveToStatus($projectId, $current['assignee_id'], $status, $user);
        }
        $completedAt = $this->completedAt($wasDone, $isDone, $current['completed_at']);

        // Đổi cột và sắp lại thứ tự phải cùng thành công, nên gói trong một transaction
        $taskModel = new TaskModel();
        $taskModel->beginTransaction();
        try {
            $taskModel->updateStatus($taskId, $status, $completedAt);

            // ordered_ids: id các thẻ trong cột đích, theo thứ tự từ trên xuống
            $position = 0;
            foreach ($this->inputArray('ordered_ids') as $id) {
                $taskModel->updatePosition((int) $id, $projectId, $position);
                $position++;
            }

            $taskModel->commit();
        } catch (PDOException $e) {
            $taskModel->rollBack();
            $this->fail('Không cập nhật được vị trí công việc.', 500);
        }

        // Kéo sang "Hoàn thành" thì báo ngay, không chờ lượt quét
        $reminderService = new ReminderService();
        $notified = ['logged' => 0, 'discord_sent' => 0, 'discord_failed' => 0, 'discord_mocked' => 0];

        if (!$wasDone && $isDone) {
            $notified = $reminderService->notifyCompleted($taskId);
        }
        if ($wasDone && !$isDone) {
            $reminderService->resetForTask($taskId, ['completed', 'due_soon', 'overdue']);
        }

        $this->json([
            'task'     => Helper::formatTask($taskModel->find($taskId)),
            'notified' => $notified,
            'message'  => 'Đã chuyển sang “' . Helper::STATUS_LABELS[$status] . '”.',
        ]);
    }

    /** POST ?url=task/delete — xoá công việc. */
    public function delete()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $taskId = $this->inputInt('task_id');
        $this->findTaskForUser($taskId, $user);

        $taskModel = new TaskModel();
        $taskModel->delete($taskId);

        $this->json(['message' => 'Đã xoá công việc.']);
    }

    // =====================================================================
    // CÁC HÀM PHỤ (private: chỉ dùng trong lớp này)
    // =====================================================================

    /** Tìm công việc và kiểm tra người dùng có thuộc dự án chứa công việc đó không. */
    private function findTaskForUser($taskId, array $user)
    {
        if ($taskId <= 0) {
            $this->fail('Thiếu mã công việc.');
        }

        $taskModel = new TaskModel();
        $task = $taskModel->find($taskId);
        if (!$task) {
            $this->fail('Không tìm thấy công việc.', 404);
        }

        $projectModel = new ProjectModel();
        if ($projectModel->getRole((int) $task['project_id'], $user['id']) === null) {
            $this->fail('Bạn không có quyền truy cập công việc này.', 403);
        }

        return $task;
    }

    /** Kiểm tra quyền chuyển việc vào các cột cần kiểm soát. */
    private function requireCanMoveToStatus($projectId, $assigneeId, $status, array $user)
    {
        if (!in_array($status, ['in_progress', 'review', 'done'], true)) {
            return;
        }

        $projectModel = new ProjectModel();
        if ($projectModel->getRole($projectId, $user['id']) === 'owner') {
            return;
        }

        if ($status === 'done') {
            $this->fail('Chỉ chủ dự án mới có thể chuyển công việc sang “Hoàn thành”.', 403);
        }

        if ($assigneeId === null || (int) $assigneeId !== (int) $user['id']) {
            $label = Helper::STATUS_LABELS[$status] ?? $status;
            $this->fail('Bạn chỉ có thể chuyển công việc được giao cho mình sang “' . $label . '”.', 403);
        }
    }

    /** Thành viên không được lấy hoặc gỡ việc đang được giao cho người khác. */
    private function requireCanChangeAssignee($projectId, $currentAssigneeId, $newAssigneeId, array $user)
    {
        if ($currentAssigneeId === null || (int) $currentAssigneeId === (int) $newAssigneeId) {
            return;
        }

        if ((int) $currentAssigneeId === (int) $user['id']) {
            return;
        }

        $projectModel = new ProjectModel();
        if ($projectModel->getRole($projectId, $user['id']) !== 'owner') {
            $this->fail('Bạn không thể đổi người được giao của công việc thuộc thành viên khác.', 403);
        }
    }

    private function checkTitle($title)
    {
        if ($title === '') {
            $this->fail('Tiêu đề công việc không được để trống.');
        }
        if (mb_strlen($title) > 255) {
            $this->fail('Tiêu đề công việc tối đa 255 ký tự.');
        }
    }

    /** Hạn chót: để trống thì trả về null, nhập sai định dạng thì báo lỗi. */
    private function readDueAt()
    {
        $raw   = $this->input('due_at');
        $dueAt = Helper::parseDateTime($raw);

        if ($raw !== '' && $dueAt === null) {
            $this->fail('Hạn chót không đúng định dạng ngày giờ.');
        }
        return $dueAt;
    }

    /** Mô tả: tối đa 5000 ký tự, để trống thì lưu NULL. */
    private function readDescription()
    {
        $description = mb_substr($this->input('description'), 0, 5000);
        return $description !== '' ? $description : null;
    }

    /** Người được giao phải là thành viên của dự án; 0 nghĩa là chưa phân công. */
    private function checkAssignee($projectId, $assigneeId)
    {
        if ($assigneeId <= 0) {
            return null;
        }

        $projectModel = new ProjectModel();
        if ($projectModel->getRole($projectId, $assigneeId) === null) {
            $this->fail('Chỉ có thể phân công cho thành viên của dự án này.');
        }
        return $assigneeId;
    }

    /** Thời điểm hoàn thành: vừa xong thì ghi giờ hiện tại, mở lại thì xoá đi. */
    private function completedAt($wasDone, $isDone, $oldCompletedAt)
    {
        if ($isDone && !$wasDone) {
            return date('Y-m-d H:i:s');
        }
        if (!$isDone && $wasDone) {
            return null;
        }
        return $oldCompletedAt;
    }

    /**
     * "Dấu vân tay" của danh sách công việc. JavaScript so dấu vân tay giữa hai lần tải:
     * giống nhau thì không vẽ lại, nhờ vậy giao diện không bị giật khi đang kéo thả.
     */
    private function makeSignature(array $tasks)
    {
        $parts = [];
        foreach ($tasks as $task) {
            $parts[] = $task['id'] . ':' . $task['status'] . ':' . $task['position'] . ':' . $task['updated_at'];
        }
        return md5(implode('|', $parts));
    }
}
