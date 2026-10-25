<?php
/** Làm việc với bảng projects (dự án) và vai trò của người dùng trong dự án. */
class ProjectModel extends Model
{
    /** Vai trò của người dùng trong dự án: 'owner', 'member', hoặc null nếu không phải thành viên. */
    public function getRole($projectId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT role FROM project_members WHERE project_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$projectId, $userId]);
        $role = $stmt->fetchColumn();

        return $role === false ? null : $role;
    }

    /** Một dự án theo id, hoặc false nếu không có. */
    public function find($projectId)
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = ? LIMIT 1');
        $stmt->execute([$projectId]);
        return $stmt->fetch();
    }

    /** Id các dự án mà người dùng đang tham gia. */
    public function getProjectIdsOfUser($userId)
    {
        $stmt = $this->db->prepare('SELECT project_id FROM project_members WHERE user_id = ?');
        $stmt->execute([$userId]);

        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int) $row['project_id'];
        }
        return $ids;
    }

    /**
     * Các dự án người dùng tham gia, kèm số liệu: tổng số việc, số việc theo từng cột,
     * số việc quá hạn, sắp tới hạn và số thành viên.
     * Truyền $projectId để chỉ lấy một dự án.
     */
    public function listWithStats($userId, $projectId = null)
    {
        $sql = "
            SELECT  p.id, p.name, p.description, p.color, p.owner_id, p.created_at,
                    p.discord_webhook_url, p.discord_enabled, p.mock_mode,
                    p.notify_due_soon, p.notify_overdue, p.notify_completed,
                    p.remind_before_minutes,
                    pm.role         AS my_role,
                    owner.full_name AS owner_name,

                    COUNT(t.id) AS total_tasks,
                    SUM(CASE WHEN t.status = 'todo'        THEN 1 ELSE 0 END) AS todo_tasks,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
                    SUM(CASE WHEN t.status = 'review'      THEN 1 ELSE 0 END) AS review_tasks,
                    SUM(CASE WHEN t.status = 'done'        THEN 1 ELSE 0 END) AS done_tasks,

                    SUM(CASE WHEN t.status <> 'done' AND t.due_at < NOW()
                             THEN 1 ELSE 0 END) AS overdue_tasks,

                    SUM(CASE WHEN t.status <> 'done' AND t.due_at >= NOW()
                              AND t.due_at <= DATE_ADD(NOW(), INTERVAL p.remind_before_minutes MINUTE)
                             THEN 1 ELSE 0 END) AS due_soon_tasks,

                    (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS member_count
            FROM        projects        p
            INNER JOIN  project_members pm    ON pm.project_id = p.id AND pm.user_id = ?
            LEFT  JOIN  users           owner ON owner.id      = p.owner_id
            LEFT  JOIN  tasks           t     ON t.project_id  = p.id
        ";
        $params = [$userId];

        if ($projectId !== null) {
            $sql .= ' WHERE p.id = ?';
            $params[] = $projectId;
        }

        $sql .= ' GROUP BY p.id ORDER BY p.created_at ASC, p.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Tạo dự án mới; trả về id vừa tạo. */
    public function create($name, $description, $color, $ownerId)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO projects (name, description, color, owner_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $description, $color, $ownerId]);

        return (int) $this->db->lastInsertId();
    }

    public function update($projectId, $name, $description, $color)
    {
        $stmt = $this->db->prepare('UPDATE projects SET name = ?, description = ?, color = ? WHERE id = ?');
        $stmt->execute([$name, $description, $color, $projectId]);
    }

    /** Xoá dự án. Khoá ngoại ON DELETE CASCADE tự xoá công việc, thành viên và nhật ký bên trong. */
    public function delete($projectId)
    {
        $stmt = $this->db->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$projectId]);
    }

    /** Lưu cấu hình nhắc việc và webhook Discord của dự án. */
    public function updateSettings($projectId, array $settings)
    {
        $stmt = $this->db->prepare(
            'UPDATE projects SET
                discord_webhook_url   = ?,
                discord_enabled       = ?,
                mock_mode             = ?,
                notify_due_soon       = ?,
                notify_overdue        = ?,
                notify_completed      = ?,
                remind_before_minutes = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $settings['discord_webhook_url'],
            $settings['discord_enabled'],
            $settings['mock_mode'],
            $settings['notify_due_soon'],
            $settings['notify_overdue'],
            $settings['notify_completed'],
            $settings['remind_before_minutes'],
            $projectId,
        ]);
    }
}
