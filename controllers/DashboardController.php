<?php
/** Số liệu cho trang Tổng quan. */
class DashboardController extends Controller
{
    /** GET ?url=dashboard — thống kê mọi dự án của người dùng (hoặc một dự án nếu có project_id). */
    public function index()
    {
        $user      = $this->requireLogin();
        $projectId = $this->inputInt('project_id');

        if ($projectId > 0) {
            $this->requireProjectAccess($projectId, $user);
        }

        $projectModel = new ProjectModel();
        $projects     = [];
        $projectIds   = [];
        foreach ($projectModel->listWithStats($user['id'], $projectId > 0 ? $projectId : null) as $row) {
            $project      = Helper::formatProject($row);
            $projects[]   = $project;
            $projectIds[] = $project['id'];
        }

        $urgentTasks  = [];
        $workload     = [];
        $recentlyDone = [];

        if ($projectIds !== []) {
            $dashboardModel = new DashboardModel();

            // Việc cần chú ý: quá hạn trước, rồi tới sắp hết hạn
            foreach ($dashboardModel->urgentTasks($projectIds) as $row) {
                $urgentTasks[] = Helper::formatTask($row);
            }

            // Khối lượng công việc theo thành viên
            foreach ($dashboardModel->workload($projectIds) as $row) {
                $row['id']       = (int) $row['id'];
                $row['total']    = (int) $row['total'];
                $row['done']     = (int) $row['done'];
                $row['overdue']  = (int) $row['overdue'];
                $row['progress'] = $row['total'] > 0 ? (int) round($row['done'] / $row['total'] * 100) : 0;
                $workload[] = $row;
            }

            // Hoàn thành gần đây
            foreach ($dashboardModel->recentlyDone($projectIds) as $row) {
                $row['id']           = (int) $row['id'];
                $row['project_id']   = (int) $row['project_id'];
                $row['completed_at'] = Helper::toIso($row['completed_at']);
                $recentlyDone[] = $row;
            }
        }

        $reminderModel = new ReminderModel();

        $this->json([
            'totals'        => $this->sumTotals($projects),
            'projects'      => $projects,
            'urgent_tasks'  => $urgentTasks,
            'workload'      => $workload,
            'recently_done' => $recentlyDone,
            'last_cron_run' => Helper::toIso($reminderModel->getLastScan()),
            'server_time'   => date('c'),
        ]);
    }

    /** Cộng dồn số liệu của các dự án cho các thẻ thống kê ở đầu trang. */
    private function sumTotals(array $projects)
    {
        $totals = [
            'projects'    => count($projects),
            'total'       => 0,
            'todo'        => 0,
            'in_progress' => 0,
            'review'      => 0,
            'done'        => 0,
            'overdue'     => 0,
            'due_soon'    => 0,
        ];

        foreach ($projects as $project) {
            $totals['total']       += $project['total_tasks'];
            $totals['todo']        += $project['todo_tasks'];
            $totals['in_progress'] += $project['in_progress_tasks'];
            $totals['review']      += $project['review_tasks'];
            $totals['done']        += $project['done_tasks'];
            $totals['overdue']     += $project['overdue_tasks'];
            $totals['due_soon']    += $project['due_soon_tasks'];
        }

        $totals['progress'] = $totals['total'] > 0 ? (int) round($totals['done'] / $totals['total'] * 100) : 0;

        return $totals;
    }
}
