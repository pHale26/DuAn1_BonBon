<?php

// Controller xử lý các chức năng xem/hủy đơn hàng phía người dùng
class OrderController
{
    private OrderModel $orderModel;
    private ReturnRequestModel $returnModel;

    public function __construct()
    {
        // Khởi tạo model để tái sử dụng ở mọi action
        $this->orderModel = new OrderModel();
        $this->returnModel = new ReturnRequestModel();
    }

    // Trang liệt kê lịch sử các đơn của tài khoản đang đăng nhập
    public function history(): void
    {
        $user = $this->requireUser();
        
        // Lấy user_id từ nhiều nguồn có thể (user_id hoặc id)
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
        $userEmail = $user['email'] ?? null;
        
        // Lấy status filter từ URL
        $statusFilter = $_GET['status'] ?? null;
        
        // Debug: Log để kiểm tra
        error_log("OrderController::history - User ID: $userId, Email: " . ($userEmail ?? 'N/A'));
        error_log("OrderController::history - User data: " . json_encode(array_keys($user)));
        error_log("OrderController::history - Status filter: " . ($statusFilter ?? 'all'));
        
        $orders = $this->orderModel->getHistory($userId, $userEmail);
        
        // Lọc theo status nếu có
        if ($statusFilter) {
            $orders = array_filter($orders, function($order) use ($statusFilter) {
                return ($order['status'] ?? '') === $statusFilter;
            });
        }
        
        error_log("OrderController::history - Found " . count($orders) . " orders");

        // Load items cho mỗi đơn hàng để hiển thị sản phẩm
        foreach ($orders as &$order) {
            $orderId = (int)($order['id'] ?? 0);
            if ($orderId > 0) {
                $order['items'] = $this->orderModel->getItems($orderId);
            } else {
                $order['items'] = [];
            }
        }
        unset($order);

        $view = 'orders/history';
        $title = 'Đơn hàng của tôi';
        $logoUrl = BASE_URL . 'assets/images/logo.png';
        $statusMap = OrderModel::statuses();

        require_once PATH_VIEW . 'main.php';
    }

    // Trang chi tiết đơn hàng
    public function detail(): void
    {
        $user = $this->requireUser();
        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$orderId) {
            set_flash('warning', 'Không tìm thấy đơn hàng.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        $order = $this->orderModel->findWithItems($orderId);

        if (!$order || !$this->canViewOrder($order, $user)) {
            set_flash('danger', 'Bạn không có quyền xem đơn hàng này.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        // Lấy thông tin yêu cầu trả hàng (nếu có)
        $returnRequest = $this->returnModel->findByOrder($orderId);

        // Auto-cancel nếu UNPAID quá 24h
        if (($order['status'] ?? '') === OrderModel::STATUS_UNPAID) {
            $createdAt = $order['created_at'] ?? null;
            if ($createdAt) {
                $deadline = strtotime($createdAt . ' +24 hours');
                if (time() >= $deadline) {
                    $this->orderModel->updateStatus($orderId, OrderModel::STATUS_CANCELLED);
                    $order = $this->orderModel->findWithItems($orderId);
                }
            }
        }

        // Nếu đơn đang ở trạng thái DELIVERED quá 3 ngày, tự động chuyển COMPLETED
        if (($order['status'] ?? '') === OrderModel::STATUS_DELIVERED) {
            $updatedAt = $order['updated_at'] ?? $order['created_at'] ?? null;
            if ($updatedAt) {
                $deadline = strtotime($updatedAt . ' +3 days');
                if (time() >= $deadline) {
                    $this->orderModel->updateStatus($orderId, OrderModel::STATUS_COMPLETED);
                    $order = $this->orderModel->findWithItems($orderId);
                }
            }
        }

        $canCancel = $this->orderModel->canCancel($order);
        
        // Load thông tin đánh giá chỉ khi đơn hàng đã hoàn thành
        // Chỉ cho phép đánh giá khi trạng thái là COMPLETED (sau khi user ấn "Tôi đã nhận hàng")
        $reviews = [];
        $canReview = ($order['status'] ?? '') === OrderModel::STATUS_COMPLETED;
        
        if ($canReview) {
            require_once PATH_MODEL . 'ReviewModel.php';
            $reviewModel = new ReviewModel();
            $userId = (int)($user['id'] ?? 0);
            
            // Kiểm tra đã đánh giá chưa cho từng item
            foreach ($order['items'] as &$item) {
                // Thử lấy id từ nhiều nguồn khác nhau
                $orderItemId = 0;
                if (isset($item['id']) && $item['id']) {
                    $orderItemId = (int)$item['id'];
                } elseif (isset($item['order_item_id']) && $item['order_item_id']) {
                    $orderItemId = (int)$item['order_item_id'];
                }
                
                if ($orderItemId > 0) {
                    $item['has_reviewed'] = $reviewModel->hasReviewed($orderItemId, $userId);
                    $existingReview = $reviewModel->getByOrderItem($orderItemId);
                    if ($existingReview) {
                        // Parse images nếu có
                        if (!empty($existingReview['images'])) {
                            $images = json_decode($existingReview['images'], true);
                            $existingReview['images'] = is_array($images) ? $images : [];
                        } else {
                            $existingReview['images'] = [];
                        }
                    }
                    $item['review'] = $existingReview;
                } else {
                    $item['has_reviewed'] = false;
                    $item['review'] = null;
                }
            }
            unset($item);
            
            // Nếu có tham số review=true trong URL, đánh dấu để tự động cuộn đến form đánh giá
            // (Khi admin vừa cập nhật trạng thái thành delivered)
            if (isset($_GET['review']) && $_GET['review'] === 'true') {
                // Có thể thêm logic ở đây nếu cần
            }
        }
        
        $view = 'orders/detail';
        $title = 'Chi tiết đơn hàng';
        $statusMap = OrderModel::statuses();
        $returnData = $returnRequest;

        require_once PATH_VIEW . 'main.php';
    }

    // Người dùng xác nhận đã nhận hàng (chuyển trạng thái từ Đã Giao -> Hoàn Thành)
    public function confirmReceived(): void
    {
        $user = $this->requireUser();
        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

        if (!$orderId) {
            set_flash('warning', 'Thiếu mã đơn hàng.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        $order = $this->orderModel->findWithItems($orderId);
        if (!$order || !$this->canViewOrder($order, $user)) {
            set_flash('danger', 'Bạn không có quyền thao tác trên đơn hàng này.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        if ($order['status'] !== OrderModel::STATUS_DELIVERED) {
            set_flash('warning', 'Chỉ có thể xác nhận khi đơn hàng đang ở trạng thái Đã Giao.');
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
            exit;
        }

        // Cập nhật trạng thái sang COMPLETED
        $this->orderModel->updateStatus($orderId, OrderModel::STATUS_COMPLETED);
        set_flash('success', 'Cảm ơn bạn đã xác nhận. Bạn có thể đánh giá sản phẩm.');
        header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId . '&review=true');
        exit;
    }

    // Xử lý yêu cầu hủy đơn hàng
    public function cancel(): void
    {
        $user = $this->requireUser();
        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

        if (!$orderId) {
            set_flash('warning', 'Thiếu mã đơn hàng.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        $order = $this->orderModel->findWithItems($orderId);
        if (!$order || !$this->canViewOrder($order, $user)) {
            set_flash('danger', 'Bạn không có quyền hủy đơn hàng này.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        if (!$this->orderModel->canCancel($order)) {
            set_flash('warning', 'Chỉ có thể hủy đơn hàng khi đơn chưa được giao.');
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
            exit;
        }

        // Lấy lý do hủy
        $reasonPre = trim($_POST['reason_predefined'] ?? '');
        $reasonOther = trim($_POST['reason_other'] ?? '');
        $reason = $reasonPre;
        if ($reasonPre === 'Lý do khác') {
            $reason = $reasonOther;
        }

        // Khôi phục tồn kho trước khi hủy đơn
        $this->orderModel->restoreStock($orderId);

        // Hủy trực tiếp: chuyển sang trạng thái cancelled
        $this->orderModel->updateStatus($orderId, OrderModel::STATUS_CANCELLED);

        // Lưu lý do vào order nếu có (bỏ qua nếu bảng không có cột cancel_reason)
        $this->orderModel->saveCancelReason($orderId, $reason ?: null);

        set_flash('success', 'Đã hủy đơn hàng thành công.');
        header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
        exit;
    }

    /**
     * Thanh toán lại đơn hàng UNPAID / PAYMENT_FAILED qua VNPay
     */
    public function pay(): void
    {
        $user = $this->requireUser();
        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$orderId) {
            set_flash('warning', 'Thiếu mã đơn hàng.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        $order = $this->orderModel->findWithItems($orderId);
        if (!$order || !$this->canViewOrder($order, $user)) {
            set_flash('danger', 'Bạn không có quyền thanh toán đơn này.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        if (!in_array($order['status'], [OrderModel::STATUS_UNPAID, OrderModel::STATUS_PAYMENT_FAILED], true)) {
            set_flash('warning', 'Chỉ thanh toán lại khi đơn đang chờ thanh toán hoặc thanh toán thất bại.');
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
            exit;
        }

        // Chuẩn bị redirect VNPay
        require_once PATH_ROOT . 'libs/VnPay.php';
        $vnp = new VnPay();

        $txnRef = $orderId . '_' . time();
        $amount = (float)($order['total_amount'] ?? 0);
        $orderInfo = 'Thanh toan don hang #' . ($order['order_code'] ?? $orderId);
        // Điều hướng về vnpay-return kèm order_id để chắc chắn mapping được đơn
        $returnUrl = BASE_URL . '?action=vnpay-return&order_id=' . $orderId;

        $payUrl = $vnp->createPaymentUrl([
            'txn_ref'    => $txnRef,
            'amount'     => $amount,
            'order_info' => $orderInfo,
            'return_url' => $returnUrl,
        ]);

        if (!$payUrl) {
            set_flash('danger', 'Không tạo được liên kết thanh toán. Vui lòng thử lại sau.');
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
            exit;
        }

        header('Location: ' . $payUrl);
        exit;
    }

    // Mua lại toàn bộ sản phẩm của đơn (thêm vào giỏ và chuyển tới trang giỏ hàng)
    public function rebuy(): void
    {
        $user = $this->requireUser();
        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$orderId) {
            set_flash('warning', 'Không tìm thấy đơn hàng để mua lại.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        $order = $this->orderModel->findWithItems($orderId);
        if (!$order || !$this->canViewOrder($order, $user)) {
            set_flash('danger', 'Bạn không có quyền mua lại đơn hàng này.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        // Chặn tài khoản admin mua hàng
        if (($user['role'] ?? '') === 'admin') {
            set_flash('warning', 'Tài khoản quản trị không thể mua hàng. Vui lòng sử dụng tài khoản khách hàng.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        if (empty($order['items'])) {
            set_flash('warning', 'Đơn hàng không có sản phẩm để mua lại.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        require_once PATH_MODEL . 'CartModel.php';
        require_once PATH_MODEL . 'ProductModel.php';

        $cartModel = new CartModel();
        $productModel = new ProductModel();

        $userId = (int)($user['id'] ?? $user['user_id'] ?? 0);
        if ($userId <= 0) {
            set_flash('warning', 'Vui lòng đăng nhập để mua lại đơn hàng.');
            header('Location: ' . BASE_URL . '?action=show-login');
            exit;
        }

        $cartId = $cartModel->getOrCreateCartIdByUserId($userId);
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $added = 0;
        $skipped = 0;

        foreach ($order['items'] as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            if ($productId <= 0) {
                $skipped++;
                continue;
            }

            $quantity = max(1, (int)($item['quantity'] ?? 1));
            $size = $item['variant_size'] ?? null;
            $color = $item['variant_color'] ?? null;

            $product = $productModel->getProductById($productId);
            if (!$product) {
                $skipped++;
                continue;
            }

            $variantId = null;
            $variant = null;
            if ($size || $color) {
                $variant = $productModel->getVariantByValueNames($productId, $size, $color);
                if ($variant) {
                    $variantId = (int)$variant['variant_id'];
                }
            }

            // Tính giá cuối cùng (bao gồm chênh lệch biến thể nếu có)
            $finalPrice = (float)$product['price'];
            if ($variant && isset($variant['additional_price']) && $variant['additional_price'] !== null) {
                $finalPrice += (float)$variant['additional_price'];
            }

            // Ảnh sản phẩm/biến thể
            $productImage = $product['image'] ?? '';
            if (!$productImage) {
                $images = $productModel->getProductImages($productId);
                if (!empty($images)) {
                    $productImage = $images[0]['image_url'] ?? '';
                }
            }
            if ($variant && !empty($variant['image_url'])) {
                $productImage = $variant['image_url'];
            }

            // Lưu DB
            $cartModel->addOrIncrementItem($cartId, $productId, $variantId, $quantity);

            // Lưu session
            $cartKey = $productId . '_' . ($size ?? 'null') . '_' . ($color ?? 'null');
            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'id' => $productId,
                    'variant_id' => $variantId,
                    'name' => $product['name'],
                    'price' => $finalPrice,
                    'image' => $productImage,
                    'quantity' => $quantity,
                    'size' => $size,
                    'color' => $color
                ];
            }

            $added++;
        }

        if ($added > 0) {
            $msg = "Đã thêm {$added} sản phẩm vào giỏ hàng.";
            if ($skipped > 0) {
                $msg .= " {$skipped} sản phẩm không còn khả dụng.";
            }
            set_flash('success', $msg);
        } else {
            set_flash('warning', 'Không thể thêm sản phẩm nào vào giỏ hàng. Có thể sản phẩm đã ngừng kinh doanh.');
        }

        header('Location: ' . BASE_URL . '?action=cart-list');
        exit;
    }

    // Bắt buộc đăng nhập, nếu không sẽ điều hướng về trang login
    private function requireUser(): array
    {
        if (!isset($_SESSION['user'])) {
            set_flash('warning', 'Vui lòng đăng nhập để xem đơn hàng.');
            header('Location: ' . BASE_URL . '?action=show-login');
            exit;
        }

        return $_SESSION['user'];
    }

    // Kiểm tra quyền truy cập đơn hàng (user sở hữu hoặc admin)
    private function canViewOrder(array $order, array $user): bool
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        // Lấy user_id từ nhiều nguồn
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
        $orderUserId = (int)($order['user_id'] ?? 0);
        
        // Kiểm tra theo user_id
        if ($userId > 0 && $orderUserId > 0 && $orderUserId === $userId) {
            return true;
        }
        
        // Kiểm tra theo email nếu user_id không khớp
        $userEmail = $user['email'] ?? null;
        $orderEmail = $order['email'] ?? null;
        
        if ($userEmail && $orderEmail && strtolower(trim($userEmail)) === strtolower(trim($orderEmail))) {
            return true;
        }
        
        return false;
    }
}
