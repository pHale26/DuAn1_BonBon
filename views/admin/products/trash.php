<div class="container-fluid">
    <form method="POST" action="<?= BASE_URL ?>?action=admin-product-bulk-trash" id="bulkForm">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Thùng rác sản phẩm</h1>
                <p class="text-muted mb-0 small mt-1">Quản lý sản phẩm đã xóa tạm thời</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($products)): ?>
                    <a href="<?= BASE_URL ?>?action=admin-product-restore-all" 
                       class="btn btn-outline-success" 
                       onclick="return confirm('Khôi phục tất cả sản phẩm trong thùng rác?');">
                        <i class="bi bi-reply-all me-1"></i>Khôi phục tất cả
                    </a>
                    <a href="<?= BASE_URL ?>?action=admin-product-empty-trash" 
                       class="btn btn-outline-danger" 
                       onclick="return confirm('Bạn có chắc muốn xóa sạch thùng rác? Hành động này KHÔNG THỂ khôi phục!');">
                        <i class="bi bi-trash me-1"></i>Xóa sạch thùng rác
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>?action=admin-products" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Bulk Action Toolbar -->
        <div class="card shadow mb-3 border-primary" id="bulkToolbar" style="display: none;">
             <div class="card-body py-2 d-flex align-items-center justify-content-between bg-primary bg-opacity-10">
                 <span class="fw-medium text-primary"><span id="selectedCount">0</span> sản phẩm đang được chọn</span>
                 <div class="btn-group">
                     <button type="submit" name="bulk_action" value="restore" class="btn btn-primary btn-sm">
                         <i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục đã chọn
                     </button>
                     <button type="submit" name="bulk_action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Xóa vĩnh viễn các sản phẩm đã chọn?');">
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
                                <th style="width: 80px">Hình ảnh</th>
                                <th style="width: 30%">Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá bán</th>
                                <th>Ngày xóa</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-trash display-4 d-block mb-3"></i>
                                        Thùng rác trống.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="form-check">
                                                <input class="form-check-input item-check" type="checkbox" name="ids[]" value="<?= $product['product_id'] ?>">
                                            </div>
                                        </td>
                                        <td>#<?= $product['product_id'] ?></td>
                                        <td>
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                     alt="" class="rounded" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-truncate" style="max-width: 300px;">
                                                <?= htmlspecialchars($product['product_name']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?></td>
                                        <td><?= number_format($product['price'] ?? 0, 0, ',', '.') ?>đ</td>
                                        <td><?= date('d/m/Y H:i', strtotime($product['deleted_at'])) ?></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <form method="POST" action="<?= BASE_URL ?>?action=admin-product-restore" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Khôi phục">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= BASE_URL ?>?action=admin-product-force-delete" class="d-inline ms-1" onsubmit="return confirm('Bạn có chắc muốn xóa vĩnh viễn?');">
                                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
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
