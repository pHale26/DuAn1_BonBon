<?php

class OrderModel extends BaseModel
{
    // Các hằng trạng thái của đơn hàng được dùng xuyên suốt hệ thống
    public const STATUS_UNPAID         = 'unpaid';          // Chờ thanh toán
    public const STATUS_PAID           = 'paid';            // Đã thanh toán
    public const STATUS_PAYMENT_FAILED = 'payment_failed';  // Thanh toán thất bại
    public const STATUS_PENDING        = 'pending';         // Chờ shop xác nhận
    public const STATUS_CONFIRMED      = 'confirmed';       // Xác nhận đơn hàng
    public const STATUS_PREPARING      = 'preparing';       // Đang chuẩn bị đơn hàng
    public const STATUS_HANDED_TO_SHIPPER = 'handed_to_shipper'; // Đã giao cho đơn vị vận chuyển
    public const STATUS_SHIPPING       = 'shipping';        // Đang vận chuyển đơn hàng
    public const STATUS_TO_SHIP        = 'to_ship';         // Đang giao (chuẩn bị/ giao) - giữ lại để tương thích
    public const STATUS_DELIVERED      = 'delivered';       // Đã giao
    public const STATUS_COMPLETED      = 'completed';       // Hoàn thành (user xác nhận đã nhận)
    public const STATUS_CANCELLED      = 'cancelled';       // Đã hủy
    public const STATUS_RETURNED       = 'returned';        // Trả hàng/Hoàn tiền
    public const STATUS_CANCEL_REQUEST = 'cancel_requested'; // Người dùng yêu cầu hủy

    // Map trạng thái -> nội dung tiếng Việt hiển thị ngoài giao diện
    private const STATUS_LABELS = [
        self::STATUS_UNPAID         => 'Chờ Thanh Toán',
        self::STATUS_PAID           => 'Đã Thanh Toán',
        self::STATUS_PAYMENT_FAILED => 'Thanh Toán Thất Bại',
        self::STATUS_PENDING        => 'Chờ Xác Nhận',
        self::STATUS_CONFIRMED      => 'Đã Xác Nhận',
        self::STATUS_PREPARING      => 'Đang Chuẩn Bị',
        self::STATUS_HANDED_TO_SHIPPER => 'Đã Giao Vận Chuyển',
        self::STATUS_SHIPPING       => 'Đang Vận Chuyển',
        self::STATUS_TO_SHIP        => 'Đang Giao',
        self::STATUS_DELIVERED      => 'Đã Giao Hàng',
        self::STATUS_COMPLETED      => 'Hoàn Thành',
        self::STATUS_CANCELLED      => 'Đã Hủy',
        self::STATUS_RETURNED       => 'Trả Hàng / Hoàn Tiền',
        self::STATUS_CANCEL_REQUEST => 'Yêu Cầu Hủy',
    ];

    // Map trạng thái -> màu sắc badge Bootstrap (phục vụ view)
    private const STATUS_BADGES = [
        self::STATUS_UNPAID         => 'warning',          // chờ thanh toán
        self::STATUS_PAID           => 'primary',          // đã thanh toán online
        self::STATUS_PAYMENT_FAILED => 'danger',
        self::STATUS_PENDING        => 'secondary',        // chờ shop xác nhận
        self::STATUS_CONFIRMED      => 'info',             // đã xác nhận (màu nhạt)
        self::STATUS_PREPARING      => 'primary',          // đang chuẩn bị (màu đậm)
        self::STATUS_HANDED_TO_SHIPPER => 'warning',        // giao cho shipper
        self::STATUS_SHIPPING       => 'secondary',        // đang vận chuyển
        self::STATUS_TO_SHIP        => 'secondary',        // tương thích với trạng thái cũ
        self::STATUS_DELIVERED      => 'success',          // đã giao hàng
        self::STATUS_COMPLETED      => 'success',          // hoàn thành
        self::STATUS_CANCELLED      => 'danger',
        self::STATUS_RETURNED       => 'dark',
        self::STATUS_CANCEL_REQUEST => 'warning',
    ];

    public function __construct()
    {
        parent::__construct();
        // Lưu ý: Cấu trúc bảng đã được tách sang file database_schema_orders.txt
    }

    // Dùng ở dropdown để lấy toàn bộ trạng thái có thể chọn
    public static function statuses(): array
    {
        return self::STATUS_LABELS;
    }

    // Chuyển mã trạng thái -> chuỗi tiếng Việt
    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? 'Không xác định';
    }

    // Chuyển mã trạng thái -> màu nền badge
    public static function statusBadge(string $status): string
    {
        return self::STATUS_BADGES[$status] ?? 'secondary';
    }

    /**
     * Kiểm tra chuyển trạng thái hợp lệ (không nhảy cóc / lùi trạng thái)
     */
    public static function isValidTransition(string $current, string $target, string $paymentMethod = 'cod'): bool
    {
        if ($current === $target) {
            return true;
        }

        // Map chuyển trạng thái cho đơn online (thanh toán trước)
        $online = [
            self::STATUS_UNPAID         => [self::STATUS_PAID, self::STATUS_PAYMENT_FAILED, self::STATUS_CANCELLED],
            self::STATUS_PAYMENT_FAILED => [self::STATUS_UNPAID, self::STATUS_CANCELLED],
            self::STATUS_PAID           => [self::STATUS_PENDING],
            self::STATUS_PENDING        => [self::STATUS_CONFIRMED, self::STATUS_CANCEL_REQUEST],
            self::STATUS_CONFIRMED      => [self::STATUS_PREPARING],
            self::STATUS_PREPARING      => [self::STATUS_HANDED_TO_SHIPPER],
            self::STATUS_HANDED_TO_SHIPPER => [self::STATUS_SHIPPING, self::STATUS_CANCELLED], // Admin có thể hủy ở trạng thái này
            self::STATUS_SHIPPING       => [self::STATUS_DELIVERED],
            self::STATUS_CANCEL_REQUEST => [self::STATUS_CANCELLED],
            self::STATUS_TO_SHIP        => [self::STATUS_DELIVERED, self::STATUS_HANDED_TO_SHIPPER], // Tương thích với status cũ
            self::STATUS_DELIVERED      => [self::STATUS_COMPLETED, self::STATUS_RETURNED],
            self::STATUS_COMPLETED      => [self::STATUS_RETURNED],
            self::STATUS_CANCELLED      => [],
            self::STATUS_RETURNED       => [],
        ];

        // Map chuyển trạng thái cho đơn COD (bỏ qua unpaid/paid/payment_failed)
        $cod = [
            self::STATUS_PENDING        => [self::STATUS_CONFIRMED, self::STATUS_CANCEL_REQUEST],
            self::STATUS_CONFIRMED      => [self::STATUS_PREPARING],
            self::STATUS_PREPARING      => [self::STATUS_HANDED_TO_SHIPPER],
            self::STATUS_HANDED_TO_SHIPPER => [self::STATUS_SHIPPING, self::STATUS_CANCELLED], // Admin có thể hủy ở trạng thái này
            self::STATUS_SHIPPING       => [self::STATUS_DELIVERED],
            self::STATUS_CANCEL_REQUEST => [self::STATUS_CANCELLED],
            self::STATUS_TO_SHIP        => [self::STATUS_DELIVERED, self::STATUS_HANDED_TO_SHIPPER], // Tương thích với status cũ
            self::STATUS_DELIVERED      => [self::STATUS_COMPLETED, self::STATUS_RETURNED],
            self::STATUS_COMPLETED      => [self::STATUS_RETURNED],
            self::STATUS_CANCELLED      => [],
            self::STATUS_RETURNED       => [],
        ];

        $isCod = strtolower($paymentMethod) === 'cod';

        // Nếu COD, không cho chuyển tới/ra các trạng thái thanh toán online
        if ($isCod) {
            if (in_array($target, [self::STATUS_UNPAID, self::STATUS_PAID, self::STATUS_PAYMENT_FAILED], true)) {
                return false;
            }
            $map = $cod;
        } else {
            $map = $online;
        }

        // Nếu current không nằm trong map, coi như không hợp lệ
        if (!isset($map[$current])) {
            return false;
        }

        return in_array($target, $map[$current], true);
    }

    // Tạo mới đơn hàng + danh sách sản phẩm con
    public function create(array $orderData, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            // Loại bỏ PRIMARY KEY khỏi orderData bằng helper function
            // Truyền tên bảng để xác định chính xác PRIMARY KEY
            $orderData = $this->removePrimaryKeyFromData($orderData, 'orders_new');
            
            // Nếu chưa truyền total_amount thì tự tính từ danh sách item
            $total = $orderData['total_amount'] ?? 0;
            if (!$total) {
                $total = array_reduce($items, function ($carry, $item) {
                    return $carry + ($item['unit_price'] * $item['quantity']);
                }, 0);
            }

            // Luôn sử dụng bảng 'orders_new' vì có đầy đủ các cột cần thiết
            $tableName = 'orders_new';
            $primaryKeyColumn = 'id'; // PRIMARY KEY của orders_new là 'id'
            
            // Định nghĩa rõ ràng danh sách cột được phép INSERT (KHÔNG BAO GỒM PRIMARY KEY 'id')
            // Danh sách này dựa trên cấu trúc bảng orders_new
            $allowedColumns = [
                'order_code', 'user_id', 'fullname', 'email', 'phone', 'address',
                'city', 'district', 'ward', 'note', 'payment_method', 'status',
                'total_amount', 'coupon_id', 'discount_amount', 'coupon_code', 'coupon_name'
            ];
            
            // Xây dựng danh sách cột và giá trị để INSERT
            $insertColumns = [];
            $insertValues = [];
            $orderPayload = [];
            
            // Thêm các cột bắt buộc
            if (isset($orderData['user_id'])) {
                $insertColumns[] = 'user_id';
                $insertValues[] = ':user_id';
                $orderPayload[':user_id'] = $orderData['user_id'];
            }
            
            if (isset($orderData['fullname'])) {
                $insertColumns[] = 'fullname';
                $insertValues[] = ':fullname';
                $orderPayload[':fullname'] = $orderData['fullname'];
            }
            
            if (isset($orderData['email'])) {
                $insertColumns[] = 'email';
                $insertValues[] = ':email';
                $orderPayload[':email'] = $orderData['email'];
            }
            
            if (isset($orderData['phone'])) {
                $insertColumns[] = 'phone';
                $insertValues[] = ':phone';
                $orderPayload[':phone'] = $orderData['phone'];
            }
            
            if (isset($orderData['address'])) {
                $insertColumns[] = 'address';
                $insertValues[] = ':address';
                $orderPayload[':address'] = $orderData['address'];
            }
            
            $insertColumns[] = 'payment_method';
            $insertValues[] = ':payment_method';
            $orderPayload[':payment_method'] = $orderData['payment_method'] ?? 'cod';
            
            $insertColumns[] = 'status';
            $insertValues[] = ':status';
            $orderPayload[':status'] = $orderData['status'] ?? self::STATUS_UNPAID;
            
            $insertColumns[] = 'total_amount';
            $insertValues[] = ':total_amount';
            $orderPayload[':total_amount'] = $total;
            
            // Thêm các cột tùy chọn
            $optionalData = [
                'order_code' => $orderData['order_code'] ?? $this->generateOrderCode(),
                'city' => $orderData['city'] ?? null,
                'district' => $orderData['district'] ?? null,
                'ward' => $orderData['ward'] ?? null,
                'note' => $orderData['note'] ?? null,
                'coupon_id' => $orderData['coupon_id'] ?? null,
                'discount_amount' => $orderData['discount_amount'] ?? 0,
                'coupon_code' => $orderData['coupon_code'] ?? null,
                'coupon_name' => $orderData['coupon_name'] ?? null,
            ];
            
            foreach ($optionalData as $col => $value) {
                // Chỉ thêm nếu cột nằm trong danh sách được phép và không phải PRIMARY KEY
                if (in_array($col, $allowedColumns) && $col !== $primaryKeyColumn) {
                    $insertColumns[] = $col;
                    $insertValues[] = ':' . $col;
                    $orderPayload[':' . $col] = $value;
                }
            }
            
            // KIỂM TRA CUỐI CÙNG: Đảm bảo KHÔNG có PRIMARY KEY trong insertColumns
            foreach ($insertColumns as $col) {
                if ($col === 'id' || $col === 'order_id' || $col === $primaryKeyColumn) {
                    throw new Exception("LỖI NGHIÊM TRỌNG: PRIMARY KEY '$col' đã được thêm vào INSERT statement!");
                }
            }
            
            // Validate: Phải có ít nhất một cột
            if (empty($insertColumns)) {
                throw new Exception("LỖI: Không có cột nào để INSERT vào bảng $tableName!");
            }
            
            // Xây dựng SQL statement
            $columnsStr = implode(', ', $insertColumns);
            $valuesStr = implode(', ', $insertValues);
            $sql = "INSERT INTO $tableName ($columnsStr) VALUES ($valuesStr)";
            
            error_log("OrderModel::create - SQL: $sql");
            error_log("OrderModel::create - Columns: " . implode(', ', $insertColumns));
            
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute($orderPayload);
            $orderId = (int)$this->pdo->lastInsertId();
            
            if ($orderId === 0) {
                error_log('OrderModel::create - WARNING: lastInsertId() returned 0. This might indicate an issue with AUTO_INCREMENT.');
                
                // Kiểm tra xem có bản ghi với id = 0 không và xóa nó
                $this->fixZeroIdRecord($tableName, $primaryKeyColumn);
                
                // Đảm bảo AUTO_INCREMENT được bật
                if ($primaryKeyColumn) {
                    $this->ensureAutoIncrement($tableName, $primaryKeyColumn);
                }
                
                // Thử lấy ID bằng cách khác
                $stmt = $this->pdo->query("SELECT LAST_INSERT_ID() as id");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && isset($result['id']) && $result['id'] > 0) {
                    $orderId = (int)$result['id'];
                    error_log('OrderModel::create - Got ID from LAST_INSERT_ID(): ' . $orderId);
                } else {
                    // Thử lấy ID từ bản ghi vừa insert bằng cách khác
                    if ($primaryKeyColumn) {
                        $stmt = $this->pdo->query("SELECT MAX({$primaryKeyColumn}) as max_id FROM $tableName");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result && isset($result['max_id']) && $result['max_id'] > 0) {
                            $orderId = (int)$result['max_id'];
                            error_log('OrderModel::create - Got ID from MAX(): ' . $orderId);
                        } else {
                            throw new Exception('Không thể lấy ID của đơn hàng vừa tạo. Có thể do lỗi AUTO_INCREMENT hoặc có bản ghi với id = 0. Vui lòng kiểm tra cấu hình database.');
                        }
                    } else {
                        throw new Exception('Không thể lấy ID của đơn hàng vừa tạo. Không tìm thấy PRIMARY KEY column.');
                    }
                }
            }
            
            error_log('OrderModel::create - Created order with ID: ' . $orderId);

            // Chuẩn bị statement để loop thêm từng sản phẩm
            $itemStmt = $this->pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, variant_size, variant_color, quantity, unit_price, image_url
                )
                VALUES (
                    :order_id, :product_id, :product_name, :variant_size, :variant_color, :quantity, :unit_price, :image_url
                )
            ");

            // Lưu ý: Stock đã được trừ khi thêm vào giỏ hàng, nên không cần trừ lại khi tạo đơn hàng
            // Stock sẽ được cộng lại khi hủy đơn hàng (xem method restoreStockForOrder)
            
            foreach ($items as $item) {
                $itemStmt->execute([
                    ':order_id'      => $orderId,
                    ':product_id'    => $item['product_id'] ?? null,
                    ':product_name'  => $item['product_name'],
                    ':variant_size'  => $item['variant_size'] ?? null,
                    ':variant_color' => $item['variant_color'] ?? null,
                    ':quantity'      => $item['quantity'],
                    ':unit_price'    => $item['unit_price'],
                    ':image_url'     => $item['image_url'] ?? null,
                ]);
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    // Lấy lịch sử đơn hàng dành cho user
    public function getHistory(?int $userId, ?string $email): array
    {
        // Nếu không có cả user_id và email, trả về mảng rỗng
        if (!$userId && !$email) {
            error_log("OrderModel::getHistory - No user_id or email provided");
            return [];
        }
        
        // Sử dụng bảng orders_new với PRIMARY KEY là 'id'
        // Tìm theo user_id HOẶC email (nếu user_id không khớp thì vẫn tìm được theo email)
        $query = "SELECT * FROM orders_new WHERE (";
        $params = [];
        $conditions = [];
        
        if ($userId) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        if ($email) {
            $conditions[] = "email = :email";
            $params[':email'] = $email;
        }
        
        // Nếu có cả user_id và email, dùng OR để tìm theo cả hai
        // Nếu chỉ có một trong hai, chỉ tìm theo cái đó
        if (count($conditions) > 0) {
            $query .= implode(" OR ", $conditions);
        } else {
            error_log("OrderModel::getHistory - No valid conditions");
            return [];
        }
        
        $query .= ") ORDER BY id DESC";
        
        error_log("OrderModel::getHistory - Query: $query");
        error_log("OrderModel::getHistory - Params: " . json_encode($params));

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            error_log("OrderModel::getHistory - Found " . count($results) . " orders");
            
            return $results;
        } catch (PDOException $e) {
            error_log("OrderModel::getHistory - Error: " . $e->getMessage());
            return [];
        }
    }

    // Đếm số đơn trong ngày (theo created_at) của user
    public function countOrdersToday(int $userId): int
    {
        if (!$userId) {
            return 0;
        }
        $sql = "SELECT COUNT(*) AS cnt FROM orders_new WHERE user_id = :uid AND DATE(created_at) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    // Đếm số đơn đã giao thành công của user
    // CẬP NHẬT: Đếm tất cả các đơn hàng được xem là "hợp lệ" / "đã mua"
    // Gồm: tất cả các trạng thái trừ Cancelled, Payment Failed, và Unpaid
    public function countValidOrders(int $userId): int
    {
        if (!$userId) {
            return 0;
        }
        // Danh sách các trạng thái coi là "đơn hàng thành công" để xác định khách cũ
        $validStatuses = [
            self::STATUS_PAID,
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_HANDED_TO_SHIPPER,
            self::STATUS_SHIPPING,
            self::STATUS_TO_SHIP,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_RETURNED // Đã từng mua, dù hoàn trả cũng không phải khách mới tinh
        ];
        
        // Tạo chuỗi placeholder cho IN clause
        $placeholders = implode(',', array_fill(0, count($validStatuses), '?'));
        
        $sql = "SELECT COUNT(*) AS cnt FROM orders_new WHERE user_id = ? AND status IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        
        // Merge userId vào mảng params
        $params = array_merge([$userId], $validStatuses);
        
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    // Giữ lại hàm cũ để tương thích ngược nếu có dùng ở đâu đó, nhưng trỏ về hàm mới logic rộng hơn
    public function countDeliveredOrders(int $userId): int
    {
        return $this->countValidOrders($userId);
    }

    // Kiểm tra user đã có đơn giao thành công với tổng tiền >= ngưỡng chưa
    public function hasDeliveredOrderOverAmount(int $userId, float $amountThreshold): bool
    {
        if (!$userId) {
            return false;
        }
        $sql = "SELECT COUNT(*) AS cnt FROM orders_new WHERE user_id = :uid AND status = :status AND total_amount >= :amt LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'status' => self::STATUS_DELIVERED,
            'amt' => $amountThreshold
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    // Lấy thông tin đơn hàng + danh sách sản phẩm (dùng cho chi tiết)
    public function findWithItems(int $orderId): ?array
    {
        // Sử dụng bảng orders_new với PRIMARY KEY là 'id'
        $stmt = $this->pdo->prepare("SELECT * FROM orders_new WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getItems($orderId);
        return $order;
    }

    // Lấy danh sách sản phẩm con trong 1 đơn
    public function getItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    // Trang admin: lấy tất cả đơn + lọc theo keyword/status
    public function getAll(?string $keyword = null, ?string $status = null): array
    {
        // Sử dụng bảng orders_new
        $query = "SELECT * FROM orders_new WHERE 1=1";
        $params = [];

        if ($keyword) {
            // Tìm kiếm theo fullname, phone, hoặc order_code
            $query .= " AND (fullname LIKE :keyword OR phone LIKE :keyword OR order_code LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($status) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }

        // Sắp xếp theo id (mới nhất trước)
        $query .= " ORDER BY id DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Admin đổi trạng thái đơn
    public function updateStatus(int $orderId, string $status): bool
    {
        if (!isset(self::STATUS_LABELS[$status])) {
            throw new InvalidArgumentException('Trạng thái không hợp lệ.');
        }

        // Lấy đơn hàng hiện tại để tránh tạo thông báo trùng và có dữ liệu user/order_code
        $order = $this->findWithItems($orderId);
        $previousStatus = $order['status'] ?? null;
        if ($previousStatus === $status) {
            return true;
        }

        // Sử dụng bảng orders_new với PRIMARY KEY là 'id'
        $stmt = $this->pdo->prepare("
            UPDATE orders_new 
            SET status = :status, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");

        $updated = $stmt->execute([
            ':status' => $status,
            ':id'     => $orderId,
        ]);

        // Tự động tạo thông báo thay đổi trạng thái đơn cho khách hàng
        if ($updated && $order) {
            try {
                $statusLabel = self::statusLabel($status);
                $orderCode = $order['order_code'] ?? ('#' . $orderId);
                $notificationModel = new NotificationModel();

                // xác định user_id: ưu tiên user_id, fallback email
                $targetUserId = (int)($order['user_id'] ?? 0);
                if ($targetUserId <= 0 && !empty($order['email'])) {
                    $userModel = new UserModel();
                    $found = $userModel->findByEmail($order['email']);
                    if ($found) {
                        $targetUserId = (int)($found['user_id'] ?? $found['id'] ?? 0);
                    }
                }
                if ($targetUserId > 0) {
                    $notificationModel->create(
                        $targetUserId,
                        'order_status',
                        "Đơn {$orderCode} cập nhật trạng thái",
                        "Trạng thái mới: {$statusLabel}",
                        BASE_URL . '?action=order-detail&id=' . $orderId,
                        [
                            'order_id' => $orderId,
                            'order_code' => $order['order_code'] ?? null,
                            'status' => $status,
                            'status_label' => $statusLabel,
                            'payment_method' => $order['payment_method'] ?? null,
                        ]
                    );
                }
            } catch (Throwable $e) {
                error_log('OrderModel::updateStatus notification error: ' . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Tự động chuyển đơn "Đã Giao" (delivered) sang "Hoàn Thành" (completed)
     * nếu khách hàng không bấm xác nhận sau 3 ngày.
     * Nên gọi method này khi load danh sách đơn hàng (admin/user).
     */
    public function autoCompleteDeliveredOrders(int $days = 3): int
    {
        try {
            // Lấy các đơn delivered quá hạn trước khi update (để gửi notification)
            $days = (int) $days; // đảm bảo an toàn
            $stmt = $this->pdo->prepare("
                SELECT id, order_code, user_id, email, payment_method 
                FROM orders_new 
                WHERE status = :delivered 
                  AND updated_at <= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            ");
            $stmt->execute([
                ':delivered' => self::STATUS_DELIVERED,
            ]);
            $expiredOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($expiredOrders)) {
                return 0;
            }

            // Cập nhật hàng loạt sang COMPLETED
            $updateStmt = $this->pdo->prepare("
                UPDATE orders_new 
                SET status = :completed, updated_at = CURRENT_TIMESTAMP 
                WHERE status = :delivered 
                  AND updated_at <= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            ");
            $updateStmt->execute([
                ':completed' => self::STATUS_COMPLETED,
                ':delivered' => self::STATUS_DELIVERED,
            ]);
            $count = $updateStmt->rowCount();

            // Gửi thông báo cho từng khách hàng
            if ($count > 0) {
                try {
                    $notificationModel = new NotificationModel();
                    foreach ($expiredOrders as $order) {
                        $targetUserId = (int)($order['user_id'] ?? 0);
                        if ($targetUserId <= 0 && !empty($order['email'])) {
                            $userModel = new UserModel();
                            $found = $userModel->findByEmail($order['email']);
                            if ($found) {
                                $targetUserId = (int)($found['user_id'] ?? $found['id'] ?? 0);
                            }
                        }
                        if ($targetUserId > 0) {
                            $orderCode = $order['order_code'] ?? ('#' . $order['id']);
                            $notificationModel->create(
                                $targetUserId,
                                'order_status',
                                "Đơn {$orderCode} đã tự động hoàn thành",
                                "Đơn hàng đã được tự động xác nhận hoàn thành sau {$days} ngày giao hàng.",
                                BASE_URL . '?action=order-detail&id=' . $order['id'],
                                [
                                    'order_id' => $order['id'],
                                    'order_code' => $order['order_code'] ?? null,
                                    'status' => self::STATUS_COMPLETED,
                                    'status_label' => self::statusLabel(self::STATUS_COMPLETED),
                                    'auto_completed' => true,
                                ]
                            );
                        }
                    }
                } catch (Throwable $e) {
                    error_log('OrderModel::autoCompleteDeliveredOrders notification error: ' . $e->getMessage());
                }
            }

            return $count;
        } catch (Throwable $e) {
            error_log('OrderModel::autoCompleteDeliveredOrders error: ' . $e->getMessage());
            return 0;
        }
    }

    // Người dùng hủy đơn (ghi nhận lý do nếu có)
    public function cancel(int $orderId, ?string $reason = null): bool
    {
        // Sử dụng bảng orders_new với PRIMARY KEY là 'id'
        $stmt = $this->pdo->prepare("
            UPDATE orders_new 
            SET status = :status, cancel_reason = :reason, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");

        return $stmt->execute([
            ':status' => self::STATUS_CANCELLED,
            ':reason' => $reason,
            ':id'     => $orderId,
        ]);
    }

    // Điều kiện cho phép hủy đơn - chỉ được hủy khi đơn chưa được giao (cho user)
    public function canCancel(array $order): bool
    {
        // Cho phép hủy các trạng thái trước khi giao hàng
        return in_array($order['status'], [
            self::STATUS_UNPAID,
            self::STATUS_PAYMENT_FAILED,
            self::STATUS_PAID,
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_TO_SHIP,
            self::STATUS_HANDED_TO_SHIPPER
        ], true);
    }

    // Điều kiện cho phép admin hủy đơn - chỉ được hủy TRƯỚC KHI giao cho đơn vị vận chuyển
    public function canCancelByAdmin(array $order): bool
    {
        // Cho phép hủy ở các trạng thái trước khi giao cho đơn vị vận chuyển
        return in_array($order['status'], [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
        ], true);
    }

    // Lưu lý do hủy đơn (nếu cột tồn tại)
    public function saveCancelReason(int $orderId, ?string $reason): void
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE orders_new SET cancel_reason = :reason WHERE id = :id");
            $stmt->execute([':reason' => $reason, ':id' => $orderId]);
        } catch (Throwable $e) {
            // Bỏ qua nếu bảng không có cột cancel_reason
        }
    }

    // Khôi phục tồn kho khi hủy đơn hàng
    public function restoreStock(int $orderId): void
    {
        try {
            // Lấy tất cả items của đơn hàng
            $items = $this->getItems($orderId);
            
            if (empty($items)) {
                return;
            }
            
            // Load ProductModel để khôi phục tồn kho
            require_once PATH_MODEL . 'ProductModel.php';
            $productModel = new ProductModel();
            
            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                $size = $item['variant_size'] ?? null;
                $color = $item['variant_color'] ?? null;
                
                if ($productId > 0 && $quantity > 0) {
                    // Tìm variant_id từ size và color
                    $variant = $productModel->getVariantByValueNames($productId, $size, $color);
                    
                    if ($variant && isset($variant['variant_id'])) {
                        // Khôi phục tồn kho từ product_variants
                        $variantId = (int)$variant['variant_id'];
                        $updateStmt = $this->pdo->prepare("
                            UPDATE product_variants 
                            SET stock = stock + :quantity 
                            WHERE variant_id = :variant_id
                        ");
                        $updateStmt->execute([
                            ':quantity' => $quantity,
                            ':variant_id' => $variantId
                        ]);
                    } else {
                        // Không có variant, khôi phục tồn kho từ products table
                        $updateStmt = $this->pdo->prepare("
                            UPDATE products 
                            SET stock = stock + :quantity 
                            WHERE product_id = :product_id
                        ");
                        $updateStmt->execute([
                            ':quantity' => $quantity,
                            ':product_id' => $productId
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            // Log lỗi nhưng không throw để không ảnh hưởng đến việc hủy đơn
            error_log('Error restoring stock for order ' . $orderId . ': ' . $e->getMessage());
        }
    }

    // Sinh mã đơn độc nhất dạng BBxxxx
    private function generateOrderCode(): string
    {
        return 'BB' . strtoupper(dechex(time())) . strtoupper(substr(uniqid('', true), -4));
    }
    
    // Lấy danh sách cột có trong bảng
    private function getExistingColumns(string $tableName): array
    {
        static $cache = [];
        
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }
        
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$tableName}");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            $cache[$tableName] = $columns;
            return $columns;
        } catch (PDOException $e) {
            // Nếu không thể lấy danh sách cột, trả về mảng rỗng
            return [];
        }
    }
    
    // Tìm tên cột đúng từ danh sách các biến thể có thể có
    private function findColumnName(array $existingColumns, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            if (in_array($name, $existingColumns)) {
                return $name;
            }
        }
        return null;
    }
    
    // Lấy tên cột PRIMARY KEY của bảng
    private function getPrimaryKeyColumn(string $tableName): ?string
    {
        static $cache = [];
        
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }
        
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$tableName} WHERE `Key` = 'PRI'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $pkColumn = $row ? $row['Field'] : null;
            $cache[$tableName] = $pkColumn;
            return $pkColumn;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Đảm bảo cột PRIMARY KEY có AUTO_INCREMENT
     */
    private function ensureAutoIncrement(string $tableName, ?string $primaryKeyColumn): void
    {
        if (!$primaryKeyColumn) {
            return;
        }
        
        try {
            // Kiểm tra xem cột có AUTO_INCREMENT không
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$tableName} WHERE Field = '{$primaryKeyColumn}'");
            $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($columnInfo && strpos($columnInfo['Extra'], 'auto_increment') === false) {
                error_log("OrderModel::ensureAutoIncrement - Column {$primaryKeyColumn} does not have AUTO_INCREMENT. Attempting to fix...");
                
                // Lấy kiểu dữ liệu của cột
                $dataType = $columnInfo['Type'];
                $nullInfo = $columnInfo['Null'] === 'NO' ? 'NOT NULL' : '';
                
                // Sửa lại cột để có AUTO_INCREMENT
                // Lưu ý: ALTER TABLE có thể mất thời gian với bảng lớn
                $sql = "ALTER TABLE {$tableName} MODIFY COLUMN {$primaryKeyColumn} {$dataType} {$nullInfo} AUTO_INCREMENT";
                $this->pdo->exec($sql);
                
                error_log("OrderModel::ensureAutoIncrement - Fixed AUTO_INCREMENT for column {$primaryKeyColumn} in table {$tableName}");
            }
        } catch (Exception $e) {
            error_log("OrderModel::ensureAutoIncrement - Error: " . $e->getMessage());
            // Không throw exception vì đây chỉ là thử sửa, không phải bắt buộc
            // Có thể do không có quyền ALTER TABLE hoặc lỗi khác
        }
    }
    
    /**
     * Xóa bản ghi có id = 0 nếu có (gây conflict với AUTO_INCREMENT)
     */
    private function fixZeroIdRecord(string $tableName, ?string $primaryKeyColumn): void
    {
        if (!$primaryKeyColumn) {
            return;
        }
        
        try {
            // Kiểm tra xem có bản ghi với id = 0 không
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$tableName} WHERE {$primaryKeyColumn} = 0");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (int)$result['count'] > 0) {
                error_log("OrderModel::fixZeroIdRecord - Found " . $result['count'] . " record(s) with {$primaryKeyColumn} = 0 in table {$tableName}. Deleting...");
                
                // Xóa bản ghi có id = 0
                $deleteStmt = $this->pdo->prepare("DELETE FROM {$tableName} WHERE {$primaryKeyColumn} = 0");
                $deleteStmt->execute();
                
                error_log("OrderModel::fixZeroIdRecord - Deleted records with {$primaryKeyColumn} = 0");
                
                // Reset AUTO_INCREMENT về giá trị đúng
                $maxStmt = $this->pdo->query("SELECT MAX({$primaryKeyColumn}) as max_id FROM {$tableName}");
                $maxResult = $maxStmt->fetch(PDO::FETCH_ASSOC);
                $nextId = (int)($maxResult['max_id'] ?? 0) + 1;
                
                $resetStmt = $this->pdo->prepare("ALTER TABLE {$tableName} AUTO_INCREMENT = ?");
                $resetStmt->execute([$nextId]);
                
                error_log("OrderModel::fixZeroIdRecord - Reset AUTO_INCREMENT to {$nextId} for table {$tableName}");
            }
        } catch (Exception $e) {
            error_log("OrderModel::fixZeroIdRecord - Error: " . $e->getMessage());
            // Không throw exception vì đây chỉ là thử sửa, không phải bắt buộc
        }
    }

    // Lấy tổng số đơn hàng
    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM orders_new");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }
    public function getStatsByRange(string $fromDate, string $toDate): array
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS orders FROM orders_new WHERE DATE(created_at) BETWEEN :from_date AND :to_date");
        $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
        $ordersRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $orders = (int)($ordersRow['orders'] ?? 0);
        $stmt2 = $this->pdo->prepare("SELECT COALESCE(SUM(total_amount),0) AS revenue FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date");
        $stmt2->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        $revRow = $stmt2->fetch(PDO::FETCH_ASSOC);
        $revenue = (float)($revRow['revenue'] ?? 0);
        return [
            'orders' => $orders,
            'revenue' => $revenue,
        ];
    }
    public function getStatusCounts(string $fromDate, string $toDate): array
    {
        $result = [];
        foreach (array_keys(self::statuses()) as $status) {
            $result[$status] = 0;
        }
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) AS cnt FROM orders_new WHERE DATE(created_at) BETWEEN :from_date AND :to_date GROUP BY status");
        $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $s = $row['status'] ?? null;
            if ($s !== null) {
                $result[$s] = (int)($row['cnt'] ?? 0);
            }
        }
        return $result;
    }
    public function getTotalProductsSold(string $fromDate, string $toDate): int
    {
        $sql = "SELECT COALESCE(SUM(oi.quantity),0) AS qty FROM order_items oi JOIN orders_new o ON o.id = oi.order_id WHERE o.status = :status AND DATE(o.created_at) BETWEEN :from_date AND :to_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['qty'] ?? 0);
    }
    public function getTopCustomers(string $fromDate, string $toDate, int $limit = 5): array
    {
        $sql = "SELECT COALESCE(u.full_name, o.fullname, o.email) AS fullname, COALESCE(o.user_id, o.email) AS grp, COUNT(*) AS orders, COALESCE(SUM(o.total_amount),0) AS total_spent FROM orders_new o LEFT JOIN users u ON u.user_id = o.user_id WHERE o.status = :status AND DATE(o.created_at) BETWEEN :from_date AND :to_date GROUP BY grp, fullname ORDER BY total_spent DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', self::STATUS_DELIVERED, PDO::PARAM_STR);
        $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getRevenueByPaymentMethod(string $fromDate, string $toDate): array
    {
        $sql = "SELECT payment_method, COUNT(*) AS order_count, COALESCE(SUM(total_amount),0) AS revenue FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date GROUP BY payment_method ORDER BY revenue DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getReturnedOrdersCount(string $fromDate, string $toDate): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date");
        $stmt->execute([':status' => self::STATUS_RETURNED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }
    public function getDailyRevenue(string $fromDate, string $toDate): array
    {
        $sql = "SELECT DATE(created_at) AS d, COALESCE(SUM(total_amount),0) AS revenue FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getDailyOrders(string $fromDate, string $toDate): array
    {
        $sql = "SELECT DATE(created_at) AS d, COUNT(*) AS orders FROM orders_new WHERE DATE(created_at) BETWEEN :from_date AND :to_date GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPaymentBreakdown(string $fromDate, string $toDate): array
    {
        $sql = "SELECT payment_method, COUNT(*) AS orders FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date GROUP BY payment_method ORDER BY orders DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getReturnCancelChart(string $fromDate, string $toDate): array
    {
        $sql = "SELECT DATE(created_at) AS d, SUM(CASE WHEN status = :returned THEN 1 ELSE 0 END) AS returned, SUM(CASE WHEN status = :cancelled THEN 1 ELSE 0 END) AS cancelled FROM orders_new WHERE DATE(created_at) BETWEEN :from_date AND :to_date GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':returned' => self::STATUS_RETURNED, ':cancelled' => self::STATUS_CANCELLED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getOrderMetrics(string $fromDate, string $toDate): array
    {
        $stmt1 = $this->pdo->prepare("SELECT COALESCE(SUM(total_amount),0) AS revenue FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date");
        $stmt1->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        $revRow = $stmt1->fetch(PDO::FETCH_ASSOC);
        $revenue = (float)($revRow['revenue'] ?? 0);
        $stmt2 = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM orders_new WHERE status = :status AND DATE(created_at) BETWEEN :from_date AND :to_date");
        $stmt2->execute([':status' => self::STATUS_DELIVERED, ':from_date' => $fromDate, ':to_date' => $toDate]);
        $cntRow = $stmt2->fetch(PDO::FETCH_ASSOC);
        $deliveredCount = (int)($cntRow['cnt'] ?? 0);
        $aov = $deliveredCount > 0 ? ($revenue / $deliveredCount) : 0.0;
        return ['aov' => $aov];
    }
    public function getReturningCustomerRate(string $fromDate, string $toDate): float
    {
        $validStatuses = [
            self::STATUS_PAID,
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_HANDED_TO_SHIPPER,
            self::STATUS_SHIPPING,
            self::STATUS_TO_SHIP,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_RETURNED
        ];
        $placeholders = implode(',', array_fill(0, count($validStatuses), '?'));
        $sqlTotal = "SELECT COUNT(DISTINCT user_id) AS total_customers FROM orders_new WHERE user_id IS NOT NULL AND DATE(created_at) BETWEEN ? AND ? AND status IN ($placeholders)";
        $stmtTotal = $this->pdo->prepare($sqlTotal);
        $paramsTotal = array_merge([$fromDate, $toDate], $validStatuses);
        $stmtTotal->execute($paramsTotal);
        $rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);
        $totalCustomers = (int)($rowTotal['total_customers'] ?? 0);
        if ($totalCustomers === 0) {
            return 0.0;
        }
        $sqlReturning = "SELECT COUNT(DISTINCT o.user_id) AS returning_customers FROM orders_new o WHERE o.user_id IS NOT NULL AND DATE(o.created_at) BETWEEN ? AND ? AND o.status IN ($placeholders) AND EXISTS (SELECT 1 FROM orders_new o2 WHERE o2.user_id = o.user_id AND o2.status IN ($placeholders) AND o2.created_at < STR_TO_DATE(?, '%Y-%m-%d'))";
        $stmtReturning = $this->pdo->prepare($sqlReturning);
        $paramsReturning = array_merge([$fromDate, $toDate], $validStatuses, $validStatuses, [$fromDate]);
        $stmtReturning->execute($paramsReturning);
        $rowReturning = $stmtReturning->fetch(PDO::FETCH_ASSOC);
        $returningCustomers = (int)($rowReturning['returning_customers'] ?? 0);
        return ($returningCustomers / $totalCustomers) * 100.0;
    }
    public function getMonthlyRevenue(int $months = 12): array
    {
        $months = max(1, $months);
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(total_amount),0) AS revenue FROM orders_new WHERE status = :status AND created_at >= DATE_SUB(CURDATE(), INTERVAL $months MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => self::STATUS_DELIVERED]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
