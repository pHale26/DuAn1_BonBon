<!-- Trang Bộ Sưu Tập -->
<section class="collection-page py-5">
    <div class="container">
        <!-- Header Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">Bộ Sưu Tập</h1>
            <p class="lead text-muted">Khám phá các bộ sưu tập độc đáo của chúng tôi</p>
        </div>

        <?php if (empty($collections)): ?>
            <div class="text-center py-5">
                <i class="bi bi-images empty-collection-icon"></i>
                <h3 class="mt-3">Chưa có bộ sưu tập nào</h3>
                <p class="text-muted">Vui lòng quay lại sau.</p>
            </div>
        <?php else: ?>
            <?php foreach ($collections as $collection): ?>
                <div class="collection-section">
                    <!-- Collection Header -->
                    <div class="collection-header">
                        <h2 class="collection-title"><?= htmlspecialchars($collection['category']['category_name']) ?></h2>
                        <?php if (!empty($collection['category']['description'])): ?>
                            <p class="collection-description"><?= htmlspecialchars($collection['category']['description']) ?></p>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>?action=products&category_id=<?= $collection['category']['category_id'] ?>" class="collection-link">
                            Xem tất cả <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Products Grid -->
                    <div class="row g-4">
                        <?php foreach ($collection['products'] as $product): 
                            $productId = (int)($product['product_id'] ?? 0);
                            // Lấy ảnh sử dụng helper function
                            $imageUrl = '';
                            if (!empty($product['image_url'])) {
                                $imageUrl = getProductImageUrl($product['image_url'], false);
                            } elseif (!empty($product['image'])) {
                                $imageUrl = getProductImageUrl($product['image'], false);
                            } elseif (!empty($product['primary_image'])) {
                                $imageUrl = getProductImageUrl($product['primary_image'], false);
                            }
                            // Nếu vẫn không có, thử lấy từ BASE_URL
                            if (empty($imageUrl) && $productId > 0) {
                                // Fallback: sử dụng placeholder
                                $imageUrl = BASE_URL . 'assets/images/logo.png';
                            }
                            $price = (float)($product['price'] ?? 0);
                            $productName = htmlspecialchars($product['product_name'] ?? '');
                        ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="collection-product-card">
                                    <a href="<?= BASE_URL ?>?action=product-detail&id=<?= $productId ?>" class="product-image-wrapper">
                                        <?php if ($imageUrl && $imageUrl !== BASE_URL . 'assets/images/logo.png'): ?>
                                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= $productName ?>" class="product-image" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/logo.png'; this.parentElement.innerHTML='<div class=\'product-image-placeholder\'><i class=\'bi bi-image\'></i></div>';">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-overlay">
                                            <span class="view-product-btn">Xem sản phẩm</span>
                                        </div>
                                    </a>
                                    <div class="product-info">
                                        <h5 class="product-name">
                                            <a href="<?= BASE_URL ?>?action=product-detail&id=<?= $productId ?>" class="text-decoration-none text-dark">
                                                <?= $productName ?>
                                            </a>
                                        </h5>
                                        <p class="product-price mb-0"><?= number_format($price, 0, ',', '.') ?> đ</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

