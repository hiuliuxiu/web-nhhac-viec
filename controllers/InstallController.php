<?php
/** Trang cài đặt CSDL. */
class InstallController extends Controller
{
    /** ?url=install — GET: hiện trang cài đặt; POST: tạo bảng và nạp dữ liệu mẫu. */
    public function index()
    {
        $result   = ['steps' => [], 'fatal' => null];
        $withSeed = true;

        if ($this->isPost()) {
            $this->verifyCsrf();
            $withSeed = $this->inputBool('with_seed');

            $installModel = new InstallModel();
            $result = $installModel->install($withSeed);
        }

        // Thành công khi đã chạy ít nhất một bước, không có lỗi và bước nào cũng ổn
        $allOk = $result['steps'] !== [] && $result['fatal'] === null;
        foreach ($result['steps'] as $step) {
            $allOk = $allOk && $step['ok'];
        }

        $this->view('install', [
            'pageTitle'    => 'Cài đặt',
            'steps'        => $result['steps'],
            'fatal'        => $result['fatal'],
            'withSeed'     => $withSeed,
            'allOk'        => $allOk,
            'databaseName' => DB_NAME,
            'csrfToken'    => $this->csrfToken(),
        ], 'auth-layout');
    }
}
