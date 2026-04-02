<?php /* trimmed duplicate content */ ?>
<div class="container-fluid px-3">
<div class="admin-page-header">
    <div class="title-wrap">
        <p class="text-uppercase mb-1 small">Bảng điều khiển</p>
        <h2 class="d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-ticket-perforated"></i>
            <span>Quản lý mã giảm giá</span>
    </h2>
    </div>

</div>

<!-- Form tìm kiếm và lọc -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= BASE_URL ?>" id="searchForm">
            <input type="hidden" name="action" value="admin-coupons">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-uppercase fw-bold">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="keyword" 
                               class="form-control" 
                               placeholder="Mã, tên hoặc mô tả..." 
                               value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                               id="searchKeyword">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Trạng thái</label>
                    <select name="status" class="form-select" id="statusFilter">
                        <?php $statusValue = $_GET['status'] ?? ''; ?>
                        <option value="">Tất cả</option>
                        <option value="active" <?= $statusValue === 'active' ? 'selected' : '' ?>>Đang chạy</option>
                        <option value="pending" <?= $statusValue === 'pending' ? 'selected' : '' ?>>Sắp diễn ra</option>
                        <option value="expired" <?= $statusValue === 'expired' ? 'selected' : '' ?>>Hết hạn</option>
                        <option value="out_of_stock" <?= $statusValue === 'out_of_stock' ? 'selected' : '' ?>>Hết lượt</option>
                        <option value="inactive" <?= $statusValue === 'inactive' ? 'selected' : '' ?>>Ngừng hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Loại giảm giá</label>
                    <select name="discount_type" class="form-select" id="discountTypeFilter">
                        <option value="">Tất cả</option>
                        <option value="percent" <?= ($_GET['discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>>Phần trăm</option>
                        <option value="fixed" <?= ($_GET['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Cố định</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Thời hạn</label>
                    <select name="duration_type" class="form-select" id="durationTypeFilter">
                        <option value="">Tất cả</option>
                        <option value="unlimited" <?= ($_GET['duration_type'] ?? '') === 'unlimited' ? 'selected' : '' ?>>Vô hạn</option>
                        <option value="limited" <?= ($_GET['duration_type'] ?? '') === 'limited' ? 'selected' : '' ?>>Có hạn</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Từ ngày</label>
                    <input type="date" name="created_from" class="form-control" value="<?= htmlspecialchars($_GET['created_from'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Đến ngày</label>
                    <input type="date" name="created_to" class="form-control" value="<?= htmlspecialchars($_GET['created_to'] ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Lọc
                        </button>
                        <?php if (!empty($_GET['keyword']) || !empty($_GET['status']) || !empty($_GET['discount_type']) || !empty($_GET['created_from']) || !empty($_GET['created_to'])): ?>
                            <a href="<?= BASE_URL ?>?action=admin-coupons" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="admin-section-bar">
    <div>
        <h5 class="mb-0">Danh sách mã giảm giá</h5>
        <small>
            <?php if (!empty($coupons)): ?>
                Tìm thấy <strong><?= count($coupons) ?></strong> mã giảm giá
                <?php if (!empty($_GET['keyword']) || !empty($_GET['status']) || !empty($_GET['discount_type']) || !empty($_GET['created_from']) || !empty($_GET['created_to'])): ?>
                    (đã lọc)
                <?php endif; ?>
            <?php else: ?>
                Không tìm thấy mã giảm giá nào
            <?php endif; ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>?action=admin-coupons-trash" class="btn btn-outline-secondary">
            <i class="bi bi-trash"></i> Thùng rác
            <?php
            require_once PATH_MODEL . 'CouponModel.php';
            $couponModel = new CouponModel();
            $deletedCount = count($couponModel->getDeleted());
            if ($deletedCount > 0):
            ?>
                <span class="badge bg-danger ms-1"><?= $deletedCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>?action=admin-coupon-create" class="btn btn-light-soft">
            <i class="bi bi-plus-circle"></i> Thêm mã giảm giá
        </a>
    </div>
</div>

<div class="admin-table">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Tên</th>
                <th>Loại giảm</th>
                <th>Giá trị</th>
                <th>Đơn tối thiểu</th>
                <th>Giảm tối đa</th>
                <th>Thời gian</th>
                <th>Số lần dùng</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($coupons)): ?>
                <tr>
                    <td colspan="10" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <div>Chưa có mã giảm giá nào</div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                        <td><?= htmlspecialchars($coupon['name']) ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?= $coupon['discount_type'] === 'percent' ? 'Phần trăm' : 'Cố định' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($coupon['discount_type'] === 'percent'): ?>
                                <?= number_format($coupon['discount_value'], 0) ?>%
                            <?php else: ?>
                                <?= number_format($coupon['discount_value'], 0, ',', '.') ?> đ
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($coupon['min_order_amount'], 0, ',', '.') ?> đ</td>
                        <td>
                            <?php 
                            if ($coupon['discount_type'] === 'fixed') {
                                echo 'Không áp dụng';
                            } else {
                                echo $coupon['max_discount_amount'] ? number_format($coupon['max_discount_amount'], 0, ',', '.') . ' đ' : 'Không giới hạn';
                            }
                            ?>
                        </td>
                        <td>
                            <small>
                                <?php 
                                    $endDate = strtotime($coupon['end_date']);
                                    if ($endDate > strtotime('+50 years')) {
                                        echo '<span class="badge bg-success">Không giới hạn</span>';
                                    } else {
                                        echo date('d/m/Y', strtotime($coupon['start_date'])) . '<br>đến ' . date('d/m/Y', $endDate);
                                    }
                                ?>
                            </small>
                        </td>
                        <td>
                            <?php 
                                $limitDisplay = isset($coupon['usage_limit']) && $coupon['usage_limit'] !== null 
                                    ? (int)$coupon['usage_limit'] 
                                    : '∞';
                                if (!empty($coupon['new_customer_only'])) {
                                    $limitDisplay = isset($coupon['per_user_limit']) && $coupon['per_user_limit'] !== null 
                                        ? (int)$coupon['per_user_limit'] 
                                        : 1;
                                }
                            ?>
                            <?= (int)$coupon['used_count'] ?> / <?= is_numeric($limitDisplay) ? $limitDisplay : $limitDisplay ?>
                        </td>
                        <td>
                            <?php
                            $calculatedStatus = $coupon['calculated_status'] ?? 'active';
                            $statusBadge = 'success';
                            $statusText = 'Đang chạy';
                            
                            if ($calculatedStatus === 'expired') {
                                $statusBadge = 'danger';
                                $statusText = 'Hết hạn';
                            } elseif ($calculatedStatus === 'out_of_stock') {
                                $statusBadge = 'warning';
                                $statusText = 'Hết lượt';
                            } elseif ($calculatedStatus === 'inactive') {
                                $statusBadge = 'secondary';
                                $statusText = 'Ngừng hoạt động';
                            } elseif ($calculatedStatus === 'pending') {
                                $statusBadge = 'info';
                                $statusText = 'Sắp diễn ra';
                            }
                            ?>
                            <span class="badge bg-<?= $statusBadge ?>">
                                <?= $statusText ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>?action=admin-coupon-edit&id=<?= htmlspecialchars($coupon['coupon_id']) ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" 
                                  action="<?= BASE_URL ?>?action=admin-coupon-delete" 
                                  class="d-inline"
                                  onsubmit="return confirm('Bạn có chắc muốn xóa mã giảm giá này?')">
                                <input type="hidden" name="coupon_id" value="<?= $coupon['coupon_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<!-- Modal thêm/sửa mã giảm giá -->
<div class="modal fade coupon-modal" id="couponModal" tabindex="-1">
    <div class="modal-dialog modal-xxl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalTitle">Thêm mã giảm giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>?action=admin-coupon-create" id="couponForm">
                <input type="hidden" name="coupon_id" id="couponId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Mã giảm giá <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   name="code" 
                                   id="couponCode" 
                                   required 
                                   placeholder="WELCOME10"
                                   class="text-uppercase">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tên mã giảm giá <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   name="name" 
                                   id="couponName" 
                                   required 
                                   placeholder="Mã chào mừng 10%">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" 
                                      name="description" 
                                      id="couponDescription" 
                                      rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loại giảm giá <span class="text-danger">*</span></label>
                            <select class="form-select" name="discount_type" id="discountType" required>
                                <option value="percent">Phần trăm (%)</option>
                                <option value="fixed">Cố định (VNĐ)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giá trị giảm <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   name="discount_value" 
                                   id="discountValue" 
                                   required 
                                   min="0" 
                                   step="0.01"
                                   placeholder="10% hoặc 100%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Đơn hàng tối thiểu (VNĐ)</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="min_order_amount" 
                                   id="minOrderAmount" 
                                   min="0" 
                                   step="1000"
                                   value="0"
                                   placeholder="500000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giảm tối đa (VNĐ) - Chỉ áp dụng với phần trăm</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="max_discount_amount" 
                                   id="maxDiscountAmount" 
                                   min="0" 
                                   step="1000"
                                   placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   name="start_date" 
                                   id="startDate" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   name="end_date" 
                                   id="endDate" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giới hạn số lần sử dụng</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="usage_limit" 
                                   id="usageLimit" 
                                   min="1"
                                   placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giới hạn mỗi khách hàng</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="per_user_limit" 
                                   id="perUserLimit" 
                                   min="1"
                                   placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nhóm khách hàng</label>
                            <select class="form-select" name="customer_group" id="customerGroup">
                                <option value="">Tất cả</option>
                                <option value="vip_today">VIP (>= 3 đơn trong ngày)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="requireLogin" name="require_login">
                                        <label class="form-check-label" for="requireLogin">Yêu cầu đăng nhập</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="newCustomerOnly" name="new_customer_only">
                                        <label class="form-check-label" for="newCustomerOnly">Chỉ khách mới</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="excludeSaleItems" name="exclude_sale_items">
                                        <label class="form-check-label" for="excludeSaleItems">Không áp dụng hàng sale</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="excludeOtherCoupons" name="exclude_other_coupons">
                                        <label class="form-check-label" for="excludeOtherCoupons">Không kèm mã khác</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="returnOnRefund" name="return_on_refund">
                                        <label class="form-check-label" for="returnOnRefund">Hoàn lượt khi hoàn tiền</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="couponStatus" required>
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Tạm dừng</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Đảm bảo hàm openCouponModal được định nghĩa trước khi sử dụng
window.openCouponModal = function(coupon = null) {
    try {
        const form = document.getElementById('couponForm');
        const modalTitle = document.getElementById('couponModalTitle');
        const couponId = document.getElementById('couponId');
        const modalElement = document.getElementById('couponModal');
        
        if (!form || !modalTitle || !couponId || !modalElement) {
            alert('Có lỗi xảy ra khi mở form. Vui lòng tải lại trang.');
            return;
        }
        
        if (coupon) {
            // Sửa mã giảm giá
            modalTitle.textContent = 'Sửa mã giảm giá';
            form.action = '<?= BASE_URL ?>?action=admin-coupon-update';
            couponId.value = coupon.coupon_id;
            document.getElementById('couponCode').value = coupon.code || '';
            document.getElementById('couponName').value = coupon.name || '';
            document.getElementById('couponDescription').value = coupon.description || '';
            document.getElementById('discountType').value = coupon.discount_type || 'percent';
            document.getElementById('discountValue').value = coupon.discount_value || '';
            document.getElementById('minOrderAmount').value = coupon.min_order_amount || 0;
            document.getElementById('maxDiscountAmount').value = coupon.max_discount_amount || '';
            document.getElementById('startDate').value = coupon.start_date ? coupon.start_date.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('endDate').value = coupon.end_date ? coupon.end_date.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('usageLimit').value = coupon.usage_limit || '';
            document.getElementById('perUserLimit').value = coupon.per_user_limit || '';
            document.getElementById('customerGroup').value = coupon.customer_group || '';
            document.getElementById('requireLogin').checked = coupon.require_login == 1;
            document.getElementById('newCustomerOnly').checked = coupon.new_customer_only == 1;
            document.getElementById('excludeSaleItems').checked = coupon.exclude_sale_items == 1;
            document.getElementById('excludeOtherCoupons').checked = coupon.exclude_other_coupons == 1;
            document.getElementById('returnOnRefund').checked = coupon.return_on_refund == 1;
            document.getElementById('couponStatus').value = coupon.status || 'active';
            
            // Xử lý max_discount_amount khi sửa
            handleDiscountTypeChange();
        } else {
            // Thêm mã giảm giá mới
            modalTitle.textContent = 'Thêm mã giảm giá';
            form.action = '<?= BASE_URL ?>?action=admin-coupon-create';
            form.reset();
            couponId.value = '';
            
            // Reset tất cả các trường
            document.getElementById('couponCode').value = '';
            document.getElementById('couponName').value = '';
            document.getElementById('couponDescription').value = '';
            document.getElementById('discountType').value = 'percent';
            document.getElementById('discountValue').value = '';
            document.getElementById('minOrderAmount').value = '0';
            document.getElementById('maxDiscountAmount').value = '';
            document.getElementById('usageLimit').value = '';
            document.getElementById('perUserLimit').value = '';
            document.getElementById('customerGroup').value = '';
            document.getElementById('requireLogin').checked = false;
            document.getElementById('newCustomerOnly').checked = false;
            document.getElementById('excludeSaleItems').checked = false;
            document.getElementById('excludeOtherCoupons').checked = false;
            document.getElementById('returnOnRefund').checked = false;
            document.getElementById('couponStatus').value = 'active';
            
            // Set ngày mặc định
            document.getElementById('startDate').value = new Date().toISOString().slice(0, 16);
            document.getElementById('endDate').value = new Date(Date.now() + 30*24*60*60*1000).toISOString().slice(0, 16);
            
            // Xử lý max_discount_amount khi thêm mới
            handleDiscountTypeChange();
            
            // Clear validation
            document.getElementById('endDate').setCustomValidity('');
        }
        
        // Validate ngày khi mở modal với dữ liệu
        if (coupon) {
            setTimeout(() => {
                validateDates();
            }, 100);
        }
        
        // Mở modal bằng Bootstrap
        if (typeof bootstrap === 'undefined') {
            alert('Bootstrap chưa được load. Vui lòng tải lại trang.');
            return;
        }
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    } catch (error) {
        console.error('Lỗi khi mở modal:', error);
        alert('Có lỗi xảy ra khi mở form sửa mã giảm giá: ' + error.message);
    }
};

// Các hàm helper khác

// Xử lý khi thay đổi loại giảm giá
function handleDiscountTypeChange() {
    const discountType = document.getElementById('discountType').value;
    const maxDiscountInput = document.getElementById('maxDiscountAmount');
    
    if (discountType === 'fixed') {
        // Nếu chọn cố định, xóa giá trị và disable
        maxDiscountInput.value = '';
        maxDiscountInput.disabled = true;
        maxDiscountInput.placeholder = 'Không áp dụng cho giảm giá cố định';
    } else {
        // Nếu chọn phần trăm, enable lại
        maxDiscountInput.disabled = false;
        maxDiscountInput.placeholder = 'Để trống nếu không giới hạn';
    }
}

// Khóa và thiết lập giới hạn mỗi khách khi chọn "Chỉ khách mới"
function syncNewCustomerLock() {
    const newCustomerOnly = document.getElementById('newCustomerOnly');
    const perUserLimit = document.getElementById('perUserLimit');
    const returnOnRefund = document.getElementById('returnOnRefund');
    if (!newCustomerOnly || !perUserLimit || !returnOnRefund) return;
    if (newCustomerOnly.checked) {
        perUserLimit.value = 1;
        perUserLimit.readOnly = true;
        perUserLimit.title = 'Tự động set 1 lượt cho khách mới';
        returnOnRefund.checked = true;
    } else {
        perUserLimit.readOnly = false;
        perUserLimit.title = '';
        if (perUserLimit.value === '1') {
            perUserLimit.value = '';
        }
    }
}

// Validate ngày kết thúc > ngày bắt đầu
function validateDates() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (!startDateInput.value || !endDateInput.value) {
        return true; // Để HTML5 validation xử lý
    }
    
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    
    if (endDate <= startDate) {
        endDateInput.setCustomValidity('Ngày kết thúc phải sau ngày bắt đầu. Vui lòng nhập lại.');
        endDateInput.reportValidity();
        endDateInput.focus();
        return false;
    } else {
        endDateInput.setCustomValidity('');
    }
    
    return true;
}

    // Event listeners
    // Helper: set values for multi-select (accept array or comma string)
    function setMultiSelectValues(selectId, values) {
        const el = document.getElementById(selectId);
        if (!el) return;
        let arr = [];
        if (Array.isArray(values)) {
            arr = values.map(v => String(v).trim()).filter(Boolean);
        } else if (typeof values === 'string' && values.trim() !== '') {
            arr = values.split(',').map(v => v.trim()).filter(Boolean);
        }
        Array.from(el.options).forEach(opt => {
            opt.selected = arr.includes(opt.value);
        });
        el.dispatchEvent(new Event('change'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const discountType = document.getElementById('discountType');
        const couponForm = document.getElementById('couponForm');
        
        // Xử lý khi thay đổi loại giảm giá
        if (discountType) {
            discountType.addEventListener('change', handleDiscountTypeChange);
        }
        
        // Validate form trước khi submit
        if (couponForm) {
            couponForm.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        // Đồng bộ giới hạn mỗi khách khi tick "Chỉ khách mới"
        const newCustomerOnly = document.getElementById('newCustomerOnly');
        if (newCustomerOnly) {
            newCustomerOnly.addEventListener('change', syncNewCustomerLock);
            // Chạy lần đầu để set giá trị ban đầu khi mở modal
            syncNewCustomerLock();
        }
        
        // Validate ngày khi thay đổi
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                validateDates();
            });
            
            endDateInput.addEventListener('change', function() {
                validateDates();
            });
            
            // Validate khi mở modal với dữ liệu có sẵn
            if (startDateInput.value && endDateInput.value) {
                validateDates();
            }
        }
        
        // Auto-submit khi thay đổi filter (trạng thái, loại giảm giá)
        const statusFilter = document.getElementById('statusFilter');
        const discountTypeFilter = document.getElementById('discountTypeFilter');
        
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                document.getElementById('searchForm').submit();
            });
        }
        
        if (discountTypeFilter) {
            discountTypeFilter.addEventListener('change', function() {
                document.getElementById('searchForm').submit();
            });
        }
        
        // Cho phép nhấn Enter để tìm kiếm
        const searchKeyword = document.getElementById('searchKeyword');
        if (searchKeyword) {
            searchKeyword.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('searchForm').submit();
                }
            });
        }
        
    });
</script>

