<?php
/** Dự án: xem, tạo, sửa, xoá và cấu hình nhắc việc (webhook Discord). */
class ProjectController extends Controller
{
    /** GET ?url=project/list — các dự án mà người dùng tham gia, kèm số liệu tiến độ. */
    public function list()
    {
        $user = $this->requireLogin();

        $projectModel = new ProjectModel();
        $projects = [];
        foreach ($projectModel->listWithStats($user['id']) as $row) {
            $projects[] = Helper::formatProject($row);
        }

        $this->json(['projects' => $projects]);
    }

    /** GET ?url=project/get&project_id=1 — một dự án. */
    public function get()
    {
        $user      = $this->requireLogin();
        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user);

        $this->json(['project' => $this->loadProject($user, $projectId)]);
    }

    /** POST ?url=project/create — tạo dự án mới; người tạo trở thành chủ dự án. */
    public function create()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $name = $this->input('name');
        $this->checkName($name);

        $projectModel = new ProjectModel();
        $memberModel  = new MemberModel();

        // Tạo dự án và thêm chủ dự án phải cùng thành công
        $projectModel->beginTransaction();
        try {
            $projectId = $projectModel->create(
                $name,
                $this->readDescription(),
                Helper::safeColor($this->input('color')),
                $user['id']
            );
            $memberModel->add($projectId, $user['id'], 'owner');

            $projectModel->commit();
        } catch (PDOException $e) {
            $projectModel->rollBack();
            $this->fail('Không tạo được dự án.', 500);
        }

        $this->json([
            'project' => $this->loadProject($user, $projectId),
            'message' => 'Đã tạo dự án mới.',
        ]);
    }

    /** POST ?url=project/update — đổi tên, mô tả, màu (chỉ chủ dự án). */
    public function update()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user, true);

        $name = $this->input('name');
        $this->checkName($name);

        $projectModel = new ProjectModel();
        $projectModel->update($projectId, $name, $this->readDescription(), Helper::safeColor($this->input('color')));

        $this->json([
            'project' => $this->loadProject($user, $projectId),
            'message' => 'Đã lưu thông tin dự án.',
        ]);
    }

    /** POST ?url=project/delete — xoá dự án cùng mọi công việc bên trong (chỉ chủ dự án). */
    public function delete()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user, true);

        $projectModel = new ProjectModel();
        $projectModel->delete($projectId);

        $this->json(['message' => 'Đã xoá dự án cùng toàn bộ công việc bên trong.']);
    }

    /** POST ?url=project/settings — lưu cấu hình nhắc việc và webhook Discord (chỉ chủ dự án). */
    public function settings()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user, true);

        $discord = new DiscordService();

        $webhook = $this->input('discord_webhook_url');
        if ($webhook !== '' && !$discord->isValidWebhook($webhook)) {
            $this->fail('URL webhook không hợp lệ. Đường dẫn phải có dạng https://discord.com/api/webhooks/…');
        }

        $discordEnabled = $this->inputBool('discord_enabled');
        if ($discordEnabled && $webhook === '') {
            $this->fail('Bạn cần dán URL webhook trước khi bật gửi nhắc nhở lên Discord.');
        }

        // Ngưỡng "sắp tới hạn": từ 5 phút đến 7 ngày (10080 phút)
        $remindBefore = $this->inputInt('remind_before_minutes', 120);
        $remindBefore = min(10080, max(5, $remindBefore));

        $projectModel = new ProjectModel();
        $projectModel->updateSettings($projectId, [
            'discord_webhook_url'   => $webhook !== '' ? $webhook : null,
            'discord_enabled'       => $discordEnabled ? 1 : 0,
            'mock_mode'             => $this->inputBool('mock_mode') ? 1 : 0,
            'notify_due_soon'       => $this->inputBool('notify_due_soon', true) ? 1 : 0,
            'notify_overdue'        => $this->inputBool('notify_overdue', true) ? 1 : 0,
            'notify_completed'      => $this->inputBool('notify_completed') ? 1 : 0,
            'remind_before_minutes' => $remindBefore,
        ]);

        $this->json([
            'project' => $this->loadProject($user, $projectId),
            'message' => 'Đã lưu cấu hình nhắc việc.',
        ]);
    }

    /** POST ?url=project/testWebhook — gửi thử một tin nhắn lên Discord (chỉ chủ dự án). */
    public function testWebhook()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $project   = $this->requireProjectAccess($projectId, $user, true);

        // Ưu tiên URL vừa gõ (để thử trước khi lưu); để trống thì dùng URL đã lưu
        $webhook = $this->input('discord_webhook_url');
        if ($webhook === '') {
            $webhook = (string) $project['discord_webhook_url'];
        }

        $discord = new DiscordService();

        if ($webhook === '') {
            $this->fail('Chưa có URL webhook để kiểm tra.');
        }
        if (!$discord->isValidWebhook($webhook)) {
            $this->fail('URL webhook không hợp lệ. Đường dẫn phải có dạng https://discord.com/api/webhooks/…');
        }

        $payload = $discord->buildTestMessage($project['name'], $user['full_name']);

        if ((int) $project['mock_mode'] === 1) {
            $this->json([
                'mocked'  => true,
                'preview' => $discord->summarize($payload),
                'message' => 'Chế độ giả lập đang bật — không gọi mạng thật. Nội dung tin nhắn đã được dựng thành công.',
            ]);
            return;
        }

        $result = $discord->send($webhook, $payload);
        if (!$result['ok']) {
            $this->fail('Gửi thất bại: ' . $result['error'], 502);
        }

        $this->json(['message' => 'Đã gửi tin nhắn kiểm tra. Hãy mở kênh Discord để xem kết quả.']);
    }

    /** POST ?url=project/clearLog — xoá nhật ký nhắc việc để hệ thống nhắc lại từ đầu (chỉ chủ dự án). */
    public function clearLog()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user, true);

        $reminderModel = new ReminderModel();
        $deleted = $reminderModel->deleteForProject($projectId);

        $this->json([
            'deleted' => $deleted,
            'message' => 'Đã xoá ' . $deleted . ' bản ghi. Hệ thống sẽ nhắc lại từ đầu ở lượt quét kế tiếp.',
        ]);
    }

    // =====================================================================
    // CÁC HÀM PHỤ
    // =====================================================================

    /** Đọc lại một dự án kèm số liệu, đã định dạng để trả về cho JavaScript. */
    private function loadProject(array $user, $projectId)
    {
        $projectModel = new ProjectModel();
        $rows = $projectModel->listWithStats($user['id'], $projectId);

        if ($rows === []) {
            $this->fail('Không tìm thấy dự án.', 404);
        }
        return Helper::formatProject($rows[0]);
    }

    private function checkName($name)
    {
        if ($name === '' || mb_strlen($name) > 150) {
            $this->fail('Tên dự án không được để trống và tối đa 150 ký tự.');
        }
    }

    /** Mô tả: tối đa 1000 ký tự, để trống thì lưu NULL. */
    private function readDescription()
    {
        $description = mb_substr($this->input('description'), 0, 1000);
        return $description !== '' ? $description : null;
    }
}
