<?php

require_once PATH_MODEL . 'BaseModel.php';

class CartModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy hoặc tạo cart_id cho user
     */
    public function getOrCreateCartIdByUserId(int $userId): int
    {
        // Kiểm tra xem user đã có cart chưa
        $sql = "SELECT cart_id FROM carts WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return (int)$row['cart_id'];
        }

        // Tạo cart mới
        $sql = "INSERT INTO carts (user_id, created_at) VALUES (:user_id, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Thêm hoặc tăng số lượng sản phẩm trong giỏ hàng
     */
    public function addOrIncrementItem(int $cartId, int $productId, ?int $variantId, int $quantity): bool
    {
        try {
            // Kiểm tra xem item đã tồn tại chưa
            // Phải kiểm tra chính xác: cùng cart_id, cùng product_id, và cùng variant_id (hoặc cả 2 đều NULL)
            if ($variantId !== null) {
                $sql = "SELECT cart_item_id, quantity 
                        FROM cart_items 
                        WHERE cart_id = :cart_id 
                          AND product_id = :product_id 
                          AND variant_id = :variant_id
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            } else {
                $sql = "SELECT cart_item_id, quantity 
                        FROM cart_items 
                        WHERE cart_id = :cart_id 
                          AND product_id = :product_id 
                          AND variant_id IS NULL
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Checking existing item. Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL') . " | Found: " . ($existing ? 'YES (ID: ' . $existing['cart_item_id'] . ', Qty: ' . $existing['quantity'] . ')' : 'NO'));

            if ($existing) {
                // Cập nhật số lượng
                $newQuantity = (int)$existing['quantity'] + $quantity;
                $sql = "UPDATE cart_items 
                        SET quantity = :quantity 
                        WHERE cart_item_id = :cart_item_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':quantity', $newQuantity, PDO::PARAM_INT);
                $stmt->bindValue(':cart_item_id', (int)$existing['cart_item_id'], PDO::PARAM_INT);
                $result = $stmt->execute();
                
                if (!$result) {
                    error_log("Failed to update cart item quantity. Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL'));
                }
                
                return $result;
            } else {
                // Thêm mới
                $sql = "INSERT INTO cart_items (cart_id, product_id, variant_id, quantity) 
                        VALUES (:cart_id, :product_id, :variant_id, :quantity)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stmt->bindValue(':variant_id', $variantId, $variantId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                
                try {
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        error_log("Failed to insert cart item. Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL') . " | SQL Error: " . json_encode($errorInfo));
                    } else {
                        error_log("Successfully inserted cart item. Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL') . ", Quantity: $quantity");
                    }
                    
                    return $result;
                } catch (PDOException $e) {
                    error_log("PDO Exception when inserting cart item. Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL') . " | Error: " . $e->getMessage());
                    throw $e;
                }
            }
        } catch (PDOException $e) {
            error_log("CartModel::addOrIncrementItem error: " . $e->getMessage() . " | Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL'));
            return false;
        }
    }

    /**
     * Lấy tất cả items trong giỏ hàng của user
     */
    public function getItemsByUserId(int $userId): array
    {
        $cartId = $this->getOrCreateCartIdByUserId($userId);
        
        // Đảm bảo cột image_url tồn tại trong product_variants
        try {
            $this->pdo->exec("ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) NULL");
        } catch (PDOException $e) {
            // Cột đã tồn tại hoặc không thể thêm, bỏ qua
        }
        
        $sql = "SELECT 
                    ci.cart_item_id,
                    ci.product_id,
                    ci.variant_id,
                    ci.quantity,
                    p.product_name,
                    p.price,
                    COALESCE(pv.image_url, pi.image_url, 
                        (SELECT image_url FROM product_images WHERE product_id = p.product_id ORDER BY is_primary DESC LIMIT 1)
                    ) as product_image,
                    pv.sku,
                    pv.additional_price,
                    pv.stock,
                    MAX(CASE WHEN a.attribute_name = 'Size' THEN av.value_name END) as size,
                    MAX(CASE WHEN a.attribute_name = 'Color' THEN av.value_name END) as color
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.product_id
                LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_attribute_values pav ON pv.variant_id = pav.variant_id
                LEFT JOIN attribute_values av ON pav.value_id = av.value_id
                LEFT JOIN attributes a ON av.attribute_id = a.attribute_id
                WHERE ci.cart_id = :cart_id
                GROUP BY ci.cart_item_id, ci.product_id, ci.variant_id, ci.quantity, 
                         p.product_name, p.price, pv.image_url, pi.image_url, pv.sku, pv.additional_price, pv.stock
                ORDER BY ci.cart_item_id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Load giỏ hàng từ DB vào session
     * @param bool $merge Nếu true, merge với session cart hiện tại thay vì ghi đè
     */
    public function loadCartToSession(int $userId, bool $merge = false): void
    {
        $items = $this->getItemsByUserId($userId);
        
        if (!$merge) {
            $_SESSION['cart'] = [];
        }
        
        foreach ($items as $item) {
            $finalPrice = (float)$item['price'];
            if ($item['additional_price'] !== null) {
                $finalPrice += (float)$item['additional_price'];
            }
            
            // Tạo key dựa trên product_id, size và color (giống với CartController::add)
            $size = $item['size'] ?? null;
            $color = $item['color'] ?? null;
            $key = $item['product_id'] . '_' . ($size ?? 'null') . '_' . ($color ?? 'null');
            
            // Lấy ảnh - ưu tiên variant image, sau đó product image
            $productImage = $item['product_image'] ?? '';
            
            // Nếu merge và item đã tồn tại, cộng số lượng
            if ($merge && isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += (int)$item['quantity'];
            } else {
                $_SESSION['cart'][$key] = [
                    'id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'name' => $item['product_name'],
                    'price' => $finalPrice,
                    'image' => $productImage,
                    'quantity' => (int)$item['quantity'],
                    'size' => $size,
                    'color' => $color,
                    'sku' => $item['sku'] ?? null
                ];
            }
        }
    }

    /**
     * Xóa item khỏi giỏ hàng trong database
     */
    public function deleteItem(int $cartItemId): bool
    {
        try {
            $sql = "DELETE FROM cart_items WHERE cart_item_id = :cart_item_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':cart_item_id', $cartItemId, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Failed to delete cart item. Cart Item ID: $cartItemId");
            } else {
                $rowCount = $stmt->rowCount();
                if ($rowCount === 0) {
                    error_log("No rows deleted. Cart Item ID: $cartItemId may not exist.");
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("CartModel::deleteItem error: " . $e->getMessage() . " | Cart Item ID: $cartItemId");
            return false;
        }
    }

    /**
     * Cập nhật số lượng item trong giỏ hàng
     */
    public function updateItemQuantity(int $cartId, int $productId, ?int $variantId, int $quantity): bool
    {
        try {
            // Tìm item trong database
            $sql = "SELECT cart_item_id 
                    FROM cart_items 
                    WHERE cart_id = :cart_id 
                      AND product_id = :product_id 
                      AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindValue(':variant_id', $variantId, $variantId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                // Cập nhật số lượng
                $sql = "UPDATE cart_items 
                        SET quantity = :quantity 
                        WHERE cart_item_id = :cart_item_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':cart_item_id', (int)$item['cart_item_id'], PDO::PARAM_INT);
                return $stmt->execute();
            }
            return false;
        } catch (PDOException $e) {
            error_log("CartModel::updateItemQuantity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tìm cart_item_id từ cart_key (product_id + size + color)
     */
    /**
     * Tìm cart_item_id từ cartKey và session item
     * Sử dụng variant_id từ session item nếu có, nếu không thì tìm từ size/color
     */
    public function findCartItemIdByKey(int $cartId, string $cartKey, ?int $variantIdFromSession = null): ?int
    {
        try {
            // Parse cartKey: productId_size_color
            $parts = explode('_', $cartKey);
            if (count($parts) < 1) {
                error_log("Invalid cartKey format: $cartKey");
                return null;
            }
            
            $productId = (int)$parts[0];
            $size = ($parts[1] ?? 'null') !== 'null' ? $parts[1] : null;
            $color = ($parts[2] ?? 'null') !== 'null' ? $parts[2] : null;
            
            // Ưu tiên dùng variant_id từ session (chính xác hơn)
            $variantId = $variantIdFromSession;
            
            // Nếu không có variant_id từ session, tìm từ size và color
            if ($variantId === null && ($size || $color)) {
                require_once PATH_MODEL . 'ProductModel.php';
                $productModel = new ProductModel();
                $variant = $productModel->getVariantByValueNames($productId, $size, $color);
                if ($variant) {
                    $variantId = (int)$variant['variant_id'];
                }
            }
            
            // Tìm cart_item_id - sử dụng logic tương tự addOrIncrementItem
            if ($variantId !== null) {
                $sql = "SELECT cart_item_id 
                        FROM cart_items 
                        WHERE cart_id = :cart_id 
                          AND product_id = :product_id 
                          AND variant_id = :variant_id
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            } else {
                $sql = "SELECT cart_item_id 
                        FROM cart_items 
                        WHERE cart_id = :cart_id 
                          AND product_id = :product_id 
                          AND variant_id IS NULL
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                error_log("Cart item not found. Cart ID: $cartId, Product ID: $productId, Variant ID: " . ($variantId ?? 'NULL') . ", CartKey: $cartKey");
            }
            
            return $item ? (int)$item['cart_item_id'] : null;
        } catch (PDOException $e) {
            error_log("CartModel::findCartItemIdByKey error: " . $e->getMessage() . " | CartKey: $cartKey");
            return null;
        }
    }

    /**
     * Đồng bộ giỏ hàng từ session vào database
     * Dùng khi user đăng nhập và có giỏ hàng trong session
     */
    public function syncSessionCartToDatabase(int $userId, array $sessionCart): void
    {
        try {
            $cartId = $this->getOrCreateCartIdByUserId($userId);
            
            foreach ($sessionCart as $cartKey => $item) {
                $productId = (int)($item['id'] ?? 0);
                $variantId = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
                $quantity = (int)($item['quantity'] ?? 1);
                
                if ($productId > 0) {
                    // Thêm hoặc cập nhật item trong database
                    $this->addOrIncrementItem($cartId, $productId, $variantId, $quantity);
                }
            }
        } catch (Exception $e) {
            error_log("CartModel::syncSessionCartToDatabase error: " . $e->getMessage());
        }
    }
}

