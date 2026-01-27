<?php

require_once PATH_MODEL . 'PostModel.php';

class PostController
{
    private PostModel $postModel;

    public function __construct()
    {
        $this->postModel = new PostModel();
    }

    /**
     * Hiển thị trang danh sách tin tức
     */
    public function index()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        
        // Lấy danh sách bài viết published
        $posts = $this->postModel->getAll(null, 'published', $perPage, $offset);
        $totalPosts = $this->postModel->countAll(null, 'published');
        $totalPages = ceil($totalPosts / $perPage);
        
        // Đôi khi views cần biến $currentPage
        $currentPage = $page;

        // Logo cho view
        $logoUrl = BASE_URL . 'assets/images/logo.png';
        if (file_exists(PATH_ROOT . 'assets/images/logo.png')) {
            $data = file_get_contents(PATH_ROOT . 'assets/images/logo.png');
            $type = pathinfo(PATH_ROOT . 'assets/images/logo.png', PATHINFO_EXTENSION);
            $logoUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $view = 'posts/index';
        $title = 'Tin Tức - BonBonWear';
        require_once PATH_VIEW . 'main.php';
    }

    /**
     * Hiển thị chi tiết một tin tức
     */
    public function detail()
    {
        $postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$postId) {
            header('Location: ' . BASE_URL . '?action=posts');
            exit;
        }

        $post = $this->postModel->getById($postId);
        
        if (!$post || ($post['status'] ?? '') !== 'published') {
            header('Location: ' . BASE_URL . '?action=posts');
            exit;
        }
        
        // Tăng view
        $this->postModel->increaseViewCount($postId);

        // Logo cho view
        $logoUrl = BASE_URL . 'assets/images/logo.png';
        if (file_exists(PATH_ROOT . 'assets/images/logo.png')) {
            $data = file_get_contents(PATH_ROOT . 'assets/images/logo.png');
            $type = pathinfo(PATH_ROOT . 'assets/images/logo.png', PATHINFO_EXTENSION);
            $logoUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $view = 'posts/detail';
        $title = $post['title'] . ' - BonBonWear';
        require_once PATH_VIEW . 'main.php';
    }
}

