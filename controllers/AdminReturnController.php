<?php

require_once PATH_MODEL . 'ReturnRequestModel.php';
require_once PATH_MODEL . 'OrderModel.php';
require_once PATH_MODEL . 'NotificationModel.php';
require_once PATH_MODEL . 'UserModel.php';

class AdminReturnController
{
    private ReturnRequestModel $returnModel;
    private OrderModel $orderModel;
    private NotificationModel $notificationModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->returnModel = new ReturnRequestModel();
        $this->orderModel = new OrderModel();
        $this->notificationModel = new NotificationModel();
        $this->userModel = new UserModel();
    }

    /**
     * Duyệt yêu cầu trả hàng.
     */
    public function approve(): void
    {
        $this->requireAdmin();
        $requestId = (int)($_POST['return_id'] ?? 0);
        $orderId = (int)($_POST['order_id'] ?? 0);

        if (!$requestId || !$orderId) {
            set_flash('danger', 'Thiếu dữ liệu yêu cầu trả hàng');
            header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
            exit;
        }

        $this->returnModel->updateStatus($requestId, ReturnRequestModel::STATUS_APPROVED);
        $this->notifyUser($orderId, 'return_approved', 'Yêu cầu trả hàng được duyệt', 'Vui lòng gửi hàng hoàn theo hướng dẫn.', ReturnRequestModel::STATUS_APPROVED);
        set_flash('success', 'Đã duyệt yêu cầu trả hàng');
        header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
        exit;
    }

    /**
     * Từ chối yêu cầu trả hàng.
     */
    public function reject(): void
    {
        $this->requireAdmin();
        $requestId = (int)($_POST['return_id'] ?? 0);
        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason = trim($_POST['reject_reason'] ?? '');

        if (!$requestId || !$orderId) {
            set_flash('danger', 'Thiếu dữ liệu yêu cầu trả hàng');
            header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
            exit;
        }
        if ($reason === '') {
            set_flash('danger', 'Vui lòng nhập lý do từ chối');
            header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
            exit;
        }

        $this->returnModel->rejectRequest($requestId, $reason);
        $this->notifyUser($orderId, 'return_rejected', 'Yêu cầu trả hàng bị từ chối', 'Lý do: ' . $reason, ReturnRequestModel::STATUS_REJECTED, ['reason' => $reason]);
        set_flash('success', 'Đã từ chối yêu cầu trả hàng');
        header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
        exit;
    }

    /**
     * Xác nhận đã nhận hàng hoàn.
     */
    public function receive(): void
    {
        $this->requireAdmin();
        $requestId = (int)($_POST['return_id'] ?? 0);
        $orderId = (int)($_POST['order_id'] ?? 0);

        if (!$requestId || !$orderId) {
            set_flash('danger', 'Thiếu dữ liệu');
            header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
            exit;
        }

        $this->returnModel->updateStatus($requestId, ReturnRequestModel::STATUS_RECEIVED);
        $this->notifyUser($orderId, 'return_received', 'Shop đã nhận hàng hoàn', 'Chúng tôi sẽ kiểm tra và hoàn tiền nếu đủ điều kiện.', ReturnRequestModel::STATUS_RECEIVED);
        set_flash('success', 'Đã nhận hàng hoàn');
        header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
        exit;
    }

    /**
     * Hoàn tiền đơn giản - đánh dấu hoàn tiền thành công.
     */
    public function refund(): void
    {
        $this->requireAdmin();
        $requestId = (int)($_POST['return_id'] ?? 0);
        $orderId = (int)($_POST['order_id'] ?? 0);

        if (!$requestId || !$orderId) {
            set_flash('danger', 'Thiếu dữ liệu');
            header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
            exit;
        }

        // Chỉ hoàn khi đơn đã hoàn thành
        $order = $this->orderModel->findWithItems($orderId);
        if (!$order || ($order['status'] ?? '') !== OrderModel::STATUS_COMPLETED) {
            set_flash('danger', 'Chỉ hoàn tiền khi đơn ở trạng thái Hoàn thành.');
            header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
            exit;
        }

        // Đơn giản: đánh dấu refunded
        $this->returnModel->updateStatus($requestId, ReturnRequestModel::STATUS_REFUNDED);
        $this->orderModel->updateStatus($orderId, OrderModel::STATUS_RETURNED);
        $this->notifyUser($orderId, 'return_refunded', 'Đã hoàn tiền', 'Yêu cầu trả hàng đã được hoàn tiền.', ReturnRequestModel::STATUS_REFUNDED);

        set_flash('success', 'Đã đánh dấu hoàn tiền');
        header('Location: ' . BASE_URL . '?action=admin-order-detail&id=' . $orderId);
        exit;
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
            set_flash('danger', 'Bạn cần quyền quản trị');
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    private function notifyUser(int $orderId, string $type, string $title, string $content, string $returnStatus, array $metaExtra = []): void
    {
        try {
            $order = $this->orderModel->findWithItems($orderId);
            if (!$order) return;
            $targetUserId = (int)($order['user_id'] ?? 0);
            if ($targetUserId <= 0 && !empty($order['email'])) {
                $foundUser = $this->userModel->findByEmail($order['email']);
                if ($foundUser) {
                    $targetUserId = (int)($foundUser['user_id'] ?? $foundUser['id'] ?? 0);
                }
            }
            if ($targetUserId <= 0) return;

            $meta = array_merge([
                'order_id' => $orderId,
                'status' => $returnStatus,
                'order_code' => $order['order_code'] ?? null,
            ], $metaExtra);

            $this->notificationModel->create(
                $targetUserId,
                $type,
                $title,
                $content,
                BASE_URL . '?action=order-detail&id=' . $orderId,
                $meta
            );
        } catch (Throwable $e) {
            // ignore notify failure
        }
    }
}


