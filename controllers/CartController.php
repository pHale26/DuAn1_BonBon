<?php

require_once PATH_MODEL . 'ProductModel.php';
require_once PATH_MODEL . 'CartModel.php';

class CartController
{
    private ProductModel $productModel;
    private CartModel $cartModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->cartModel = new CartModel();
    }
    public function index()
    {
        // Chặn admin không được xem giỏ hàng
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
            set_flash('warning', 'Tài khoản quản trị không thể mua hàng. Vui lòng sử dụng tài khoản khách hàng.');
            header('Location: ' . BASE_URL . '?action=admin-dashboard');
            exit;
        }

        // Nếu user đã đăng nhập, load giỏ hàng từ database
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
            $this->cartModel->loadCartToSession($userId);
        }
        
        // Lấy giỏ hàng từ session
        $cart = $_SESSION['cart'] ?? [];
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Logo cho view
        $logoUrl = BASE_URL . 'assets/images/logo.png';
        if (file_exists(PATH_ROOT . 'assets/images/logo.png')) {
            $data = file_get_contents(PATH_ROOT . 'assets/images/logo.png');
            $type = pathinfo(PATH_ROOT . 'assets/images/logo.png', PATHINFO_EXTENSION);
            $logoUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        
        $view = 'cart';
        $title = 'Giỏ Hàng - BonBonWear';
        require_once PATH_VIEW . 'main.php';
    }

    public function add()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Nhận dữ liệu JSON từ fetch
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            // Fallback cho form submit thường (nếu có)
            $productId = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            $size = $_POST['size'] ?? null;
            $color = $_POST['color'] ?? null;
        } else {
            $productId = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;
            $size = $data['size'] ?? null;
            $color = $data['color'] ?? null;
        }

        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID sản phẩm']);
            exit;
        }

        // Kiểm tra đăng nhập trước
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            echo json_encode([
                'success' => false, 
                'require_login' => true,
                'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng'
            ]);
            exit;
        }

        // Chặn admin không được thêm vào giỏ hàng
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
            echo json_encode([
                'success' => false,
                'message' => 'Tài khoản quản trị không thể mua hàng. Vui lòng sử dụng tài khoản khách hàng.'
            ]);
            exit;
        }

        try {
            // Lấy thông tin sản phẩm từ DB
            $product = $this->productModel->getProductById($productId);
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
                exit;
            }

            // Tìm variant_id từ size và color
            $variantId = null;
            $variant = null;
            if ($size || $color) {
                $variant = $this->productModel->getVariantByValueNames($productId, $size, $color);
                if ($variant) {
                    $variantId = (int)$variant['variant_id'];
                }
            }

            // Tính giá cuối cùng (giá sản phẩm + additional_price của variant nếu có)
            $finalPrice = (float)$product['price'];
            if ($variant && isset($variant['additional_price']) && $variant['additional_price'] !== null) {
                $finalPrice += (float)$variant['additional_price'];
            }

            // Kiểm tra số lượng tồn kho
            $availableStock = 0;
            if ($variant && isset($variant['stock'])) {
                // Nếu có variant, lấy stock từ variant
                $availableStock = (int)$variant['stock'];
            } else {
                // Nếu không có variant, lấy stock từ sản phẩm
                $availableStock = (int)($product['stock'] ?? 0);
            }

            // Kiểm tra số lượng trong giỏ hàng hiện tại (nếu đã có)
            $cartKey = $productId . '_' . ($size ?? 'null') . '_' . ($color ?? 'null');
            $currentCartQuantity = 0;
            if (isset($_SESSION['cart'][$cartKey])) {
                $currentCartQuantity = (int)$_SESSION['cart'][$cartKey]['quantity'];
            }

            // Tính tổng số lượng sau khi thêm
            $totalQuantity = $currentCartQuantity + $quantity;

            // Kiểm tra nếu vượt quá số lượng tồn kho
            if ($totalQuantity > $availableStock) {
                echo json_encode([
                    'success' => false,
                    'message' => "Số lượng tồn kho không đủ. Hiện tại còn {$availableStock} sản phẩm. Vui lòng chọn số lượng nhỏ hơn hoặc bằng {$availableStock}.",
                    'available_stock' => $availableStock,
                    'requested_quantity' => $totalQuantity
                ]);
                exit;
            }

            // Lấy ảnh sản phẩm
            $productImage = $product['image'] ?? '';
            if (!$productImage) {
                $images = $this->productModel->getProductImages($productId);
                if (!empty($images)) {
                    $productImage = $images[0]['image_url'] ?? '';
                }
            }

            // Lưu vào database TRƯỚC (user đã đăng nhập)
            $userId = (int)$_SESSION['user']['id'];
            $cartId = $this->cartModel->getOrCreateCartIdByUserId($userId);
            
            error_log("Attempting to add item to cart. User: $userId, Cart: $cartId, Product: $productId, Variant: " . ($variantId ?? 'NULL') . ", Size: " . ($size ?? 'NULL') . ", Color: " . ($color ?? 'NULL') . ", Quantity: $quantity");
            
            // Lưu vào database trước, nếu thành công mới lưu vào session
            try {
                $saved = $this->cartModel->addOrIncrementItem($cartId, $productId, $variantId, $quantity);
                if (!$saved) {
                    error_log("Failed to save cart item to database. User: $userId, Product: $productId, Variant: " . ($variantId ?? 'NULL') . ", Size: " . ($size ?? 'NULL') . ", Color: " . ($color ?? 'NULL'));
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Không thể lưu vào giỏ hàng. Vui lòng thử lại.',
                        'debug' => [
                            'user_id' => $userId,
                            'cart_id' => $cartId,
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'size' => $size,
                            'color' => $color,
                            'quantity' => $quantity
                        ]
                    ]);
                    exit;
                }
                
                // Trừ số lượng tồn kho sau khi lưu vào giỏ hàng thành công
                $stockDecreased = $this->productModel->decreaseStock($productId, $variantId, $size, $color, $quantity);
                if (!$stockDecreased) {
                    error_log("Failed to decrease stock. User: $userId, Product: $productId, Variant: " . ($variantId ?? 'NULL') . ", Quantity: $quantity");
                    // Không throw error vì đã lưu vào giỏ hàng, chỉ log
                }
            } catch (Exception $e) {
                error_log("Exception when saving cart item: " . $e->getMessage() . " | Stack: " . $e->getTraceAsString());
                echo json_encode([
                    'success' => false, 
                    'message' => 'Có lỗi xảy ra khi lưu vào giỏ hàng: ' . $e->getMessage(),
                    'debug' => [
                        'user_id' => $userId,
                        'cart_id' => $cartId,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'size' => $size,
                        'color' => $color,
                        'error' => $e->getMessage()
                    ]
                ]);
                exit;
            }

            // Sau khi lưu vào database thành công, mới lưu vào session

            // Lưu vào session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'id' => $productId,
                    'variant_id' => $variantId,
                    'name' => $product['name'],
                    'price' => $finalPrice,
                    'image' => $productImage,
                    'quantity' => $quantity,
                    'size' => $size,
                    'color' => $color
                ];
            }

            // Trả về cartKey để có thể dùng cho "Mua ngay"
            echo json_encode([
                'success' => true, 
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_key' => $cartKey
            ]);
            exit;
        } catch (Exception $e) {
            error_log("CartController::add error: " . $e->getMessage() . " | Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function update()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $cartKey = $data['cart_key'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!$cartKey || !isset($_SESSION['cart'][$cartKey])) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng']);
            exit;
        }

        $item = $_SESSION['cart'][$cartKey];
        $productId = (int)$item['id'];
        $variantId = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
        $size = $item['size'] ?? null;
        $color = $item['color'] ?? null;

        if ($quantity > 0) {
            // Kiểm tra số lượng tồn kho trước khi cập nhật
            $availableStock = 0;
            
            // Lấy thông tin sản phẩm và variant
            $product = $this->productModel->getProductById($productId);
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
                exit;
            }
            
            if ($variantId) {
                // Nếu có variant, lấy stock từ variant
                $variant = $this->productModel->getVariantByValueNames($productId, $size, $color);
                if ($variant && isset($variant['stock'])) {
                    $availableStock = (int)$variant['stock'];
                } else {
                    $availableStock = 0;
                }
            } else {
                // Nếu không có variant, lấy stock từ sản phẩm
                $availableStock = (int)($product['stock'] ?? 0);
            }
            
            // Lấy số lượng cũ để tính chênh lệch
            $oldQuantity = (int)$item['quantity'];
            
            // Tính tồn kho thực tế: stock hiện tại (đã trừ) + số lượng cũ trong giỏ hàng
            // Vì stock đã bị trừ khi thêm vào giỏ, nên cần cộng lại số lượng cũ để có tồn kho thực tế
            $actualAvailableStock = $availableStock + $oldQuantity;
            
            // Kiểm tra nếu vượt quá số lượng tồn kho (cho phép mua đến khi hết hàng)
            if ($quantity > $actualAvailableStock) {
                echo json_encode([
                    'success' => false,
                    'message' => "Số lượng tồn kho không đủ. Hiện tại còn {$actualAvailableStock} sản phẩm. Vui lòng chọn số lượng nhỏ hơn hoặc bằng {$actualAvailableStock}.",
                    'available_stock' => $actualAvailableStock,
                    'requested_quantity' => $quantity
                ]);
                exit;
            }
            $quantityDiff = $quantity - $oldQuantity;
            
            // Cập nhật số lượng trong session
            $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            
            // Cập nhật trong database nếu user đã đăng nhập (BẮT BUỘC)
            if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                $userId = (int)$_SESSION['user']['id'];
                $cartId = $this->cartModel->getOrCreateCartIdByUserId($userId);
                
                $updated = $this->cartModel->updateItemQuantity($cartId, $productId, $variantId, $quantity);
                if (!$updated) {
                    error_log("Failed to update cart item quantity in database for user $userId");
                    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật số lượng. Vui lòng thử lại.']);
                    exit;
                }
                
                // Cập nhật stock: nếu tăng số lượng thì trừ stock, nếu giảm thì cộng lại
                if ($quantityDiff > 0) {
                    // Tăng số lượng -> trừ stock
                    $this->productModel->decreaseStock($productId, $variantId, $size, $color, $quantityDiff);
                } elseif ($quantityDiff < 0) {
                    // Giảm số lượng -> cộng lại stock
                    $this->productModel->increaseStock($productId, $variantId, $size, $color, abs($quantityDiff));
                }
            }
            
            echo json_encode(['success' => true]);
        } else {
            // Lấy số lượng cũ để cộng lại stock
            $oldQuantity = (int)$item['quantity'];
            
            // Xóa khỏi session
            unset($_SESSION['cart'][$cartKey]);
            
            // Xóa khỏi database nếu user đã đăng nhập (BẮT BUỘC)
            if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                $userId = (int)$_SESSION['user']['id'];
                $cartId = $this->cartModel->getOrCreateCartIdByUserId($userId);
                
                // Truyền variant_id từ session để tìm chính xác hơn
                $cartItemId = $this->cartModel->findCartItemIdByKey($cartId, $cartKey, $variantId);
                if ($cartItemId) {
                    $deleted = $this->cartModel->deleteItem($cartItemId);
                    if (!$deleted) {
                        error_log("Failed to delete cart item from database for user $userId");
                        echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm. Vui lòng thử lại.']);
                        exit;
                    }
                }
                
                // Cộng lại stock khi xóa khỏi giỏ hàng
                if ($oldQuantity > 0) {
                    $this->productModel->increaseStock($productId, $variantId, $size, $color, $oldQuantity);
                }
            }
            
            echo json_encode(['success' => true]);
        }
        exit;
    }

    public function delete()
    {
        $cartKey = $_GET['id'] ?? null;
        if (!$cartKey || !isset($_SESSION['cart'][$cartKey])) {
            header('Location: ' . BASE_URL . '?action=cart-list');
            exit;
        }

        // Lấy thông tin item trước khi xóa để cộng lại stock
        $item = $_SESSION['cart'][$cartKey];
        $productId = (int)$item['id'];
        $variantId = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
        $size = $item['size'] ?? null;
        $color = $item['color'] ?? null;
        $quantity = (int)$item['quantity'];

        // Xóa khỏi session
        unset($_SESSION['cart'][$cartKey]);

        // Xóa khỏi database nếu user đã đăng nhập (BẮT BUỘC)
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
            $cartId = $this->cartModel->getOrCreateCartIdByUserId($userId);
            
            $cartItemId = $this->cartModel->findCartItemIdByKey($cartId, $cartKey, $variantId);
            if ($cartItemId) {
                $deleted = $this->cartModel->deleteItem($cartItemId);
                if (!$deleted) {
                    error_log("Failed to delete cart item from database for user $userId");
                }
            }
            
            // Cộng lại stock khi xóa khỏi giỏ hàng
            if ($quantity > 0) {
                $this->productModel->increaseStock($productId, $variantId, $size, $color, $quantity);
            }
        }

        header('Location: ' . BASE_URL . '?action=cart-list');
        exit;
    }

    /**
     * Xóa nhiều sản phẩm cùng lúc
     */
    public function deleteMultiple()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $cartKeys = $data['cart_keys'] ?? [];

        if (empty($cartKeys) || !is_array($cartKeys)) {
            echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào được chọn']);
            exit;
        }

        $deletedCount = 0;
        $errors = [];
        $userId = null;
        $cartId = null;

        // Lấy userId và cartId nếu user đã đăng nhập
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
            $cartId = $this->cartModel->getOrCreateCartIdByUserId($userId);
        }

        foreach ($cartKeys as $cartKey) {
            if (!isset($_SESSION['cart'][$cartKey])) {
                continue;
            }
            
            $item = $_SESSION['cart'][$cartKey];
            $productId = (int)$item['id'];
            $variantIdFromSession = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
            $size = $item['size'] ?? null;
            $color = $item['color'] ?? null;
            $quantity = (int)$item['quantity'];
            
            // Xóa khỏi session trước
            unset($_SESSION['cart'][$cartKey]);
            $deletedCount++;

            // Xóa khỏi database nếu user đã đăng nhập
            if ($userId && $cartId) {
                try {
                    // Truyền variant_id từ session để tìm chính xác hơn
                    $cartItemId = $this->cartModel->findCartItemIdByKey($cartId, $cartKey, $variantIdFromSession);
                    if ($cartItemId) {
                        $deleted = $this->cartModel->deleteItem($cartItemId);
                        if (!$deleted) {
                            $errors[] = "Không thể xóa sản phẩm $cartKey khỏi database";
                            error_log("Failed to delete cart item $cartItemId from database for user $userId");
                        }
                    } else {
                        // Nếu không tìm thấy trong DB, vẫn OK vì đã xóa khỏi session
                        error_log("Cart item not found in DB for cartKey: $cartKey, variant_id: " . ($variantIdFromSession ?? 'null') . ", user: $userId");
                    }
                    
                    // Cộng lại stock khi xóa khỏi giỏ hàng
                    if ($quantity > 0 && $productId > 0) {
                        $this->productModel->increaseStock($productId, $variantIdFromSession, $size, $color, $quantity);
                    }
                } catch (Exception $e) {
                    $errors[] = "Lỗi khi xóa sản phẩm $cartKey: " . $e->getMessage();
                    error_log("Error deleting cart item from database: " . $e->getMessage());
                    // Tiếp tục xóa các item khác
                }
            }
        }

        // Trả về success nếu đã xóa được ít nhất 1 item (dù có lỗi DB)
        if ($deletedCount > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Đã xóa {$deletedCount} sản phẩm",
                'deleted_count' => $deletedCount,
                'errors' => $errors
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Không thể xóa sản phẩm',
                'errors' => $errors
            ]);
        }
        exit;
    }
    
    public function count() {
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        echo json_encode(['count' => $count]);
        exit;
    }

    /**
     * Lưu danh sách sản phẩm đã chọn để mua vào session
     */
    public function setSelected()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $selectedItems = $data['selected_items'] ?? [];

        if (empty($selectedItems) || !is_array($selectedItems)) {
            echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào được chọn']);
            exit;
        }

        // Lưu vào session
        $_SESSION['selected_cart_items'] = $selectedItems;

        echo json_encode(['success' => true, 'message' => 'Đã lưu danh sách sản phẩm đã chọn']);
        exit;
    }
}
