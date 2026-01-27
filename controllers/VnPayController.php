<?php

require_once PATH_ROOT . 'libs/VnPay.php';
require_once PATH_MODEL . 'OrderModel.php';

class VnPayController
{
    private VnPay $vnpay;
    private OrderModel $orderModel;

    public function __construct()
    {
        $this->vnpay = new VnPay();
        $this->orderModel = new OrderModel();
    }

   
    public function ipn(): void
    {
        // IPN phải trả về JSON, không redirect
        header('Content-Type: application/json; charset=utf-8');
        
        // Lấy tất cả tham số từ GET hoặc POST
        $inputData = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        $returnData = [
            'RspCode' => '99',
            'Message' => 'Unknown error'
        ];
        
        try {
            // Validate callback từ VNPay
            $result = $this->vnpay->validateCallback($inputData);
            
            if (!$result['success']) {
                $returnData = [
                    'RspCode' => '97',
                    'Message' => 'Invalid signature'
                ];
                echo json_encode($returnData);
                exit;
            }
            
            // Lấy order_id từ vnp_TxnRef (format: orderId_timestamp)
            $txnRef = $result['txn_ref'] ?? '';
            $orderIdParts = explode('_', $txnRef);
            $orderId = (int)($orderIdParts[0] ?? 0);
            
            if (!$orderId) {
                $returnData = [
                    'RspCode' => '01',
                    'Message' => 'Order not found'
                ];
                echo json_encode($returnData);
                exit;
            }
            
            // Lấy thông tin đơn hàng
            $order = $this->orderModel->findWithItems($orderId);
            
            if (!$order) {
                $returnData = [
                    'RspCode' => '01',
                    'Message' => 'Order not found'
                ];
                echo json_encode($returnData);
                exit;
            }
            
            // Kiểm tra số tiền
            $vnpAmount = $result['amount'] ?? 0;
            $orderAmount = (float)($order['total_amount'] ?? 0);
            
            // So sánh số tiền (cho phép sai số nhỏ do làm tròn)
            if (abs($vnpAmount - $orderAmount) > 1000) {
                $returnData = [
                    'RspCode' => '04',
                    'Message' => 'Invalid amount'
                ];
                echo json_encode($returnData);
                exit;
            }
            
            // Xử lý thanh toán cho đơn đang ở trạng thái UNPAID/PAYMENT_FAILED
            if (in_array($order['status'], [OrderModel::STATUS_UNPAID, OrderModel::STATUS_PAYMENT_FAILED], true)) {
                
                // Kiểm tra response code
                $responseCode = $result['response_code'] ?? '';
                
                if ($responseCode === '00') {
                    // Thanh toán thành công -> chuyển sang PAID rồi PENDING (chờ shop xác nhận)
                    $this->orderModel->updateStatus($orderId, OrderModel::STATUS_PAID);
                    $this->orderModel->updateStatus($orderId, OrderModel::STATUS_PENDING);
                    $returnData = [
                        'RspCode' => '00',
                        'Message' => 'Confirm Success'
                    ];
                } else {
                    // Thanh toán thất bại: giữ trạng thái UNPAID để khách thanh toán lại
                    $this->orderModel->updateStatus($orderId, OrderModel::STATUS_UNPAID);
                    $returnData = [
                        'RspCode' => '00',
                        'Message' => 'Payment failed'
                    ];
                }
            } else {
                // Đơn hàng đã được xác nhận trước đó
                $returnData = [
                    'RspCode' => '02',
                    'Message' => 'Order already confirmed'
                ];
            }
            
        } catch (Exception $e) {
            error_log('VNPay IPN Error: ' . $e->getMessage());
            $returnData = [
                'RspCode' => '99',
                'Message' => 'Unknown error: ' . $e->getMessage()
            ];
        }
        
        echo json_encode($returnData);
        exit;
    }

    /**
     * Xử lý callback từ VNPay sau khi thanh toán (return URL)
     */
    public function return(): void
    {
        // Lấy order_id từ query string hoặc từ vnp_TxnRef
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        
        // Nếu không có order_id trong query, thử lấy từ vnp_TxnRef
        if (!$orderId && isset($_GET['vnp_TxnRef'])) {
            $txnRef = $_GET['vnp_TxnRef'];
            $orderIdParts = explode('_', $txnRef);
            $orderId = (int)($orderIdParts[0] ?? 0);
        }
        
        if (!$orderId) {
            set_flash('danger', 'Không tìm thấy đơn hàng.');
            header('Location: ' . BASE_URL . '?action=order-history');
            exit;
        }

        // Validate callback từ VNPay
        $result = $this->vnpay->validateCallback($_GET);

        if ($result['success'] && $result['response_code'] === '00') {
            // Thanh toán thành công
            $order = $this->orderModel->findWithItems($orderId);
            
            if (!$order) {
                set_flash('danger', 'Không tìm thấy đơn hàng.');
                header('Location: ' . BASE_URL . '?action=order-history');
                exit;
            }
            
            // Kiểm tra số tiền để đảm bảo an toàn
            $vnpAmount = $result['amount'] ?? 0;
            $orderAmount = (float)($order['total_amount'] ?? 0);
            
            if (abs($vnpAmount - $orderAmount) > 1000) {
                error_log("VNPay Return: Amount mismatch. Order: $orderAmount, VNPay: $vnpAmount");
                set_flash('warning', 'Có sự không khớp về số tiền. Vui lòng liên hệ hỗ trợ.');
                header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
                exit;
            }
            
            // Cập nhật trạng thái nếu đơn đang UNPAID / PAYMENT_FAILED
            if (in_array($order['status'], [OrderModel::STATUS_UNPAID, OrderModel::STATUS_PAYMENT_FAILED], true)) {
                $this->orderModel->updateStatus($orderId, OrderModel::STATUS_PAID);
                $this->orderModel->updateStatus($orderId, OrderModel::STATUS_PENDING); // chờ shop xác nhận
            }

            // Xóa đơn hàng tạm khỏi session
            unset($_SESSION['pending_vnpay_order']);
            
            // Xóa giỏ hàng và coupon
            if (!empty($_SESSION['selected_cart_items'])) {
                foreach ($_SESSION['selected_cart_items'] as $cartKey) {
                    if (isset($_SESSION['cart'][$cartKey])) {
                        unset($_SESSION['cart'][$cartKey]);
                    }
                }
            }
            unset($_SESSION['selected_cart_items']);
            unset($_SESSION['applied_coupon']);
            
            set_flash('success', 'Đặt hàng thành công! Chúng tôi sẽ liên hệ để xác nhận. Bạn có thể theo dõi trạng thái đơn hàng tại đây.', ['order_id' => $orderId]);
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId . '&payment_success=1');
            exit;
        } else {
            // Thanh toán thất bại hoặc bị hủy
            $order = $this->orderModel->findWithItems($orderId);
            
            if (!$order) {
                set_flash('danger', 'Không tìm thấy đơn hàng.');
                header('Location: ' . BASE_URL . '?action=order-history');
                exit;
            }
            
            unset($_SESSION['pending_vnpay_order']);
            
            // Lấy thông báo lỗi chi tiết từ VNPay
            $responseCode = $result['response_code'] ?? '';
            $message = $this->getVnPayErrorMessage($responseCode);
            
            set_flash('warning', $message);
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
            exit;
        }
    }
    
    /**
     * Lấy thông báo lỗi từ mã response code của VNPay
     */
    private function getVnPayErrorMessage(string $responseCode): string
    {
        $messages = [
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường).',
            '09' => 'Thẻ/Tài khoản chưa đăng ký dịch vụ InternetBanking',
            '10' => 'Xác thực thông tin thẻ/tài khoản không đúng. Quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán. Vui lòng thực hiện lại giao dịch.',
            '12' => 'Thẻ/Tài khoản bị khóa.',
            '13' => 'Nhập sai mật khẩu xác thực giao dịch (OTP). Quá số lần quy định.',
            '51' => 'Tài khoản không đủ số dư để thực hiện giao dịch.',
            '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
            '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định.',
            '99' => 'Lỗi không xác định.',
        ];
        
        return $messages[$responseCode] ?? 'Thanh toán không thành công. Mã lỗi: ' . $responseCode;
    }
}

