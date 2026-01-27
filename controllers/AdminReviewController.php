<?php

require_once PATH_MODEL . 'ReviewModel.php';
require_once PATH_MODEL . 'NotificationModel.php';

class AdminReviewController
{
    private ReviewModel $reviewModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
    }

    /**
     * Trang quản lý đánh giá
     */
    public function index(): void
    {
        $this->requireAdmin();

        $keyword = isset($_GET['keyword']) && trim($_GET['keyword']) !== '' ? trim($_GET['keyword']) : null;
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $rating = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
        $status = isset($_GET['status']) && trim($_GET['status']) !== '' ? trim($_GET['status']) : null;

        $reviews = $this->reviewModel->getAll($keyword, $productId, $rating, $status);

        $view = 'admin/reviews/index';
        $title = 'Quản lý đánh giá';
        $logoUrl = BASE_URL . 'assets/images/logo.png';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    /**
     * Toggle ẩn/hiện đánh giá
     */
    public function toggleHidden(): void
    {
        $this->requireAdmin();

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $reviewId = (int)($data['review_id'] ?? 0);

        if (!$reviewId) {
            echo json_encode(['success' => false, 'message' => 'Thiếu review_id']);
            exit;
        }

        try {
            $success = $this->reviewModel->toggleHidden($reviewId);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Trang chi tiết đánh giá
     */
    public function detail(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Thiếu mã đánh giá');
            header('Location: ' . BASE_URL . '?action=admin-reviews');
            exit;
        }

        $review = $this->reviewModel->getDetailById($id);
        if (!$review) {
            set_flash('danger', 'Không tìm thấy đánh giá');
            header('Location: ' . BASE_URL . '?action=admin-reviews');
            exit;
        }

        // Chuẩn hóa ảnh và lịch sử
        $images = [];
        if (!empty($review['images'])) {
            if (is_array($review['images'])) {
                $images = $review['images'];
            } else {
                $decoded = json_decode($review['images'], true);
                if (is_array($decoded)) $images = $decoded;
            }
        }

        $history = [];
        if (!empty($review['comment_history'])) {
            $decoded = json_decode($review['comment_history'], true);
            if (is_array($decoded)) $history = $decoded;
        }

        $title = 'Chi tiết đánh giá';
        $view  = 'admin/reviews/show';
        require_once PATH_VIEW . 'admin/layout.php';
    }

    /**
     * Reply đánh giá
     */
    public function reply(): void
    {
        $this->requireAdmin();

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $reviewId = (int)($data['review_id'] ?? 0);
        $reply = trim($data['reply'] ?? '');

        if (!$reviewId) {
            echo json_encode(['success' => false, 'message' => 'Thiếu review_id']);
            exit;
        }

        if (empty($reply)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung phản hồi']);
            exit;
        }

        try {
            $success = $this->reviewModel->updateReply($reviewId, $reply);
            if ($success) {
                $this->notifyReviewReply($reviewId, $reply);
                echo json_encode(['success' => true, 'message' => 'Phản hồi thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể phản hồi']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xóa đánh giá
     */
    // Xóa đánh giá không được phép
    public function delete(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không cho phép xóa đánh giá. Vui lòng sử dụng Ẩn/Hiện.']);
    }

    private function notifyReviewReply(int $reviewId, string $reply): void
    {
        try {
            $review = $this->reviewModel->getById($reviewId);
            if (!$review) {
                return;
            }

            $userId = (int)($review['user_id'] ?? 0);
            if ($userId <= 0) {
                return;
            }

            $orderId = (int)($review['order_id'] ?? 0);
            $orderItemId = (int)($review['order_item_id'] ?? 0);
            $productId = (int)($review['product_id'] ?? 0);

            // Ưu tiên dẫn về trang sản phẩm để khách xem phản hồi mà không bị chặn quyền xem đơn
            $anchor = '#reviews';
            $actionUrl = $productId ? BASE_URL . '?action=product-detail&id=' . $productId . $anchor : null;
            // Nếu không có product_id, fallback về trang đơn hàng
            if (!$actionUrl && $orderId) {
                $anchorOrder = $orderItemId ? '#reviewItem_' . $orderItemId : '';
                $actionUrl = BASE_URL . '?action=order-detail&id=' . $orderId . $anchorOrder;
            }

            $notificationModel = new NotificationModel();
            $notificationModel->create(
                $userId,
                'review_reply',
                'Shop đã phản hồi đánh giá của bạn',
                $reply,
                $actionUrl,
                [
                    'order_id' => $orderId ?: null,
                    'order_item_id' => $orderItemId ?: null,
                    'product_id' => $productId ?: null,
                    'review_id' => $reviewId,
                ]
            );
        } catch (Throwable $e) {
            error_log('AdminReviewController::notifyReviewReply - ' . $e->getMessage());
        }
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            set_flash('danger', 'Bạn không có quyền truy cập.');
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}

