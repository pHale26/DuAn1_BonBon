<?php

class ContactModel extends BaseModel
{
    protected $table = 'contacts';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Đảm bảo bảng contacts tồn tại
     */
    private function ensureTableExists(): void
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `contacts` (
                `contact_id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `subject` varchar(255) DEFAULT NULL,
                `message` text NOT NULL,
                `status` enum('pending','read','replied') DEFAULT 'pending',
                `admin_reply` text DEFAULT NULL,
                `replied_at` datetime DEFAULT NULL,
                `replied_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`contact_id`),
                KEY `idx_status` (`status`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Bảng đã tồn tại hoặc có lỗi, bỏ qua
            error_log("ContactModel::ensureTableExists - " . $e->getMessage());
        }
    }

    /**
     * Tạo liên hệ mới
     */
    public function create(array $data): int
    {
        $data = $this->removePrimaryKeyFromData($data, $this->table);
        
        $sql = "INSERT INTO {$this->table} 
                (name, email, phone, subject, message, status, created_at) 
                VALUES (:name, :email, :phone, :subject, :message, 'pending', NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Lấy tất cả liên hệ (cho admin)
     */
    public function getAll(?string $status = null, ?string $keyword = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        if ($keyword) {
            $sql .= " AND (name LIKE :keyword OR email LIKE :keyword OR phone LIKE :keyword OR message LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }
        
        if ($fromDate) {
            $sql .= " AND created_at >= :from_date";
            $params['from_date'] = $fromDate . ' 00:00:00';
        }
        
        if ($toDate) {
            $sql .= " AND created_at <= :to_date";
            $params['to_date'] = $toDate . ' 23:59:59';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy liên hệ theo ID
     */
    public function getById(int $contactId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE contact_id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $contactId]);
        
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        return $contact ?: null;
    }

    /**
     * Đánh dấu đã đọc
     */
    public function markAsRead(int $contactId): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'read' WHERE contact_id = :id AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $contactId]);
    }

    /**
     * Phản hồi liên hệ
     */
    public function reply(int $contactId, string $reply, int $adminId): bool
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'replied', 
                    admin_reply = :reply, 
                    replied_at = NOW(), 
                    replied_by = :admin_id 
                WHERE contact_id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $contactId,
            'reply' => $reply,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * Đếm số liên hệ chưa đọc
     */
    public function countUnread(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'pending'";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Xóa liên hệ
     */
    public function delete(int $contactId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE contact_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $contactId]);
    }

    /**
     * Lấy liên hệ theo email (cho user xem lịch sử)
     */
    public function getByEmail(string $email): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

