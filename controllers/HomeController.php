<?php

require_once PATH_MODEL . 'PostModel.php';
require_once PATH_MODEL . 'ProductModel.php';

class HomeController
{
    public function index() 
    {
        $title = 'BonBonwear';
        $view  = 'home';
        $logoUrl = BASE_URL . 'assets/images/logo.png';
        $isLoggedIn = isset($_SESSION['user']) ? 'true' : 'false';

        // Lấy 8 sản phẩm mới nhất từ database
        $productModel = new ProductModel();
        $products = $productModel->getAllProducts(8);
        
        // Lấy danh sách ID của 8 sản phẩm mới nhất để hiển thị tag "NEW"
        $newProductIds = $productModel->getNewProductIds(8);
        $newProductIds = array_map('intval', $newProductIds); // Chuyển sang int để so sánh

        // Lấy 3 tin tức nổi bật từ database
        $postModel = new PostModel();
        $news = $postModel->getFeaturedPosts(3);

        require_once PATH_VIEW . 'main.php';
    }
}