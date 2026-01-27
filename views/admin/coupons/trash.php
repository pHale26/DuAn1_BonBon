<div class="container-fluid">
    <form method="POST" action="<?= BASE_URL ?>?action=admin-coupon-bulk-trash" id="bulkForm">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Thùng rác mã giảm giá</h1>
                <p class="text-muted mb-0 small mt-1">Quản lý mã giảm giá đã xóa tạm thời</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($coupons)): ?>
                    <a href="<?= BASE_URL ?>?action=admin-coupon-restore-all" 
                       class="btn btn-outline-success" 
                       onclick="return confirm('Khôi phục tất cả mã giảm giá trong thùng rác?');">
                        <i class="bi bi-reply-all me-1"></i>Khôi phục tất cả
                    </a>
                    <a href="<?= BASE_URL ?>?action=admin-coupon-empty-trash" 
                       class="btn btn-outline-danger" 
                       onclick="return confirm('Bạn có chắc muốn xóa sạch thùng rác? Hành động này KHÔNG THỂ khôi phục!');">
                        <i class="bi bi-trash me-1"></i>Xóa sạch thùng rác
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>?action=admin-coupons" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Bulk Action Toolbar -->
        <div class="card shadow mb-3 border-primary" id="bulkToolbar" style="display: none;">
             <div class="card-body py-2 d-flex align-items-center justify-content-between bg-primary bg-opacity-10">
                 <span class="fw-medium text-primary"><span id="selectedCount">0</span> mã giảm giá đang được chọn</span>
                 <div class="btn-group">
                     <button type="submit" name="bulk_action" value="restore" class="btn btn-primary btn-sm">
                         <i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục đã chọn
                     </button>
                     <button type="submit" name="bulk_action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Xóa vĩnh viễn các mã đã chọn?');">
                         <i class="bi bi-x-lg me-1"></i>Xóa vĩnh viễn đã chọn
                     </button>
                 </div>
             </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width: 40px">
                                   <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                   </div>
                                </th>
                                <th style="width: 5%">ID</th>
                                <th>Mã code</th>
                                <th>Tên mã</th>
                                <th>Loại giảm</th>
                                <th>Giá trị</th>
                                <th>Ngày xóa</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-trash display-4 d-block mb-3"></i>
                                        Thùng rác trống.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="form-check">
                                                <input class="form-check-input item-check" type="checkbox" name="ids[]" value="<?= $coupon['coupon_id'] ?>">
                                            </div>
                                        </td>
                                        <td>#<?= $coupon['coupon_id'] ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">
                                                <?= htmlspecialchars($coupon['code']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($coupon['name']) ?></td>
                                        <td>
                                            <?php if ($coupon['discount_type'] == 'percent'): ?>
                                                <span class="badge bg-info">Phần trăm</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Cố định</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($coupon['discount_type'] == 'percent'): ?>
                                                <?= number_format($coupon['discount_value'], 0) ?>%
                                            <?php else: ?>
                                                <?= number_format($coupon['discount_value'], 0, ',', '.') ?> đ
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($coupon['deleted_at'])) ?></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <form method="POST" action="<?= BASE_URL ?>?action=admin-coupon-restore" class="d-inline">
                                                    <input type="hidden" name="coupon_id" value="<?= $coupon['coupon_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Khôi phục">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= BASE_URL ?>?action=admin-coupon-force-delete" class="d-inline ms-1" onsubmit="return confirm('Bạn có chắc muốn xóa vĩnh viễn?');">
                                                    <input type="hidden" name="coupon_id" value="<?= $coupon['coupon_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa vĩnh viễn">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const itemChecks = document.querySelectorAll('.item-check');
    const toolbar = document.getElementById('bulkToolbar');
    const selectedCount = document.getElementById('selectedCount');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            itemChecks.forEach(cb => cb.checked = isChecked);
            updateToolbar();
        });
    }

    itemChecks.forEach(cb => {
        cb.addEventListener('change', updateToolbar);
    });

    function updateToolbar() {
        const count = document.querySelectorAll('.item-check:checked').length;
        selectedCount.innerText = count;
        
        if (count > 0) {
            toolbar.style.display = 'block';
        } else {
            toolbar.style.display = 'none';
        }
        
        if (selectAll) {
            selectAll.checked = count > 0 && count === itemChecks.length;
            selectAll.indeterminate = count > 0 && count < itemChecks.length;
        }
    }
});
</script>
