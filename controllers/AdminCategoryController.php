<?php

class AdminCategoryController
{
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    public function index(): void
    {
        $this->requireAdmin();

        $keyword = trim($_GET['keyword'] ?? '');
        $categories = $keyword
            ? $this->categoryModel->searchCategories($keyword)
            : $this->categoryModel->getAllCategories();

        $title = 'Quản lý danh mục';
        $view  = 'admin/categories/index';
        $searchKeyword = $keyword;

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function create(): void
    {
        $this->requireAdmin();

        $title = 'Thêm danh mục mới';
        $view  = 'admin/categories/form';
        $formData = [
            'category_name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
        ];
        $errors = [];

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function edit(): void
    {
        $this->requireAdmin();

        $categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($categoryId <= 0) {
            set_flash('danger', 'Danh mục không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-categories');
            exit;
        }

        $category = $this->categoryModel->getCategoryById($categoryId);
        if (!$category) {
            set_flash('danger', 'Không tìm thấy danh mục.');
            header('Location: ' . BASE_URL . '?action=admin-categories');
            exit;
        }

        $title = 'Chỉnh sửa danh mục';
        $view  = 'admin/categories/form';
        $formData = $category;
        $errors = [];

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function store(): void
    {
        $this->requireAdmin();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $errors = [];
        $formData = [
            'category_name' => $name,
            'description' => $description,
        ];

        if ($name === '') {
            $errors[] = 'Tên danh mục không được để trống.';
        }

        if ($this->categoryModel->existsName($name)) {
            $errors[] = 'Tên danh mục đã tồn tại (không phân biệt hoa/thường).';
        }

        if (!empty($errors)) {
            $title = 'Thêm danh mục mới';
            $view  = 'admin/categories/form';
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }

        try {
            $this->categoryModel->createCategory($name, $description);
            set_flash('success', 'Thêm danh mục thành công.');
            header('Location: ' . BASE_URL . '?action=admin-categories');
            exit;
        } catch (Throwable $exception) {
            $errors[] = 'Không thể tạo danh mục: ' . $exception->getMessage();
            $title = 'Thêm danh mục mới';
            $view  = 'admin/categories/form';
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }
    }

    public function update(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $errors = [];
        $formData = [
            'category_id' => $id,
            'category_name' => $name,
            'description' => $description,
        ];

        if ($id <= 0 || $name === '') {
            $errors[] = 'Dữ liệu danh mục không hợp lệ.';
        }

        if ($this->categoryModel->existsName($name, $id)) {
            $errors[] = 'Tên danh mục đã tồn tại (không phân biệt hoa/thường).';
        }

        if (!empty($errors)) {
            $category = $formData;
            $title = 'Chỉnh sửa danh mục';
            $view  = 'admin/categories/form';
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }

        try {
            $this->categoryModel->updateCategory($id, $name, $description);
            set_flash('success', 'Cập nhật danh mục thành công.');
            header('Location: ' . BASE_URL . '?action=admin-categories');
            exit;
        } catch (Throwable $exception) {
            $errors[] = 'Không thể cập nhật: ' . $exception->getMessage();
            $category = $formData;
            $title = 'Chỉnh sửa danh mục';
            $view  = 'admin/categories/form';
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        if ($id <= 0) {
            set_flash('danger', 'Danh mục không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-categories');
            exit;
        }

        try {
            $this->categoryModel->deleteCategory($id);
            set_flash('success', 'Đã xóa danh mục.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Danh mục đang có sản phẩm, không thể xóa.');
        }

        header('Location: ' . BASE_URL . '?action=admin-categories');
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

