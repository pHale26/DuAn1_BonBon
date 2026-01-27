<?php

class ReturnRequestModel extends BaseModel
{
    protected $table = 'return_requests';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REFUNDING = 'refunding';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    private const STATUS_LABELS = [
        self::STATUS_REQUESTED  => 'Chờ duyệt trả hàng',
        self::STATUS_APPROVED   => 'Đã duyệt trả hàng',
        self::STATUS_REJECTED   => 'Đã từ chối trả',
        self::STATUS_SHIPPING   => 'Đang gửi hàng hoàn',
        self::STATUS_RECEIVED   => 'Shop đã nhận hàng hoàn',
        self::STATUS_REFUNDING  => 'Đang hoàn tiền',
        self::STATUS_REFUNDED   => 'Đã hoàn tiền',
        self::STATUS_CANCELLED  => 'Đã hủy yêu cầu',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
        $this->ensureRejectReasonColumn();
    }

    private function ensureTable(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    user_id INT NOT NULL,
                    reason VARCHAR(255) NOT NULL,
                    note TEXT NULL,
                    images TEXT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'requested',
                    shipping_code VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT NULL,
                    approved_at TIMESTAMP NULL DEFAULT NULL,
                    rejected_at TIMESTAMP NULL DEFAULT NULL,
                    received_at TIMESTAMP NULL DEFAULT NULL,
                    refunded_at TIMESTAMP NULL DEFAULT NULL,
                    INDEX idx_order (order_id),
                    INDEX idx_user (user_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (Throwable $e) {
            error_log('ReturnRequestModel::ensureTable - ' . $e->getMessage());
        }
    }

    public function findByOrder(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE order_id = :oid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':oid' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            $row['images'] = is_array($decoded) ? $decoded : [];
        }
        return $row ?: null;
    }

    public function createRequest(int $orderId, int $userId, string $reason, ?string $note = null, array $images = []): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (order_id, user_id, reason, note, images, status)
            VALUES (:oid, :uid, :reason, :note, :images, :status)
        ");
        $stmt->execute([
            ':oid' => $orderId,
            ':uid' => $userId,
            ':reason' => $reason,
            ':note' => $note,
            ':images' => !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
            ':status' => self::STATUS_REQUESTED,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $fields = ['status = :status', 'updated_at = CURRENT_TIMESTAMP'];
        $params = [':status' => $status, ':id' => $id];

        if ($status === self::STATUS_APPROVED) {
            $fields[] = "approved_at = CURRENT_TIMESTAMP";
        } elseif ($status === self::STATUS_REJECTED) {
            $fields[] = "rejected_at = CURRENT_TIMESTAMP";
        } elseif ($status === self::STATUS_RECEIVED) {
            $fields[] = "received_at = CURRENT_TIMESTAMP";
        } elseif ($status === self::STATUS_REFUNDED) {
            $fields[] = "refunded_at = CURRENT_TIMESTAMP";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function saveShippingCode(int $id, string $code): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET shipping_code = :code, status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            ':code' => $code,
            ':status' => self::STATUS_SHIPPING,
            ':id' => $id,
        ]);
    }

    public function rejectRequest(int $id, string $reason): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET status = :status, reject_reason = :reject_reason, rejected_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            ':status' => self::STATUS_REJECTED,
            ':reject_reason' => $reason,
            ':id' => $id,
        ]);
    }

    /**
     * Lấy yêu cầu trả hàng mới nhất cho nhiều đơn (map order_id => return_request).
     */
    public function getLatestByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if (empty($orderIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE order_id IN ($placeholders)
            ORDER BY id DESC
        ");
        $stmt->execute($orderIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            if (!isset($map[$row['order_id']])) {
                if (!empty($row['images'])) {
                    $decoded = json_decode($row['images'], true);
                    $row['images'] = is_array($decoded) ? $decoded : [];
                }
                $map[$row['order_id']] = $row;
            }
        }
        return $map;
    }

    private function ensureRejectReasonColumn(): void
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :tbl
                  AND COLUMN_NAME = 'reject_reason'
            ");
            $stmt->execute([':tbl' => $this->table]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN reject_reason TEXT NULL AFTER rejected_at");
            }
        } catch (Throwable $e) {
            // ignore if cannot alter
        }
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }
}


