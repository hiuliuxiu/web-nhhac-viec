<?php
/** Làm việc với bảng project_members (ai là thành viên của dự án nào). */
class MemberModel extends Model
{
    /** Thành viên của dự án, kèm số việc đang làm và đã xong của từng người. Chủ dự án xếp đầu. */
    public function listByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT  u.id, u.username, u.full_name, u.email, u.avatar_color,
                    pm.role, pm.joined_at,
                    (SELECT COUNT(*) FROM tasks t
                      WHERE t.project_id = pm.project_id
                        AND t.assignee_id = u.id
                        AND t.status <> 'done') AS open_tasks,
                    (SELECT COUNT(*) FROM tasks t
                      WHERE t.project_id = pm.project_id
                        AND t.assignee_id = u.id
                        AND t.status = 'done')  AS done_tasks
            FROM        project_members pm
            INNER JOIN  users           u ON u.id = pm.user_id
            WHERE pm.project_id = ?
            ORDER BY (pm.role = 'owner') DESC, u.full_name ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    /** Tìm người dùng theo tên đăng nhập, email hoặc họ tên; bỏ qua người đã ở trong dự án. */
    public function searchUsersNotInProject($projectId, $keyword)
    {
        $like = '%' . $keyword . '%';

        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.full_name, u.email, u.avatar_color
            FROM users u
            WHERE (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)
              AND u.id NOT IN (SELECT user_id FROM project_members WHERE project_id = ?)
            ORDER BY u.full_name ASC
            LIMIT 8
        ");
        $stmt->execute([$like, $like, $like, $projectId]);
        return $stmt->fetchAll();
    }

    /** Thêm một người vào dự án với vai trò 'owner' hoặc 'member'. */
    public function add($projectId, $userId, $role)
    {
        $stmt = $this->db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)');
        $stmt->execute([$projectId, $userId, $role]);
    }

    /** Xoá một người khỏi dự án; trả về số dòng đã xoá (0 nếu người đó không có trong dự án). */
    public function remove($projectId, $userId)
    {
        $stmt = $this->db->prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?');
        $stmt->execute([$projectId, $userId]);
        return $stmt->rowCount();
    }

    /** Đổi vai trò; trả về số dòng đã sửa. */
    public function setRole($projectId, $userId, $role)
    {
        $stmt = $this->db->prepare('UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?');
        $stmt->execute([$role, $projectId, $userId]);
        return $stmt->rowCount();
    }
}
