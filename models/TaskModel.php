<?php
/** Làm việc với bảng tasks (công việc). */
class TaskModel extends Model
{
    /** Phần SELECT dùng chung: công việc kèm tên dự án, người thực hiện và người tạo. */
    private function selectSql()
    {
        return "
            SELECT  t.id, t.project_id, t.title, t.description, t.status, t.priority,
                    t.due_at, t.position, t.assignee_id, t.created_by,
                    t.completed_at, t.created_at, t.updated_at,
                    p.name  AS project_name,
                    p.color AS project_color,
                    p.remind_before_minutes,
                    a.full_name    AS assignee_name,
                    a.avatar_color AS assignee_color,
                    c.full_name    AS creator_name
            FROM        tasks    t
            INNER JOIN  projects p ON p.id = t.project_id
            LEFT  JOIN  users    a ON a.id = t.assignee_id
            LEFT  JOIN  users    c ON c.id = t.created_by
        ";
    }

    /** Một công việc theo id, hoặc false nếu không có. */
    public function find($taskId)
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE t.id = ? LIMIT 1');
        $stmt->execute([$taskId]);
        return $stmt->fetch();
    }

    /**
     * Công việc của các dự án trong $projectIds.
     * Truyền $assigneeId thì chỉ lấy việc được giao cho người đó (bộ lọc "Việc của tôi").
     */
    public function listByProjects(array $projectIds, $assigneeId = null)
    {
        $sql    = $this->selectSql() . ' WHERE t.project_id IN (' . $this->placeholders($projectIds) . ')';
        $params = $projectIds;

        if ($assigneeId !== null) {
            $sql .= ' AND t.assignee_id = ?';
            $params[] = $assigneeId;
        }

        $sql .= ' ORDER BY t.status ASC, t.position ASC, t.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Vị trí cho thẻ mới: nằm cuối cột tương ứng trên bảng Kanban. */
    public function nextPosition($projectId, $status)
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(position), -1) + 1 FROM tasks WHERE project_id = ? AND status = ?'
        );
        $stmt->execute([$projectId, $status]);
        return (int) $stmt->fetchColumn();
    }

    /** Thêm công việc mới; trả về id vừa tạo. */
    public function insert(array $task)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tasks
                (project_id, title, description, assignee_id, created_by,
                 priority, status, due_at, position, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $task['project_id'],
            $task['title'],
            $task['description'],
            $task['assignee_id'],
            $task['created_by'],
            $task['priority'],
            $task['status'],
            $task['due_at'],
            $task['position'],
            $task['completed_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Cập nhật nội dung một công việc. */
    public function update($taskId, array $task)
    {
        $stmt = $this->db->prepare(
            'UPDATE tasks SET
                title = ?, description = ?, assignee_id = ?, priority = ?,
                status = ?, due_at = ?, completed_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $task['title'],
            $task['description'],
            $task['assignee_id'],
            $task['priority'],
            $task['status'],
            $task['due_at'],
            $task['completed_at'],
            $taskId,
        ]);
    }

    /** Đổi trạng thái (cột Kanban) của một công việc. */
    public function updateStatus($taskId, $status, $completedAt)
    {
        $stmt = $this->db->prepare('UPDATE tasks SET status = ?, completed_at = ? WHERE id = ?');
        $stmt->execute([$status, $completedAt, $taskId]);
    }

    /** Đặt thứ tự của một thẻ trong cột; chỉ sửa thẻ thuộc đúng dự án. */
    public function updatePosition($taskId, $projectId, $position)
    {
        $stmt = $this->db->prepare('UPDATE tasks SET position = ? WHERE id = ? AND project_id = ?');
        $stmt->execute([$position, $taskId, $projectId]);
    }

    public function delete($taskId)
    {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
    }

    /** Gỡ phân công các việc của một người trong dự án (khi người đó bị xoá khỏi dự án). */
    public function unassignMember($projectId, $userId)
    {
        $stmt = $this->db->prepare('UPDATE tasks SET assignee_id = NULL WHERE project_id = ? AND assignee_id = ?');
        $stmt->execute([$projectId, $userId]);
    }

    // =====================================================================
    // DÙNG CHO NHẮC VIỆC
    // =====================================================================

    /** Phần SELECT công việc kèm cấu hình nhắc việc của dự án. */
    private function reminderSelectSql()
    {
        return "
            SELECT  t.id, t.project_id, t.title, t.description, t.status,
                    t.priority, t.due_at,
                    p.name AS project_name,
                    p.discord_webhook_url, p.discord_enabled, p.mock_mode,
                    p.notify_due_soon, p.notify_overdue, p.notify_completed,
                    p.remind_before_minutes,
                    u.full_name AS assignee_name
            FROM        tasks    t
            INNER JOIN  projects p ON p.id = t.project_id
            LEFT  JOIN  users    u ON u.id = t.assignee_id
        ";
    }

    /**
     * Các việc chưa xong mà đã quá hạn hoặc sắp tới hạn (trong ngưỡng nhắc của dự án).
     * Việc trễ lâu nhất xếp trước, để khi hết hạn ngạch gửi Discord thì việc gấp nhất đã được báo.
     */
    public function findDueForReminder()
    {
        $sql = $this->reminderSelectSql() . "
            WHERE   t.status <> 'done'
                AND t.due_at IS NOT NULL
                AND t.due_at <= DATE_ADD(NOW(), INTERVAL p.remind_before_minutes MINUTE)
            ORDER BY t.due_at ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    /** Một công việc kèm cấu hình nhắc việc của dự án (dùng khi vừa hoàn thành). */
    public function findForReminder($taskId)
    {
        $stmt = $this->db->prepare($this->reminderSelectSql() . ' WHERE t.id = ? LIMIT 1');
        $stmt->execute([$taskId]);
        return $stmt->fetch();
    }
}
