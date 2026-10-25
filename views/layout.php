<?php
/** Giao diện HTML; dữ liệu được Controller truyền vào. */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($appName) ?> · Quản lý &amp; theo dõi tiến độ công việc</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>

<?= $content ?>

<!-- =========================================================================
     THƯ VIỆN NGOÀI (CDN)
     ========================================================================= -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<!-- Cấu hình truyền từ PHP sang JavaScript -->
<script>window.APP_CONFIG = <?= to_js($boot) ?>;</script>

<!-- =========================================================================
     MÃ NGUỒN ỨNG DỤNG — nạp theo đúng thứ tự phụ thuộc giữa các lớp
     ========================================================================= -->
<script src="<?= e(asset('assets/js/Catalog.js')) ?>"></script>
<script src="<?= e(asset('assets/js/Formatter.js')) ?>"></script>
<script src="<?= e(asset('assets/js/Ui.js')) ?>"></script>

<script src="<?= e(asset('assets/js/HttpClient.js')) ?>"></script>
<script src="<?= e(asset('assets/js/ApiClient.js')) ?>"></script>

<script src="<?= e(asset('assets/js/AppStore.js')) ?>"></script>

<script src="<?= e(asset('assets/js/View.js')) ?>"></script>
<script src="<?= e(asset('assets/js/BoardView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/ListView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/CalendarView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/DashboardView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/MembersView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/SettingsView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/LogView.js')) ?>"></script>
<script src="<?= e(asset('assets/js/GuideView.js')) ?>"></script>

<script src="<?= e(asset('assets/js/App.js')) ?>"></script>
<script src="<?= e(asset('assets/js/bootstrap.js')) ?>"></script>

</body>
</html>
