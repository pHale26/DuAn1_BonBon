<?php

require_once PATH_MODEL . 'CouponModel.php';
require_once PATH_MODEL . 'NotificationModel.php';
require_once PATH_MODEL . 'UserModel.php';

class AdminCouponController
{
    private CouponModel $couponModel;

    public function __construct()
    {
        $this->couponModel = new CouponModel();
    }

    public function index(): void
    {
        $this->requireAdmin();

        $keyword = trim($_GET['keyword'] ?? '');
        $statusFilter = $_GET['status'] ?? '';
        $discountTypeFilter = $_GET['discount_type'] ?? '';
        $createdFrom = $_GET['created_from'] ?? '';
        $createdTo = $_GET['created_to'] ?? '';
        $durationTypeFilter = $_GET['duration_type'] ?? '';

        // Chuẩn bị dữ liệu products, categories cho dropdown
        require_once PATH_MODEL . 'ProductModel.php';
        require_once PATH_MODEL . 'CategoryModel.php';
        $productModel = new ProductModel();
        $categoryModel = new CategoryModel();
        $products = is_callable([$productModel, 'getAll']) ? (call_user_func([$productModel, 'getAll']) ?? []) : [];
        $categories = is_callable([$categoryModel, 'getAll']) ? (call_user_func([$categoryModel, 'getAll']) ?? []) : [];

        $coupons = $this->couponModel->getAll(
            $keyword ?: null,
            $statusFilter ?: null,
            $discountTypeFilter ?: null,
            $createdFrom ?: null,
            $createdTo ?: null,
            $durationTypeFilter ?: null
        );

        // Đồng bộ trạng thái tự động: nếu hết hạn thì chuyển sang ngừng hoạt động
        foreach ($coupons as &$c) {
            $calc = $c['calculated_status'] ?? 'active';
            if ($calc === 'expired' && ($c['status'] ?? 'active') !== 'inactive') {
                try {
                    $this->couponModel->updateStatusOnly((int)$c['coupon_id'], 'inactive');
                    $c['status'] = 'inactive';
                } catch (Throwable $e) {
                    // silent; chỉ đồng bộ khi có thể
                }
            }
        }
        unset($c);

        $title = 'Quản lý mã giảm giá';
        $view  = 'admin/coupons/index';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function create(): void
    {
        $this->requireAdmin();

        $formData = [];
        $errors = [];
        $warnings = [];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Hiển thị trang thêm mới
            $title = 'Thêm mã giảm giá';
            $view = 'admin/coupons/create';
            $formData = [
                'start_date' => date('Y-m-d\TH:i'),
                'end_date' => date('Y-m-d\TH:i', strtotime('+30 days')),
                'status' => 'active',
                'discount_type' => 'percent',
                'min_order_amount' => 0,
            ];
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }

        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'discount_type' => $_POST['discount_type'] ?? 'percent',
            'discount_value' => (float)($_POST['discount_value'] ?? 0),
            'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0),
            'max_discount_amount' => ($_POST['max_discount_amount'] ?? '') === '' ? null : (float)$_POST['max_discount_amount'],
            'start_date' => $_POST['start_date'] ?? date('Y-m-d H:i:s'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d H:i:s', strtotime('+1 month')),
            'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
            'per_user_limit' => !empty($_POST['per_user_limit']) ? (int)$_POST['per_user_limit'] : null,
            'apply_scope' => 'all',
            'apply_product_ids' => null,
            'apply_category_ids' => null,
            'new_customer_only' => !empty($_POST['new_customer_only']) ? 1 : 0,
            'customer_group' => $_POST['customer_group'] ?? null,
            'return_on_refund' => !empty($_POST['return_on_refund']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'active',
        ];
        $formData = $data;

        // Nếu là khách mới: cố định mỗi khách 1 lượt, tự bật hoàn lượt khi hoàn
        if (!empty($data['new_customer_only'])) {
            $data['per_user_limit'] = 1;
            $formData['per_user_limit'] = 1;
            $data['return_on_refund'] = 1;
            $formData['return_on_refund'] = 1;
        }

        // Nếu là giảm giá cố định, không cho phép max_discount_amount
        if ($data['discount_type'] === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        // Validation
        if (empty($data['code']) || empty($data['name']) || $data['discount_value'] <= 0) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin và giá trị giảm > 0.';
        }

        if ($data['discount_type'] === 'percent') {
            if ($data['discount_value'] < 10) {
                $errors[] = 'Giá trị % phải từ 10% đến 100%.';
            }
            if ($data['discount_value'] > 100) {
                $errors[] = 'Giá trị % không được vượt quá 100%.';
            }
        } else {
            // Cố định tiền: không dùng max_discount_amount
            $data['max_discount_amount'] = null;
        }

        // Validate ngày - ngày kết thúc phải sau ngày bắt đầu
        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        if ($endDate < $startDate) {
            $errors[] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
        }

        // Validate tương quan giới hạn tổng và mỗi khách
        if (!empty($data['usage_limit']) && !empty($data['per_user_limit']) && (int)$data['per_user_limit'] > (int)$data['usage_limit']) {
            $errors[] = 'Giới hạn mỗi khách không được lớn hơn giới hạn tổng.';
        }
        
        // Kiểm tra trùng mã code
        $codeCheck = $this->couponModel->getByCode($data['code'], true);
        if ($codeCheck) {
            if (!empty($codeCheck['deleted_at'])) {
                 $errors[] = 'Mã giảm giá "' . htmlspecialchars($data['code']) . '" đang nằm trong thùng rác. <a href="'.BASE_URL.'?action=admin-coupons-trash" class="fw-bold">Khôi phục tại đây</a>.';
            } else {
                 $errors[] = 'Mã giảm giá "' . htmlspecialchars($data['code']) . '" đã tồn tại. Vui lòng chọn mã khác.';
            }
        }
        
        // Validate max_discount_amount khi là percent
        if ($data['discount_type'] === 'percent' && $data['max_discount_amount'] !== null) {
            if ($data['max_discount_amount'] < 0) {
                $errors[] = 'Giảm tối đa không hợp lệ.';
            }
            if ((float)$data['max_discount_amount'] === 0.0) {
                $warnings[] = 'Giảm tối đa đang để 0 (hợp lệ nhưng không khuyến nghị).';
            }
        }

        if (!empty($errors)) {
            $title = 'Thêm mã giảm giá';
            $view = 'admin/coupons/create';
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }

        // Đảm bảo KHÔNG có id trong data
        unset($data['id'], $data['coupon_id'], $data['couponId']);
        // Xóa các trường không tồn tại trong database
        unset($data['description'], $data['apply_scope'], 
              $data['apply_product_ids'], $data['apply_category_ids'], $data['require_login'],
              $data['exclude_sale_items'], $data['exclude_other_coupons'],
              $data['customer_group'], $data['return_on_refund']);
        
        try {
            $couponId = $this->couponModel->create($data);
            $this->notifyNewCoupon($couponId, $data);
            set_flash('success', 'Tạo mã giảm giá thành công.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể tạo mã giảm giá: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-coupons');
        exit;
    }

    public function update(): void
    {
        $this->requireAdmin();

        $errors = [];
        $warnings = [];
        $formData = [];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }

        $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;

        if (!$couponId) {
            set_flash('danger', 'Mã giảm giá không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }

        $data = [
            'code' => $_POST['code'] ?? '',
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'discount_type' => $_POST['discount_type'] ?? 'percent',
            'discount_value' => (float)($_POST['discount_value'] ?? 0),
            'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0),
            'max_discount_amount' => ($_POST['max_discount_amount'] ?? '') === '' ? null : (float)$_POST['max_discount_amount'],
            'start_date' => $_POST['start_date'] ?? date('Y-m-d H:i:s'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d H:i:s', strtotime('+1 month')),
            'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
            'per_user_limit' => !empty($_POST['per_user_limit']) ? (int)$_POST['per_user_limit'] : null,
            'apply_scope' => 'all',
            'apply_product_ids' => null,
            'apply_category_ids' => null,
            'new_customer_only' => !empty($_POST['new_customer_only']) ? 1 : 0,
            'customer_group' => $_POST['customer_group'] ?? null,
            'return_on_refund' => !empty($_POST['return_on_refund']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'active',
        ];
        $formData = $data;

        // Nếu là khách mới: cố định mỗi khách 1 lượt, tự bật hoàn lượt khi hoàn
        if (!empty($data['new_customer_only'])) {
            $data['per_user_limit'] = 1;
            $formData['per_user_limit'] = 1;
            $data['return_on_refund'] = 1;
            $formData['return_on_refund'] = 1;
        }

        // Nếu là giảm giá cố định, không cho phép max_discount_amount
        if ($data['discount_type'] === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        // Validation
        if (empty($data['code']) || empty($data['name']) || $data['discount_value'] <= 0) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin và giá trị giảm > 0.';
        }

        if ($data['discount_type'] === 'percent') {
            if ($data['discount_value'] < 10) {
                $errors[] = 'Giá trị % phải từ 10% đến 100%.';
            }
            if ($data['discount_value'] > 100) {
                $errors[] = 'Giá trị % không được vượt quá 100%.';
            }
        } else {
            // Cố định tiền: không dùng max_discount_amount
            $data['max_discount_amount'] = null;
        }

        // Validate ngày - ngày kết thúc phải sau ngày bắt đầu
        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        if ($endDate < $startDate) {
            $errors[] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
        }

        // Validate tương quan giới hạn tổng và mỗi khách
        if (!empty($data['usage_limit']) && !empty($data['per_user_limit']) && (int)$data['per_user_limit'] > (int)$data['usage_limit']) {
            $errors[] = 'Giới hạn mỗi khách không được lớn hơn giới hạn tổng.';
        }
        
        // Kiểm tra mã giảm giá có tồn tại không
        $existingCoupon = $this->couponModel->getById($couponId, true); // Cho phép lấy cả mã đã xóa để kiểm tra
        if (!$existingCoupon) {
            set_flash('danger', 'Mã giảm giá không tồn tại.');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }
        
        // Kiểm tra nếu mã đã bị xóa mềm
        if ($existingCoupon['deleted_at'] !== null) {
            set_flash('danger', 'Không thể sửa mã giảm giá đã bị xóa. Vui lòng khôi phục trước.');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }
        
        // Kiểm tra trùng mã code (trừ chính mã đó)
        $codeCheck = $this->couponModel->getByCode($data['code'], true);
        if ($codeCheck && (int)$codeCheck['coupon_id'] !== $couponId) {
             if (!empty($codeCheck['deleted_at'])) {
                 $errors[] = 'Mã giảm giá "' . htmlspecialchars($data['code']) . '" đang nằm trong thùng rác.';
            } else {
                 $errors[] = 'Mã giảm giá "' . htmlspecialchars($data['code']) . '" đã tồn tại. Vui lòng chọn mã khác.';
            }
        }
        
        // Validate max_discount_amount khi là percent
        if ($data['discount_type'] === 'percent' && $data['max_discount_amount'] !== null) {
            if ($data['max_discount_amount'] < 0) {
                $errors[] = 'Giảm tối đa không hợp lệ.';
            }
            if ((float)$data['max_discount_amount'] === 0.0) {
                $warnings[] = 'Giảm tối đa đang để 0 (hợp lệ nhưng không khuyến nghị).';
            }
        }

        if (!empty($errors)) {
            $title = 'Sửa mã giảm giá';
            $view = 'admin/coupons/edit';
            $coupon = array_merge($existingCoupon, $formData);
            require_once PATH_VIEW . 'admin/layout.php';
            return;
        }
        
        // Validate usage_limit phải >= used_count hiện tại
        if ($data['usage_limit'] !== null && $data['usage_limit'] < $existingCoupon['used_count']) {
            set_flash('danger', 'Giới hạn số lần sử dụng không thể nhỏ hơn số lần đã sử dụng (' . $existingCoupon['used_count'] . ').');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }

        try {
            // Lưu ý: Khi sửa mã giảm giá, các đơn hàng đã đặt sẽ KHÔNG bị ảnh hưởng
            // vì thông tin mã giảm giá (code, name, discount_amount) đã được lưu snapshot
            // vào bảng orders tại thời điểm đặt hàng (coupon_code, coupon_name, discount_amount).
            // Các đơn hàng đã đặt sẽ luôn hiển thị thông tin mã giảm giá tại thời điểm đặt hàng.
            // Xóa các trường không tồn tại trong database
            unset($data['description'], $data['apply_scope'], 
                  $data['apply_product_ids'], $data['apply_category_ids'], $data['require_login'],
                  $data['exclude_sale_items'], $data['exclude_other_coupons'],
                  $data['customer_group'], $data['return_on_refund']);
            $this->couponModel->update($couponId, $data);
            set_flash('success', 'Cập nhật mã giảm giá thành công.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể cập nhật: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-coupons');
        exit;
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;

        if (!$couponId) {
            set_flash('danger', 'Mã giảm giá không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }

        try {
            $coupon = $this->couponModel->getById($couponId, true);
            if (!$coupon) {
                set_flash('danger', 'Mã giảm giá không tồn tại.');
                header('Location: ' . BASE_URL . '?action=admin-coupons');
                exit;
            }

            // Kiểm tra nếu mã đã bị xóa (trong thùng rác)
            if (!empty($coupon['deleted_at'])) {
                set_flash('warning', 'Mã giảm giá đã được xóa vào thùng rác.');
                header('Location: ' . BASE_URL . '?action=admin-coupons');
                exit;
            }

            // Luôn soft delete (cho vào thùng rác) thay vì xóa vĩnh viễn
            $this->couponModel->delete($couponId);
            set_flash('success', 'Đã xóa mã giảm giá vào thùng rác.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể xóa: ' . $exception->getMessage());
        }

        header('Location: ' . BASE_URL . '?action=admin-coupons');
        exit;
    }

    public function edit(): void
    {
        $this->requireAdmin();

        $couponId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$couponId) {
            set_flash('danger', 'Mã giảm giá không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }

        $coupon = $this->couponModel->getById($couponId, true);
        if (!$coupon) {
            set_flash('danger', 'Không tìm thấy mã giảm giá.');
            header('Location: ' . BASE_URL . '?action=admin-coupons');
            exit;
        }

        $title = 'Sửa mã giảm giá';
        $view = 'admin/coupons/edit';

        require_once PATH_VIEW . 'admin/layout.php';
    }

    public function trash(): void
    {
        $this->requireAdmin();
        
        $coupons = $this->couponModel->getDeleted();
        
        $title = 'Thùng rác - Mã giảm giá';
        $view = 'admin/coupons/trash';
        
        require_once PATH_VIEW . 'admin/layout.php';
    }
    
    public function restore(): void
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
            exit;
        }
        
        $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;
        
        if (!$couponId) {
            set_flash('danger', 'Mã giảm giá không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
            exit;
        }
        
        try {
            $this->couponModel->restore($couponId);
            set_flash('success', 'Khôi phục mã giảm giá thành công.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể khôi phục: ' . $exception->getMessage());
        }
        
        header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
        exit;
    }
    
    public function forceDelete(): void
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
            exit;
        }
        
        $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;
        
        if (!$couponId) {
            set_flash('danger', 'Mã giảm giá không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
            exit;
        }
        
        try {
            $this->couponModel->forceDelete($couponId);
            set_flash('success', 'Xóa vĩnh viễn mã giảm giá thành công.');
        } catch (Throwable $exception) {
            set_flash('danger', 'Không thể xóa vĩnh viễn: ' . $exception->getMessage());
        }
        
        header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
        exit;
    }

    private function notifyNewCoupon(int $couponId, array $data): void
    {
        try {
            $coupon = $this->couponModel->getById($couponId, true);
            if (!$coupon) {
                return;
            }

            $userModel = new UserModel();
            $customers = $userModel->getAll(null, 'customer');
            $userIds = array_map(function ($user) {
                return (int)($user['user_id'] ?? $user['id'] ?? 0);
            }, $customers);

            $notificationModel = new NotificationModel();
            $title = 'Mã giảm giá mới: ' . ($coupon['code'] ?? '');
            $content = $coupon['name'] ?? 'Nhận ưu đãi mới cho đơn hàng của bạn.';

            $notificationModel->createBulk(
                $userIds,
                'coupon',
                $title,
                $content,
                null,
                [
                    'coupon_id' => $couponId,
                    'code' => $coupon['code'] ?? null,
                    'name' => $coupon['name'] ?? null,
                    'discount_type' => $coupon['discount_type'] ?? null,
                    'discount_value' => $coupon['discount_value'] ?? null,
                    'min_order_amount' => $coupon['min_order_amount'] ?? null,
                    'max_discount_amount' => $coupon['max_discount_amount'] ?? null,
                    'start_date' => $coupon['start_date'] ?? null,
                    'end_date' => $coupon['end_date'] ?? null,
                    'status' => $coupon['status'] ?? null,
                ]
            );
        } catch (Throwable $e) {
            error_log('AdminCouponController::notifyNewCoupon - ' . $e->getMessage());
        }
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
            set_flash('danger', 'Bạn cần quyền quản trị để truy cập trang này.');
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function emptyTrashAction(): void
    {
        $this->requireAdmin();
        $this->couponModel->emptyTrash();
        set_flash('success', 'Đã xóa sạch thùng rác mã giảm giá.');
        header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
        exit;
    }

    public function restoreAllAction(): void
    {
        $this->requireAdmin();
        $this->couponModel->restoreAll();
        set_flash('success', 'Đã khôi phục tất cả mã giảm giá.');
        header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
        exit;
    }

    public function bulkTrashAction(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
             exit;
        }

        $action = $_POST['bulk_action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            set_flash('warning', 'Vui lòng chọn ít nhất một mã!');
            header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
            exit;
        }

        if ($action === 'restore') {
            $this->couponModel->restoreMany($ids);
            set_flash('success', 'Đã khôi phục các mã đã chọn!');
        } elseif ($action === 'delete') {
            $this->couponModel->forceDeleteMany($ids);
            set_flash('success', 'Đã xóa vĩnh viễn các mã đã chọn!');
        }

        header('Location: ' . BASE_URL . '?action=admin-coupons-trash');
        exit;
    }
}

