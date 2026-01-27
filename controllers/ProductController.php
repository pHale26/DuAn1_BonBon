<?php

class ProductController
{
	public function index()
	{
		$title = 'Tất cả sản phẩm';
		$view  = 'products/index';

		$page     = max(1, (int)($_GET['page'] ?? 1));
		$perPage  = max(1, min(36, (int)($_GET['per_page'] ?? 12)));
		$category = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
		$keyword  = trim((string)($_GET['q'] ?? ''));
		$priceKey = (string)($_GET['price'] ?? '');

		// Parse price range
		$priceMin = null; $priceMax = null;
		switch ($priceKey) {
			case 'under300':
				$priceMax = 300000;
				break;
			case '300-500':
				$priceMin = 300000; $priceMax = 500000;
				break;
			case '500-800':
				$priceMin = 500000; $priceMax = 800000;
				break;
			case 'above800':
				$priceMin = 800000;
				break;
			default:
				// allow custom price_min, price_max if provided
				if (isset($_GET['price_min'])) $priceMin = max(0, (float)$_GET['price_min']);
				if (isset($_GET['price_max'])) $priceMax = max(0, (float)$_GET['price_max']);
		}

		require_once PATH_MODEL . 'ProductModel.php';
		require_once PATH_MODEL . 'CategoryModel.php';

		$productModel  = new ProductModel();
		$categoryModel = new CategoryModel();

		// Nếu có từ khóa và chưa chọn danh mục, thử tìm danh mục khớp
		if ($keyword && !$category) {
			$matchedCategories = $categoryModel->searchCategories($keyword);
			if (!empty($matchedCategories)) {
				// Nếu tìm thấy danh mục khớp, tự động lọc theo danh mục đó
				$category = (int)$matchedCategories[0]['category_id'];
			}
		}

		$totalProducts = $productModel->countAllProducts($category, $keyword ?: null, $priceMin, $priceMax);
		$products      = $productModel->getProductsPage($page, $perPage, $category, $keyword ?: null, $priceMin, $priceMax);
		$categories    = $categoryModel->getAllCategories();

		// Lấy danh sách ID của 8 sản phẩm mới nhất để hiển thị tag "NEW"
		$newProductIds = $productModel->getNewProductIds(8);
		$newProductIds = array_map('intval', $newProductIds); // Chuyển sang int để so sánh

		$hasMore = ($page * $perPage) < $totalProducts;

		// Pass filters to view
		$_GET['q'] = $keyword;
		$_GET['price'] = $priceKey;

		require_once PATH_VIEW . 'main.php';
	}

	public function detail()
	{
		$id = (int)($_GET['id'] ?? 0);
		if ($id <= 0) {
			header('Location: ' . BASE_URL . '?action=products');
			exit;
		}

		require_once PATH_MODEL . 'ProductModel.php';
		$productModel = new ProductModel();
		$product = $productModel->getProductById($id);

		if (!$product) {
			header('Location: ' . BASE_URL . '?action=products');
			exit;
		}

		// Optional: get gallery images
		$images = $productModel->getProductImages($id);
		$attributes = $productModel->getProductAttributes($id);
		$variants   = $productModel->getVariantsDetailed($id);
		
		// Thu thập tất cả ảnh từ biến thể
		$variantImages = [];
		if (!empty($variants)) {
			foreach ($variants as $variant) {
				if (!empty($variant['image_url'])) {
					$variantImages[] = [
						'image_url' => $variant['image_url'],
						'is_primary' => 0,
						'variant_id' => $variant['variant_id'] ?? null
					];
				}
				// Lấy thêm ảnh từ variant_images nếu có
				if (!empty($variant['variant_id'])) {
					$vImages = $productModel->getVariantImages((int)$variant['variant_id']);
					foreach ($vImages as $vImg) {
						if (!empty($vImg['image_url'])) {
							$variantImages[] = [
								'image_url' => $vImg['image_url'],
								'is_primary' => $vImg['is_primary'] ?? 0,
								'variant_id' => $variant['variant_id']
							];
						}
					}
				}
			}
		}
		
		// Gộp ảnh sản phẩm và ảnh biến thể, loại bỏ trùng lặp
		$allImages = [];
		$seenUrls = [];
		
		// Thêm ảnh sản phẩm trước
		foreach ($images as $img) {
			if (!empty($img['image_url']) && !in_array($img['image_url'], $seenUrls)) {
				$allImages[] = $img;
				$seenUrls[] = $img['image_url'];
			}
		}
		
		// Thêm ảnh biến thể (không trùng)
		foreach ($variantImages as $vImg) {
			if (!empty($vImg['image_url']) && !in_array($vImg['image_url'], $seenUrls)) {
				$allImages[] = $vImg;
				$seenUrls[] = $vImg['image_url'];
			}
		}
		
		$images = $allImages; // Gán lại để view sử dụng
		
		$similarProducts = $productModel->getSimilarProducts((int)$product['category_id'], (int)$product['id'], 8);

		// Load đánh giá sản phẩm
		require_once PATH_MODEL . 'ReviewModel.php';
		$reviewModel = new ReviewModel();
		$reviews = $reviewModel->getByProduct($id);
		$reviewStats = $reviewModel->getProductStats($id);

		$title = $product['name'] ?? 'Chi tiết sản phẩm';
		$view  = 'products/detail';

		require_once PATH_VIEW . 'main.php';
	}

	// Trả về JSON danh sách thuộc tính (size, color) cho sản phẩm
	public function attributes()
	{
		header('Content-Type: application/json; charset=utf-8');
		$productId = (int)($_GET['product_id'] ?? 0);
		if ($productId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Thiếu product_id']);
			exit;
		}
		require_once PATH_MODEL . 'ProductModel.php';
		$model = new ProductModel();
		$data = $model->getProductAttributes($productId);
		echo json_encode(['success' => true, 'data' => $data]);
		exit;
	}

	// Trả về ảnh theo biến thể từ size/color
	public function variantImages()
	{
		header('Content-Type: application/json; charset=utf-8');
		$productId = (int)($_GET['product_id'] ?? 0);
		$size = isset($_GET['size']) ? trim((string)$_GET['size']) : null;
		$color = isset($_GET['color']) ? trim((string)$_GET['color']) : null;
		if ($productId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Thiếu product_id']);
			exit;
		}
		require_once PATH_MODEL . 'ProductModel.php';
		$model = new ProductModel();

		// 1) Cố gắng lấy ảnh theo biến thể cụ thể (size + color nếu có)
		$imagesUrls = [];
		$variant = $model->getVariantByValueNames($productId, $size, $color);
		if ($variant) {
			// Ưu tiên ảnh ngay trên bảng product_variants nếu có
			if (!empty($variant['image_url'])) {
				$imagesUrls = [getProductImageUrl($variant['image_url'], false)];
			} else {
				$images = $model->getVariantImages((int)$variant['variant_id']);
				if (!empty($images)) {
					$imagesUrls = array_map(fn($r) => getProductImageUrl($r['image_url'] ?? '', false), $images);
				}
			}
		}

		// 2) Nếu không có ảnh cho biến thể cụ thể, fallback theo Color (gom ảnh của mọi variant cùng màu)
		if (empty($imagesUrls) && $color) {
			$colorImages = $model->getVariantImagesByColor($productId, $color);
			$imagesUrls = array_map(fn($url) => getProductImageUrl($url, false), $colorImages);
		}

		// 3) Nếu vẫn trống, fallback cuối cùng: ảnh sản phẩm chung
		if (empty($imagesUrls)) {
			$common = $model->getProductImages($productId);
			$imagesUrls = array_map(fn($r) => getProductImageUrl($r['image_url'] ?? '', false), $common);
		}
		
		// Loại bỏ các URL rỗng
		$imagesUrls = array_filter($imagesUrls, fn($url) => !empty($url));

		echo json_encode(['success' => true, 'data' => array_values($imagesUrls)]);
		exit;
	}

	/**
	 * API tìm kiếm sản phẩm (cho autocomplete)
	 */
	public function searchApi()
	{
		header('Content-Type: application/json; charset=utf-8');
		$keyword = trim((string)($_GET['q'] ?? ''));
		
		if (empty($keyword) || strlen($keyword) < 2) {
			echo json_encode(['success' => false, 'message' => 'Từ khóa quá ngắn', 'products' => []]);
			exit;
		}
		
		require_once PATH_MODEL . 'ProductModel.php';
		$productModel = new ProductModel();
		
		// Tìm kiếm tối đa 20 sản phẩm cho autocomplete
		$products = $productModel->searchProducts($keyword, 20);
		
		echo json_encode([
			'success' => true,
			'products' => $products,
			'count' => count($products)
		]);
		exit;
	}

	/**
	 * Tìm kiếm thông minh: sản phẩm hoặc danh mục
	 * - Nếu tìm thấy đúng 1 sản phẩm → trả về product_id
	 * - Nếu tìm thấy danh mục → trả về category_id
	 * - Nếu nhiều kết quả → trả về type: 'multiple'
	 */
	public function searchSmart()
	{
		header('Content-Type: application/json; charset=utf-8');
		$keyword = trim((string)($_GET['q'] ?? ''));
		
		if (empty($keyword)) {
			echo json_encode(['success' => false, 'message' => 'Thiếu từ khóa']);
			exit;
		}
		
		require_once PATH_MODEL . 'ProductModel.php';
		require_once PATH_MODEL . 'CategoryModel.php';
		
		$productModel = new ProductModel();
		$categoryModel = new CategoryModel();
		
		// Tìm kiếm sản phẩm
		$products = $productModel->searchProducts($keyword, 10);
		
		// Tìm kiếm danh mục
		$matchedCategories = $categoryModel->searchCategories($keyword);
		$matchedCategory = !empty($matchedCategories) ? $matchedCategories[0] : null;
		
		// Logic: Ưu tiên danh mục nếu tìm thấy
		if ($matchedCategory) {
			echo json_encode([
				'success' => true,
				'type' => 'category',
				'category_id' => (int)$matchedCategory['category_id'],
				'category_name' => $matchedCategory['category_name']
			]);
			exit;
		}
		
		// Nếu tìm thấy đúng 1 sản phẩm → trả về product_id
		if (count($products) === 1) {
			echo json_encode([
				'success' => true,
				'type' => 'product',
				'product_id' => (int)$products[0]['id'],
				'product_name' => $products[0]['name']
			]);
			exit;
		}
		
		// Nếu nhiều sản phẩm hoặc không tìm thấy
		echo json_encode([
			'success' => true,
			'type' => 'multiple',
			'count' => count($products)
		]);
		exit;
	}

	/**
	 * API: Get variant stock by size and color
	 */
	public function getVariantStock()
	{
		header('Content-Type: application/json');
		
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        $size = isset($_GET['size']) ? trim($_GET['size']) : '';
        $color = isset($_GET['color']) ? trim($_GET['color']) : '';
        
        if (!$productId || ($size === '' && $color === '')) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit;
        }
		
		require_once PATH_MODEL . 'ProductModel.php';
		$productModel = new ProductModel();
		
        $stock = $productModel->getVariantStock($productId, $size ?: null, $color ?: null);
		
		echo json_encode([
			'success' => true,
			'stock' => $stock
		]);
		exit;
	}
}
