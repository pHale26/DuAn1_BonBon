<?php

require_once PATH_MODEL . 'UserModel.php';
require_once PATH_MODEL . 'UserAddressModel.php';

class CheckoutController
{
    private OrderModel $orderModel;
    private UserModel $userModel;
    private UserAddressModel $userAddressModel;

    public function __construct()
    {
        // Chuẩn bị model order để tái sử dụng
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
        $this->userAddressModel = new UserAddressModel();
    }

    public function index()
    {
        // Chặn admin không được checkout
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
            set_flash('warning', 'Tài khoản quản trị không thể mua hàng. Vui lòng sử dụng tài khoản khách hàng.');
            header('Location: ' . BASE_URL . '?action=admin-dashboard');
            exit;
        }

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            header('Location: ' . BASE_URL . '?action=cart-list');
            exit;
        }

        // Lấy danh sách sản phẩm đã chọn để mua
        $selectedItems = $_SESSION['selected_cart_items'] ?? [];
        
        // Nếu có danh sách đã chọn, chỉ lấy các sản phẩm đó
        if (!empty($selectedItems)) {
            $filteredCart = [];
            foreach ($selectedItems as $cartKey) {
                if (isset($cart[$cartKey])) {
                    $filteredCart[$cartKey] = $cart[$cartKey];
                }
            }
            $cart = $filteredCart;
        }
        
        // Nếu không có sản phẩm nào được chọn, quay về giỏ hàng
        if (empty($cart)) {
            header('Location: ' . BASE_URL . '?action=cart-list');
            exit;
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Lấy thông tin user và địa chỉ nếu đã đăng nhập
        $user = null;
        $defaultAddress = null;
        $userAddresses = [];
        
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
            $user = $this->userModel->findById($userId);
            
            // Lấy địa chỉ mặc định từ bảng user_addresses
            $defaultAddress = $this->userAddressModel->getDefaultByUserId($userId);
            
            // Nếu không có địa chỉ trong user_addresses, tạo từ thông tin user
            if (!$defaultAddress && $user) {
                $defaultAddress = [
                    'fullname' => $user['full_name'] ?? ($user['first_name'] . ' ' . $user['last_name']),
                    'phone' => $user['phone'] ?? '',
                    'email' => $user['email'] ?? '',
                    'address' => $user['address'] ?? '',
                    'city' => null,
                    'district' => null,
                    'ward' => null,
                    'is_default' => 1,
                ];
            }
            
            // Lấy tất cả địa chỉ của user
            $userAddresses = $this->userAddressModel->getByUserId($userId);
        }

        // Logo cho view
        $logoUrl = BASE_URL . 'assets/images/logo.png';
        if (file_exists(PATH_ROOT . 'assets/images/logo.png')) {
            $data = file_get_contents(PATH_ROOT . 'assets/images/logo.png');
            $type = pathinfo(PATH_ROOT . 'assets/images/logo.png', PATHINFO_EXTENSION);
            $logoUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $view = 'checkout';
        $title = 'Thanh Toán - BonBonWear';
        require_once PATH_VIEW . 'main.php';
    }

    public function process()
    {
        // Chặn admin không được đặt hàng
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
            set_flash('warning', 'Tài khoản quản trị không thể mua hàng. Vui lòng sử dụng tài khoản khách hàng.');
            header('Location: ' . BASE_URL . '?action=admin-dashboard');
            exit;
        }

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            set_flash('warning', 'Giỏ hàng trống, không thể đặt hàng.');
            header('Location: ' . BASE_URL . '?action=cart-list');
            exit;
        }

        // Lấy danh sách sản phẩm đã chọn để mua
        $selectedItems = $_SESSION['selected_cart_items'] ?? [];
        
        // Nếu có danh sách đã chọn, chỉ lấy các sản phẩm đó
        if (!empty($selectedItems)) {
            $filteredCart = [];
            foreach ($selectedItems as $cartKey) {
                if (isset($cart[$cartKey])) {
                    $filteredCart[$cartKey] = $cart[$cartKey];
                }
            }
            $cart = $filteredCart;
        }
        
        // Nếu không có sản phẩm nào được chọn, quay về giỏ hàng
        if (empty($cart)) {
            set_flash('warning', 'Vui lòng chọn ít nhất một sản phẩm để mua.');
            header('Location: ' . BASE_URL . '?action=cart-list');
            exit;
        }

        // Kiểm tra nhanh các field bắt buộc
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $requiredFields = ['fullname', 'phone', 'email', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errorMessage = 'Vui lòng nhập đầy đủ thông tin giao hàng.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                    exit;
                }
                set_flash('danger', $errorMessage);
                header('Location: ' . BASE_URL . '?action=checkout');
                exit;
            }
        }

        $userId = $_SESSION['user']['id'] ?? null;
        $isNewCustomer = false;
        $isVipCustomer = false;
        if ($userId) {
            try {
                // Khách mới: chưa có đơn giao thành công
                if (method_exists($this->orderModel, 'countDeliveredOrders')) {
                    $isNewCustomer = $this->orderModel->countDeliveredOrders((int)$userId) === 0;
                }

                // Xét VIP: có đơn giao thành công với tổng tiền >= 2.000.000đ
                $userRank = 'customer';
                try {
                    $userRank = $this->userModel->getRank((int)$userId) ?? 'customer';
                } catch (Throwable $e) {
                    error_log('Error getting user rank: ' . $e->getMessage());
                    $userRank = 'customer';
                }
                
                $hasVipOrder = false;
                if (method_exists($this->orderModel, 'hasDeliveredOrderOverAmount')) {
                    try {
                        $hasVipOrder = $this->orderModel->hasDeliveredOrderOverAmount((int)$userId, 2000000);
                    } catch (Throwable $e) {
                        error_log('Error checking VIP order: ' . $e->getMessage());
                        $hasVipOrder = false;
                    }
                }
                
                if ($hasVipOrder && $userRank !== 'VIP') {
                    try {
                        $this->userModel->updateRank((int)$userId, 'VIP');
                        $userRank = 'VIP';
                    } catch (Throwable $e) {
                        error_log('Error updating user rank: ' . $e->getMessage());
                        // Tiếp tục với rank hiện tại
                    }
                }
                $isVipCustomer = ($userRank === 'VIP');
            } catch (Throwable $e) {
                // Nếu có lỗi khi kiểm tra user info, tiếp tục với giá trị mặc định
                error_log('Error checking user info in checkout: ' . $e->getMessage());
                $isNewCustomer = false;
                $isVipCustomer = false;
            }
        }
        $total = 0;
        $items = [];
        $hasFlashSaleItem = false;

        // Load ProductModel để lấy ảnh sản phẩm đúng cách
        require_once PATH_MODEL . 'ProductModel.php';
        $productModel = new ProductModel();

        // Chuyển dữ liệu giỏ hàng thành payload để lưu vào DB
        foreach ($cart as $item) {
            $lineTotal = $item['price'] * $item['quantity'];
            $total += $lineTotal;
            
            // Validate dữ liệu item
            if (empty($item['name'])) {
                set_flash('danger', 'Thông tin sản phẩm không hợp lệ. Vui lòng thử lại.');
                header('Location: ' . BASE_URL . '?action=checkout');
                exit;
            }
            
            // Lấy ảnh sản phẩm đúng cách từ database
            $productId = (int)($item['id'] ?? 0);
            $size = $item['size'] ?? null;
            $color = $item['color'] ?? null;
            $productImage = $item['image'] ?? null;
            
            // Nếu có product_id, lấy ảnh từ database để đảm bảo đúng
            if ($productId > 0) {
                $product = $productModel->getProductById($productId);
                if ($product) {
                    // Kiểm tra số lượng tồn kho
                    $availableStock = 0;
                    $variant = null;
                    
                    if ($size || $color) {
                        $variant = $productModel->getVariantByValueNames($productId, $size, $color);
                        if ($variant && isset($variant['stock'])) {
                            $availableStock = (int)$variant['stock'];
                        } else {
                            $availableStock = 0;
                        }
                    } else {
                        $availableStock = (int)($product['stock'] ?? 0);
                    }
                    
                    // Kiểm tra nếu số lượng mua vượt quá số lượng tồn kho
                    // Lưu ý: Stock đã bị trừ khi thêm vào giỏ hàng, nên cần cộng lại số lượng trong giỏ để có tồn kho thực tế
                    $requestedQuantity = (int)($item['quantity'] ?? 1);
                    // Tồn kho thực tế = stock hiện tại (đã trừ) + số lượng đang có trong giỏ hàng
                    $actualAvailableStock = $availableStock + $requestedQuantity;
                    
                    // Cho phép mua nếu số lượng yêu cầu <= tồn kho thực tế (cho phép mua đến khi hết hàng)
                    if ($requestedQuantity > $actualAvailableStock) {
                        $productName = $item['name'] ?? 'Sản phẩm';
                        set_flash('danger', "Số lượng tồn kho không đủ cho sản phẩm \"{$productName}\". Hiện tại còn {$actualAvailableStock} sản phẩm. Vui lòng giảm số lượng hoặc xóa sản phẩm khỏi giỏ hàng.");
                        header('Location: ' . BASE_URL . '?action=cart-list');
                        exit;
                    }
                    
                    // Lấy ảnh sản phẩm
                    $productImage = $product['image'] ?? '';
                    if (!$productImage) {
                        $images = $productModel->getProductImages($productId);
                        if (!empty($images)) {
                            $productImage = $images[0]['image_url'] ?? '';
                        }
                    }
                    
                    // Nếu có variant, ưu tiên ảnh variant
                    if ($variant && !empty($variant['image_url'])) {
                        $productImage = $variant['image_url'];
                    }
                }
            }
            
            $items[] = [
                'product_id'    => $productId > 0 ? $productId : null,
                'product_name'  => trim($item['name']),
                'variant_size'  => $size,
                'variant_color' => $color,
                'quantity'      => (int)($item['quantity'] ?? 1),
                'unit_price'    => (float)($item['price'] ?? 0),
                'image_url'     => $productImage,
            ];

            // Phát hiện sản phẩm Flash Sale (nếu giỏ hàng có đánh dấu)
            if (!empty($item['is_flash_sale']) || (!empty($item['flash_sale']))) {
                $hasFlashSaleItem = true;
            }
        }
        
        // Validate items
        if (empty($items)) {
            set_flash('danger', 'Giỏ hàng trống. Vui lòng thêm sản phẩm vào giỏ hàng.');
            header('Location: ' . BASE_URL . '?action=cart-list');
            exit;
        }
        
        // Xử lý mã giảm giá (ưu tiên từ session, sau đó từ POST)
        $couponId = null;
        $discountAmount = 0;
        $finalTotal = $total;
        
        require_once PATH_MODEL . 'CouponModel.php';
        $couponModel = new CouponModel();
        
        // Biến để lưu snapshot thông tin mã giảm giá
        $couponCode = null;
        $couponName = null;
        
        // Chuẩn bị danh sách sản phẩm và danh mục cho coupon validation
        require_once PATH_MODEL . 'ProductModel.php';
        $productModel = new ProductModel();
        $productIds = [];
        $categoryIds = [];
        foreach ($items as $it) {
            if (!empty($it['product_id'])) {
                $pid = (int)$it['product_id'];
                $productIds[] = $pid;
                $product = $productModel->getProductById($pid);
                if ($product && isset($product['category_id'])) {
                    $categoryIds[] = (int)$product['category_id'];
                }
            }
        }

        // Kiểm tra mã giảm giá từ session trước
        if (isset($_SESSION['applied_coupon'])) {
            try {
                $appliedCoupon = $_SESSION['applied_coupon'];
                $check = $couponModel->validateCouponDetailed(
                    $appliedCoupon['code'],
                    $total,
                    $userId ? (int)$userId : null,
                    [],
                    [],
                    $isNewCustomer,
                    false,
                    $hasFlashSaleItem,
                    $isVipCustomer
                );
                if ($check['ok'] && $check['coupon'] && (int)$check['coupon']['coupon_id'] === (int)$appliedCoupon['id']) {
                    $discountAmount = $check['discount']['discount_amount'];
                    $finalTotal = $check['discount']['final_amount'];
                    $couponId = (int)$check['coupon']['coupon_id'];
                    // Lưu snapshot thông tin mã giảm giá
                    $couponCode = $check['coupon']['code'];
                    $couponName = $check['coupon']['name'];
                } else {
                    // Mã không còn hợp lệ, xóa khỏi session
                    unset($_SESSION['applied_coupon']);
                }
            } catch (Throwable $e) {
                // Nếu có lỗi khi validate coupon, bỏ qua và tiếp tục không dùng coupon
                error_log('Error validating coupon from session: ' . $e->getMessage());
                unset($_SESSION['applied_coupon']);
            }
        }
        
        // Nếu không có từ session, kiểm tra từ POST
        if (!$couponId && !empty($_POST['coupon_id']) && !empty($_POST['applied_coupon_code'])) {
            try {
                $check = $couponModel->validateCouponDetailed(
                    $_POST['applied_coupon_code'],
                    $total,
                    $userId ? (int)$userId : null,
                    [],
                    [],
                    $isNewCustomer,
                    false,
                    $hasFlashSaleItem,
                    $isVipCustomer
                );
                if ($check['ok'] && $check['coupon'] && (int)$check['coupon']['coupon_id'] === (int)$_POST['coupon_id']) {
                    $discountAmount = $check['discount']['discount_amount'];
                    $finalTotal = $check['discount']['final_amount'];
                    $couponId = (int)$check['coupon']['coupon_id'];
                    // Lưu snapshot thông tin mã giảm giá
                    $couponCode = $check['coupon']['code'];
                    $couponName = $check['coupon']['name'];
                }
            } catch (Throwable $e) {
                // Nếu có lỗi khi validate coupon, bỏ qua và tiếp tục không dùng coupon
                error_log('Error validating coupon from POST: ' . $e->getMessage());
            }
        }
        
        // Xóa danh sách đã chọn sau khi đặt hàng
        unset($_SESSION['selected_cart_items']);

        // Lưu địa chỉ nếu user chọn và đã đăng nhập
        if (isset($_POST['save_address']) && $_POST['save_address'] == '1' && $userId) {
            try {
                $addressData = [
                    'user_id' => $userId,
                    'fullname' => trim($_POST['fullname']),
                    'phone' => trim($_POST['phone']),
                    'email' => trim($_POST['email']),
                    'address' => trim($_POST['address']),
                    'city' => $_POST['city'] ?? null,
                    'district' => $_POST['district'] ?? null,
                    'ward' => $_POST['ward'] ?? null,
                    'is_default' => 0, // Không tự động đặt làm mặc định
                ];
                // Đảm bảo KHÔNG có id trong addressData
                unset($addressData['id'], $addressData['address_id'], $addressData['addressId']);
                
                $this->userAddressModel->create($addressData);
            } catch (Exception $e) {
                // Không fail nếu không lưu được địa chỉ, chỉ log
                error_log('Failed to save address: ' . $e->getMessage());
            }
        }

        // Gói dữ liệu đơn hàng chính (bao gồm snapshot mã giảm giá)
        // QUAN TRỌNG: KHÔNG BAO GIỜ thêm order_id, id, hoặc bất kỳ PRIMARY KEY nào vào đây
        $orderPayload = [
            'user_id'        => $userId,
            'fullname'       => trim($_POST['fullname']),
            'email'          => trim($_POST['email']),
            'phone'          => trim($_POST['phone']),
            'address'        => trim($_POST['address']),
            'city'           => $_POST['city'] ?? null,
            'district'       => $_POST['district'] ?? null,
            'ward'           => $_POST['ward'] ?? null,
            'note'           => $_POST['note'] ?? null,
            'payment_method' => $_POST['payment_method'] ?? 'cod',
            'status'         => (($_POST['payment_method'] ?? 'cod') === 'cod') ? OrderModel::STATUS_PENDING : OrderModel::STATUS_UNPAID,
            'total_amount'   => $finalTotal,
            'coupon_id'      => $couponId,
            'discount_amount' => $discountAmount,
            'coupon_code'    => $couponCode,  // Snapshot: mã code tại thời điểm đặt hàng
            'coupon_name'    => $couponName,  // Snapshot: tên mã tại thời điểm đặt hàng
        ];
        
        // Đảm bảo KHÔNG có order_id hoặc id trong orderPayload
        unset($orderPayload['order_id'], $orderPayload['id'], $orderPayload['orderId']);
        
        // Loại bỏ order_id từ $_POST nếu có (phòng trường hợp form gửi lên)
        if (isset($_POST['order_id']) || isset($_POST['id'])) {
            error_log('CheckoutController::process - WARNING: Found order_id or id in $_POST, removing...');
        }

        $paymentMethod = $_POST['payment_method'] ?? 'cod';
        
        // Kiểm tra nếu là AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Nếu thanh toán qua VNPay
        if ($paymentMethod === 'banking') {
            // Kiểm tra xem VNPay đã được cấu hình chưa
            if (!defined('VNPAY_TMN_CODE') || empty(VNPAY_TMN_CODE)) {
                $errorMessage = 'Hệ thống thanh toán VNPay chưa được cấu hình. Vui lòng chọn phương thức thanh toán khác.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                }
                set_flash('warning', $errorMessage);
                header('Location: ' . BASE_URL . '?action=checkout');
                exit;
            }
            
            $vnpayPath = PATH_ROOT . 'libs/VnPay.php';
            if (!file_exists($vnpayPath)) {
                $errorMessage = 'Không tìm thấy file VNPay. Vui lòng thử lại sau.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                }
                set_flash('danger', $errorMessage);
                header('Location: ' . BASE_URL . '?action=checkout');
                exit;
            }
            
            require_once $vnpayPath;
            
            try {
                $vnpay = new VnPay();
                
                // Tạo đơn hàng trước
                $orderId = $this->orderModel->create($orderPayload, $items);
                
                // Tăng số lần sử dụng mã giảm giá nếu có
                if ($couponId) {
                    require_once PATH_MODEL . 'CouponModel.php';
                    $couponModel = new CouponModel();
                    $couponModel->incrementUsage($couponId, 1);
                    $couponModel->logUsage($couponId, $userId ? (int)$userId : null, $orderId, $discountAmount);
                    unset($_SESSION['applied_coupon']);
                }
                
                // Xóa giỏ hàng
                if (!empty($selectedItems)) {
                    foreach ($selectedItems as $cartKey) {
                        if (isset($_SESSION['cart'][$cartKey])) {
                            unset($_SESSION['cart'][$cartKey]);
                        }
                    }
                }
                unset($_SESSION['selected_cart_items']);
                
                // Xóa khỏi database nếu user đã đăng nhập
                if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                    try {
                        require_once PATH_MODEL . 'CartModel.php';
                        $cartModel = new CartModel();
                        $userId = (int)$_SESSION['user']['id'];
                        $cartId = $cartModel->getOrCreateCartIdByUserId($userId);
                        
                        if (!empty($selectedItems)) {
                            foreach ($selectedItems as $cartKey) {
                                try {
                                    $parts = explode('_', $cartKey);
                                    if (count($parts) >= 1) {
                                        $productId = (int)$parts[0];
                                        $size = ($parts[1] ?? 'null') !== 'null' ? $parts[1] : null;
                                        $color = ($parts[2] ?? 'null') !== 'null' ? $parts[2] : null;
                                        
                                        $variantId = null;
                                        if ($size || $color) {
                                            require_once PATH_MODEL . 'ProductModel.php';
                                            $productModel = new ProductModel();
                                            $variant = $productModel->getVariantByValueNames($productId, $size, $color);
                                            if ($variant) {
                                                $variantId = (int)$variant['variant_id'];
                                            }
                                        }
                                        
                                        $cartItemId = $cartModel->findCartItemIdByKey($cartId, $cartKey, $variantId);
                                        if ($cartItemId) {
                                            $cartModel->deleteItem($cartItemId);
                                        }
                                    }
                                } catch (Throwable $e) {
                                    // Log lỗi nhưng không dừng quá trình checkout
                                    error_log('Error deleting cart item from DB (VNPay): ' . $e->getMessage());
                                }
                            }
                        }
                    } catch (Throwable $e) {
                        // Log lỗi nhưng không dừng quá trình checkout
                        error_log('Error deleting cart from DB (VNPay): ' . $e->getMessage());
                    }
                }
                
                // Tạo URL thanh toán VNPay
                // Lưu ý: order_info không được vượt quá 255 ký tự
                $orderInfo = 'Thanh toan don hang #' . $orderId;
                if (strlen($orderInfo) > 255) {
                    $orderInfo = substr($orderInfo, 0, 252) . '...';
                }
                
                $vnpayUrl = $vnpay->createPaymentUrl([
                    'txn_ref' => $orderId . '_' . time(),
                    'amount' => $finalTotal,
                    'order_info' => $orderInfo,
                    'return_url' => BASE_URL . '?action=vnpay-return&order_id=' . $orderId,
                ]);
                
                // Lưu order_id vào session để xử lý sau
                $_SESSION['pending_vnpay_order'] = $orderId;
                
                // Nếu là AJAX request, trả về JSON với redirect URL
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'order_id' => $orderId,
                        'total_amount' => $finalTotal,
                        'payment_method' => 'banking',
                        'redirect_url' => $vnpayUrl,
                        'message' => 'Đang chuyển hướng đến trang thanh toán VNPay...'
                    ]);
                    exit;
                }
                
                // Redirect đến VNPay (non-AJAX)
                header('Location: ' . $vnpayUrl);
                exit;
            } catch (Throwable $exception) {
                // Log lỗi để debug
                error_log('Checkout VNPay Error: ' . $exception->getMessage());
                $errorMessage = 'Có lỗi xảy ra khi tạo đơn hàng: ' . $exception->getMessage();
                
                // Nếu là AJAX request, trả về JSON
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                }
                
                set_flash('danger', $errorMessage);
                header('Location: ' . BASE_URL . '?action=checkout');
                exit;
            }
        }

        try {
            // Lưu đơn + item (cho thanh toán COD)
            $orderId = $this->orderModel->create($orderPayload, $items);
            
            // Tăng số lần sử dụng mã giảm giá nếu có (mỗi đơn hàng = 1 lần sử dụng)
            if ($couponId) {
                require_once PATH_MODEL . 'CouponModel.php';
                $couponModel = new CouponModel();
                $couponModel->incrementUsage($couponId, 1);
                $couponModel->logUsage($couponId, $userId ? (int)$userId : null, $orderId, $discountAmount);
                // Xóa mã giảm giá khỏi session sau khi sử dụng
                unset($_SESSION['applied_coupon']);
            }
            
            // Xóa các sản phẩm đã đặt hàng khỏi giỏ hàng
            if (!empty($selectedItems)) {
                foreach ($selectedItems as $cartKey) {
                    if (isset($_SESSION['cart'][$cartKey])) {
                        unset($_SESSION['cart'][$cartKey]);
                    }
                }
            } else {
                // Nếu không có danh sách đã chọn, xóa toàn bộ giỏ hàng
                unset($_SESSION['cart']);
            }
            
            // Xóa danh sách đã chọn
            unset($_SESSION['selected_cart_items']);
            
            // Xóa khỏi database nếu user đã đăng nhập
            if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                try {
                    require_once PATH_MODEL . 'CartModel.php';
                    $cartModel = new CartModel();
                    $userId = (int)$_SESSION['user']['id'];
                    $cartId = $cartModel->getOrCreateCartIdByUserId($userId);
                    
                    if (!empty($selectedItems)) {
                        foreach ($selectedItems as $cartKey) {
                            try {
                                // Parse cartKey để tìm cart_item_id
                                $parts = explode('_', $cartKey);
                                if (count($parts) >= 1) {
                                    $productId = (int)$parts[0];
                                    $size = ($parts[1] ?? 'null') !== 'null' ? $parts[1] : null;
                                    $color = ($parts[2] ?? 'null') !== 'null' ? $parts[2] : null;
                                    
                                    // Tìm variant_id
                                    $variantId = null;
                                    if ($size || $color) {
                                        require_once PATH_MODEL . 'ProductModel.php';
                                        $productModel = new ProductModel();
                                        $variant = $productModel->getVariantByValueNames($productId, $size, $color);
                                        if ($variant) {
                                            $variantId = (int)$variant['variant_id'];
                                        }
                                    }
                                    
                                    // Tìm và xóa cart_item
                                    $cartItemId = $cartModel->findCartItemIdByKey($cartId, $cartKey, $variantId);
                                    if ($cartItemId) {
                                        $cartModel->deleteItem($cartItemId);
                                    }
                                }
                            } catch (Throwable $e) {
                                // Log lỗi nhưng không dừng quá trình checkout
                                error_log('Error deleting cart item from DB: ' . $e->getMessage());
                            }
                        }
                    }
                } catch (Throwable $e) {
                    // Log lỗi nhưng không dừng quá trình checkout
                    error_log('Error deleting cart from DB: ' . $e->getMessage());
                }
            }
            
            set_flash('success', 'Đặt hàng thành công! Chúng tôi sẽ liên hệ để xác nhận. Bạn có thể theo dõi trạng thái đơn hàng tại đây.', ['order_id' => $orderId]);
            
            // Kiểm tra nếu là AJAX request thì trả về JSON
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'order_id' => $orderId,
                    'total_amount' => $finalTotal,
                    'redirect_url' => BASE_URL . '?action=order-detail&id=' . $orderId,
                    'message' => 'Đặt hàng thành công!'
                ]);
                exit;
            }
            
            // Chuyển hướng sang trang chi tiết đơn hàng vừa đặt để hiển thị thông tin chi tiết
            header('Location: ' . BASE_URL . '?action=order-detail&id=' . $orderId);
            exit;
        } catch (Throwable $exception) {
            // Log lỗi chi tiết để debug
            error_log('Checkout Error: ' . $exception->getMessage());
            error_log('Stack trace: ' . $exception->getTraceAsString());
            
            // Hiển thị thông báo lỗi chi tiết để debug
            $errorMessage = 'Có lỗi xảy ra khi tạo đơn hàng: ' . $exception->getMessage();
            
            // Log chi tiết
            error_log('Checkout Error Details:');
            error_log('Message: ' . $exception->getMessage());
            error_log('File: ' . $exception->getFile() . ':' . $exception->getLine());
            error_log('Trace: ' . $exception->getTraceAsString());
            
            // Kiểm tra nếu là AJAX request thì trả về JSON
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
                exit;
            }
            
            set_flash('danger', $errorMessage);
            header('Location: ' . BASE_URL . '?action=checkout');
            exit;
        }
    }

    /**
     * Đặt địa chỉ làm mặc định
     */
    public function setDefaultAddress(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $addressId = (int)($data['address_id'] ?? 0);
        
        if ($addressId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Địa chỉ không hợp lệ']);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        
        try {
            $success = $this->userAddressModel->setDefault($addressId, $userId);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đã đặt địa chỉ làm mặc định']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể đặt địa chỉ làm mặc định']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Lưu hoặc cập nhật địa chỉ
     */
    public function saveAddress(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)$_SESSION['user']['id'];
        $addressId = !empty($data['address_id']) ? (int)$data['address_id'] : null;
        
        // Validate
        if (empty($data['fullname']) || empty($data['phone']) || empty($data['email']) || empty($data['address'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            exit;
        }

        try {
            $addressData = [
                'user_id' => $userId,
                'fullname' => trim($data['fullname']),
                'phone' => trim($data['phone']),
                'email' => trim($data['email']),
                'address' => trim($data['address']),
                'city' => !empty($data['city']) ? trim($data['city']) : null,
                'district' => !empty($data['district']) ? trim($data['district']) : null,
                'ward' => !empty($data['ward']) ? trim($data['ward']) : null,
                'address_type' => !empty($data['address_type']) ? trim($data['address_type']) : 'home',
                'is_default' => isset($data['is_default']) && $data['is_default'] == 1 ? 1 : 0,
            ];

            // Đảm bảo KHÔNG có id trong addressData
            unset($addressData['id'], $addressData['address_id'], $addressData['addressId']);
            
            if ($addressId) {
                // Cập nhật địa chỉ
                $success = $this->userAddressModel->update($addressId, $addressData);
            } else {
                // Tạo địa chỉ mới
                $newAddressId = $this->userAddressModel->create($addressData);
                $success = $newAddressId > 0;
            }

            if ($success) {
                echo json_encode(['success' => true, 'message' => $addressId ? 'Đã cập nhật địa chỉ' : 'Đã thêm địa chỉ mới']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể lưu địa chỉ']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
        exit;
    }
}
