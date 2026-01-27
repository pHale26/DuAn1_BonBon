<?php

require_once PATH_MODEL . 'CouponModel.php';
require_once PATH_MODEL . 'OrderModel.php';
require_once PATH_MODEL . 'UserModel.php';

class CouponController
{
    private CouponModel $couponModel;

    public function __construct()
    {
        $this->couponModel = new CouponModel();
    }

    /**
     * API để validate và tính toán mã giảm giá
     * POST: coupon_code, order_amount
     */
    public function validate()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $code = $data['coupon_code'] ?? '';
        $orderAmount = (float)($data['order_amount'] ?? 0);
        $productIds = is_array($data['product_ids'] ?? null) ? array_map('intval', $data['product_ids']) : [];
        
        if (empty($code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập mã giảm giá'
            ]);
            exit;
        }
        
        // Cho phép orderAmount = 0 để validate mã không yêu cầu đơn tối thiểu
        if ($orderAmount < 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tổng tiền đơn hàng không hợp lệ'
            ]);
            exit;
        }
        
        // Xác định khách mới nếu có đăng nhập
        $userId = $_SESSION['user']['id'] ?? null;
        $isNewCustomer = false;
        $isVipCustomer = false;
        if ($userId) {
            $orderModel = new OrderModel();
            $userModel = new UserModel();

            if (method_exists($orderModel, 'countValidOrders')) {
                $isNewCustomer = $orderModel->countValidOrders((int)$userId) === 0;
            } elseif (method_exists($orderModel, 'countDeliveredOrders')) {
                $isNewCustomer = $orderModel->countDeliveredOrders((int)$userId) === 0;
            }

            $userRank = $userModel->getRank((int)$userId) ?? 'customer';
            $hasVipOrder = method_exists($orderModel, 'hasDeliveredOrderOverAmount')
                ? $orderModel->hasDeliveredOrderOverAmount((int)$userId, 2000000)
                : false;

            if ($hasVipOrder && $userRank !== 'VIP') {
                $userModel->updateRank((int)$userId, 'VIP');
                $userRank = 'VIP';
            }

            $isVipCustomer = ($userRank === 'VIP');
        }

        $result = $this->couponModel->validateCouponDetailed(
            $code,
            $orderAmount,
            $userId ? (int)$userId : null,
            [],
            [],
            $isNewCustomer,
            false,
            false,
            $isVipCustomer
        );
        if (!$result['ok']) {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
            exit;
        }

        $coupon = $result['coupon'];
        $discount = $result['discount'];
        
        // Lưu mã giảm giá vào session
        $_SESSION['applied_coupon'] = [
            'id' => $coupon['coupon_id'],
            'code' => $coupon['code'],
            'name' => $coupon['name'],
            'discount_amount' => $discount['discount_amount'],
        ];
        
        echo json_encode([
            'success' => true,
            'coupon' => [
                'id' => $coupon['coupon_id'],
                'code' => $coupon['code'],
                'name' => $coupon['name'],
                'discount_type' => $coupon['discount_type'],
                'discount_value' => $coupon['discount_value'],
            ],
            'discount_amount' => $discount['discount_amount'],
            'final_amount' => $discount['final_amount'],
            'message' => 'Áp dụng mã giảm giá thành công!'
        ]);
        exit;
    }

    /**
     * API để lấy danh sách mã giảm giá khả dụng
     * GET: order_amount
     */
    public function getAvailable()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $orderAmount = (float)($_GET['order_amount'] ?? 0);
        
        // Cho phép orderAmount = 0 để hiển thị các mã không yêu cầu đơn tối thiểu
        // Nếu orderAmount < 0 thì mới là lỗi
        if ($orderAmount < 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tổng tiền đơn hàng không hợp lệ',
                'coupons' => []
            ]);
            exit;
        }
        
        $coupons = $this->couponModel->getAvailableCoupons($orderAmount);
        
        echo json_encode([
            'success' => true,
            'coupons' => $coupons
        ]);
        exit;
    }

    /**
     * API để xóa mã giảm giá đã áp dụng
     */
    public function remove()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        unset($_SESSION['applied_coupon']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa mã giảm giá'
        ]);
        exit;
    }
}

