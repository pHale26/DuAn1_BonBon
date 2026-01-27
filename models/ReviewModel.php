<?php

require_once PATH_MODEL . 'BaseModel.php';

class ReviewModel extends BaseModel
{
    protected $table = 'reviews';

    public function __construct()
    {
        parent::__construct();
        $this->ensureCommentHistoryColumn();
    }

    /**
     * Tạo đánh giá mới
     */
    public function create(array $data): int
    {
        try {
            $this->ensureCommentHistoryColumn();
            // QUAN TRỌNG: Chỉ loại bỏ PRIMARY KEY của bảng reviews (review_id hoặc id)
            // KHÔNG loại bỏ các FOREIGN KEY như order_id, order_item_id, product_id, user_id
            // Vì chúng là dữ liệu cần thiết để insert
            if (isset($data['review_id'])) {
                unset($data['review_id']);
            }
            if (isset($data['id'])) {
                unset($data['id']);
            }
            
            // Log để debug
            error_log("ReviewModel::create - Data after removing PK: " . json_encode(array_keys($data)));
            
            // Validate dữ liệu bắt buộc (không dùng empty() vì 0 cũng là giá trị hợp lệ cho một số trường)
            $orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
            $orderItemId = isset($data['order_item_id']) ? (int)$data['order_item_id'] : 0;
            $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            $rating = isset($data['rating']) ? (int)$data['rating'] : 0;
            
            // Kiểm tra các trường bắt buộc
            if ($orderId <= 0) {
                throw new Exception('Thiếu order_id hoặc order_id không hợp lệ');
            }
            if ($orderItemId <= 0) {
                throw new Exception('Thiếu order_item_id hoặc order_item_id không hợp lệ');
            }
            if ($productId <= 0) {
                throw new Exception('Thiếu product_id hoặc product_id không hợp lệ');
            }
            if ($userId <= 0) {
                throw new Exception('Thiếu user_id hoặc user_id không hợp lệ');
            }
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Rating phải từ 1 đến 5 sao');
            }
            
            // Lưu images dưới dạng JSON nếu có
            $imagesJson = null;
            if (!empty($data['images']) && is_array($data['images'])) {
                $imagesJson = json_encode($data['images']);
            } elseif (!empty($data['images']) && is_string($data['images'])) {
                $imagesJson = $data['images'];
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (
                    order_id, order_item_id, product_id, user_id, rating, comment, images
                ) VALUES (
                    :order_id, :order_item_id, :product_id, :user_id, :rating, :comment, :images
                )
            ");

            $stmt->execute([
                ':order_id' => $orderId,
                ':order_item_id' => $orderItemId,
                ':product_id' => $productId,
                ':user_id' => $userId,
                ':rating' => $rating,
                ':comment' => !empty($data['comment']) ? trim($data['comment']) : null,
                ':images' => $imagesJson,
            ]);

            $reviewId = (int)$this->pdo->lastInsertId();
            
            if ($reviewId <= 0) {
                error_log("ReviewModel::create - lastInsertId returned 0 or negative. Data: " . json_encode($data));
                throw new Exception('Không thể tạo đánh giá. Vui lòng thử lại.');
            }
            
            return $reviewId;
        } catch (PDOException $e) {
            error_log("ReviewModel::create - PDO Error: " . $e->getMessage() . " | Data: " . json_encode($data));
            throw new Exception('Lỗi database khi tạo đánh giá: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("ReviewModel::create - Error: " . $e->getMessage() . " | Data: " . json_encode($data));
            throw $e;
        }
    }

    /**
     * Lấy tất cả đánh giá của một sản phẩm (chỉ hiển thị những cái không bị ẩn)
     */
    public function getByProduct(int $productId, bool $includeHidden = false): array
    {
        $this->ensureCommentHistoryColumn();
        $whereClause = "product_id = :product_id";
        if (!$includeHidden) {
            $whereClause .= " AND is_hidden = 0";
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                r.*,
                u.full_name as user_name,
                u.email as user_email
            FROM {$this->table} r
            INNER JOIN users u ON r.user_id = u.user_id
            WHERE {$whereClause}
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([':product_id' => $productId]);
        $reviews = $stmt->fetchAll();
        
        // Parse images JSON thành array
        foreach ($reviews as &$review) {
            if (!empty($review['images'])) {
                $images = json_decode($review['images'], true);
                $review['images'] = is_array($images) ? $images : [];
            } else {
                $review['images'] = [];
            }
        }
        unset($review);
        
        return $reviews;
    }

    /**
     * Lấy đánh giá theo order_item_id (để kiểm tra đã đánh giá chưa)
     */
    public function getByOrderItem(int $orderItemId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE order_item_id = :order_item_id
            LIMIT 1
        ");

        $stmt->execute([':order_item_id' => $orderItemId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Lấy tất cả đánh giá của một đơn hàng
     */
    public function getByOrder(int $orderId): array
    {
        $this->ensureCommentHistoryColumn();
        // Bảng order_items có PRIMARY KEY là 'id', không có cột 'order_item_id'
        $stmt = $this->pdo->prepare("
            SELECT 
                r.*,
                u.full_name as user_name,
                p.product_name,
                oi.variant_size,
                oi.variant_color
            FROM {$this->table} r
            INNER JOIN users u ON r.user_id = u.user_id
            LEFT JOIN order_items oi ON r.order_item_id = oi.id
            INNER JOIN products p ON r.product_id = p.product_id
            WHERE r.order_id = :order_id
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tất cả đánh giá (cho admin)
     */
    public function getAll(?string $keyword = null, ?int $productId = null, ?int $rating = null, ?string $status = null): array
    {
        $this->ensureCommentHistoryColumn();
        $conditions = ['1=1'];
        $params = [];

        if ($keyword) {
            $conditions[] = "(r.comment LIKE :keyword OR u.full_name LIKE :keyword OR p.product_name LIKE :keyword)";
            $params[':keyword'] = "%{$keyword}%";
        }

        if ($productId) {
            $conditions[] = "r.product_id = :product_id";
            $params[':product_id'] = $productId;
        }

        if ($rating) {
            $conditions[] = "r.rating = :rating";
            $params[':rating'] = $rating;
        }

        // Lọc theo trạng thái (hidden/visible)
        if ($status === 'hidden') {
            $conditions[] = "r.is_hidden = 1";
        } elseif ($status === 'visible') {
            $conditions[] = "(r.is_hidden = 0 OR r.is_hidden IS NULL)";
        }

        $whereClause = implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare("
            SELECT 
                r.*,
                u.full_name as user_name,
                u.email as user_email,
                p.product_name,
                p.product_id,
                oi.variant_size,
                oi.variant_color
            FROM {$this->table} r
            INNER JOIN users u ON r.user_id = u.user_id
            INNER JOIN products p ON r.product_id = p.product_id
            LEFT JOIN order_items oi ON r.order_item_id = oi.id
            WHERE {$whereClause}
            ORDER BY r.created_at DESC, r.review_id DESC
        ");

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cập nhật reply của admin
     */
    public function updateReply(int $reviewId, string $reply): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET reply = :reply, updated_at = CURRENT_TIMESTAMP
            WHERE review_id = :review_id
        ");

        return $stmt->execute([
            ':reply' => $reply,
            ':review_id' => $reviewId,
        ]);
    }

    /**
     * Toggle ẩn/hiện đánh giá
     */
    public function toggleHidden(int $reviewId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET is_hidden = NOT is_hidden, updated_at = CURRENT_TIMESTAMP
            WHERE review_id = :review_id
        ");

        return $stmt->execute([':review_id' => $reviewId]);
    }

    /**
     * Xóa đánh giá
     */
    public function delete(int $reviewId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE review_id = :review_id");
        return $stmt->execute([':review_id' => $reviewId]);
    }

    /**
     * Tính điểm trung bình và số lượng đánh giá của sản phẩm
     */
    public function getProductStats(int $productId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
            FROM {$this->table}
            WHERE product_id = :product_id AND is_hidden = 0
        ");

        $stmt->execute([':product_id' => $productId]);
        $result = $stmt->fetch();

        return [
            'total_reviews' => (int)($result['total_reviews'] ?? 0),
            'average_rating' => round((float)($result['average_rating'] ?? 0), 1),
            'rating_5' => (int)($result['rating_5'] ?? 0),
            'rating_4' => (int)($result['rating_4'] ?? 0),
            'rating_3' => (int)($result['rating_3'] ?? 0),
            'rating_2' => (int)($result['rating_2'] ?? 0),
            'rating_1' => (int)($result['rating_1'] ?? 0),
        ];
    }

    /**
     * Kiểm tra user đã đánh giá order_item này chưa
     */
    public function hasReviewed(int $orderItemId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE order_item_id = :order_item_id AND user_id = :user_id
        ");

        $stmt->execute([
            ':order_item_id' => $orderItemId,
            ':user_id' => $userId,
        ]);

        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Lấy chi tiết đánh giá kèm user, product, variant
     */
    public function getDetailById(int $reviewId): ?array
    {
        $this->ensureCommentHistoryColumn();
        $stmt = $this->pdo->prepare("
            SELECT 
                r.*,
                u.full_name AS user_name,
                u.email AS user_email,
                p.product_name,
                p.product_id,
                oi.variant_size,
                oi.variant_color
            FROM {$this->table} r
            INNER JOIN users u ON r.user_id = u.user_id
            INNER JOIN products p ON r.product_id = p.product_id
            LEFT JOIN order_items oi ON r.order_item_id = oi.id
            WHERE r.review_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $reviewId]);
        $row = $stmt->fetch();
        if (!$row) return null;

        if (!empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            if (is_array($decoded)) {
                $row['images'] = $decoded;
            }
        }
        return $row;
    }

    /**
     * Lấy review theo ID
     */
    public function getById(int $reviewId): ?array
    {
        $this->ensureCommentHistoryColumn();
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE review_id = :review_id
            LIMIT 1
        ");
        $stmt->execute([':review_id' => $reviewId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            $row['images'] = is_array($decoded) ? $decoded : [];
        }
        return $row ?: null;
    }

    /**
     * Người dùng cập nhật bình luận và/hoặc số sao. Lưu lịch sử thay đổi.
     */
    public function updateUserComment(int $reviewId, int $userId, string $newComment, ?int $newRating = null): bool
    {
        $this->ensureCommentHistoryColumn();

        $review = $this->getById($reviewId);
        if (!$review) {
            throw new Exception('Không tìm thấy đánh giá');
        }
        if ((int)($review['user_id'] ?? 0) !== $userId) {
            throw new Exception('Bạn không có quyền sửa đánh giá này');
        }

        // Nếu không gửi rating mới, giữ nguyên rating cũ
        $currentRating = (int)($review['rating'] ?? 0);
        $targetRating = $newRating !== null ? (int)$newRating : $currentRating;
        if ($targetRating < 1 || $targetRating > 5) {
            throw new Exception('Số sao không hợp lệ (1-5)');
        }

        $history = [];
        if (!empty($review['comment_history'])) {
            $decoded = json_decode($review['comment_history'], true);
            $history = is_array($decoded) ? $decoded : [];
        }

        // Ghi lại lịch sử với nội dung và rating cũ
        $history[] = [
            'comment'   => $review['comment'] ?? null,
            'rating'    => $currentRating,
            'edited_at' => date('Y-m-d H:i:s'),
            'user_id'   => $userId,
        ];

        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET comment = :comment,
                rating = :rating,
                comment_history = :history,
                updated_at = CURRENT_TIMESTAMP
            WHERE review_id = :review_id
        ");

        return $stmt->execute([
            ':comment' => $newComment !== '' ? $newComment : null,
            ':rating' => $targetRating,
            ':history' => json_encode($history),
            ':review_id' => $reviewId,
        ]);
    }

    /**
     * Đảm bảo có cột comment_history để lưu lịch sử chỉnh sửa
     */
    private function ensureCommentHistoryColumn(): void
    {
        try {
            $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = :table
                      AND TABLE_SCHEMA = DATABASE()
                      AND COLUMN_NAME = 'comment_history'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':table' => $this->table]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN comment_history TEXT NULL AFTER comment");
            }
        } catch (PDOException $e) {
            // nếu không có quyền alter, bỏ qua để không chặn flow
        }
    }
}

