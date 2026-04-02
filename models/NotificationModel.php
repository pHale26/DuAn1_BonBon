<?php

class NotificationModel extends BaseModel
{
    // Không khai báo kiểu để tương thích với BaseModel::$table
    protected $table = 'notifications';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    /**
     * Tạo bảng notifications nếu chưa có.
     */
    private function ensureTable(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NULL,
                    action_url VARCHAR(255) NULL,
                    meta TEXT NULL,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_read (user_id, is_read),
                    INDEX idx_user_created (user_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (Throwable $e) {
            error_log('NotificationModel::ensureTable - ' . $e->getMessage());
        }
    }

    public function create(
        int $userId,
        string $type,
        string $title,
        ?string $content = null,
        ?string $actionUrl = null,
        array $meta = []
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (user_id, type, title, content, action_url, meta)
            VALUES (:user_id, :type, :title, :content, :action_url, :meta)
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':content' => $content,
            ':action_url' => $actionUrl,
            ':meta' => !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Tạo nhiều thông báo cho danh sách user.
     */
    public function createBulk(
        array $userIds,
        string $type,
        string $title,
        ?string $content = null,
        ?string $actionUrl = null,
        array $meta = []
    ): int {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return 0;
        }

        $inserted = 0;
        $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (user_id, type, title, content, action_url, meta)
                VALUES (:user_id, :type, :title, :content, :action_url, :meta)
            ");

            foreach ($userIds as $uid) {
                $stmt->execute([
                    ':user_id' => $uid,
                    ':type' => $type,
                    ':title' => $title,
                    ':content' => $content,
                    ':action_url' => $actionUrl,
                    ':meta' => $metaJson,
                ]);
                $inserted += (int)$stmt->rowCount();
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('NotificationModel::createBulk - ' . $e->getMessage());
        }

        return $inserted;
    }

    public function getByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));

        $stmt = $this->pdo->prepare("
            SELECT id, type, title, content, action_url, meta, is_read, created_at
            FROM {$this->table}
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            if (!empty($item['meta'])) {
                $decoded = json_decode($item['meta'], true);
                $item['meta'] = is_array($decoded) ? $decoded : [];
            } else {
                $item['meta'] = [];
            }
            $item['is_read'] = (int)($item['is_read'] ?? 0);
        }
        unset($item);

        return $items;
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total
            FROM {$this->table}
            WHERE user_id = :uid AND is_read = 0
        ");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function markReadMany(int $userId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET is_read = 1
            WHERE user_id = ? AND id IN ({$placeholders})
        ");

        $params = array_merge([$userId], $ids);
        $stmt->execute($params);
        return (int)$stmt->rowCount();
    }

    public function markAllRead(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET is_read = 1
            WHERE user_id = :uid AND is_read = 0
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->rowCount();
    }

    public function deleteMany(int $userId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table}
            WHERE user_id = ? AND id IN ({$placeholders})
        ");

        $params = array_merge([$userId], $ids);
        $stmt->execute($params);
        return (int)$stmt->rowCount();
    }

    public function deleteRead(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table}
            WHERE user_id = :uid AND is_read = 1
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->rowCount();
    }

    public function deleteAll(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table}
            WHERE user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->rowCount();
    }
}


