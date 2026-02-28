<div class="admin-page-header">
    <h1>Quản lý sản phẩm</h1>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-products-trash" class="btn btn-light-soft">
            <i class="bi bi-trash"></i>
            Thùng rác
        </a>
        <a href="<?= BASE_URL ?>?action=admin-product-create" class="btn btn-light-soft">
            <i class="bi bi-plus-lg"></i>
            Thêm sản phẩm mới
        </a>
    </div>
</div>

<?php
// Tính toán thống kê tồn kho
$totalProducts = count($products);
$outOfStock = 0;
$lowStock = 0;
$mediumStock = 0;
$inStock = 0;

foreach ($products as $p) {
    $stock = (int)($p['stock'] ?? 0);
    if ($stock <= 0) {
        $outOfStock++;
    } elseif ($stock <= 10) {
        $lowStock++;
    } elseif ($stock <= 50) {
        $mediumStock++;
    } else {
        $inStock++;
    }
}
?>

<!-- Stock Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Tổng sản phẩm</div>
                        <div class="fs-4 fw-bold"><?= number_format($totalProducts) ?></div>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-box-seam fs-3 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Hết hàng</div>
                        <div class="fs-4 fw-bold text-danger"><?= number_format($outOfStock) ?></div>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                        <i class="bi bi-x-circle-fill fs-3 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Sắp hết (≤10)</div>
                        <div class="fs-4 fw-bold text-danger"><?= number_format($lowStock) ?></div>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Còn hàng</div>
                        <div class="fs-4 fw-bold text-success"><?= number_format($inStock) ?></div>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Form tìm kiếm và lọc -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= BASE_URL ?>" id="searchForm">
            <input type="hidden" name="action" value="admin-products">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-uppercase fw-bold">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="keyword" 
                               class="form-control" 
                               placeholder="Tên sản phẩm..." 
                               value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Danh mục</label>
                    <select name="category" class="form-select">
                        <option value="">Tất cả danh mục</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" 
                                    <?= ($_GET['category'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Mức giá</label>
                    <select name="price_range" class="form-select">
                        <option value="">Tất cả mức giá</option>
                        <option value="0-500000" <?= ($_GET['price_range'] ?? '') == '0-500000' ? 'selected' : '' ?>>Dưới 500.000đ</option>
                        <option value="500000-1000000" <?= ($_GET['price_range'] ?? '') == '500000-1000000' ? 'selected' : '' ?>>500.000đ - 1.000.000đ</option>
                        <option value="1000000-2000000" <?= ($_GET['price_range'] ?? '') == '1000000-2000000' ? 'selected' : '' ?>>1.000.000đ - 2.000.000đ</option>
                        <option value="2000000-999999999" <?= ($_GET['price_range'] ?? '') == '2000000-999999999' ? 'selected' : '' ?>>Trên 2.000.000đ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase fw-bold">Tồn kho</label>
                    <select name="stock_filter" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="out_of_stock" <?= ($_GET['stock_filter'] ?? '') == 'out_of_stock' ? 'selected' : '' ?>>Hết hàng</option>
                        <option value="low_stock" <?= ($_GET['stock_filter'] ?? '') == 'low_stock' ? 'selected' : '' ?>>Sắp hết (≤10)</option>
                        <option value="medium_stock" <?= ($_GET['stock_filter'] ?? '') == 'medium_stock' ? 'selected' : '' ?>>Tồn kho thấp (≤50)</option>
                        <option value="in_stock" <?= ($_GET['stock_filter'] ?? '') == 'in_stock' ? 'selected' : '' ?>>Còn hàng</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Tìm
                        </button>
                        <a href="<?= BASE_URL ?>?action=admin-products" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="admin-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hình ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá gốc</th>
                <th>Giá khuyến mãi</th>
                <th>Tồn kho</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox empty-icon-lg"></i>
                        <div>Chưa có sản phẩm nào</div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): 
                    // Lấy giá gốc: ưu tiên original_price, nếu không có thì dùng price
                    $originalPrice = (float)($product['original_price'] ?? $product['price'] ?? 0);
                    // Lấy giá khuyến mãi: sale_price nếu có và hợp lệ
                    $salePrice = null;
                    if (isset($product['sale_price']) && $product['sale_price'] !== null && $product['sale_price'] !== '') {
                        $salePriceFloat = (float)$product['sale_price'];
                        if ($salePriceFloat > 0 && $salePriceFloat < $originalPrice) {
                            $salePrice = $salePriceFloat;
                        }
                    }
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($product['product_id']) ?></strong></td>
                        <td class="product-image-cell">
                            <div class="product-image-frame">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="admin-product-image">
                                <?php else: ?>
                                    <div class="placeholder-80-box">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
                        </td>
                        <td>
                            <span class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?></span>
                        </td>
                        <td>
                            <span class="price-original"><?= number_format($originalPrice, 0, ',', '.') ?> VNĐ</span>
                        </td>
                        <td>
                            <?php 
                            // Hiển thị giá khuyến mãi nếu có và hợp lệ
                            if ($salePrice !== null && $salePrice > 0 && $salePrice < $originalPrice): 
                            ?>
                                <span class="price-promotional text-danger fw-bold"><?= number_format($salePrice, 0, ',', '.') ?> VNĐ</span>
                                <small class="text-success d-block">Giảm <?= round((($originalPrice - $salePrice) / $originalPrice) * 100) ?>%</small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $stockQty = (int)($product['stock'] ?? 0);
                            $stockClass = 'text-success'; // Mặc định: đủ hàng
                            $stockIcon = 'bi-check-circle-fill';
                            $stockLabel = 'Còn hàng';
                            
                            if ($stockQty <= 0) {
                                $stockClass = 'text-danger';
                                $stockIcon = 'bi-x-circle-fill';
                                $stockLabel = 'Hết hàng';
                            } elseif ($stockQty <= 10) {
                                $stockClass = 'text-danger';
                                $stockIcon = 'bi-exclamation-triangle-fill';
                                $stockLabel = 'Sắp hết';
                            } elseif ($stockQty <= 50) {
                                $stockClass = 'text-warning';
                                $stockIcon = 'bi-exclamation-circle-fill';
                                $stockLabel = 'Tồn kho thấp';
                            }
                            ?>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?= $stockIcon ?> <?= $stockClass ?>"></i>
                                <div>
                                    <div class="fw-bold <?= $stockClass ?>"><?= number_format($stockQty) ?></div>
                                    <small class="text-muted"><?= $stockLabel ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a 
                                    class="btn btn-info btn-sm text-white me-1" 
                                    title="Xem chi tiết" 
                                    href="<?= BASE_URL ?>?action=admin-product-detail&id=<?= $product['product_id'] ?>"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>?action=admin-product-edit&id=<?= $product['product_id'] ?>" class="btn-edit" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>?action=admin-product-delete" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                    <button type="submit" class="btn-delete" title="Xóa">
                                        <i class="bi bi-trash"></i>
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


