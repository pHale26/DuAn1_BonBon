<?php

require_once PATH_MODEL . 'ProductModel.php';
require_once PATH_MODEL . 'CategoryModel.php';

class CollectionController
{
    public function index()
    {
        $productModel = new ProductModel();
        $categoryModel = new CategoryModel();
        
        // Lấy tất cả danh mục để hiển thị các bộ sưu tập
        $categories = $categoryModel->getAllCategories();
        
        // Lấy sản phẩm theo từng danh mục để tạo bộ sưu tập
        $collections = [];
        foreach ($categories as $category) {
            if ($category['category_id'] == 0) continue; // Bỏ qua category "link"
            
            $products = $productModel->getProductsByCategory($category['category_id'], 6);
            if (!empty($products)) {
                // Chuyển đổi field names để phù hợp với view
                $formattedProducts = [];
                foreach ($products as $product) {
                    // Lấy hình ảnh từ nhiều nguồn
                    $imageUrl = '';
                    if (!empty($product['image'])) {
                        $imageUrl = $product['image'];
                    } elseif (!empty($product['image_url'])) {
                        $imageUrl = $product['image_url'];
                    } elseif (!empty($product['primary_image'])) {
                        $imageUrl = $product['primary_image'];
                    }
                    
                    $formattedProducts[] = [
                        'product_id' => $product['id'] ?? $product['product_id'] ?? 0,
                        'product_name' => $product['name'] ?? $product['product_name'] ?? '',
                        'price' => $product['price'] ?? 0,
                        'image_url' => $imageUrl,
                        'image' => $imageUrl,
                        'description' => $product['description'] ?? '',
                    ];
                }
                $collections[] = [
                    'category' => $category,
                    'products' => $formattedProducts
                ];
            }
        }
        
        $view = 'collection';
        $title = 'Bộ Sưu Tập - BonBonWear';
        require_once PATH_VIEW . 'main.php';
    }
}

