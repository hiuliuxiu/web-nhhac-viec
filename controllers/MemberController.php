<?php
/** Thành viên của dự án: xem danh sách, tìm, mời, xoá và đổi vai trò. */
class MemberController extends Controller
{
    /** GET ?url=member/list&project_id=1 — thành viên của dự án. */
    public function list()
    {
        $user      = $this->requireLogin();
        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user);

        $memberModel = new MemberModel();
        $members = [];
        foreach ($memberModel->listByProject($projectId) as $row) {
            $row['id']         = (int) $row['id'];
            $row['open_tasks'] = (int) $row['open_tasks'];
            $row['done_tasks'] = (int) $row['done_tasks'];
            $row['joined_at']  = Helper::toIso($row['joined_at']);
            $members[] = $row;
        }

        $this->json(['members' => $members]);
    }

    /** GET ?url=member/search&project_id=1&q=binh — tìm người để mời (chỉ chủ dự án). */
    public function search()
    {
        $user      = $this->requireLogin();
        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user, true);

        $keyword = $this->input('q');
        if (mb_strlen($keyword) < 2) {
            $this->json(['users' => []]);
            return;
        }

        $memberModel = new MemberModel();
        $users = [];
        foreach ($memberModel->searchUsersNotInProject($projectId, $keyword) as $row) {
            $row['id'] = (int) $row['id'];
            $users[] = $row;
        }

        $this->json(['users' => $users]);
    }

    /** POST ?url=member/invite — thêm một người đã có tài khoản vào dự án (chỉ chủ dự án). */
    public function invite()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $this->requireProjectAccess($projectId, $user, true);

        $identifier = strtolower($this->input('identifier'));
        if ($identifier === '') {
            $this->fail('Hãy nhập tên đăng nhập hoặc email của người bạn muốn mời.');
        }

        $userModel = new UserModel();
        $invitee   = $userModel->findByLogin($identifier);
        if (!$invitee) {
            $this->fail('Không tìm thấy người dùng “' . $identifier . '”. Người này cần đăng ký tài khoản trước.');
        }

        $projectModel = new ProjectModel();
        if ($projectModel->getRole($projectId, (int) $invitee['id']) !== null) {
            $this->fail($invitee['full_name'] . ' đã là thành viên của dự án này rồi.');
        }

        $memberModel = new MemberModel();
        $memberModel->add($projectId, (int) $invitee['id'], 'member');

        $this->json(['message' => 'Đã thêm ' . $invitee['full_name'] . ' vào dự án.']);
    }

    /** POST ?url=member/remove — xoá một thành viên khỏi dự án (chỉ chủ dự án). */
    public function remove()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $project   = $this->requireProjectAccess($projectId, $user, true);

        $memberId = $this->inputInt('user_id');
        if ($memberId <= 0) {
            $this->fail('Thiếu mã thành viên.');
        }
        if ($memberId === $project['owner_id']) {
            $this->fail('Không thể xoá chủ dự án khỏi chính dự án của họ.');
        }

        // Gỡ phân công trước, để không còn thẻ nào giao cho người đã rời dự án
        $taskModel = new TaskModel();
        $taskModel->unassignMember($projectId, $memberId);

        $memberModel = new MemberModel();
        if ($memberModel->remove($projectId, $memberId) === 0) {
            $this->fail('Người này không có trong dự án.', 404);
        }

        $this->json(['message' => 'Đã xoá thành viên khỏi dự án. Các công việc của họ được chuyển về “chưa phân công”.']);
    }

    /** POST ?url=member/setRole — đổi vai trò owner/member (chỉ chủ dự án). */
    public function setRole()
    {
        $this->requirePost();
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $projectId = $this->inputInt('project_id');
        $project   = $this->requireProjectAccess($projectId, $user, true);

        $memberId = $this->inputInt('user_id');
        $role     = $this->input('role');

        if (!in_array($role, ['owner', 'member'], true)) {
            $this->fail('Vai trò không hợp lệ.');
        }
        if ($memberId === $project['owner_id'] && $role !== 'owner') {
            $this->fail('Không thể hạ quyền của người tạo dự án.');
        }

        $memberModel = new MemberModel();
        if ($memberModel->setRole($projectId, $memberId, $role) === 0) {
            $this->fail('Không tìm thấy thành viên để cập nhật.', 404);
        }

        $this->json(['message' => 'Đã cập nhật vai trò thành viên.']);
    }
}
