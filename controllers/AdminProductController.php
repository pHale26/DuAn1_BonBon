<?php

class AdminProductController
{
    private ProductModel $productModel;
    private CategoryModel $categoryModel;
    private AttributeModel $attributeModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->attributeModel = new AttributeModel();
    }

    public function index(): void
    {
        $this->requireAdmin();

        $keyword = trim($_GET['keyword'] ?? '');
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $priceRange = trim($_GET['price_range'] ?? '');

        // Lấy tất cả sản phẩm trước
        $products = $this->productModel->getAdminProducts($keyword ?: null, $categoryId ?: null);
        
        // Lọc theo giá nếu có
        if ($priceRange && strpos($priceRange, '-') !== false) {
            list($minPrice, $maxPrice) = explode('-', $priceRange);
            $minPrice = (float)$minPrice;
            $maxPrice = (float)$maxPrice;
            
            $products = array_filter($products, function($product) use ($minPrice, $maxPrice) {
                $price = (float)($product['price'] ?? 0);
                return $price >= $minPrice && $price <= $maxPrice;
            });
        }
        
        // Lọc theo tồn kho nếu có
        $stockFilter = trim($_GET['stock_filter'] ?? '');
        if ($stockFilter) {
            $products = array_filter($products, function($product) use ($stockFilter) {
                $stock = (int)($product['stock'] ?? 0);
                switch ($stockFilter) {
                    case 'out_of_stock':
                        return $stock <= 0;
                    case 'low_stock':
                        return $stock > 0 && $stock <= 10;
                    case 'medium_stock':
                        return $stock > 10 && $stock <= 50;
                    case 'in_stock':
                        return $stock > 50;
                    default:
                        return true;
                }
            });
        }
        
        $categories = $this->categoryModel->getAllCategories();

        $title = 'Quản lý sản phẩm';
        $view  = 'admin/products/index';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function detail(): void
    {
        $this->requireAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        $product = $this->productModel->getProductById($id);
        if (!$product) {
            set_flash('danger', 'Không tìm thấy sản phẩm.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        // Thuộc tính sản phẩm (size, color)
        $attributes = $this->productModel->getProductAttributes($id);
        $variants   = $this->productModel->getVariantsDetailed($id);
        // Tính tổng tồn kho
        $totalStock = 0;
        if (!empty($variants)) {
            foreach ($variants as $v) {
                $totalStock += (int)($v['stock'] ?? 0);
            }
        } else {
            $totalStock = (int)($product['stock'] ?? 0);
        }

        $title = 'Chi tiết sản phẩm';
        $view  = 'admin/products/show';
        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function create(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->getAllCategories();
        $attributes = $this->attributeModel->getAttributesWithValues();

        $title = 'Thêm sản phẩm';
        $view  = 'admin/products/form';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function edit(): void
    {
        $this->requireAdmin();

        $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($productId <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        $product = $this->productModel->getProductById($productId);
        if (!$product) {
            set_flash('danger', 'Không tìm thấy sản phẩm.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        $categories = $this->categoryModel->getAllCategories();
        $attributes = $this->attributeModel->getAttributesWithValues();
        $variants   = $this->productModel->getVariantsDetailed($productId);

        $title = 'Chỉnh sửa sản phẩm';
        $view  = 'admin/products/form';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function store(): void
    {
        $this->requireAdmin();
        $payload = $this->buildProductPayload();

        if (!$payload) {
            header('Location: ' . BASE_URL . '?action=admin-product-create');
            exit;
        }

        // Đảm bảo KHÔNG có id trong payload
        unset($payload['id'], $payload['product_id'], $payload['productId']);
        
        try {
            $productId = $this->productModel->createProduct($payload);
            if ($productId > 0) {
                $this->notifyNewProduct($productId, $payload);
                set_flash('success', 'Đã tạo sản phẩm thành công. Bạn có thể thêm biến thể ngay bây giờ.');
                set_flash('warning', 'Sản phẩm chưa có thuộc tính/biến thể nên chưa được hiển thị để bán. Vui lòng thêm thuộc tính và biến thể.');
                error_log("AdminProductController::store - Successfully created product_id: " . $productId);
                header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            } else {
                set_flash('danger', 'Không thể tạo sản phẩm: Không lấy được ID sản phẩm.');
                error_log("AdminProductController::store - createProduct returned invalid ID: " . $productId);
                header('Location: ' . BASE_URL . '?action=admin-product-create');
            }
            exit;
        } catch (Throwable $exception) {
            $errorMsg = 'Không thể tạo sản phẩm: ' . $exception->getMessage();
            set_flash('danger', $errorMsg);
            error_log("AdminProductController::store - Exception: " . $exception->getMessage());
            error_log("AdminProductController::store - Stack trace: " . $exception->getTraceAsString());
            header('Location: ' . BASE_URL . '?action=admin-product-create');
            exit;
        }
    }

    public function update(): void
    {
        $this->requireAdmin();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

        if ($productId <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        $payload = $this->buildProductPayload();
        if (!$payload) {
            header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            exit;
        }

        try {
            $result = $this->productModel->updateProduct($productId, $payload);
            if ($result) {
                set_flash('success', 'Đã cập nhật sản phẩm thành công.');
                error_log("AdminProductController::update - Successfully updated product_id: " . $productId);
            } else {
                set_flash('danger', 'Cập nhật sản phẩm thất bại.');
                error_log("AdminProductController::update - updateProduct returned false for product_id: " . $productId);
            }
        } catch (Throwable $exception) {
            $errorMsg = 'Không thể cập nhật sản phẩm: ' . $exception->getMessage();
            set_flash('danger', $errorMsg);
            error_log("AdminProductController::update - Exception: " . $exception->getMessage());
            error_log("AdminProductController::update - Stack trace: " . $exception->getTraceAsString());
        }

        header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
        exit;
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        try {
            $this->productModel->deleteProduct($productId);
            set_flash('success', 'Đã xóa sản phẩm vào thùng rác. Bạn có thể khôi phục sau.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể xóa sản phẩm: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-products');
        exit;
    }

    public function trash(): void
    {
        $this->requireAdmin();

        $keyword = trim($_GET['keyword'] ?? '');
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        $products = $this->productModel->getAdminProducts($keyword ?: null, $categoryId ?: null, true);
        $categories = $this->categoryModel->getAllCategories();

        $title = 'Thùng rác sản phẩm';
        $view  = 'admin/products/trash';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function restore(): void
    {
        $this->requireAdmin();

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products-trash');
            exit;
        }

        try {
            $this->productModel->restoreProduct($productId);
            set_flash('success', 'Đã khôi phục sản phẩm.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể khôi phục sản phẩm: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-products-trash');
        exit;
    }

    public function forceDelete(): void
    {
        $this->requireAdmin();

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products-trash');
            exit;
        }

        try {
            $this->productModel->forceDeleteProduct($productId);
            set_flash('success', 'Đã xóa vĩnh viễn sản phẩm.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể xóa vĩnh viễn sản phẩm: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-products-trash');
        exit;
    }

    public function storeVariant(): void
    {
        $this->requireAdmin();

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            set_flash('danger', 'Sản phẩm không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        $variantData = [
            'sku' => trim($_POST['sku'] ?? ''),
            'additional_price' => $this->toFloat($_POST['additional_price'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
        ];
        // YÊU CẦU phải có ảnh biến thể
        if (!isset($_FILES['variant_image']) || $_FILES['variant_image']['error'] !== UPLOAD_ERR_OK) {
            set_flash('danger', 'Vui lòng tải lên ảnh biến thể.');
            header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            exit;
        }
        $imageUrl = $this->handleImageUpload($_FILES['variant_image']);
        if (!$imageUrl) {
            set_flash('danger', 'Không thể upload ảnh biến thể. Vui lòng thử lại.');
            header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            exit;
        }
        $variantData['image_url'] = $imageUrl;
        $valueIds = $this->collectAttributeValues($_POST['attribute_values'] ?? []);

        if (empty($valueIds)) {
            set_flash('danger', 'Vui lòng chọn đầy đủ thuộc tính (ví dụ: size, màu).');
            header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            exit;
        }

        // Đảm bảo KHÔNG có id trong variantData
        unset($variantData['id'], $variantData['variant_id'], $variantData['variantId']);
        if ($variantData['sku'] === '' || $variantData['stock'] <= 0) {
            set_flash('danger', 'SKU bắt buộc và tồn kho phải > 0.');
            header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            exit;
        }
        
        try {
            $this->productModel->createVariant($productId, $variantData, $valueIds);
            set_flash('success', 'Đã thêm biến thể.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể thêm biến thể: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
        exit;
    }

    public function updateVariant(): void
    {
        $this->requireAdmin();

        $variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

        if ($variantId <= 0 || $productId <= 0) {
            set_flash('danger', 'Biến thể không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        $variantData = [
            'sku' => trim($_POST['sku'] ?? ''),
            'additional_price' => $this->toFloat($_POST['additional_price'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
        ];
        if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = $this->handleImageUpload($_FILES['variant_image']);
            if (!$imageUrl) {
                set_flash('danger', 'Không thể upload ảnh biến thể. Vui lòng thử lại.');
                header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
                exit;
            }
            $variantData['image_url'] = $imageUrl;
        }
        $valueIds = $this->collectAttributeValues($_POST['attribute_values'] ?? []);

        if (empty($valueIds)) {
            set_flash('danger', 'Vui lòng chọn đầy đủ thuộc tính.');
            header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
            exit;
        }

        try {
            $this->productModel->updateVariant($variantId, $variantData, $valueIds);
            set_flash('success', 'Đã cập nhật biến thể.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể cập nhật biến thể: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
        exit;
    }

    public function deleteVariant(): void
    {
        $this->requireAdmin();

        $variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

        if ($variantId <= 0 || $productId <= 0) {
            set_flash('danger', 'Biến thể không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-products');
            exit;
        }

        try {
            $this->productModel->deleteVariant($variantId);
            set_flash('success', 'Đã xóa biến thể.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể xóa biến thể: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-product-edit&id=' . $productId);
        exit;
    }

    private function buildProductPayload(): ?array
    {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $originalPrice = $this->toFloat($_POST['original_price'] ?? 0);
        // Xử lý sale_price: nếu rỗng hoặc 0 thì set null
        $salePriceInput = trim($_POST['sale_price'] ?? '');
        $salePrice = null;
        if ($salePriceInput !== '' && $salePriceInput !== '0') {
            $salePriceFloat = $this->toFloat($salePriceInput);
            if ($salePriceFloat > 0) {
                $salePrice = $salePriceFloat;
            }
        }
        $stock = (int)($_POST['stock'] ?? 0);
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        // Xử lý upload ảnh
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Có upload ảnh mới
            $imageUrl = $this->handleImageUpload($_FILES['image']);
            if (!$imageUrl) {
                set_flash('danger', 'Không thể upload ảnh. Vui lòng thử lại.');
                return null;
            }
        } elseif ($productId > 0) {
            // Không có upload mới, nhưng đang cập nhật → lấy ảnh cũ từ database
            $existingProduct = $this->productModel->getProductById($productId);
            if ($existingProduct && !empty($existingProduct['image'])) {
                $imageUrl = $existingProduct['image'];
            }
        }

        // Validation: giá gốc là bắt buộc
        if ($name === '' || $originalPrice < 0) {
            set_flash('danger', 'Vui lòng nhập ít nhất tên và giá gốc hợp lệ.');
            return null;
        }

        // Validation: giá giảm giá phải nhỏ hơn giá gốc
        if ($salePrice !== null && $salePrice >= $originalPrice) {
            set_flash('danger', 'Giá giảm giá phải nhỏ hơn giá gốc.');
            return null;
        }

        // Giá hiển thị: nếu có sale_price thì dùng sale_price, nếu không thì dùng original_price
        $displayPrice = $salePrice !== null && $salePrice > 0 ? $salePrice : $originalPrice;

        $payload = [
            'name' => $name,
            'description' => $description,
            'price' => $displayPrice, // Giá hiển thị
            'original_price' => $originalPrice,
            'sale_price' => $salePrice,
            'stock' => max(0, $stock),
            'category_id' => $categoryId,
        ];
        
        // Thêm image_url nếu có (ảnh mới hoặc ảnh cũ)
        if ($imageUrl) {
            $payload['image_url'] = $imageUrl;
        }
        
        return $payload;
    }
    
    private function handleImageUpload(array $file): ?string
    {
        // Kiểm tra file có hợp lệ không
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            error_log("AdminProductController::handleImageUpload - Invalid file upload");
            return null;
        }
        
        // Kiểm tra kích thước file (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            set_flash('danger', 'Kích thước ảnh không được vượt quá 5MB.');
            return null;
        }
        
        // Kiểm tra loại file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            set_flash('danger', 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP).');
            return null;
        }
        
        // Tạo tên file unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'product_' . time() . '_' . uniqid() . '.' . strtolower($extension);
        
        // Đường dẫn lưu file (sử dụng PATH_ROOT để có đường dẫn tuyệt đối)
        $uploadDir = PATH_ROOT . 'assets/uploads/products/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("AdminProductController::handleImageUpload - Cannot create directory: " . $uploadDir);
                set_flash('danger', 'Không thể tạo thư mục lưu ảnh.');
                return null;
            }
        }
        
        // Kiểm tra quyền ghi
        if (!is_writable($uploadDir)) {
            error_log("AdminProductController::handleImageUpload - Directory not writable: " . $uploadDir);
            set_flash('danger', 'Thư mục lưu ảnh không có quyền ghi.');
            return null;
        }
        
        $uploadPath = $uploadDir . $fileName;
        
        // Di chuyển file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $imageUrl = BASE_URL . 'assets/uploads/products/' . $fileName;
            error_log("AdminProductController::handleImageUpload - Successfully uploaded: " . $imageUrl);
            return $imageUrl;
        } else {
            error_log("AdminProductController::handleImageUpload - Failed to move uploaded file to: " . $uploadPath);
            set_flash('danger', 'Không thể lưu file ảnh. Vui lòng kiểm tra quyền ghi file.');
            return null;
        }
    }

    private function collectAttributeValues(array $rawValues): array
    {
        $valueIds = [];
        foreach ($rawValues as $attributeId => $valueId) {
            $valueId = (int)$valueId;
            if ($valueId > 0) {
                $valueIds[] = $valueId;
            }
        }

        return $valueIds;
    }

    private function toFloat($value): float
    {
        $value = str_replace(['.', ','], ['', '.'], (string)$value);
        return (float)$value;
    }

    private function notifyNewProduct(int $productId, array $payload): void
    {
        try {
            $userModel = new UserModel();
            $customers = $userModel->getAll(null, 'customer');
            $userIds = array_map(function ($user) {
                return (int)($user['user_id'] ?? $user['id'] ?? 0);
            }, $customers);

            $notificationModel = new NotificationModel();
            $title = 'Sản phẩm mới: ' . ($payload['name'] ?? 'Sản phẩm mới');
            $content = 'Khám phá ngay sản phẩm vừa ra mắt.';

            $notificationModel->createBulk(
                $userIds,
                'product',
                $title,
                $content,
                BASE_URL . '?action=product-detail&id=' . $productId,
                [
                    'product_id' => $productId,
                    'name' => $payload['name'] ?? null,
                    'price' => $payload['price'] ?? null,
                    'image_url' => $payload['image_url'] ?? null,
                ]
            );
        } catch (Throwable $e) {
            error_log('AdminProductController::notifyNewProduct - ' . $e->getMessage());
        }
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
            set_flash('danger', 'Bạn cần quyền quản trị để truy cập trang này.');
            header('Location: ' . BASE_URL);
            exit;
        }
    }
    public function emptyTrashAction(): void
    {
        $this->requireAdmin();
        $this->productModel->emptyTrash();
        set_flash('success', 'Đã xóa sạch thùng rác sản phẩm.');
        header('Location: ' . BASE_URL . '?action=admin-products-trash');
        exit;
    }

    public function restoreAllAction(): void
    {
        $this->requireAdmin();
        $this->productModel->restoreAll();
        set_flash('success', 'Đã khôi phục tất cả sản phẩm.');
        header('Location: ' . BASE_URL . '?action=admin-products-trash');
        exit;
    }

    public function bulkTrashAction(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: ' . BASE_URL . '?action=admin-products-trash');
             exit;
        }

        $action = $_POST['bulk_action'] ?? '';
        $ids = $_POST['ids'] ?? []; // product_ids

        if (empty($ids)) {
            set_flash('warning', 'Vui lòng chọn ít nhất một sản phẩm!');
            header('Location: ' . BASE_URL . '?action=admin-products-trash');
            exit;
        }

        if ($action === 'restore') {
            $this->productModel->restoreMany($ids);
            set_flash('success', 'Đã khôi phục các sản phẩm đã chọn!');
        } elseif ($action === 'delete') {
            $this->productModel->forceDeleteMany($ids);
            set_flash('success', 'Đã xóa vĩnh viễn các sản phẩm đã chọn!');
        }

        header('Location: ' . BASE_URL . '?action=admin-products-trash');
        exit;
    }
}

