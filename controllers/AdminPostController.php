<?php

class AdminPostController
{
    private PostModel $postModel;

    public function __construct()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once PATH_MODEL . 'PostModel.php';
        $this->postModel = new PostModel();
    }

    public function index()
    {
        $keyword = $_GET['keyword'] ?? null;
        $posts = $this->postModel->getAll($keyword);

        $title = 'Quản lý bài viết';
        $view = 'admin/posts/index';
        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function create()
    {
        $title = 'Thêm bài viết mới';
        $view = 'admin/posts/create';
        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=admin-post-create');
            exit;
        }

        $data = [
            'title'       => $_POST['title'] ?? '',
            'excerpt'     => $_POST['excerpt'] ?? '',
            'content'     => $_POST['content'] ?? '',
            'status'      => $_POST['status'] ?? 'published',
            'user_id'     => $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'],
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        // Ensure upload directory exists
        $uploadDir = PATH_ASSETS_UPLOADS . 'posts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $imagePath = upload_file('posts', $_FILES['image']);
                $data['thumbnail'] = 'assets/uploads/' . $imagePath;
            } catch (Exception $e) {
                set_flash('danger', 'Lỗi upload ảnh: ' . $e->getMessage());
                header('Location: ' . BASE_URL . '?action=admin-post-create');
                exit;
            }
        }

        $this->postModel->create($data);
        set_flash('success', 'Thêm bài viết thành công!');
        header('Location: ' . BASE_URL . '?action=admin-posts');
    }

    public function edit()
    {
        $id = $_GET['id'] ?? 0;
        $post = $this->postModel->getById($id);

        if (!$post) {
            set_flash('danger', 'Bài viết không tồn tại!');
            header('Location: ' . BASE_URL . '?action=admin-posts');
            exit;
        }

        $title = 'Cập nhật bài viết';
        $view = 'admin/posts/edit';
        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function update()
    {
        $id = $_GET['id'] ?? 0;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=admin-post-edit&id=' . $id);
            exit;
        }

        $data = [
            'title'       => $_POST['title'] ?? '',
            'excerpt'     => $_POST['excerpt'] ?? '',
            'content'     => $_POST['content'] ?? '',
            'status'      => $_POST['status'] ?? 'published',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        // Ensure upload directory exists
        $uploadDir = PATH_ASSETS_UPLOADS . 'posts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $imagePath = upload_file('posts', $_FILES['image']);
                $data['thumbnail'] = 'assets/uploads/' . $imagePath;
            } catch (Exception $e) {
                set_flash('danger', 'Lỗi upload ảnh: ' . $e->getMessage());
                header('Location: ' . BASE_URL . '?action=admin-post-edit&id=' . $id);
                exit;
            }
        }
        
        $this->postModel->update($id, $data);
        set_flash('success', 'Cập nhật bài viết thành công!');
        header('Location: ' . BASE_URL . '?action=admin-posts');
    }

    public function delete()
    {
        $id = $_GET['id'] ?? 0;
        $this->postModel->softDelete($id);
        set_flash('success', 'Đã chuyển bài viết vào thùng rác!');
        header('Location: ' . BASE_URL . '?action=admin-posts');
    }

    public function trash()
    {
        $keyword = $_GET['keyword'] ?? null;
        $posts = $this->postModel->getAll($keyword, 'trash');

        $title = 'Thùng rác bài viết';
        $view = 'admin/posts/trash';
        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function restore()
    {
        $id = $_GET['id'] ?? 0;
        $this->postModel->restore($id);
        set_flash('success', 'Khôi phục bài viết thành công!');
        header('Location: ' . BASE_URL . '?action=admin-post-trash');
    }

    public function forceDelete()
    {
        $id = $_GET['id'] ?? 0;
        $this->postModel->delete($id);
        set_flash('success', 'Đã xóa vĩnh viễn bài viết!');
        header('Location: ' . BASE_URL . '?action=admin-post-trash');
    }

    public function emptyTrashAction()
    {
        $this->postModel->emptyTrash();
        set_flash('success', 'Đã xóa sạch thùng rác!');
        header('Location: ' . BASE_URL . '?action=admin-post-trash');
    }

    public function restoreAllAction()
    {
        $this->postModel->restoreAll();
        set_flash('success', 'Đã khôi phục tất cả bài viết!');
        header('Location: ' . BASE_URL . '?action=admin-post-trash');
    }

    // Xử lý selected items
    public function bulkTrashAction()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: ' . BASE_URL . '?action=admin-post-trash');
             exit;
        }

        $action = $_POST['bulk_action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            set_flash('warning', 'Vui lòng chọn ít nhất một bài viết!');
            header('Location: ' . BASE_URL . '?action=admin-post-trash');
            exit;
        }

        if ($action === 'restore') {
            $this->postModel->restoreMany($ids);
            set_flash('success', 'Đã khôi phục các bài viết đã chọn!');
        } elseif ($action === 'delete') {
            $this->postModel->deleteMany($ids);
            set_flash('success', 'Đã xóa vĩnh viễn các bài viết đã chọn!');
        }

        header('Location: ' . BASE_URL . '?action=admin-post-trash');
    }
}
