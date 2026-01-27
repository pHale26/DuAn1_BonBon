<div class="admin-page-header">
    <h1>Chi tiết sản phẩm</h1>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-products" class="btn btn-light-soft">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
        <a href="<?= BASE_URL ?>?action=admin-product-edit&id=<?= htmlspecialchars($product['id'] ?? $product['product_id'] ?? 0) ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Sửa
        </a>
    </div>
</div>

<div class="card p-3">
    <div class="row g-4">
        <div class="col-md-4 text-center">
            <?php if (!empty($product['image'])): ?>
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid rounded border">
            <?php elseif (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['product_name'] ?? '') ?>" class="img-fluid rounded border">
            <?php else: ?>
                <div class="placeholder-200 d-flex align-items-center justify-content-center border rounded">
                    <i class="bi bi-image fs-1 text-muted"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted small">Mã sản phẩm</label>
                    <div class="fw-bold"><?= htmlspecialchars($product['id'] ?? $product['product_id'] ?? '') ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Danh mục</label>
                    <div class="fw-bold"><?= htmlspecialchars($product['category'] ?? $product['category_name'] ?? 'Chưa phân loại') ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Tên sản phẩm</label>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($product['name'] ?? $product['product_name'] ?? '') ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Giá</label>
                    <div class="fw-bold text-primary"><?= number_format((float)($product['price'] ?? 0), 0, ',', '.') ?> VNĐ</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Tồn kho tổng</label>
                    <?php 
                    $totalStockQty = (int)($totalStock ?? 0);
                    $totalStockClass = 'text-success';
                    $totalStockIcon = 'bi-check-circle-fill';
                    $totalStockLabel = 'Còn hàng';
                    
                    if ($totalStockQty <= 0) {
                        $totalStockClass = 'text-danger';
                        $totalStockIcon = 'bi-x-circle-fill';
                        $totalStockLabel = 'Hết hàng';
                    } elseif ($totalStockQty <= 10) {
                        $totalStockClass = 'text-danger';
                        $totalStockIcon = 'bi-exclamation-triangle-fill';
                        $totalStockLabel = 'Sắp hết';
                    } elseif ($totalStockQty <= 50) {
                        $totalStockClass = 'text-warning';
                        $totalStockIcon = 'bi-exclamation-circle-fill';
                        $totalStockLabel = 'Tồn kho thấp';
                    }
                    ?>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $totalStockIcon ?> <?= $totalStockClass ?> fs-5"></i>
                        <div>
                            <div class="fw-bold <?= $totalStockClass ?> fs-4"><?= number_format($totalStockQty) ?></div>
                            <small class="text-muted"><?= $totalStockLabel ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Mô tả</label>
                    <div><?= nl2br(htmlspecialchars($product['description'] ?? 'Chưa có mô tả')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Thuộc tính</label>
                    <?php
                        $sizes  = $attributes['sizes'] ?? [];
                        $colors = $attributes['colors'] ?? [];
                    ?>
                    <?php if (!empty($sizes) || !empty($colors)): ?>
                        <div class="d-flex flex-wrap gap-3">
                            <?php if (!empty($sizes)): ?>
                                <div>
                                    <div class="text-muted small">Size:</div>
                                    <div class="fw-bold"><?= htmlspecialchars(implode(', ', $sizes)) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($colors)): ?>
                                <div>
                                    <div class="text-muted small">Màu:</div>
                                    <div class="fw-bold"><?= htmlspecialchars(implode(', ', $colors)) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">Chưa có thuộc tính cho sản phẩm này.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($variants ?? [])): ?>
<div class="card p-3 mt-3">
    <h5 class="fw-bold mb-3">Tồn kho theo biến thể</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Biến thể</th>
                    <th>SKU</th>
                    <th>Giá cộng thêm</th>
                    <th>Tồn kho</th>
                    <th>Ảnh</th>
                </tr>
            </thead>
            <tbody>
                    <?php foreach ($variants as $variant): 
                        $variantAttrs = [];
                        foreach ($variant['attributes'] ?? [] as $attr) {
                            $variantAttrs[] = $attr['value_name'] ?? '';
                        }
                        $variantName = implode(' / ', array_filter($variantAttrs));
                        $fallbackName = $variant['sku'] ? 'SKU: ' . $variant['sku'] : ('Biến thể #' . $variant['variant_id']);
                    ?>
                <tr>
                        <td><?= htmlspecialchars($variantName ?: $fallbackName) ?></td>
                    <td><?= htmlspecialchars($variant['sku'] ?? '-') ?></td>
                    <td><?= number_format((float)($variant['additional_price'] ?? 0), 0, ',', '.') ?> VNĐ</td>
                    <td>
                        <?php 
                        $varStockQty = (int)($variant['stock'] ?? 0);
                        $varStockClass = 'text-success';
                        $varStockIcon = 'bi-check-circle-fill';
                        
                        if ($varStockQty <= 0) {
                            $varStockClass = 'text-danger';
                            $varStockIcon = 'bi-x-circle-fill';
                        } elseif ($varStockQty <= 10) {
                            $varStockClass = 'text-danger';
                            $varStockIcon = 'bi-exclamation-triangle-fill';
                        } elseif ($varStockQty <= 50) {
                            $varStockClass = 'text-warning';
                            $varStockIcon = 'bi-exclamation-circle-fill';
                        }
                        ?>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= $varStockIcon ?> <?= $varStockClass ?>"></i>
                            <span class="fw-bold <?= $varStockClass ?>"><?= number_format($varStockQty) ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($variant['image_url'])): ?>
                            <img src="<?= htmlspecialchars($variant['image_url']) ?>" alt="Variant image" style="max-width:70px; max-height:70px; border-radius:6px; border:1px solid #e2e8f0;">
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

