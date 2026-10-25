<?php
/** Các câu truy vấn cho trang Tổng quan. */
class DashboardModel extends Model
{
    /** Việc cần chú ý: chưa xong và đã quá hạn hoặc sắp tới hạn; việc trễ lâu nhất xếp trước. */
    public function urgentTasks(array $projectIds)
    {
        $stmt = $this->db->prepare("
            SELECT  t.id, t.title, t.status, t.priority, t.due_at, t.project_id,
                    p.name  AS project_name,
                    p.color AS project_color,
                    p.remind_before_minutes,
                    u.full_name    AS assignee_name,
                    u.avatar_color AS assignee_color
            FROM        tasks    t
            INNER JOIN  projects p ON p.id = t.project_id
            LEFT  JOIN  users    u ON u.id = t.assignee_id
            WHERE   t.project_id IN (" . $this->placeholders($projectIds) . ")
                AND t.status <> 'done'
                AND t.due_at IS NOT NULL
                AND t.due_at <= DATE_ADD(NOW(), INTERVAL p.remind_before_minutes MINUTE)
            ORDER BY t.due_at ASC
            LIMIT 12
        ");
        $stmt->execute($projectIds);
        return $stmt->fetchAll();
    }

    /** Khối lượng công việc của từng thành viên: tổng số việc, đã xong, quá hạn. */
    public function workload(array $projectIds)
    {
        $stmt = $this->db->prepare("
            SELECT  u.id, u.full_name, u.avatar_color,
                    COUNT(t.id) AS total,
                    SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) AS done,
                    SUM(CASE WHEN t.status <> 'done' AND t.due_at < NOW() THEN 1 ELSE 0 END) AS overdue
            FROM        tasks t
            INNER JOIN  users u ON u.id = t.assignee_id
            WHERE t.project_id IN (" . $this->placeholders($projectIds) . ")
            GROUP BY u.id
            ORDER BY total DESC, u.full_name ASC
            LIMIT 10
        ");
        $stmt->execute($projectIds);
        return $stmt->fetchAll();
    }

    /** Các việc vừa hoàn thành gần đây (dòng thời gian hoạt động của nhóm). */
    public function recentlyDone(array $projectIds)
    {
        $stmt = $this->db->prepare("
            SELECT  t.id, t.title, t.completed_at, t.project_id,
                    p.name  AS project_name,
                    p.color AS project_color,
                    u.full_name    AS assignee_name,
                    u.avatar_color AS assignee_color
            FROM        tasks    t
            INNER JOIN  projects p ON p.id = t.project_id
            LEFT  JOIN  users    u ON u.id = t.assignee_id
            WHERE   t.project_id IN (" . $this->placeholders($projectIds) . ")
                AND t.status = 'done'
                AND t.completed_at IS NOT NULL
            ORDER BY t.completed_at DESC
            LIMIT 8
        ");
        $stmt->execute($projectIds);
        return $stmt->fetchAll();
    }
}
