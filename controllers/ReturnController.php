<?php

require_once PATH_MODEL . 'ReturnRequestModel.php';
require_once PATH_MODEL . 'OrderModel.php';
require_once PATH_MODEL . 'NotificationModel.php';

class ReturnController
{
    private ReturnRequestModel $returnModel;
    private OrderModel $orderModel;
    private NotificationModel $notificationModel;
    use ReturnUploadHelper;

    public function __construct()
    {
        $this->returnModel = new ReturnRequestModel();
        $this->orderModel = new OrderModel();
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Khách tạo yêu cầu trả hàng (đơn đã giao, trong 3 ngày).
     */
    public function create(): void
    {
        header('Content-Type: application/json');
        $user = $_SESSION['user'] ?? null;
        $userId = (int)($user['id'] ?? $user['user_id'] ?? 0);
        $userEmail = $user['email'] ?? null;
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            return;
        }

        $isMultipart = !empty($_FILES);
        $data = $isMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
        $orderId = (int)($data['order_id'] ?? 0);
        $reason = trim($data['reason'] ?? '');
        $note = trim($data['note'] ?? '');

        if ($orderId <= 0 || $reason === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin yêu cầu']);
            return;
        }

        $order = $this->orderModel->findWithItems($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
            return;
        }

        // Kiểm tra quyền sở hữu
        $orderUserId = (int)($order['user_id'] ?? 0);
        $orderEmail = $order['email'] ?? null;
        $ownOrder = false;
        if ($orderUserId > 0 && $orderUserId === $userId) {
            $ownOrder = true;
        }
        if (!$ownOrder && $userEmail && $orderEmail && strcasecmp(trim($userEmail), trim($orderEmail)) === 0) {
            $ownOrder = true;
        }
        if (!$ownOrder) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền với đơn này']);
            return;
        }

        // Điều kiện: đã giao, trong 3 ngày
        if (!in_array($order['status'], [OrderModel::STATUS_DELIVERED, OrderModel::STATUS_COMPLETED], true)) {
            echo json_encode(['success' => false, 'message' => 'Chỉ yêu cầu trả khi đơn đã giao/hoàn thành']);
            return;
        }
        $deliveredAt = $order['updated_at'] ?? $order['created_at'] ?? null;
        if (!$deliveredAt || time() - strtotime($deliveredAt) > 3 * 24 * 3600) {
            echo json_encode(['success' => false, 'message' => 'Đã quá hạn 3 ngày để yêu cầu trả hàng']);
            return;
        }

        // Không tạo trùng nếu đã có yêu cầu (kể cả bị từ chối)
        $existing = $this->returnModel->findByOrder($orderId);
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã gửi yêu cầu trả hàng cho đơn này']);
            return;
        }

        // Xử lý upload minh chứng (bắt buộc)
        $images = [];
        try {
            $images = $this->handleEvidenceUploads($_FILES['evidences'] ?? null);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            return;
        }
        if (empty($images)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng tải lên ít nhất 1 file minh chứng (ảnh/video).']);
            return;
        }

        $id = $this->returnModel->createRequest($orderId, $userId, $reason, $note ?: null, $images);
        // Thông báo cho chính user để dễ thấy trong dropdown
        $this->notificationModel->create(
            $userId,
            'return_requested',
            'Đã gửi yêu cầu trả hàng',
            'Mã đơn #' . ($order['order_code'] ?? $orderId) . ' - Lý do: ' . $reason,
            BASE_URL . '?action=order-detail&id=' . $orderId,
            [
                'order_id' => $orderId,
                'status' => ReturnRequestModel::STATUS_REQUESTED,
                'reason' => $reason,
            ]
        );
        echo json_encode(['success' => true, 'message' => 'Đã gửi yêu cầu trả hàng', 'return_id' => $id, 'files' => $images]);
    }

    /**
     * Khách cập nhật mã vận chuyển hoàn khi đã được duyệt.
     */
    public function uploadShipping(): void
    {
        header('Content-Type: application/json');
        $user = $_SESSION['user'] ?? null;
        $userId = (int)($user['id'] ?? $user['user_id'] ?? 0);
        $userEmail = $user['email'] ?? null;
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $orderId = (int)($data['order_id'] ?? 0);
        $shippingCode = trim($data['shipping_code'] ?? '');

        if ($orderId <= 0 || $shippingCode === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
            return;
        }

        $request = $this->returnModel->findByOrder($orderId);
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu trả hàng']);
            return;
        }
        $order = $this->orderModel->findWithItems($orderId);
        $orderUserId = (int)($order['user_id'] ?? 0);
        $orderEmail = $order['email'] ?? null;
        $ownOrder = false;
        if ($orderUserId > 0 && $orderUserId === $userId) {
            $ownOrder = true;
        }
        if (!$ownOrder && $userEmail && $orderEmail && strcasecmp(trim($userEmail), trim($orderEmail)) === 0) {
            $ownOrder = true;
        }
        if (!$ownOrder) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền với đơn này']);
            return;
        }
        if ($request['status'] !== ReturnRequestModel::STATUS_APPROVED) {
            echo json_encode(['success' => false, 'message' => 'Chỉ cập nhật mã khi yêu cầu đã được duyệt']);
            return;
        }

        $this->returnModel->saveShippingCode((int)$request['id'], $shippingCode);
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật mã vận chuyển']);
    }

    /**
     * Khách hủy yêu cầu khi chưa được duyệt.
     */
    public function cancel(): void
    {
        header('Content-Type: application/json');
        $user = $_SESSION['user'] ?? null;
        $userId = (int)($user['id'] ?? $user['user_id'] ?? 0);
        $userEmail = $user['email'] ?? null;
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $orderId = (int)($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu order_id']);
            return;
        }

        $request = $this->returnModel->findByOrder($orderId);
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu']);
            return;
        }
        $order = $this->orderModel->findWithItems($orderId);
        $orderUserId = (int)($order['user_id'] ?? 0);
        $orderEmail = $order['email'] ?? null;
        $ownOrder = false;
        if ($orderUserId > 0 && $orderUserId === $userId) {
            $ownOrder = true;
        }
        if (!$ownOrder && $userEmail && $orderEmail && strcasecmp(trim($userEmail), trim($orderEmail)) === 0) {
            $ownOrder = true;
        }
        if (!$ownOrder) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền với đơn này']);
            return;
        }
        if (!in_array($request['status'], [ReturnRequestModel::STATUS_REQUESTED, ReturnRequestModel::STATUS_APPROVED], true)) {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy ở trạng thái hiện tại']);
            return;
        }

        $this->returnModel->updateStatus((int)$request['id'], ReturnRequestModel::STATUS_CANCELLED);
        echo json_encode(['success' => true, 'message' => 'Đã hủy yêu cầu trả hàng']);
    }
}

// Helpers
trait ReturnUploadHelper
{
    private function handleEvidenceUploads($fileInput): array
    {
        if (!$fileInput || empty($fileInput['name'])) {
            throw new Exception('Vui lòng tải lên ít nhất 1 file minh chứng.');
        }

        $uploadDir = PATH_ASSETS_UPLOADS . 'returns/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowed = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'
        ];
        $maxSize = 10 * 1024 * 1024; // 10MB

        $files = [];
        $names = $fileInput['name'];
        $tmpNames = $fileInput['tmp_name'];
        $types = $fileInput['type'];
        $sizes = $fileInput['size'];
        $errors = $fileInput['error'];

        $count = is_array($names) ? count($names) : 1;
        if ($count > 5) {
            throw new Exception('Tối đa 5 file minh chứng.');
        }

        for ($i = 0; $i < $count; $i++) {
            $name = is_array($names) ? $names[$i] : $names;
            $tmp = is_array($tmpNames) ? $tmpNames[$i] : $tmpNames;
            $type = is_array($types) ? $types[$i] : $types;
            $size = is_array($sizes) ? $sizes[$i] : $sizes;
            $err = is_array($errors) ? $errors[$i] : $errors;

            if ($err !== UPLOAD_ERR_OK) {
                throw new Exception('Upload file thất bại, vui lòng thử lại.');
            }
            if (!in_array($type, $allowed, true)) {
                throw new Exception('Chỉ chấp nhận ảnh hoặc video (jpg, png, webp, gif, mp4, mov, avi, mkv).');
            }
            if ($size > $maxSize) {
                throw new Exception('Mỗi file tối đa 10MB.');
            }

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newName = 'return_' . time() . '_' . uniqid() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (!move_uploaded_file($tmp, $dest)) {
                throw new Exception('Không thể lưu file, vui lòng thử lại.');
            }
            $files[] = BASE_ASSETS_UPLOADS . 'returns/' . $newName;
        }

        return $files;
    }
}

