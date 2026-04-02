<?php

class ProductModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'products';
    }

    /**
     * Kiểm tra xem cột deleted_at có tồn tại không
     */
    private function hasDeletedAtColumn(): bool
    {
        static $hasColumn = null;
        if ($hasColumn !== null) {
            return $hasColumn;
        }
        
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'deleted_at'");
            $hasColumn = $stmt->rowCount() > 0;
            return $hasColumn;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Kiểm tra xem cột có tồn tại trong bảng không
     */
    private function hasColumn(string $columnName): bool
    {
        static $columnCache = [];
        if (isset($columnCache[$columnName])) {
            return $columnCache[$columnName];
        }
        
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE '{$columnName}'");
            $columnCache[$columnName] = $stmt->rowCount() > 0;
            return $columnCache[$columnName];
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Kiểm tra xem bảng product_images có tồn tại không
     */
    private function hasProductImagesTable(): bool
    {
        static $hasTable = null;
        if ($hasTable !== null) {
            return $hasTable;
        }
        
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'product_images'");
            $hasTable = $stmt->rowCount() > 0;
            return $hasTable;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Helper để thêm image join vào SQL nếu bảng tồn tại
     */
    private function addImageJoin(string $sql, string $alias = 'pi'): string
    {
        if ($this->hasProductImagesTable()) {
            return $sql . " LEFT JOIN product_images {$alias} ON p.product_id = {$alias}.product_id AND {$alias}.is_primary = 1";
        }
        return $sql;
    }

    /**
     * Helper để thêm image field vào SELECT
     */
    private function addImageField(string $sql, string $alias = 'pi'): string
    {
        if ($this->hasProductImagesTable()) {
            // Ưu tiên ảnh primary, nếu không có thì lấy ảnh đầu tiên của sản phẩm
            return $sql . ", (
                SELECT image_url FROM product_images i 
                WHERE i.product_id = p.product_id 
                ORDER BY i.is_primary DESC, i.image_id ASC 
                LIMIT 1
            ) as image";
        }
        return $sql . ", NULL as image";
    }

    /**
     * Helper để thêm price fields (original_price, sale_price) vào SELECT
     */
    private function addPriceFields(string $sql): string
    {
        if ($this->hasColumn('original_price')) {
            $sql .= ", CAST(p.original_price AS DECIMAL(10,2)) as original_price";
        }
        if ($this->hasColumn('sale_price')) {
            $sql .= ", CAST(p.sale_price AS DECIMAL(10,2)) as sale_price";
        }
        return $sql;
    }

    /**
     * Lấy danh sách sản phẩm cho trang quản trị
     */
    public function getAdminProducts(?string $keyword = null, ?int $categoryId = null, bool $includeDeleted = false): array
    {
        $hasDeletedAt = $this->hasDeletedAtColumn();
        
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.price,
                    COALESCE((SELECT SUM(pv.stock) FROM product_variants pv WHERE pv.product_id = p.product_id), p.stock) AS stock,
                    c.category_name,
                    c.category_id";
        
        $sql = $this->addPriceFields($sql); // Thêm original_price và sale_price
        $sql = $this->addImageField($sql, 'pi');
        $sql = str_replace('as image', 'as image_url', $sql); // Đổi tên field cho admin
        
        if ($hasDeletedAt) {
            $sql .= ", p.deleted_at";
        }
        
        $sql .= " FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.category_id";
        
        $sql = $this->addImageJoin($sql);
        
        $sql .= " WHERE 1=1";
        $params = [];

        if ($hasDeletedAt) {
            if (!$includeDeleted) {
                $sql .= " AND (p.deleted_at IS NULL OR p.deleted_at = '')";
            } else {
                $sql .= " AND (p.deleted_at IS NOT NULL AND p.deleted_at != '')";
            }
        }

        if ($keyword) {
            $sql .= " AND (p.product_name LIKE :keyword OR p.description LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        $sql .= " ORDER BY p.product_id DESC";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $paramType = $key === ':category_id' ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo sản phẩm mới
     */
    public function createProduct(array $data): int
    {
        // Đảm bảo PDO có chế độ exception
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->pdo->beginTransaction();

        try {
            // Kiểm tra xem có cột original_price và sale_price không
            $hasOriginalPrice = $this->hasColumn('original_price');
            $hasSalePrice = $this->hasColumn('sale_price');
            
            $fields = ['product_name', 'description', 'price', 'stock', 'category_id'];
            $values = [':name', ':description', ':price', ':stock', ':category_id'];
            $params = [
                ':name'        => $data['name'],
                ':description' => $data['description'] ?? null,
                ':price'       => $data['price'],
                ':stock'       => $data['stock'],
                ':category_id' => $data['category_id'] ?: null,
            ];
            
            if ($hasOriginalPrice) {
                $fields[] = 'original_price';
                $values[] = ':original_price';
                $params[':original_price'] = $data['original_price'] ?? $data['price'];
            }
            
            if ($hasSalePrice) {
                $fields[] = 'sale_price';
                $values[] = ':sale_price';
                $params[':sale_price'] = $data['sale_price'] ?? null;
            }
            
            $sql = "
                INSERT INTO {$this->table} (" . implode(', ', $fields) . ")
                VALUES (" . implode(', ', $values) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $productId = (int)$this->pdo->lastInsertId();
            
            if ($productId <= 0) {
                throw new RuntimeException("Không thể lấy ID sản phẩm mới tạo.");
            }

            $this->upsertPrimaryImage($productId, $data['image_url'] ?? null);

            $this->pdo->commit();
            error_log("ProductModel::createProduct - Successfully created product with ID: " . $productId);
            return $productId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("ProductModel::createProduct error: " . $exception->getMessage());
            error_log("ProductModel::createProduct - Stack trace: " . $exception->getTraceAsString());
            throw $exception;
        }
    }

    /**
     * Cập nhật sản phẩm
     */
    public function updateProduct(int $productId, array $data): bool
    {
        // Đảm bảo PDO có chế độ exception
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->pdo->beginTransaction();

        try {
            // Kiểm tra xem có cột updated_at không
            $hasUpdatedAt = false;
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'updated_at'");
                $hasUpdatedAt = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                error_log("ProductModel::updateProduct - Cannot check updated_at column: " . $e->getMessage());
            }
            
            // Kiểm tra xem có cột original_price và sale_price không
            $hasOriginalPrice = $this->hasColumn('original_price');
            $hasSalePrice = $this->hasColumn('sale_price');
            
            $sql = "
                UPDATE {$this->table}
                SET product_name = :name,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    category_id = :category_id";
            
            if ($hasOriginalPrice) {
                $sql .= ", original_price = :original_price";
            }
            
            if ($hasSalePrice) {
                $sql .= ", sale_price = :sale_price";
            }
            
            if ($hasUpdatedAt) {
                $sql .= ", updated_at = CURRENT_TIMESTAMP";
            }
            
            $sql .= " WHERE product_id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $params = [
                ':name'        => $data['name'],
                ':description' => $data['description'] ?? null,
                ':price'       => $data['price'],
                ':stock'       => $data['stock'],
                ':category_id' => $data['category_id'] ?: null,
                ':id'          => $productId,
            ];
            
            if ($hasOriginalPrice) {
                $params[':original_price'] = $data['original_price'] ?? $data['price'];
            }
            
            if ($hasSalePrice) {
                // Lưu sale_price, nếu null hoặc rỗng thì set NULL vào database
                $salePriceValue = isset($data['sale_price']) && $data['sale_price'] !== null && $data['sale_price'] !== '' && $data['sale_price'] > 0
                    ? $data['sale_price']
                    : null;
                $params[':sale_price'] = $salePriceValue;
                error_log("ProductModel::updateProduct - sale_price value: " . var_export($salePriceValue, true));
            }
            
            $stmt->execute($params);
            
            // Kiểm tra xem có dòng nào được cập nhật không
            $affectedRows = $stmt->rowCount();
            if ($affectedRows === 0) {
                error_log("ProductModel::updateProduct - No rows affected for product_id: " . $productId);
                throw new RuntimeException("Không tìm thấy sản phẩm với ID: " . $productId);
            }

            // Cập nhật ảnh trong bảng product_images nếu có
            // Luôn cập nhật ảnh nếu có trong data (kể cả khi là ảnh cũ được giữ lại)
            if (isset($data['image_url']) && $data['image_url'] !== null && $data['image_url'] !== '') {
                $this->upsertPrimaryImage($productId, $data['image_url']);
                error_log("ProductModel::updateProduct - Updated image for product_id: " . $productId . ", image_url: " . $data['image_url']);
            }

            $this->pdo->commit();
            error_log("ProductModel::updateProduct - Successfully updated product_id: " . $productId);
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("ProductModel::updateProduct error for product_id {$productId}: " . $exception->getMessage());
            error_log("ProductModel::updateProduct - Stack trace: " . $exception->getTraceAsString());
            throw $exception;
        }
    }

    /**
     * Xóa mềm sản phẩm (soft delete)
     */
    public function deleteProduct(int $productId): bool
    {
        if (!$this->hasDeletedAtColumn()) {
            // Nếu chưa có cột deleted_at, thực hiện xóa vĩnh viễn
            return $this->forceDeleteProduct($productId);
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE product_id = :pid");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * Khôi phục sản phẩm đã xóa
     */
    public function restoreProduct(int $productId): bool
    {
        if (!$this->hasDeletedAtColumn()) {
            throw new RuntimeException('Cột deleted_at chưa tồn tại. Vui lòng chạy script SQL để thêm cột.');
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE product_id = :pid");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * Xóa vĩnh viễn sản phẩm (hard delete)
     */
    public function forceDeleteProduct(int $productId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $variantIds = $this->getVariantIdsByProduct($productId);

            if (!empty($variantIds)) {
                $placeholder = implode(',', array_fill(0, count($variantIds), '?'));
                
                // Xóa variant_images nếu bảng tồn tại (bỏ qua lỗi nếu không tồn tại)
                try {
                    $stmt = $this->pdo->prepare("DELETE FROM variant_images WHERE variant_id IN ($placeholder)");
                    $stmt->execute($variantIds);
                } catch (PDOException $e) {
                    // Bảng variant_images không tồn tại, bỏ qua
                }

                $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE variant_id IN ($placeholder)");
                $stmt->execute($variantIds);
            }

            $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE product_id = :pid");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM product_variants WHERE product_id = :pid");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE product_id = :pid");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE product_id = :pid");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Lấy danh sách biến thể và thuộc tính cho trang quản trị
     */
    public function getVariantsDetailed(int $productId): array
    {
        // Đảm bảo có cột image_url để tránh lỗi khi select
        $this->ensureVariantImageColumn();

        $sql = "SELECT 
                    pv.variant_id,
                    pv.product_id,
                    pv.sku,
                    pv.additional_price,
                    pv.stock,
                    pv.image_url,
                    a.attribute_id,
                    a.attribute_name,
                    av.value_id,
                    av.value_name
                FROM product_variants pv
                LEFT JOIN product_attribute_values pav ON pv.variant_id = pav.variant_id
                LEFT JOIN attribute_values av ON pav.value_id = av.value_id
                LEFT JOIN attributes a ON av.attribute_id = a.attribute_id
                WHERE pv.product_id = :pid
                ORDER BY pv.variant_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $variants = [];
        foreach ($rows as $row) {
            $id = (int)$row['variant_id'];
            if (!isset($variants[$id])) {
                $variants[$id] = [
                    'variant_id' => $id,
                    'product_id' => (int)$row['product_id'],
                    'sku' => $row['sku'],
                    'additional_price' => (float)($row['additional_price'] ?? 0),
                    'stock' => (int)$row['stock'],
                    'image_url' => $row['image_url'] ?? null,
                    'attributes' => [],
                ];
            }

            if ($row['attribute_id']) {
                $variants[$id]['attributes'][(int)$row['attribute_id']] = [
                    'attribute_id' => (int)$row['attribute_id'],
                    'attribute_name' => $row['attribute_name'],
                    'value_id' => (int)$row['value_id'],
                    'value_name' => $row['value_name'],
                ];
            }
        }

        return array_values($variants);
    }

    /**
     * Tạo biến thể mới
     */
    public function createVariant(int $productId, array $variantData, array $valueIds): int
    {
        // Loại bỏ PRIMARY KEY khỏi variantData
        $variantData = $this->removePrimaryKeyFromData($variantData, 'product_variants');
        
        $this->ensureVariantImageColumn();
        $this->pdo->beginTransaction();

        try {
            // Validate bắt buộc
            $sku  = trim($variantData['sku'] ?? '');
            $stock = (int)($variantData['stock'] ?? 0);
            $imageUrl = trim($variantData['image_url'] ?? '');
            if ($sku === '') {
                throw new InvalidArgumentException('Mã SKU bắt buộc.');
            }
            if ($stock <= 0) {
                throw new InvalidArgumentException('Tồn kho biến thể phải lớn hơn 0.');
            }
            if ($imageUrl === '') {
                throw new InvalidArgumentException('Ảnh biến thể bắt buộc.');
            }

            // Chặn trùng SKU theo sản phẩm
            $checkSku = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM product_variants WHERE product_id = :pid AND LOWER(TRIM(sku)) = LOWER(TRIM(:sku))");
            $checkSku->bindValue(':pid', $productId, PDO::PARAM_INT);
            $checkSku->bindValue(':sku', $sku, PDO::PARAM_STR);
            $checkSku->execute();
            if ((int)($checkSku->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) > 0) {
                throw new InvalidArgumentException('SKU này đã tồn tại trong sản phẩm.');
            }

            // Chặn trùng tổ hợp giá trị thuộc tính (ví dụ size + color)
            $existingVariantIds = $this->getVariantIdsByProduct($productId);
            sort($valueIds);
            foreach ($existingVariantIds as $vid) {
                $vals = $this->getValueIdsByVariant($vid);
                sort($vals);
                if ($vals === $valueIds) {
                    throw new InvalidArgumentException('Thuộc tính này (tổ hợp size/color) đã có rồi.');
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO product_variants (product_id, sku, additional_price, stock, image_url)
                VALUES (:product_id, :sku, :additional_price, :stock, :image_url)
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':sku' => $sku,
                ':additional_price' => $variantData['additional_price'] ?? 0,
                ':stock' => $stock,
                ':image_url' => $imageUrl,
            ]);

            $variantId = (int)$this->pdo->lastInsertId();

            $this->syncVariantAttributeValues($productId, $variantId, $valueIds);

            $this->pdo->commit();
            return $variantId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Cập nhật biến thể
     */
    public function updateVariant(int $variantId, array $variantData, array $valueIds): bool
    {
        $this->ensureVariantImageColumn();
        $this->pdo->beginTransaction();

        try {
            $setImage = array_key_exists('image_url', $variantData);
            $sql = "
                UPDATE product_variants
                SET sku = :sku,
                    additional_price = :additional_price,
                    stock = :stock";
            if ($setImage) {
                $sql .= ", image_url = :image_url";
            }
            $sql .= " WHERE variant_id = :variant_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':sku', $variantData['sku'] ?? null);
            $stmt->bindValue(':additional_price', $variantData['additional_price'] ?? 0);
            $stmt->bindValue(':stock', $variantData['stock'] ?? 0, PDO::PARAM_INT);
            if ($setImage) {
                $stmt->bindValue(':image_url', $variantData['image_url']);
            }
            $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            $stmt->execute();

            $productId = $this->getProductIdByVariant($variantId);
            if (!$productId) {
                throw new RuntimeException('Không tìm thấy sản phẩm cho biến thể.');
            }

            $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE variant_id = :variant_id");
            $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            $stmt->execute();

            $this->syncVariantAttributeValues($productId, $variantId, $valueIds);

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Xóa biến thể
     */
    public function deleteVariant(int $variantId): bool
    {
        $this->pdo->beginTransaction();

        try {
            // Xóa variant_images nếu bảng tồn tại (bỏ qua lỗi nếu không tồn tại)
            try {
                $stmt = $this->pdo->prepare("DELETE FROM variant_images WHERE variant_id = :variant_id");
                $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
                $stmt->execute();
            } catch (PDOException $e) {
                // Bảng variant_images không tồn tại, bỏ qua
            }

            $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE variant_id = :variant_id");
            $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE variant_id = :variant_id");
            $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM product_variants WHERE variant_id = :variant_id");
            $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function getVariantIdsByProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare("SELECT variant_id FROM product_variants WHERE product_id = :pid");
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($row) => (int)$row['variant_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function getProductIdByVariant(int $variantId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT product_id FROM product_variants WHERE variant_id = :variant_id LIMIT 1");
        $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['product_id'] : null;
    }

    private function getValueIdsByVariant(int $variantId): array
    {
        $stmt = $this->pdo->prepare("SELECT value_id FROM product_attribute_values WHERE variant_id = :variant_id ORDER BY value_id");
        $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($row) => (int)$row['value_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function syncVariantAttributeValues(int $productId, int $variantId, array $valueIds): void
    {
        if (empty($valueIds)) {
            return;
        }

        // Xóa các giá trị cũ của variant này trước (nếu có)
        $deleteStmt = $this->pdo->prepare("
            DELETE FROM product_attribute_values 
            WHERE variant_id = :variant_id
        ");
        $deleteStmt->execute([':variant_id' => $variantId]);

        // Insert các giá trị mới
        $stmt = $this->pdo->prepare("
            INSERT INTO product_attribute_values (product_id, variant_id, value_id)
            VALUES (:product_id, :variant_id, :value_id)
        ");

        foreach ($valueIds as $valueId) {
            $valueId = (int)$valueId;
            if ($valueId <= 0) {
                continue;
            }

            // Kiểm tra xem đã tồn tại chưa (tránh duplicate)
            $checkStmt = $this->pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_attribute_values 
                WHERE variant_id = :variant_id AND value_id = :value_id
            ");
            $checkStmt->execute([
                ':variant_id' => $variantId,
                ':value_id' => $valueId
            ]);
            $exists = (int)$checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

            if (!$exists) {
                $stmt->execute([
                    ':product_id' => $productId,
                    ':variant_id' => $variantId,
                    ':value_id' => $valueId,
                ]);
            }
        }
    }

    /**
     * Đảm bảo bảng product_variants có cột image_url (để lưu ảnh biến thể)
     */
    private function ensureVariantImageColumn(): void
    {
        try {
            $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME = 'product_variants' 
                      AND TABLE_SCHEMA = DATABASE()
                      AND COLUMN_NAME = 'image_url'";
            $exists = (int)$this->pdo->query($sql)->fetchColumn() > 0;
            if (!$exists) {
                $this->pdo->exec("ALTER TABLE product_variants ADD COLUMN image_url VARCHAR(255) NULL AFTER stock");
            }
        } catch (PDOException $e) {
            // Nếu không có quyền/không tồn tại bảng, bỏ qua để không chặn luồng chính
        }
    }

    private function upsertPrimaryImage(int $productId, ?string $imageUrl): void
    {
        // Nếu bảng product_images không tồn tại, bỏ qua
        if (!$this->hasProductImagesTable()) {
            error_log("ProductModel::upsertPrimaryImage - product_images table does not exist");
            return;
        }

        if ($imageUrl === null || $imageUrl === '') {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE product_id = :pid AND is_primary = 1");
                $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
                $stmt->execute();
                error_log("ProductModel::upsertPrimaryImage - Deleted primary image for product_id: " . $productId);
            } catch (PDOException $e) {
                error_log("ProductModel::upsertPrimaryImage - Error deleting image: " . $e->getMessage());
            }
            return;
        }

        try {
            // Kiểm tra xem có ảnh primary nào chưa
            $stmt = $this->pdo->prepare("SELECT image_id FROM product_images WHERE product_id = :pid AND is_primary = 1 LIMIT 1");
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Cập nhật ảnh hiện có
                $stmt = $this->pdo->prepare("UPDATE product_images SET image_url = :image_url WHERE product_id = :pid AND is_primary = 1");
                $stmt->bindValue(':image_url', $imageUrl, PDO::PARAM_STR);
                $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
                $stmt->execute();
                error_log("ProductModel::upsertPrimaryImage - Updated existing image for product_id: " . $productId);
            } else {
                // Tạo mới ảnh primary
                $stmt = $this->pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary)
                    VALUES (:product_id, :image_url, 1)
                ");
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stmt->bindValue(':image_url', $imageUrl, PDO::PARAM_STR);
                $stmt->execute();
                error_log("ProductModel::upsertPrimaryImage - Inserted new primary image for product_id: " . $productId);
            }
        } catch (PDOException $e) {
            error_log("ProductModel::upsertPrimaryImage - Error: " . $e->getMessage());
            error_log("ProductModel::upsertPrimaryImage - Stack trace: " . $e->getTraceAsString());
            // Không throw exception để không làm gián đoạn quá trình cập nhật sản phẩm
        }
    }

    /**
     * Lấy các giá trị thuộc tính (Size, Color) khả dụng cho 1 sản phẩm từ DB
     * Trả về mảng: ['sizes' => [...], 'colors' => [...]]
     */
    public function getProductAttributes(int $productId): array
    {
        // Lấy tất cả cặp (attribute_name, value_name) từ variants của sản phẩm
        // Chỉ lấy từ product_variants (biến thể) - không lấy từ product_attribute_values trực tiếp
        $sql = "SELECT DISTINCT a.attribute_name, av.value_name
                FROM product_variants pv
                JOIN product_attribute_values pav ON pv.variant_id = pav.variant_id
                JOIN attribute_values av ON pav.value_id = av.value_id
                JOIN attributes a ON av.attribute_id = a.attribute_id
                WHERE pv.product_id = :pid
                  AND pav.variant_id IS NOT NULL
                  AND pv.stock >= 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sizes  = [];
        $colors = [];

        foreach ($rows as $row) {
            $attrName = mb_strtolower(trim($row['attribute_name'] ?? ''));
            $value    = trim($row['value_name'] ?? '');

            if (empty($value)) {
                continue;
            }

            // Nhận diện size: chứa "size", "kích", "kich"
            if (preg_match('/size|kích|kich/i', $attrName)) {
                $sizes[] = $value;
                continue;
            }

            // Nhận diện màu: chứa "color", "màu", "mau"
            if (preg_match('/color|màu|mau/i', $attrName)) {
                $colors[] = $value;
            }
        }

        // Loại bỏ trùng
        $sizes  = array_values(array_unique($sizes));
        $colors = array_values(array_unique($colors));

        // Sắp xếp để UI ổn định
        sort($sizes, SORT_NATURAL | SORT_FLAG_CASE);
        sort($colors, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'sizes'  => $sizes,
            'colors' => $colors,
        ];
    }

    /**
     * Tìm biến thể (variant) theo tên thuộc tính (size, color)
     * Trả về thông tin variant (variant_id, sku, additional_price, stock) hoặc null nếu không tồn tại
     */
    public function getVariantByValueNames(int $productId, ?string $sizeName, ?string $colorName): ?array
    {
        $this->ensureVariantImageColumn();
        $selected = array_values(array_filter([$sizeName, $colorName], fn($v) => $v !== null && $v !== ''));
        if (count($selected) === 0) {
            return null;
        }

        // Tìm variant có đủ tất cả value_name đã chọn (không phân biệt hoa/thường của value_name)
        $inPlaceholders = implode(',', array_fill(0, count($selected), '?'));
        $sql = "SELECT pv.variant_id, pv.sku, pv.additional_price, pv.stock, pv.image_url
                FROM product_variants pv
                JOIN product_attribute_values pav ON pv.variant_id = pav.variant_id
                JOIN attribute_values av ON pav.value_id = av.value_id
                WHERE pv.product_id = ?
                  AND LOWER(TRIM(av.value_name)) IN ($inPlaceholders)
                GROUP BY pv.variant_id, pv.sku, pv.additional_price, pv.stock, pv.image_url
                HAVING COUNT(DISTINCT LOWER(TRIM(av.value_name))) = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $bindIndex = 1;
        $stmt->bindValue($bindIndex++, $productId, PDO::PARAM_INT);
        foreach ($selected as $name) {
            $stmt->bindValue($bindIndex++, mb_strtolower(trim($name)), PDO::PARAM_STR);
        }
        $stmt->bindValue($bindIndex++, count($selected), PDO::PARAM_INT);
        $stmt->execute();
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        return $variant ?: null;
    }

    /**
     * Lấy tất cả sản phẩm kèm hình ảnh chính
     * @param int $limit Số lượng sản phẩm cần lấy (mặc định: tất cả)
     * @return array Danh sách sản phẩm
     */
    public function getAllProducts($limit = null)
    {
        $sql = "SELECT 
                    p.product_id as id,
                    p.product_name as name,
                    p.description,
                    p.price,
                    p.stock,
                    p.category_id,
                    c.category_name as category";
        
        $sql = $this->addPriceFields($sql);
        $sql = $this->addImageField($sql);
        
        $sql .= " FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.category_id";
        
        $sql = $this->addImageJoin($sql);
        
        $sql .= " ORDER BY p.product_id DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->pdo->prepare($sql);
        
        if ($limit) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách ID của các sản phẩm mới nhất
     * @param int $limit Số lượng sản phẩm mới nhất (mặc định 8)
     * @return array Danh sách product_id
     */
    public function getNewProductIds(int $limit = 8): array
    {
        $sql = "SELECT p.product_id 
                FROM {$this->table} p
                ORDER BY p.created_at DESC, p.product_id DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => (int)$r['product_id'], $results);
    }

		/**
		 * Đếm tổng số sản phẩm (có thể theo danh mục)
		 */
		public function countAllProducts($categoryId = null, ?string $keyword = null, ?float $priceMin = null, ?float $priceMax = null)
		{
			$conditions = [];
			if ($categoryId) $conditions[] = 'p.category_id = :category_id';
			if ($keyword) {
				// Luôn chỉ tìm trong product_name để đảm bảo kết quả chính xác
				// Không tìm trong description vì có thể tìm thấy sản phẩm không liên quan
				$conditions[] = 'p.product_name LIKE :keyword';
			}
			if ($priceMin !== null) $conditions[] = 'p.price >= :price_min';
			if ($priceMax !== null) $conditions[] = 'p.price <= :price_max';

            // Ẩn các sản phẩm chưa có thuộc tính/biến thể
            $conditions[] = "EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.product_id)";
            $whereSql = ' WHERE ' . implode(' AND ', $conditions);

			$sql = "SELECT COUNT(*) AS total FROM {$this->table} p" . $whereSql;
			$stmt = $this->pdo->prepare($sql);
			if ($categoryId) $stmt->bindValue(':category_id', (int)$categoryId, PDO::PARAM_INT);
			if ($keyword)    $stmt->bindValue(':keyword', "%{$keyword}%", PDO::PARAM_STR);
			if ($priceMin !== null) $stmt->bindValue(':price_min', $priceMin, PDO::PARAM_STR);
			if ($priceMax !== null) $stmt->bindValue(':price_max', $priceMax, PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			return (int)($row['total'] ?? 0);
		}

		/**
		 * Lấy sản phẩm theo trang (có thể theo danh mục)
		 */
		public function getProductsPage($page = 1, $perPage = 12, $categoryId = null, ?string $keyword = null, ?float $priceMin = null, ?float $priceMax = null)
		{
			$offset = max(0, ($page - 1) * $perPage);
			$conditions = [];
			if ($categoryId) $conditions[] = 'p.category_id = :category_id';
			if ($keyword) {
				// Luôn chỉ tìm trong product_name để đảm bảo kết quả chính xác
				// Không tìm trong description vì có thể tìm thấy sản phẩm không liên quan
				$conditions[] = 'p.product_name LIKE :keyword';
			}
			if ($priceMin !== null) $conditions[] = 'p.price >= :price_min';
			if ($priceMax !== null) $conditions[] = 'p.price <= :price_max';
            // Ẩn các sản phẩm chưa có thuộc tính/biến thể
            $conditions[] = "EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.product_id)";
            $whereSql = ' WHERE ' . implode(' AND ', $conditions);

			// Sắp xếp theo độ liên quan nếu có keyword
			$orderBy = 'p.created_at DESC';
			if ($keyword) {
				// Ưu tiên: khớp chính xác > bắt đầu bằng > chứa trong tên > ngày tạo
				$orderBy = "
					CASE 
						WHEN p.product_name LIKE :keyword_exact THEN 1
						WHEN p.product_name LIKE :keyword_start THEN 2
						WHEN p.product_name LIKE :keyword THEN 3
						ELSE 4
					END ASC,
					p.created_at DESC
				";
			}

			$sql = "SELECT 
						p.product_id as id,
						p.product_name as name,
						p.description,
						p.price,
						p.stock,
						c.category_name as category,
						pi.image_url as image";
			
			$sql = $this->addPriceFields($sql);
			
			$sql .= " FROM {$this->table} p
					LEFT JOIN categories c ON p.category_id = c.category_id
					LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
					{$whereSql}
					ORDER BY {$orderBy}
					LIMIT :limit OFFSET :offset";
			
			$stmt = $this->pdo->prepare($sql);
			if ($categoryId) $stmt->bindValue(':category_id', (int)$categoryId, PDO::PARAM_INT);
			if ($keyword) {
				$keywordEscaped = "%{$keyword}%";
				$keywordExact = "{$keyword}";
				$keywordStart = "{$keyword}%";
				$stmt->bindValue(':keyword', $keywordEscaped, PDO::PARAM_STR);
				// Luôn bind keyword_exact và keyword_start nếu có keyword (dùng trong ORDER BY)
				$stmt->bindValue(':keyword_exact', $keywordExact, PDO::PARAM_STR);
				$stmt->bindValue(':keyword_start', $keywordStart, PDO::PARAM_STR);
			}
			if ($priceMin !== null) $stmt->bindValue(':price_min', $priceMin, PDO::PARAM_STR);
			if ($priceMax !== null) $stmt->bindValue(':price_max', $priceMax, PDO::PARAM_STR);
			$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
			$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

    /**
     * Lấy sản phẩm theo ID
     * @param int $id ID sản phẩm
     * @return array|false Thông tin sản phẩm hoặc false nếu không tìm thấy
     */
    public function getProductById($id)
    {
        $hasOriginalPrice = $this->hasColumn('original_price');
        $hasSalePrice = $this->hasColumn('sale_price');
        
        $sql = "SELECT 
                    p.product_id as id,
                    p.product_name as name,
                    p.description,
                    CAST(p.price AS DECIMAL(10,2)) as price,
                    p.stock,
                    c.category_name as category,
                    c.category_id";
        
        if ($hasOriginalPrice) {
            $sql .= ", CAST(p.original_price AS DECIMAL(10,2)) as original_price";
        }
        
        if ($hasSalePrice) {
            $sql .= ", CAST(p.sale_price AS DECIMAL(10,2)) as sale_price";
        }
        
        $sql = $this->addImageField($sql);
        
        $sql .= " FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.category_id";
        
        $sql = $this->addImageJoin($sql);
        
        $sql .= " WHERE p.product_id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Đảm bảo giá được trả về đúng định dạng
        if ($result) {
            if (isset($result['price'])) {
                $result['price'] = (float)$result['price'];
            }
            if (isset($result['original_price'])) {
                $result['original_price'] = (float)$result['original_price'];
            }
            if (isset($result['sale_price'])) {
                $result['sale_price'] = $result['sale_price'] !== null ? (float)$result['sale_price'] : null;
            }
        }
        
        return $result;
    }

    /**
     * Lấy sản phẩm theo danh mục
     * @param int $categoryId ID danh mục
     * @param int $limit Số lượng sản phẩm
     * @return array Danh sách sản phẩm
     */
    public function getProductsByCategory($categoryId, $limit = null)
    {
        $sql = "SELECT 
                    p.product_id as id,
                    p.product_name as name,
                    p.description,
                    p.price,
                    p.stock,
                    c.category_name as category";
        
        $sql = $this->addPriceFields($sql);
        $sql = $this->addImageField($sql);
        
        $sql .= " FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.category_id";
        
        $sql = $this->addImageJoin($sql);
        
        $sql .= " WHERE p.category_id = :category_id
                ORDER BY p.product_id DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top sản phẩm bán chạy trong khoảng ngày (theo số lượng).
     *
     * - Chỉ tính các đơn hàng đã giao thành công (`orders_new.status = 'delivered'`).
     * - Được dùng ở `AdminStatisticsController` để hiển thị:
     *   + Bảng "Top 5 Sản Phẩm Bán Chạy" và biểu đồ thanh ngang `topProductChart`
     *     trên trang thống kê admin.
     */
    public function getTopSelling(string $fromDate, string $toDate, int $limit = 5): array
    {
        $sql = "
            SELECT 
                oi.product_id,
                p.product_name,
                SUM(oi.quantity) AS qty,
                SUM(oi.quantity * oi.unit_price) AS revenue
            FROM order_items oi
            JOIN orders_new o ON o.id = oi.order_id
            JOIN products p ON p.product_id = oi.product_id
            WHERE o.status = 'delivered'
              AND DATE(o.created_at) BETWEEN :from_date AND :to_date
            GROUP BY oi.product_id, p.product_name
            ORDER BY qty DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top sản phẩm bán chậm nhất (ít đơn giao thành công).
     *
     * - Cũng chỉ tính đơn `delivered`.
     * - Được dùng cho bảng "Top 5 Sản Phẩm Bán Chậm" trong trang thống kê.
     */
    public function getSlowSelling(string $fromDate, string $toDate, int $limit = 5): array
    {
        $sql = "
            SELECT 
                oi.product_id,
                p.product_name,
                SUM(oi.quantity) AS qty
            FROM order_items oi
            JOIN orders_new o ON o.id = oi.order_id
            JOIN products p ON p.product_id = oi.product_id
            WHERE o.status = 'delivered'
              AND DATE(o.created_at) BETWEEN :from_date AND :to_date
            GROUP BY oi.product_id, p.product_name
            ORDER BY qty ASC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy hình ảnh của sản phẩm
     * @param int $productId ID sản phẩm
     * @return array Danh sách hình ảnh
     */
    public function getProductImages($productId)
    {
        $sql = "SELECT image_url, is_primary 
                FROM product_images 
                WHERE product_id = :product_id
                ORDER BY is_primary DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy ảnh theo variant
     */
    public function getVariantImages(int $variantId): array
    {
        // Nếu bảng variant_images không tồn tại hoặc không có dữ liệu, sẽ fallback sang cột image_url của product_variants
        try {
            $sql = "SELECT image_url, is_primary
                    FROM variant_images
                    WHERE variant_id = :variant_id
                    ORDER BY is_primary DESC, variant_image_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            // Bảng không tồn tại, bỏ qua
        }

        // Fallback: lấy ảnh từ cột image_url của product_variants
        $this->ensureVariantImageColumn();
        $stmt = $this->pdo->prepare("SELECT image_url, 1 AS is_primary FROM product_variants WHERE variant_id = :variant_id AND image_url IS NOT NULL");
        $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy ảnh theo Color cho toàn bộ variant của 1 sản phẩm
     * Trả về mảng URL ảnh (không trùng), ưu tiên ảnh is_primary trước
     */
    public function getVariantImagesByColor(int $productId, string $colorName): array
    {
        // Cố gắng lấy ảnh từ variant_images trước
        try {
            $sql = "SELECT DISTINCT vi.image_url, vi.is_primary, vi.variant_image_id
                    FROM product_variants pv
                    JOIN product_attribute_values pav ON pav.variant_id = pv.variant_id
                    JOIN attribute_values av ON av.value_id = pav.value_id
                    JOIN attributes a ON a.attribute_id = av.attribute_id
                    JOIN variant_images vi ON vi.variant_id = pv.variant_id
                    WHERE pv.product_id = :pid
                      AND (LOWER(a.attribute_name) LIKE '%color%' OR LOWER(a.attribute_name) LIKE '%màu%' OR LOWER(a.attribute_name) LIKE '%mau%')
                      AND LOWER(av.value_name) = LOWER(:color)
                    ORDER BY vi.is_primary DESC, vi.variant_image_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
            $stmt->bindValue(':color', $colorName, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $urls = array_unique(array_column($rows, 'image_url'));
            if (!empty($urls)) {
                return $urls;
            }
        } catch (PDOException $e) {
            // ignore
        }

        // Fallback: lấy ảnh từ cột image_url của product_variants theo Color
        $this->ensureVariantImageColumn();
        $sql = "SELECT DISTINCT pv.image_url
                FROM product_variants pv
                JOIN product_attribute_values pav ON pav.variant_id = pv.variant_id
                JOIN attribute_values av ON av.value_id = pav.value_id
                JOIN attributes a ON a.attribute_id = av.attribute_id
                WHERE pv.product_id = :pid
                  AND LOWER(a.attribute_name) = 'color'
                  AND LOWER(av.value_name) = LOWER(:color)
                  AND pv.image_url IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':color', $colorName, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_unique(array_column($rows, 'image_url'));
    }

    /**
     * Lấy biến thể đầu tiên của 1 sản phẩm (fallback)
     */
    public function getFirstVariantByProduct(int $productId): ?array
    {
        $sql = "SELECT variant_id, product_id, sku, additional_price, stock
                FROM product_variants
                WHERE product_id = :pid
                ORDER BY variant_id ASC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lấy tên Size và Color của 1 variant
     */
    public function getVariantAttributeNames(int $variantId): array
    {
        $sql = "
            SELECT
                (SELECT av.value_name
                 FROM product_attribute_values pav
                 JOIN attribute_values av ON pav.value_id = av.value_id
                 JOIN attributes a ON a.attribute_id = av.attribute_id
                 WHERE pav.variant_id = :vid AND a.attribute_name = 'Size'
                 LIMIT 1) AS size_name,
                (SELECT av.value_name
                 FROM product_attribute_values pav
                 JOIN attribute_values av ON pav.value_id = av.value_id
                 JOIN attributes a ON a.attribute_id = av.attribute_id
                 WHERE pav.variant_id = :vid AND a.attribute_name = 'Color'
                 LIMIT 1) AS color_name
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':vid', $variantId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'size' => $row['size_name'] ?? null,
            'color' => $row['color_name'] ?? null,
        ];
    }
    /**
     * Lấy sản phẩm tương tự theo danh mục (loại trừ 1 sản phẩm)
     */
    public function getSimilarProducts(int $categoryId, int $excludeProductId, int $limit = 8): array
    {
        $sql = "SELECT 
                    p.product_id as id,
                    p.product_name as name,
                    p.description,
                    p.price,
                    p.stock,
                    c.category_name as category";
        
        $sql = $this->addPriceFields($sql);
        $sql = $this->addImageField($sql);
        
        $sql .= " FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.category_id";
        
        $sql = $this->addImageJoin($sql);
        
        $sql .= " WHERE p.category_id = :category_id
                  AND p.product_id <> :exclude_id
                ORDER BY p.product_id DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':exclude_id', $excludeProductId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm kiếm sản phẩm theo tên
     * @param string $keyword Từ khóa tìm kiếm
     * @param int $limit Số lượng sản phẩm
     * @return array Danh sách sản phẩm
     */
    public function searchProducts($keyword, $limit = null)
    {
        // Chỉ tìm trong product_name để đảm bảo kết quả chính xác
        $sql = "SELECT 
                    p.product_id as id,
                    p.product_name as name,
                    p.description,
                    p.price,
                    p.stock,
                    c.category_name as category,
                    pi.image_url as image";
        
        $sql = $this->addPriceFields($sql);
        
        $sql .= " FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE p.product_name LIKE :keyword
                ORDER BY 
                    CASE 
                        WHEN p.product_name LIKE :keyword_exact THEN 1
                        WHEN p.product_name LIKE :keyword_start THEN 2
                        WHEN p.product_name LIKE :keyword THEN 3
                        ELSE 4
                    END ASC,
                    p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $keywordEscaped = "%{$keyword}%";
        $keywordExact = "{$keyword}";
        $keywordStart = "{$keyword}%";
        $stmt->bindValue(':keyword', $keywordEscaped, PDO::PARAM_STR);
        $stmt->bindValue(':keyword_exact', $keywordExact, PDO::PARAM_STR);
        $stmt->bindValue(':keyword_start', $keywordStart, PDO::PARAM_STR);
        
        if ($limit) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Số lượng sản phẩm theo tháng (12 tháng gần nhất, dạng ước lượng).
     *
     * - Dựa trên tổng số sản phẩm hiện tại, chia đều cho từng tháng
     *   (vì không có cột ngày tạo sản phẩm chính xác cho thống kê theo tháng).
     * - Được dùng ở `AdminStatisticsController` để hiển thị trend
     *   "Số lượng sản phẩm theo tháng" trên dashboard/thống kê.
     */
    public function getMonthlyProducts(int $months = 12): array
    {
        $products = [];
        $labels = [];
        
        $totalProducts = $this->countAllProducts();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthLabel = date('M', strtotime("-$i months"));
            
            // Vì không biết cột ngày chính xác, chia đều cho các tháng
            $products[] = $totalProducts / $months;
            $labels[] = $monthLabel;
        }
        
        return [
            'labels' => $labels,
            'data' => $products
        ];
    }

    /**
     * Danh sách sản phẩm sắp hết hàng (tồn kho nhỏ hơn hoặc bằng ngưỡng).
     *
     * - Được dùng cho box "Sản Phẩm Sắp Hết Hàng" trong trang thống kê admin.
     */
    public function getLowStockProducts(int $threshold = 10, int $limit = 10): array
    {
        try {
            $sql = "SELECT 
                        p.product_id,
                        p.product_name,
                        p.stock,
                        c.category_name";
            
            $sql = $this->addImageField($sql, 'pi');
            
            $sql .= " FROM {$this->table} p
                    LEFT JOIN categories c ON p.category_id = c.category_id";
            
            $sql = $this->addImageJoin($sql);
            
            $sql .= " WHERE p.stock <= :threshold
                    AND p.stock > 0
                    ORDER BY p.stock ASC
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Số lượng sản phẩm đã bán và doanh thu theo từng danh mục.
     *
     * - Chỉ tính các đơn `delivered`.
     * - Được dùng cho bảng "Số Lượng Sản Phẩm Bán Theo Danh Mục" trong thống kê.
     */
    public function getSoldByCategory(string $fromDate, string $toDate): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.category_name,
                    SUM(oi.quantity) AS qty,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
                FROM order_items oi
                JOIN orders_new o ON o.id = oi.order_id
                JOIN products p ON p.product_id = oi.product_id
                JOIN categories c ON c.category_id = p.category_id
                WHERE o.status = 'delivered'
                AND DATE(o.created_at) BETWEEN :from_date AND :to_date
                GROUP BY c.category_id, c.category_name
                ORDER BY qty DESC
            ");
            $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Lợi nhuận ước tính theo sản phẩm (giả sử chi phí = 60%, lợi nhuận ~40%).
     *
     * - Tính theo số lượng bán * đơn giá * 0.4.
     * - Dùng để phân tích sản phẩm mang lại lợi nhuận cao trên trang thống kê.
     */
    public function getProductProfitEstimate(string $fromDate, string $toDate, int $limit = 10): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.product_id,
                    p.product_name,
                    SUM(oi.quantity) AS qty_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue,
                    COALESCE(SUM(oi.quantity * oi.unit_price * 0.4), 0) AS estimated_profit
                FROM order_items oi
                JOIN orders_new o ON o.id = oi.order_id
                JOIN products p ON p.product_id = oi.product_id
                WHERE o.status = 'delivered'
                AND DATE(o.created_at) BETWEEN :from_date AND :to_date
                GROUP BY p.product_id, p.product_name
                ORDER BY estimated_profit DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
            $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get stock for a specific variant by size and color
     * @param int $productId
     * @param string $size
     * @param string $color
     * @return int Stock quantity
     */
    public function getVariantStock(int $productId, ?string $size, ?string $color): int
    {
        try {
            // Sử dụng method getVariantByValueNames đã có sẵn
            $variant = $this->getVariantByValueNames($productId, $size, $color);
            
            if ($variant && isset($variant['stock'])) {
                return (int)$variant['stock'];
            }
            
            return 0;
        } catch (Throwable $e) {
            error_log("Error getting variant stock: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Trừ số lượng tồn kho (khi thêm vào giỏ hàng)
     * @param int $productId
     * @param ?int $variantId
     * @param ?string $size
     * @param ?string $color
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock(int $productId, ?int $variantId, ?string $size, ?string $color, int $quantity): bool
    {
        try {
            if ($quantity <= 0) {
                return false;
            }

            if ($variantId !== null) {
                // Trừ tồn kho từ product_variants
                $stmt = $this->pdo->prepare("
                    UPDATE product_variants 
                    SET stock = GREATEST(0, stock - :quantity) 
                    WHERE variant_id = :variant_id
                ");
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                // Tìm variant từ size và color nếu có
                if ($size || $color) {
                    $variant = $this->getVariantByValueNames($productId, $size, $color);
                    if ($variant && isset($variant['variant_id'])) {
                        $variantId = (int)$variant['variant_id'];
                        $stmt = $this->pdo->prepare("
                            UPDATE product_variants 
                            SET stock = GREATEST(0, stock - :quantity) 
                            WHERE variant_id = :variant_id
                        ");
                        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
                        return $stmt->execute();
                    }
                }
                
                // Không có variant, trừ tồn kho từ products table
                $stmt = $this->pdo->prepare("
                    UPDATE products 
                    SET stock = GREATEST(0, stock - :quantity) 
                    WHERE product_id = :product_id
                ");
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                return $stmt->execute();
            }
        } catch (Throwable $e) {
            error_log("Error decreasing stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cộng lại số lượng tồn kho (khi xóa khỏi giỏ hàng hoặc giảm số lượng)
     * @param int $productId
     * @param ?int $variantId
     * @param ?string $size
     * @param ?string $color
     * @param int $quantity
     * @return bool
     */
    public function increaseStock(int $productId, ?int $variantId, ?string $size, ?string $color, int $quantity): bool
    {
        try {
            if ($quantity <= 0) {
                return false;
            }

            if ($variantId !== null) {
                // Cộng lại tồn kho vào product_variants
                $stmt = $this->pdo->prepare("
                    UPDATE product_variants 
                    SET stock = stock + :quantity 
                    WHERE variant_id = :variant_id
                ");
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                // Tìm variant từ size và color nếu có
                if ($size || $color) {
                    $variant = $this->getVariantByValueNames($productId, $size, $color);
                    if ($variant && isset($variant['variant_id'])) {
                        $variantId = (int)$variant['variant_id'];
                        $stmt = $this->pdo->prepare("
                            UPDATE product_variants 
                            SET stock = stock + :quantity 
                            WHERE variant_id = :variant_id
                        ");
                        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt->bindValue(':variant_id', $variantId, PDO::PARAM_INT);
                        return $stmt->execute();
                    }
                }
                
                // Không có variant, cộng lại tồn kho vào products table
                $stmt = $this->pdo->prepare("
                    UPDATE products 
                    SET stock = stock + :quantity 
                    WHERE product_id = :product_id
                ");
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                return $stmt->execute();
            }
        } catch (Throwable $e) {
            error_log("Error increasing stock: " . $e->getMessage());
            return false;
        }
    }


    public function deleteMany(array $ids): bool
    {
        if (empty($ids)) return false;
        if (!$this->hasDeletedAtColumn()) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE product_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($ids);
    }

    public function restoreMany(array $ids): bool
    {
        if (empty($ids)) return false;
        if (!$this->hasDeletedAtColumn()) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE product_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($ids);
    }

    public function restoreAll(): bool
    {
        if (!$this->hasDeletedAtColumn()) return false;
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE deleted_at IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
    
    public function forceDeleteMany(array $ids): bool
    {
        $success = true;
        foreach ($ids as $id) {
            $result = $this->forceDeleteProduct($id);
            if (!$result) $success = false;
        }
        return $success;
    }
    
    public function emptyTrash(): bool
    {
        if (!$this->hasDeletedAtColumn()) return false;
        $stmt = $this->pdo->query("SELECT product_id FROM {$this->table} WHERE deleted_at IS NOT NULL");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $this->forceDeleteMany($ids);
    }
}
