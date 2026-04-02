<?php

class AdminUserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $this->requireAdmin();

        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;
        $lockStatus = $_GET['lock_status'] ?? null;

         $users = $this->userModel->getAll(
            null,
            null,
             null,
            $fromDate ?: null,
            $toDate ?: null,
            $lockStatus ?: null
        );

        $title = 'Quản lý người dùng';
        $view  = 'admin/users/index';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function updateRole(): void
    {
        $this->requireAdmin();

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role   = $_POST['role'] ?? '';

        if (!$userId || !in_array($role, ['customer', 'admin'], true)) {
            set_flash('danger', 'Dữ liệu không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-users');
            exit;
        }

        try {
            $this->userModel->updateRole($userId, $role);
            set_flash('success', 'Cập nhật quyền thành công.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể cập nhật: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-users');
        exit;
    }

    public function toggleLock(): void
    {
        $this->requireAdmin();

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $currentUserId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            set_flash('danger', 'Dữ liệu không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-users');
            exit;
        }

        if ($currentUserId && (int)$userId === (int)$currentUserId) {
            set_flash('danger', 'Không thể khóa tài khoản đang đăng nhập.');
            header('Location: ' . BASE_URL . '?action=admin-users');
            exit;
        }

        try {
            $isLocked = $this->userModel->toggleLock($userId);
            if ($isLocked) {
                set_flash('success', 'Đã khóa tài khoản thành công.');
            } else {
                set_flash('success', 'Đã mở khóa tài khoản thành công.');
            }
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể thay đổi trạng thái: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-users');
        exit;
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
            set_flash('danger', 'Bạn cần quyền quản trị để truy cập trang này.');
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}

