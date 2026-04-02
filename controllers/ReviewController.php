<?php

require_once PATH_MODEL . 'ReviewModel.php';
require_once PATH_MODEL . 'OrderModel.php';

class ReviewController
{
    private ReviewModel $reviewModel;
    private OrderModel $orderModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
        $this->orderModel = new OrderModel();
    }

    /**
     * Upload ảnh đánh giá
     */
    public function uploadImage(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Không có file được upload']);
            exit;
        }

        $file = $_FILES['image'];
        
        // Kiểm tra loại file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)']);
            exit;
        }

        // Kiểm tra kích thước (tối đa 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Kích thước file không được vượt quá 5MB']);
            exit;
        }

        // Tạo thư mục nếu chưa có
        $uploadDir = PATH_ASSETS_UPLOADS . 'reviews/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Tạo tên file unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'review_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Upload file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $imageUrl = BASE_ASSETS_UPLOADS . 'reviews/' . $fileName;
            echo json_encode([
                'success' => true,
                'url' => $imageUrl,
                'message' => 'Upload thành công'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể upload file']);
        }
    }

    /**
     * Người dùng cập nhật bình luận (không xóa), lưu lịch sử
     */
    public function update(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        $user = $_SESSION['user'] ?? [];
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);

        $data = json_decode(file_get_contents('php://input'), true);
        $reviewId = (int)($data['review_id'] ?? 0);
        $comment  = trim($data['comment'] ?? '');
        $rating   = isset($data['rating']) ? (int)$data['rating'] : null;

        if ($reviewId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu review_id']);
            exit;
        }

        if ($comment === '' && $rating === null) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập bình luận hoặc cập nhật số sao']);
            exit;
        }
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            echo json_encode(['success' => false, 'message' => 'Số sao phải từ 1 đến 5']);
            exit;
        }

        try {
            $review = $this->reviewModel->getById($reviewId);
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đánh giá']);
                exit;
            }
            if ((int)($review['user_id'] ?? 0) !== $userId) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa đánh giá này']);
                exit;
            }

            $this->reviewModel->updateUserComment($reviewId, $userId, $comment, $rating);
            echo json_encode(['success' => true, 'message' => 'Cập nhật đánh giá thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Submit đánh giá cho một sản phẩm trong đơn hàng
     */
    public function submit(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        // Lấy user_id từ session (có thể là 'id' hoặc 'user_id')
        $user = $_SESSION['user'] ?? [];
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
        $userEmail = $user['email'] ?? null;
        
        $data = json_decode(file_get_contents('php://input'), true);

        $orderItemId = (int)($data['order_item_id'] ?? 0);
        $orderId = (int)($data['order_id'] ?? 0);
        $productId = (int)($data['product_id'] ?? 0);
        $rating = (int)($data['rating'] ?? 0);
        $comment = trim($data['comment'] ?? '');
        $images = $data['images'] ?? []; // Array of image URLs

        // Validation
        if (!$orderItemId || !$orderId || !$productId) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn hàng']);
            exit;
        }

        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Đánh giá phải từ 1 đến 5 sao']);
            exit;
        }

        // Kiểm tra đơn hàng có thuộc về user này không
        $order = $this->orderModel->findWithItems($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
            exit;
        }

        // Kiểm tra quyền: so sánh user_id HOẶC email
        $canView = false;
        $orderUserId = (int)($order['user_id'] ?? 0);
        $orderEmail = $order['email'] ?? null;
        
        // Kiểm tra theo user_id
        if ($userId > 0 && $orderUserId > 0 && $orderUserId == $userId) {
            $canView = true;
        }
        
        // Nếu user_id không khớp, kiểm tra theo email
        if (!$canView && $userEmail && $orderEmail && strtolower(trim($userEmail)) === strtolower(trim($orderEmail))) {
            $canView = true;
        }
        
        // Log để debug
        error_log("ReviewController::submit - User ID: $userId, Order User ID: $orderUserId, User Email: " . ($userEmail ?? 'N/A') . ", Order Email: " . ($orderEmail ?? 'N/A') . ", CanView: " . ($canView ? 'true' : 'false'));

        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền đánh giá đơn hàng này']);
            exit;
        }

        // Kiểm tra đơn hàng đã được giao hoặc hoàn thành chưa
        if (!in_array($order['status'], [OrderModel::STATUS_DELIVERED, OrderModel::STATUS_COMPLETED], true)) {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể đánh giá khi đơn hàng đã được giao/hoàn thành']);
            exit;
        }

        // Kiểm tra đã đánh giá chưa
        if ($this->reviewModel->hasReviewed($orderItemId, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi']);
            exit;
        }

        // Kiểm tra order_item có trong đơn hàng này không
        $itemExists = false;
        foreach ($order['items'] as $item) {
            if ((int)($item['id'] ?? $item['order_item_id'] ?? 0) === $orderItemId) {
                $itemExists = true;
                if (!$productId) {
                    $productId = (int)($item['product_id'] ?? 0);
                }
                break;
            }
        }

        if (!$itemExists) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không thuộc đơn hàng này']);
            exit;
        }

        // Đảm bảo KHÔNG có id trong review data
        $reviewData = [
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $rating,
            'comment' => $comment ?: null,
            'images' => $images,
        ];
        unset($reviewData['id'], $reviewData['review_id'], $reviewData['reviewId']);
        
        try {
            $reviewId = $this->reviewModel->create($reviewData);

            if (!$reviewId || $reviewId <= 0) {
                error_log("ReviewController::submit - Failed to create review. Review ID: $reviewId");
                echo json_encode(['success' => false, 'message' => 'Không thể tạo đánh giá. Vui lòng thử lại.']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đánh giá thành công',
                'review_id' => $reviewId,
            ]);
        } catch (PDOException $e) {
            error_log("ReviewController::submit - PDO Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
        } catch (Exception $e) {
            error_log("ReviewController::submit - Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Lấy danh sách đánh giá của sản phẩm (AJAX)
     */
    public function getByProduct(): void
    {
        header('Content-Type: application/json');

        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Thiếu product_id']);
            exit;
        }

        $reviews = $this->reviewModel->getByProduct($productId);
        $stats = $this->reviewModel->getProductStats($productId);

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }
}
