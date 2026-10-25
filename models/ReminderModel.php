<?php
/**
 * Làm việc với bảng reminders (nhật ký nhắc việc) và bảng app_state (mốc quét gần nhất).
 *
 * Bảng reminders có khoá UNIQUE (task_id, type, channel): mỗi công việc chỉ có tối đa
 * một dòng cho mỗi loại nhắc trên mỗi kênh. Nhờ vậy một việc không bị nhắc hai lần.
 */
class ReminderModel extends Model
{
    /** Đã có dòng nhắc loại $type trên kênh $channel cho công việc này chưa. */
    public function exists($taskId, $type, $channel)
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reminders WHERE task_id = ? AND type = ? AND channel = ?'
        );
        $stmt->execute([$taskId, $type, $channel]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Ghi một dòng nhật ký.
     * INSERT IGNORE: nếu dòng này đã có (trùng khoá UNIQUE) thì bỏ qua, không báo lỗi.
     * Đây là lưới an toàn phía sau bước kiểm tra exists().
     */
    public function add($taskId, $projectId, $type, $channel, $status, $message, $error = null)
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO reminders (task_id, project_id, type, channel, status, message, error)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$taskId, $projectId, $type, $channel, $status, $message, $error]);
        return $stmt->rowCount() > 0;
    }

    /** Xoá các dòng nhắc của một công việc thuộc các loại trong $types, để được nhắc lại. */
    public function deleteForTask($taskId, array $types)
    {
        $sql  = 'DELETE FROM reminders WHERE task_id = ? AND type IN (' . $this->placeholders($types) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$taskId], $types));
    }

    /** Xoá toàn bộ nhật ký của một dự án; trả về số dòng đã xoá. */
    public function deleteForProject($projectId)
    {
        $stmt = $this->db->prepare('DELETE FROM reminders WHERE project_id = ?');
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    /** Nhật ký của các dự án, mới nhất trước. $channel rỗng nghĩa là mọi kênh. */
    public function listForProjects(array $projectIds, $channel, $limit)
    {
        $sql = "
            SELECT  r.id, r.task_id, r.project_id, r.type, r.channel,
                    r.status, r.message, r.error, r.created_at,
                    t.title  AS task_title,
                    t.status AS task_status,
                    t.due_at,
                    p.name   AS project_name,
                    p.color  AS project_color
            FROM        reminders r
            INNER JOIN  tasks     t ON t.id = r.task_id
            INNER JOIN  projects  p ON p.id = r.project_id
            WHERE r.project_id IN (" . $this->placeholders($projectIds) . ")
        ";
        $params = $projectIds;

        if ($channel !== '') {
            $sql .= ' AND r.channel = ?';
            $params[] = $channel;
        }

        // LIMIT không truyền qua dấu ? được nên ép kiểu số nguyên rồi ghép thẳng vào câu SQL
        $sql .= ' ORDER BY r.created_at DESC, r.id DESC LIMIT ' . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Số lần gửi Discord thất bại, để hiện cảnh báo trên giao diện. */
    public function countFailed(array $projectIds)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM reminders
              WHERE project_id IN (" . $this->placeholders($projectIds) . ")
                AND channel = 'discord' AND status = 'failed'"
        );
        $stmt->execute($projectIds);
        return (int) $stmt->fetchColumn();
    }

    // =====================================================================
    // MỐC QUÉT GẦN NHẤT (bảng app_state)
    // =====================================================================

    /**
     * Giành lượt quét: chỉ sửa được khi lần quét trước đã cũ hơn $threshold.
     * Nhiều tab cùng gọi một lúc thì chỉ tab đầu tiên sửa được (rowCount = 1),
     * các tab sau thấy mốc đã mới nên sửa được 0 dòng và bỏ qua.
     */
    public function claimScan($now, $threshold)
    {
        $stmt = $this->db->prepare("UPDATE app_state SET v = ? WHERE k = 'last_cron_run' AND v <= ?");
        $stmt->execute([$now, $threshold]);
        return $stmt->rowCount() > 0;
    }

    /** Ghi mốc quét mà không kiểm tra (dùng cho nút "Quét ngay"). */
    public function setLastScan($now)
    {
        $stmt = $this->db->prepare("UPDATE app_state SET v = ? WHERE k = 'last_cron_run'");
        $stmt->execute([$now]);
    }

    /** Thời điểm quét gần nhất, hoặc null nếu chưa quét lần nào. */
    public function getLastScan()
    {
        $value = $this->db->query("SELECT v FROM app_state WHERE k = 'last_cron_run' LIMIT 1")->fetchColumn();

        if (!$value || $value === '1970-01-01 00:00:00') {
            return null;
        }
        return $value;
    }
}
