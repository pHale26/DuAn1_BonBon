<div class="admin-page-header">
    <div class="title-wrap">
        <p class="text-uppercase mb-1 small">Bảng điều khiển</p>
        <h2 class="d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-ticket-perforated"></i>
            <span>Thêm mã giảm giá</span>
        </h2>
    </div>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-coupons" class="btn btn-light-soft">
            <i class="bi bi-arrow-left"></i> Danh sách
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= $err ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if (!empty($warnings)): ?>
    <div class="alert alert-warning">
        <ul class="mb-0">
            <?php foreach ($warnings as $warn): ?>
                <li><?= htmlspecialchars($warn) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="card p-3" method="POST" action="<?= BASE_URL ?>?action=admin-coupon-create">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Mã giảm giá <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" required placeholder="WELCOME10" value="<?= htmlspecialchars($formData['code'] ?? '') ?>">
            <small class="text-muted">Mã áp dụng cho toàn bộ sản phẩm (không giới hạn danh mục/sản phẩm).</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tên mã giảm giá <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="Mã chào mừng 10%" value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Loại giảm giá <span class="text-danger">*</span></label>
            <select name="discount_type" class="form-select" required>
                <?php $type = $formData['discount_type'] ?? 'percent'; ?>
                <option value="percent" <?= $type === 'percent' ? 'selected' : '' ?>>Phần trăm (%)</option>
                <option value="fixed" <?= $type === 'fixed' ? 'selected' : '' ?>>Cố định (VNĐ)</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giá trị giảm <span class="text-danger">*</span></label>
            <input type="number" name="discount_value" class="form-control" min="0" step="0.01" required placeholder="10% hoặc 100%" value="<?= htmlspecialchars($formData['discount_value'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Đơn hàng tối thiểu (VNĐ)</label>
            <input type="number" name="min_order_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars($formData['min_order_amount'] ?? 0) ?>">
            <small class="text-muted">Nếu để 0: áp dụng mọi đơn.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giảm tối đa (VNĐ) - chỉ áp dụng %</label>
            <input type="number" name="max_discount_amount" id="maxDiscountAmount" class="form-control" min="0" step="1000" placeholder="Để trống nếu không giới hạn" value="<?= htmlspecialchars($formData['max_discount_amount'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
            <input type="datetime-local" name="start_date" id="startDateInput" class="form-control" required value="<?= htmlspecialchars($formData['start_date'] ?? date('Y-m-d\TH:i')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
            <input type="datetime-local" name="end_date" id="endDateInput" class="form-control" value="<?= htmlspecialchars($formData['end_date'] ?? date('Y-m-d\TH:i', strtotime('+30 days'))) ?>">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="unlimitedEndDate" name="unlimited_end_date">
                <label class="form-check-label" for="unlimitedEndDate">
                    Không giới hạn thời gian
                </label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giới hạn số lần sử dụng</label>
            <input type="number" name="usage_limit" class="form-control" min="1" placeholder="Để trống nếu không giới hạn" value="<?= htmlspecialchars($formData['usage_limit'] ?? '') ?>">
            <small class="text-muted">Ví dụ: 5 lượt tổng toàn hệ thống.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giới hạn mỗi khách hàng</label>
            <input type="number" name="per_user_limit" id="perUserLimit" class="form-control" min="1" placeholder="Để trống nếu không giới hạn" value="<?= htmlspecialchars($formData['per_user_limit'] ?? '') ?>">
            <small class="text-muted">Ví dụ: 1 = mỗi khách chỉ dùng 1 lần.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nhóm khách hàng</label>
            <select class="form-select" name="customer_group" id="customerGroup">
                <?php $group = $formData['customer_group'] ?? ''; ?>
                <option value="" <?= $group === '' ? 'selected' : '' ?>>Tất cả</option>
                <option value="vip_today" <?= $group === 'vip_today' ? 'selected' : '' ?>>VIP (đơn giao thành công ≥ 2.000.000đ)</option>
            </select>
            <small class="text-muted" id="vipHelp">VIP được xác định khi có đơn giao thành công đạt ngưỡng và tự động gán hạng.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Trạng thái</label>
            <select class="form-select" name="status" required>
                <option value="active">Hoạt động</option>
                <option value="inactive">Ngừng hoạt động</option>
            </select>
        </div>
        <div class="col-12">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="newCustomerOnly" name="new_customer_only" <?= !empty($formData['new_customer_only']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="newCustomerOnly">Chỉ khách mới</label>
                        <small class="text-muted d-block">Khách mới = chưa có đơn giao thành công.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="returnOnRefund" name="return_on_refund" <?= !empty($formData['return_on_refund']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="returnOnRefund">Hoàn lượt khi hoàn tiền</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-4 gap-2">
        <a href="<?= BASE_URL ?>?action=admin-coupons" class="btn btn-outline-secondary">Hủy</a>
        <button type="submit" class="btn btn-primary">Lưu</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const discountType = document.querySelector('select[name="discount_type"]');
    const maxDiscountInput = document.getElementById('maxDiscountAmount');
    const customerGroupSelect = document.getElementById('customerGroup');
    const vipHelp = document.getElementById('vipHelp');
    const newCustomerOnly = document.getElementById('newCustomerOnly');
    const perUserLimit = document.getElementById('perUserLimit');
    const returnOnRefund = document.getElementById('returnOnRefund');

    function toggleMaxDiscount() {
        if (!discountType || !maxDiscountInput) return;
        if (discountType.value === 'fixed') {
            maxDiscountInput.value = '';
            maxDiscountInput.disabled = true;
            maxDiscountInput.placeholder = 'Không áp dụng cho giảm cố định';
        } else {
            maxDiscountInput.disabled = false;
            maxDiscountInput.placeholder = 'Để trống nếu không giới hạn';
        }
    }

    function toggleVipHelp() {
        if (!customerGroupSelect || !vipHelp) return;
        vipHelp.style.display = customerGroupSelect.value === 'vip_today' ? 'block' : 'none';
    }

    function syncNewCustomerLock() {
        if (!newCustomerOnly || !perUserLimit || !returnOnRefund) return;
        if (newCustomerOnly.checked) {
            perUserLimit.value = 1;
            perUserLimit.readOnly = true;
            returnOnRefund.checked = true;
            // Thêm tooltip hoặc ghi chú
            perUserLimit.title = 'Tự động set 1 lượt cho khách mới';
        } else {
            perUserLimit.readOnly = false;
            perUserLimit.title = '';
            // Nếu giá trị đang là 1 (do trước đó check) thì reset về rỗng
            if (perUserLimit.value === '1') {
                perUserLimit.value = '';
            }
        }
    }

    function toggleUnlimitedEndDate() {
        const unlimitedCheckbox = document.getElementById('unlimitedEndDate');
        const endDateInput = document.getElementById('endDateInput');
        const startDateInput = document.getElementById('startDateInput');
        
        if (!unlimitedCheckbox || !endDateInput || !startDateInput) return;
        
        if (unlimitedCheckbox.checked) {
            // Ngày kết thúc: +100 năm
            endDateInput.readOnly = true;
            endDateInput.value = new Date(new Date().setFullYear(new Date().getFullYear() + 100)).toISOString().slice(0, 16);
            endDateInput.style.opacity = '0.5';
            endDateInput.style.pointerEvents = 'none';

            // Ngày bắt đầu: Set về hiện tại và khóa lại
            startDateInput.readOnly = true;
             startDateInput.value = new Date().toISOString().slice(0, 16); // Lấy thời gian hiện tại
            startDateInput.style.opacity = '0.5';
            startDateInput.style.pointerEvents = 'none';
        } else {
            // Enable lại input
            endDateInput.readOnly = false;
            // Reset về 30 ngày sau
            endDateInput.value = new Date(Date.now() + 30*24*60*60*1000).toISOString().slice(0, 16);
            endDateInput.style.opacity = '1';
            endDateInput.style.pointerEvents = 'auto';

            // Enable lại Start Date
            startDateInput.readOnly = false;
            startDateInput.style.opacity = '1';
            startDateInput.style.pointerEvents = 'auto';
        }
    }

    if (discountType) {
        discountType.addEventListener('change', toggleMaxDiscount);
        toggleMaxDiscount();
    }

    if (customerGroupSelect) {
        customerGroupSelect.addEventListener('change', toggleVipHelp);
        toggleVipHelp();
    }

    if (newCustomerOnly) {
        newCustomerOnly.addEventListener('change', syncNewCustomerLock);
        syncNewCustomerLock(); // Chạy lần đầu để set giá trị ban đầu
    }

    // Handle unlimited end date checkbox
    const unlimitedEndDate = document.getElementById('unlimitedEndDate');
    if (unlimitedEndDate) {
        unlimitedEndDate.addEventListener('change', toggleUnlimitedEndDate);
    }
});
</script>

