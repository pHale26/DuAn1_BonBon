<?php

class CouponModel extends BaseModel
{
    private ?bool $hasDeletedAtColumn = null;
    private ?array $existingColumns = null;
    
    public function __construct()
    {
        parent::__construct();
        $this->table = 'coupons';
        $this->ensureDeletedAtColumn();
    }

    private function ensureDeletedAtColumn(): void
    {
        if (!$this->hasDeletedAtColumn()) {
            try {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN deleted_at DATETIME DEFAULT NULL");
                $this->hasDeletedAtColumn = true;
            } catch (PDOException $e) {
                // Ignore error if column already exists or permission denied
            }
        }
    }
    
    /**
     * Kiểm tra xem cột deleted_at có tồn tại không
     * @return bool
     */
    private function hasDeletedAtColumn(): bool
    {
        if ($this->hasDeletedAtColumn === null) {
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'deleted_at'");
                $this->hasDeletedAtColumn = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                $this->hasDeletedAtColumn = false;
            }
        }
        return $this->hasDeletedAtColumn;
    }
    
    /**
     * Lấy danh sách các cột có trong bảng
     * @return array
     */
    private function getExistingColumns(): array
    {
        if ($this->existingColumns === null) {
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table}");
                $this->existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            } catch (PDOException $e) {
                $this->existingColumns = [];
            }
        }
        return $this->existingColumns;
    }
    
    /**
     * Kiểm tra xem cột có tồn tại không
     * @param string $columnName
     * @return bool
     */
    private function hasColumn(string $columnName): bool
    {
        return in_array($columnName, $this->getExistingColumns());
    }

    private function hasUsageTable(): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'coupon_usage'");
            $cache = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $cache = false;
        }
        return $cache;
    }

    private function getUserUsageCount(int $couponId, int $userId): int
    {
        if (!$this->hasUsageTable() || !$userId) {
            return 0;
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM coupon_usage WHERE coupon_id = :cid AND user_id = :uid");
        $stmt->execute(['cid' => $couponId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    public function logUsage(int $couponId, ?int $userId, ?int $orderId, float $discountAmount): void
    {
        if (!$this->hasUsageTable()) {
            return;
        }
        $stmt = $this->pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (:cid, :uid, :oid, :amt)");
        $stmt->execute([
            'cid' => $couponId,
            'uid' => $userId,
            'oid' => $orderId,
            'amt' => $discountAmount
        ]);
    }

    /**
     * Validate mã giảm giá chi tiết, trả về thông điệp lỗi
     */
    public function validateCouponDetailed(
        string $code,
        float $orderAmount,
        ?int $userId = null,
        array $productIds = [],
        array $categoryIds = [],
        bool $isNewCustomer = false,
        bool $hasOtherCoupon = false,
        bool $hasSaleItem = false,
        bool $isVipToday = false
    ): array
    {
        $coupon = $this->getByCode($code, false);
        
        if (!$coupon) {
            return ['ok' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã bị xóa.', 'coupon' => null, 'discount' => null];
        }
        
        $status = $this->calculateStatus($coupon);
        if ($status === 'inactive') {
            return ['ok' => false, 'message' => 'Mã đã ngừng hoạt động.', 'coupon' => null, 'discount' => null];
        }
        if ($status === 'expired') {
            return ['ok' => false, 'message' => 'Mã đã hết hạn.', 'coupon' => null, 'discount' => null];
        }
        if ($status === 'out_of_stock') {
            return ['ok' => false, 'message' => 'Mã đã hết lượt sử dụng.', 'coupon' => null, 'discount' => null];
        }
        if ($status === 'pending') {
            return ['ok' => false, 'message' => 'Mã chưa đến thời gian áp dụng.', 'coupon' => null, 'discount' => null];
        }

        if ($coupon['min_order_amount'] > 0 && $orderAmount < (float)$coupon['min_order_amount']) {
            return ['ok' => false, 'message' => 'Giá trị đơn hàng chưa đạt mức tối thiểu.', 'coupon' => null, 'discount' => null];
        }

        // Không kèm mã khác
        if (!empty($coupon['exclude_other_coupons']) && $hasOtherCoupon) {
            return ['ok' => false, 'message' => 'Mã này không được dùng cùng mã khác.', 'coupon' => null, 'discount' => null];
        }

        // Yêu cầu đăng nhập
        if (!empty($coupon['require_login']) && !$userId) {
            return ['ok' => false, 'message' => 'Mã này yêu cầu bạn đăng nhập.', 'coupon' => null, 'discount' => null];
        }

        // Giới hạn mỗi khách hàng
        if (!empty($coupon['per_user_limit'])) {
            if (!$userId) {
                return ['ok' => false, 'message' => 'Vui lòng đăng nhập để áp dụng mã (giới hạn theo khách).', 'coupon' => null, 'discount' => null];
            }
            $usedByUser = $this->getUserUsageCount((int)$coupon['coupon_id'], $userId);
            if ($usedByUser >= (int)$coupon['per_user_limit']) {
                return ['ok' => false, 'message' => 'Bạn đã dùng hết số lượt cho mã này.', 'coupon' => null, 'discount' => null];
            }
        }
        
        // Khách mới
        if (!empty($coupon['new_customer_only'])) {
            if (!$userId) {
                return ['ok' => false, 'message' => 'Mã chỉ áp dụng cho khách hàng mới, vui lòng đăng nhập.', 'coupon' => null, 'discount' => null];
            }
            if (!$isNewCustomer) {
                return ['ok' => false, 'message' => 'Mã chỉ áp dụng cho khách mới (chưa có đơn giao thành công).', 'coupon' => null, 'discount' => null];
            }
        }

        // Không áp dụng cho sản phẩm đang giảm giá (nếu có cờ)
        if (!empty($coupon['exclude_sale_items']) && $hasSaleItem) {
            return ['ok' => false, 'message' => 'Không áp dụng cho sản phẩm đang giảm giá.', 'coupon' => null, 'discount' => null];
        }

        // Nhóm khách hàng VIP: yêu cầu VIP và chỉ dùng 1 lần/người
        if (!empty($coupon['customer_group']) && $coupon['customer_group'] === 'vip_today') {
            if (!$userId) {
                return ['ok' => false, 'message' => 'Mã VIP yêu cầu đăng nhập.', 'coupon' => null, 'discount' => null];
        }
            if (!$isVipToday) {
                return ['ok' => false, 'message' => 'Mã chỉ áp dụng cho khách VIP (đã có đơn giao thành công từ 2.000.000đ).', 'coupon' => null, 'discount' => null];
            }
            $usedByUserVip = $this->getUserUsageCount((int)$coupon['coupon_id'], $userId);
            if ($usedByUserVip >= 1) {
                return ['ok' => false, 'message' => 'Mã VIP chỉ dùng 1 lần cho mỗi khách hàng.', 'coupon' => null, 'discount' => null];
            }
        }

        $discount = $this->calculateDiscount($coupon, $orderAmount);

        return ['ok' => true, 'message' => null, 'coupon' => $coupon, 'discount' => $discount];
    }

    public function validateCoupon(string $code, float $orderAmount): ?array
    {
        $result = $this->validateCouponDetailed($code, $orderAmount);
        return $result['ok'] ? $result['coupon'] : null;
    }

    /**
     * Tính toán số tiền giảm giá
     * @param array $coupon
     * @param float $orderAmount
     * @return array ['discount_amount' => float, 'final_amount' => float]
     */
    public function calculateDiscount(array $coupon, float $orderAmount): array
    {
        $discountAmount = 0;
        
        if ($coupon['discount_type'] === 'percent') {
            // Giảm theo phần trăm
            $discountAmount = ($orderAmount * (float)$coupon['discount_value']) / 100;
            
            // Áp dụng giới hạn tối đa nếu có
            if ($coupon['max_discount_amount'] !== null) {
                $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
            }
        } else {
            // Giảm cố định
            $discountAmount = (float)$coupon['discount_value'];
        }
        
        // Nếu cấu hình giảm tối đa lớn hơn hoặc bằng giá trị đơn hàng, cho phép giảm về 0
        if ($coupon['max_discount_amount'] !== null && (float)$coupon['max_discount_amount'] >= $orderAmount) {
            $discountAmount = $orderAmount;
        }
        
        // Đảm bảo không giảm quá tổng tiền đơn hàng
        $discountAmount = min($discountAmount, $orderAmount);
        
        $finalAmount = max(0, $orderAmount - $discountAmount);
        
        return [
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * Tăng số lần sử dụng mã giảm giá
     * @param int $couponId
     * @param int $quantity Số lượng đơn hàng sử dụng mã (mặc định 1)
     */
    public function incrementUsage(int $couponId, int $quantity = 1): void
    {
        $sql = "UPDATE {$this->table} 
                SET used_count = used_count + :quantity 
                WHERE coupon_id = :coupon_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'coupon_id' => $couponId,
            'quantity' => $quantity
        ]);
    }

    /**
     * Tính trạng thái mã giảm giá
     * @param array $coupon
     * @return string 'active', 'expired', 'out_of_stock', 'inactive'
     */
    private function calculateStatus(array $coupon): string
    {
        // Nếu status = inactive, trả về inactive
        if ($coupon['status'] === 'inactive') {
            return 'inactive';
        }
        
        // Sử dụng timezone Việt Nam
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now = date('Y-m-d H:i:s');
        
        // Kiểm tra hết hạn (ưu tiên kiểm tra hết hạn trước)
        if ($now > $coupon['end_date']) {
            return 'expired';
        }
        
        // Kiểm tra hết lượt sử dụng (hết mã)
        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            return 'out_of_stock';
        }
        
        // Kiểm tra chưa đến thời gian bắt đầu
        if ($now < $coupon['start_date']) {
            return 'pending'; // Chưa bắt đầu
        }
        
        return 'active';
    }

    /**
     * Lấy tất cả mã giảm giá (cho admin)
     * @param string|null $keyword
     * @param string|null $statusFilter 'active', 'expired', 'out_of_stock', 'inactive'
     * @param string|null $discountTypeFilter 'percentage', 'fixed'
     * @return array
     */
    public function getAll(
        ?string $keyword = null,
        ?string $statusFilter = null,
        ?string $discountTypeFilter = null,
        ?string $createdFrom = null,
        ?string $createdTo = null,
        ?string $durationTypeFilter = null,
        bool $includeDeleted = false
    ): array
    {
        // Kiểm tra xem cột deleted_at có tồn tại không
        $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'deleted_at'");
        $hasDeletedAt = $stmt->rowCount() > 0;
        
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        // Lọc bỏ các mã đã bị xóa mềm (trừ khi yêu cầu hiển thị)
        if ($hasDeletedAt && !$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        if ($keyword) {
            $sql .= " AND (code LIKE :keyword OR name LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }
        
        if ($discountTypeFilter) {
            $sql .= " AND discount_type = :discount_type";
            $params['discount_type'] = $discountTypeFilter;
        }

        // Lọc theo ngày tạo nếu có cột created_at
        if ($this->hasColumn('created_at')) {
            if ($createdFrom) {
                $sql .= " AND created_at >= :created_from";
                $params['created_from'] = $createdFrom . ' 00:00:00';
            }
            if ($createdTo) {
                $sql .= " AND created_at <= :created_to";
                $params['created_to'] = $createdTo . ' 23:59:59';
            }
        }
        
        // Lọc theo thời hạn (vô hạn / có hạn)
        if ($durationTypeFilter) {
            // Sử dụng mốc 50 năm để xác định "vô hạn"
            $fiftyYears = date('Y-m-d H:i:s', strtotime('+50 years'));
            
            if ($durationTypeFilter === 'unlimited') {
                $sql .= " AND end_date > :fifty_years";
                $params['fifty_years'] = $fiftyYears;
            } elseif ($durationTypeFilter === 'limited') {
                $sql .= " AND end_date <= :fifty_years";
                $params['fifty_years'] = $fiftyYears;
            }
        }
        
        // Xây dựng ORDER BY clause dựa trên các cột có sẵn
        $orderByParts = [];
        
        // Kiểm tra và thêm CASE statement nếu các cột cần thiết tồn tại
        if ($this->hasColumn('status') || $this->hasColumn('end_date') || 
            ($this->hasColumn('usage_limit') && $this->hasColumn('used_count'))) {
            
            $caseWhen = "CASE";
            
            if ($this->hasColumn('status')) {
                $caseWhen .= " WHEN status = 'inactive' THEN 3";
            }
            
            if ($this->hasColumn('end_date')) {
                $caseWhen .= " WHEN NOW() > end_date THEN 2";
            }
            
            if ($this->hasColumn('usage_limit') && $this->hasColumn('used_count')) {
                $caseWhen .= " WHEN usage_limit IS NOT NULL AND used_count >= usage_limit THEN 2";
            }
            
            $caseWhen .= " ELSE 1 END";
            $orderByParts[] = $caseWhen;
        }
        
        // Thêm created_at nếu có
        if ($this->hasColumn('created_at')) {
            $orderByParts[] = "created_at DESC";
        } else if ($this->hasColumn('coupon_id')) {
            // Fallback: sắp xếp theo ID nếu không có created_at
            $orderByParts[] = "coupon_id DESC";
        }
        
        if (!empty($orderByParts)) {
            $sql .= " ORDER BY " . implode(", ", $orderByParts);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Thêm trạng thái tính toán vào mỗi coupon
        foreach ($coupons as &$coupon) {
            $coupon['calculated_status'] = $this->calculateStatus($coupon);
        }
        
        // Lọc theo trạng thái tính toán nếu có
        if ($statusFilter) {
            $coupons = array_filter($coupons, function($coupon) use ($statusFilter) {
                return $coupon['calculated_status'] === $statusFilter;
            });
            // Re-index array sau khi filter
            $coupons = array_values($coupons);
        }
        
        return $coupons;
    }

    /**
     * Lấy danh sách mã giảm giá khả dụng cho khách hàng (dựa trên tổng tiền đơn hàng)
     * Tự động ẩn mã hết hạn và hết mã
     * @param float $orderAmount
     * @return array
     */
    public function getAvailableCoupons(float $orderAmount): array
    {
        // Sử dụng timezone Việt Nam
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now = date('Y-m-d H:i:s');
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'active'";
        
        if ($this->hasDeletedAtColumn()) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        // Hiển thị mã chưa hết hạn (kể cả chưa đến thời gian bắt đầu)
        // Chỉ loại bỏ mã đã hết hạn (end_date < now)
        $sql .= " AND end_date >= :now";
        
        // Nếu orderAmount = 0, chỉ lấy mã không yêu cầu đơn tối thiểu
        if ($orderAmount > 0) {
            $sql .= " AND (min_order_amount IS NULL OR min_order_amount = 0 OR min_order_amount <= :order_amount)";
        } else {
            $sql .= " AND (min_order_amount IS NULL OR min_order_amount = 0)";
        }
        
        $sql .= " AND (usage_limit IS NULL OR used_count < usage_limit)
                ORDER BY discount_value DESC, created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'now' => $now,
            'order_amount' => $orderAmount
        ]);
        
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dữ liệu
        $formatted = [];
        foreach ($coupons as $coupon) {
            $discount = $this->calculateDiscount($coupon, $orderAmount);
            $formatted[] = [
                'id' => $coupon['coupon_id'],
                'code' => $coupon['code'],
                'name' => $coupon['name'],
                'description' => null,
                'discount_type' => $coupon['discount_type'],
                'discount_value' => $coupon['discount_value'],
                'min_order_amount' => $coupon['min_order_amount'],
                'max_discount_amount' => $coupon['max_discount_amount'],
                'discount_amount' => $discount['discount_amount'],
                'final_amount' => $discount['final_amount'],
            ];
        }
        
        return $formatted;
    }

    /**
     * Lấy một mã giảm giá theo ID
     * @param int $couponId
     * @param bool $includeDeleted
     * @return array|null
     */
    public function getById(int $couponId, bool $includeDeleted = false): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE coupon_id = :coupon_id";
        
        // Lọc bỏ các mã đã bị xóa mềm (trừ khi yêu cầu hiển thị)
        if ($this->hasDeletedAtColumn() && !$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['coupon_id' => $couponId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Lấy một mã giảm giá theo code
     * @param string $code
     * @param bool $includeDeleted
     * @return array|null
     */
    public function getByCode(string $code, bool $includeDeleted = false): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE code = :code";
        
        // Lọc bỏ các mã đã bị xóa mềm (trừ khi yêu cầu hiển thị)
        if ($this->hasDeletedAtColumn() && !$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['code' => strtoupper(trim($code))]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Tạo mã giảm giá mới
     * @param array $data
     * @return int coupon_id
     */
    public function create(array $data): int
    {
        // Loại bỏ PRIMARY KEY khỏi data
        $data = $this->removePrimaryKeyFromData($data, $this->table);
        
        // Nếu là giảm giá cố định, không cho phép max_discount_amount
        if ($data['discount_type'] === 'fixed') {
            $data['max_discount_amount'] = null;
        }
        
        $sql = "INSERT INTO {$this->table} 
                (code, name, discount_type, discount_value, min_order_amount, 
                 max_discount_amount, start_date, end_date, usage_limit, status) 
                VALUES 
                (:code, :name, :discount_type, :discount_value, :min_order_amount, 
                 :max_discount_amount, :start_date, :end_date, :usage_limit, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'code' => strtoupper(trim($data['code'])),
            'name' => $data['name'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'max_discount_amount' => $data['max_discount_amount'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'usage_limit' => $data['usage_limit'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật mã giảm giá
     * @param int $couponId
     * @param array $data
     */
    public function update(int $couponId, array $data): void
    {
        // Nếu là giảm giá cố định, không cho phép max_discount_amount
        if ($data['discount_type'] === 'fixed') {
            $data['max_discount_amount'] = null;
        }
        
        $sql = "UPDATE {$this->table} SET 
                code = :code,
                name = :name,
                discount_type = :discount_type,
                discount_value = :discount_value,
                min_order_amount = :min_order_amount,
                max_discount_amount = :max_discount_amount,
                start_date = :start_date,
                end_date = :end_date,
                usage_limit = :usage_limit,
                status = :status
                WHERE coupon_id = :coupon_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'coupon_id' => $couponId,
            'code' => strtoupper(trim($data['code'])),
            'name' => $data['name'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'max_discount_amount' => $data['max_discount_amount'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'usage_limit' => $data['usage_limit'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Cập nhật trạng thái nhanh
     */
    public function updateStatusOnly(int $couponId, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = :status WHERE coupon_id = :id");
        $stmt->execute([
            'status' => $status,
            'id' => $couponId,
        ]);
    }

    /**
     * Xóa mềm mã giảm giá (soft delete)
     * @param int $couponId
     */
    public function delete(int $couponId): void
    {
        $this->ensureDeletedAtColumn();
        
        if ($this->hasDeletedAtColumn()) {
            $sql = "UPDATE {$this->table} 
                    SET deleted_at = NOW() 
                    WHERE coupon_id = :coupon_id 
                      AND deleted_at IS NULL";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['coupon_id' => $couponId]);
        } else {
            // Nếu chưa có cột deleted_at, không xóa vĩnh viễn mà báo lỗi để an toàn
            throw new Exception("Lỗi hệ thống: Bảng coupons chưa hỗ trợ thùng rác (thiếu cột deleted_at). Vui lòng báo quản trị viên.");
        }
    }
    
    /**
     * Khôi phục mã giảm giá từ thùng rác
     * @param int $couponId
     */
    public function restore(int $couponId): void
    {
        if (!$this->hasDeletedAtColumn()) {
            return; // Không thể khôi phục nếu chưa có cột deleted_at
        }
        
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NULL 
                WHERE coupon_id = :coupon_id 
                  AND deleted_at IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['coupon_id' => $couponId]);
    }
    
    /**
     * Xóa vĩnh viễn mã giảm giá (hard delete)
     * @param int $couponId
     */
    public function forceDelete(int $couponId): void
    {
        $sql = "DELETE FROM {$this->table} WHERE coupon_id = :coupon_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['coupon_id' => $couponId]);
    }
    
    /**
     * Lấy danh sách mã giảm giá đã bị xóa (thùng rác)
     * @return array
     */
    public function getDeleted(): array
    {
        if (!$this->hasDeletedAtColumn()) {
            return []; // Trả về mảng rỗng nếu chưa có cột deleted_at
        }
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE deleted_at IS NOT NULL
                ORDER BY deleted_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Thêm trạng thái tính toán vào mỗi coupon
        foreach ($coupons as &$coupon) {
            $coupon['calculated_status'] = $this->calculateStatus($coupon);
        }
        
        return $coupons;
    }

    /**
     * Thống kê tổng quan về mã giảm giá trong một khoảng thời gian.
     *
     * - Nếu có bảng `coupon_usage` sẽ thống kê theo bảng này,
     *   nếu không sẽ fallback sang bảng `orders_new`.
     * - Trả về:
     *   + usage_count   : tổng số lượt dùng mã trong khoảng thời gian.
     *   + total_discount: tổng số tiền đã giảm cho khách.
     *   + expired_count : số mã đã hết hạn (tính tại thời điểm hiện tại).
     * - Được dùng ở `AdminStatisticsController` để hiển thị box
     *   "Thống Kê Mã Giảm Giá" trong trang thống kê admin.
     */
    public function getCouponStats(string $fromDate, string $toDate): array
    {
        try {
            // Lấy số lượt sử dụng và tổng tiền giảm
            if ($this->hasUsageTable()) {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) AS usage_count,
                        COALESCE(SUM(discount_amount), 0) AS total_discount
                    FROM coupon_usage cu
                    JOIN orders_new o ON o.id = cu.order_id
                    WHERE DATE(o.created_at) BETWEEN :from_date AND :to_date
                ");
                $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // Fallback: lấy từ orders_new (bao gồm cả đơn có coupon_code hoặc discount_amount > 0)
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) AS usage_count,
                        COALESCE(SUM(discount_amount), 0) AS total_discount
                    FROM orders_new
                    WHERE (coupon_id IS NOT NULL OR coupon_code IS NOT NULL OR discount_amount > 0)
                    AND DATE(created_at) BETWEEN :from_date AND :to_date
                ");
                $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Đếm số mã đã hết hạn
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS expired_count
                FROM {$this->table}
                WHERE end_date < NOW()
                AND status = 'active'
            ");
            $stmt->execute();
            $expired = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'usage_count' => (int)($result['usage_count'] ?? 0),
                'total_discount' => (float)($result['total_discount'] ?? 0),
                'expired_count' => (int)($expired['expired_count'] ?? 0)
            ];
        } catch (Exception $e) {
            return [
                'usage_count' => 0,
                'total_discount' => 0,
                'expired_count' => 0
            ];
        }
    }

    /**
     * Lấy danh sách top mã giảm giá được sử dụng nhiều nhất.
     *
     * - Ưu tiên đọc từ bảng `coupon_usage`, nếu không có thì lấy trực tiếp từ `orders_new`.
     * - Dùng cho bảng "Top 5 Mã Được Dùng Nhiều" trong phần thống kê mã giảm giá.
     */
    public function getTopUsedCoupons(string $fromDate, string $toDate, int $limit = 5): array
    {
        try {
            if ($this->hasUsageTable()) {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        c.code,
                        c.name,
                        COUNT(*) AS usage_count,
                        COALESCE(SUM(cu.discount_amount), 0) AS total_discount
                    FROM coupon_usage cu
                    JOIN {$this->table} c ON c.coupon_id = cu.coupon_id
                    JOIN orders_new o ON o.id = cu.order_id
                    WHERE DATE(o.created_at) BETWEEN :from_date AND :to_date
                    GROUP BY c.coupon_id, c.code, c.name
                    ORDER BY usage_count DESC
                    LIMIT :limit
                ");
            } else {
                // Fallback: lấy từ orders_new (bao gồm cả đơn có coupon_code hoặc discount_amount > 0)
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COALESCE(coupon_code, 'N/A') AS code,
                        COALESCE(coupon_name, 'Mã giảm giá') AS name,
                        COUNT(*) AS usage_count,
                        COALESCE(SUM(discount_amount), 0) AS total_discount
                    FROM orders_new
                    WHERE (coupon_id IS NOT NULL OR coupon_code IS NOT NULL OR discount_amount > 0)
                    AND DATE(created_at) BETWEEN :from_date AND :to_date
                    GROUP BY coupon_code, coupon_name
                    ORDER BY usage_count DESC
                    LIMIT :limit
                ");
            }
            
            $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
            $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function deleteMany(array $ids): bool
    {
        if (empty($ids)) return false;
        
        $this->ensureDeletedAtColumn();
        
        if (!$this->hasDeletedAtColumn()) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE coupon_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($ids);
    }

    public function restoreMany(array $ids): bool
    {
        if (empty($ids) || !$this->hasDeletedAtColumn()) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE coupon_id IN ($placeholders)";
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
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE coupon_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($ids);
    }
    
    public function emptyTrash(): bool
    {
        if (!$this->hasDeletedAtColumn()) return false;
        $sql = "DELETE FROM {$this->table} WHERE deleted_at IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
}

