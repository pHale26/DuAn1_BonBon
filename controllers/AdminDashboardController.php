<?php

class AdminDashboardController
{
    private OrderModel $orderModel;
    private ProductModel $productModel;
    private UserModel $userModel;

    public function __construct()
    {
        require_once PATH_MODEL . 'OrderModel.php';
        require_once PATH_MODEL . 'ProductModel.php';
        require_once PATH_MODEL . 'UserModel.php';
        
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $this->requireAdmin();

        // Lấy thống kê
        $stats = [
            'total_users' => $this->userModel->getTotalCount(),
            'total_orders' => $this->orderModel->getTotalCount(),
            'total_admins' => $this->userModel->getAdminCount(),
            'total_customers' => $this->userModel->getCustomerCount(),
        ];

        $title = 'Admin Panel';
        $view = 'admin/dashboard/index';

        require_once PATH_VIEW . 'admin/layout.php';
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

