<?php

/**
 * VNPay Payment Gateway Integration
 * 
 * Cần cấu hình trong configs/env.php:
 * - VNPAY_TMN_CODE: Mã website của bạn trên VNPay
 * - VNPAY_HASH_SECRET: Mã bảo mật từ VNPay
 * - VNPAY_URL: URL thanh toán VNPay (sandbox hoặc production)
 */

class VnPay
{
    private string $tmnCode;
    private string $hashSecret;
    private string $url;

    public function __construct()
    {
        $this->tmnCode = defined('VNPAY_TMN_CODE') ? VNPAY_TMN_CODE : '';
        $this->hashSecret = defined('VNPAY_HASH_SECRET') ? VNPAY_HASH_SECRET : '';
        $this->url = defined('VNPAY_URL') ? VNPAY_URL : 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    }

    /**
     * Tạo URL thanh toán VNPay
     */
    public function createPaymentUrl(array $params): string
    {
        // Kiểm tra cấu hình
        if (empty($this->tmnCode) || empty($this->hashSecret)) {
            throw new Exception('VNPay chưa được cấu hình đầy đủ. Vui lòng kiểm tra VNPAY_TMN_CODE và VNPAY_HASH_SECRET trong configs/env.php');
        }

        $vnp_TmnCode = $this->tmnCode;
        $vnp_HashSecret = $this->hashSecret;
        $vnp_Url = $this->url;
        $vnp_ReturnUrl = $params['return_url'] ?? BASE_URL . '?action=vnpay-return';
        
        // Kiểm tra xem IPN URL có phải localhost không
        // Nếu là localhost, không gửi IPN URL để tránh lỗi (VNPay không thể truy cập localhost)
        $vnp_IpnUrl = defined('VNPAY_IPN_URL') ? VNPAY_IPN_URL : (BASE_URL . '?action=vnpay-ipn');
        $isLocalhost = (stripos($vnp_IpnUrl, 'localhost') !== false || 
                       stripos($vnp_IpnUrl, '127.0.0.1') !== false ||
                       stripos($vnp_IpnUrl, '::1') !== false ||
                       stripos($vnp_IpnUrl, '192.168.') !== false ||
                       stripos($vnp_IpnUrl, '10.') !== false);

        $vnp_TxnRef = $params['txn_ref'] ?? time(); // Mã đơn hàng
        $vnp_OrderInfo = $params['order_info'] ?? 'Thanh toan don hang';
        $vnp_OrderType = $params['order_type'] ?? 'other';
        $vnp_Amount = (int)($params['amount'] * 100); // VNPay yêu cầu số tiền nhân 100
        
        // Validate số tiền
        if ($vnp_Amount <= 0) {
            throw new Exception('Số tiền thanh toán phải lớn hơn 0');
        }
        
        $vnp_Locale = $params['locale'] ?? 'vn';
        
        // Lấy IP thực tế của client
        $vnp_IpAddr = $this->getClientIp();
        
        $vnp_CreateDate = date('YmdHis');
        $vnp_ExpireDate = isset($params['expire_date']) ? $params['expire_date'] : date('YmdHis', strtotime('+15 minutes'));

        // Xây dựng mảng dữ liệu gửi lên VNPay
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $vnp_ExpireDate,
        );

        // Chỉ thêm IPN URL nếu:
        // 1. VNPAY_ENABLE_IPN được bật (true) HOẶC
        // 2. IPN URL không phải localhost (VNPay không thể truy cập localhost)
        $enableIpn = defined('VNPAY_ENABLE_IPN') ? VNPAY_ENABLE_IPN : !$isLocalhost;
        if ($enableIpn && !empty($vnp_IpnUrl)) {
            $inputData["vnp_IpnUrl"] = $vnp_IpnUrl;
        }

        // Thêm BankCode nếu có
        if (isset($params['bank_code']) && !empty($params['bank_code'])) {
            $inputData['vnp_BankCode'] = $params['bank_code'];
        }

        // Sắp xếp mảng theo key để tạo hash
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        // Tạo chữ ký bảo mật
        $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url = $vnp_Url . "?" . $query . "vnp_SecureHash=" . $vnp_SecureHash;

        // Log để debug (chỉ trong môi trường development)
        if (defined('DEBUG') && DEBUG) {
            error_log('VNPay Payment URL created: ' . $vnp_Url);
            error_log('VNPay Input Data: ' . json_encode($inputData));
        }

        return $vnp_Url;
    }
    
    /**
     * Lấy IP thực tế của client
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Xác thực callback từ VNPay
     */
    public function validateCallback(array $data): array
    {
        $vnp_SecureHash = $data['vnp_SecureHash'] ?? '';
        unset($data['vnp_SecureHash']);

        // Chỉ lấy các tham số bắt đầu bằng vnp_
        $vnpData = [];
        foreach ($data as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $vnpData[$key] = $value;
            }
        }

        ksort($vnpData);
        $i = 0;
        $hashdata = "";
        foreach ($vnpData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);

        if ($secureHash === $vnp_SecureHash) {
            $responseCode = $data['vnp_ResponseCode'] ?? '';
            $transactionStatus = $data['vnp_TransactionStatus'] ?? '';
            
            return [
                'success' => true,
                'txn_ref' => $data['vnp_TxnRef'] ?? '',
                'response_code' => $responseCode,
                'transaction_status' => $transactionStatus,
                'transaction_no' => $data['vnp_TransactionNo'] ?? '',
                'amount' => ($data['vnp_Amount'] ?? 0) / 100,
                'order_info' => $data['vnp_OrderInfo'] ?? '',
                'bank_code' => $data['vnp_BankCode'] ?? '',
                'pay_date' => $data['vnp_PayDate'] ?? '',
            ];
        }

        return [
            'success' => false, 
            'message' => 'Invalid signature',
            'response_code' => $data['vnp_ResponseCode'] ?? ''
        ];
    }
}

