<section class="container product-detail-section py-5">
    <div class="row g-5">
        <?php
            // Fallback ảnh chính: ưu tiên ảnh sản phẩm hợp lệ, nếu file đã mất thì dùng ảnh biến thể đầu tiên.
            $defaultImageUrl = BASE_URL . 'assets/images/logo.png';
            $mainImageCandidates = [];

            if (!empty($product['image'])) {
                $mainImageCandidates[] = $product['image'];
            } elseif (!empty($product['image_url'])) {
                $mainImageCandidates[] = $product['image_url'];
            }

            if (!empty($variants ?? [])) {
                foreach ($variants as $v) {
                    if (!empty($v['image_url'])) {
                        $mainImageCandidates[] = $v['image_url'];
                    }
                }
            }

            $mainImageUrl = $defaultImageUrl;
            foreach ($mainImageCandidates as $candidate) {
                $candidateUrl = getProductImageUrl((string)$candidate, false);
                if ($candidateUrl !== $defaultImageUrl) {
                    $mainImageUrl = $candidateUrl;
                    break;
                }
            }
        ?>
        <div class="col-lg-6 product-gallery">
            <div class="product-main-image-wrapper mb-4">
                <img id="mainProductImage" src="<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($product['name'] ?? '') ?>" class="product-main-image rounded shadow-sm" onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
            </div>
            <div class="product-thumbnails" id="productThumbnails">
                <?php 
                // Hiển thị tất cả ảnh: ảnh sản phẩm + ảnh biến thể với thông tin variant
                $allThumbnails = [];
                
                // Thêm ảnh từ $images (ảnh sản phẩm)
                if (!empty($images)) {
                    foreach ($images as $img) {
                        if (!empty($img['image_url'])) {
                            $allThumbnails[] = [
                                'url' => $img['image_url'],
                                'variant_id' => null,
                                'size' => null,
                                'color' => null
                            ];
                        }
                    }
                }
                
                // Thêm ảnh từ biến thể với thông tin size/color
                if (!empty($variants)) {
                    foreach ($variants as $variant) {
                        // Lấy size và color từ attributes
                        $variantSize = null;
                        $variantColor = null;
                        if (!empty($variant['attributes'])) {
                            foreach ($variant['attributes'] as $attr) {
                                $attrName = mb_strtolower(trim($attr['attribute_name'] ?? ''));
                                if (preg_match('/size|kích|kich/i', $attrName)) {
                                    $variantSize = $attr['value_name'] ?? null;
                                } elseif (preg_match('/color|màu|mau/i', $attrName)) {
                                    $variantColor = $attr['value_name'] ?? null;
                                }
                            }
                        }
                        
                        // Thêm ảnh variant
                        if (!empty($variant['image_url'])) {
                            $exists = false;
                            foreach ($allThumbnails as $thumb) {
                                if ($thumb['url'] === $variant['image_url']) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists) {
                                $allThumbnails[] = [
                                    'url' => $variant['image_url'],
                                    'variant_id' => $variant['variant_id'] ?? null,
                                    'size' => $variantSize,
                                    'color' => $variantColor
                                ];
                            }
                        }
                    }
                }
                
                // Nếu không có ảnh nào, dùng ảnh chính
                if (empty($allThumbnails) && !empty($mainImage)) {
                    $allThumbnails[] = [
                        'url' => $mainImage,
                        'variant_id' => null,
                        'size' => null,
                        'color' => null
                    ];
                }
                ?>
                
                <?php if (!empty($allThumbnails)): ?>
                    <?php foreach ($allThumbnails as $index => $thumb): 
                        $thumbUrlProcessed = getProductImageUrl($thumb['url'], false);
                        $dataAttrs = '';
                        if ($thumb['variant_id']) {
                            $dataAttrs .= ' data-variant-id="' . htmlspecialchars($thumb['variant_id']) . '"';
                        }
                        if ($thumb['size']) {
                            $dataAttrs .= ' data-size="' . htmlspecialchars($thumb['size']) . '"';
                        }
                        if ($thumb['color']) {
                            $dataAttrs .= ' data-color="' . htmlspecialchars($thumb['color']) . '"';
                        }
                    ?>
                        <img src="<?= htmlspecialchars($thumbUrlProcessed) ?>" alt="Thumbnail <?= $index + 1 ?>" 
                             class="product-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                             onclick="changeMainImage('<?= htmlspecialchars($thumbUrlProcessed) ?>', this)"
                             onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;"
                             title="Ảnh <?= $index + 1 ?>"
                             <?= $dataAttrs ?>>
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($mainImageUrl) ?>" alt="Thumbnail" class="product-thumbnail active"
                         onclick="changeMainImage('<?= htmlspecialchars($mainImageUrl) ?>', this)"
                         onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 product-info">
            <div class="product-header mb-4">
                <h1 class="product-title mb-3"><?= htmlspecialchars($product['name'] ?? '') ?></h1>
                <div class="product-price-wrapper mb-4">
                    <?php 
                    $salePrice = $product['sale_price'] ?? null;
                    $originalPrice = $product['original_price'] ?? $product['price'] ?? 0;
                    $displayPrice = $salePrice && $salePrice > 0 && $salePrice < $originalPrice ? $salePrice : $originalPrice;
                    ?>
                    <span class="product-price text-success fw-bold fs-4"><?= number_format($displayPrice, 0, ',', '.') ?> đ</span>
                    <?php if ($salePrice && $salePrice > 0 && $salePrice < $originalPrice): ?>
                        <div class="mt-2">
                            <span class="text-muted text-decoration-line-through"><?= number_format($originalPrice, 0, ',', '.') ?> đ</span>
                            <span class="badge bg-danger ms-2">Giảm <?= round((($originalPrice - $salePrice) / $originalPrice) * 100) ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="product-description-wrapper mb-4">
                <p class="product-description"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
            </div>

            <form id="productDetailForm">
                <input type="hidden" id="productId" value="<?= $product['id'] ?? 0 ?>">
                
                <!-- Size Selector -->
                <div class="attribute-selector" id="sizeSelector">
                    <label for="productSize">Kích thước</label>
                    <input type="hidden" id="productSize" name="size">
                    <div class="size-options" id="sizeOptions" role="radiogroup" aria-labelledby="sizeSelector">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>

                <!-- Color Selector -->
                <div class="attribute-selector" id="colorSelector">
                    <label for="productColor">Màu sắc</label>
                    <input type="hidden" id="productColor" name="color">
                    <div class="color-options" id="colorOptions" role="radiogroup" aria-labelledby="colorSelector">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>

                <!-- Stock Info -->
                <div class="mb-3">
                    <p class="small text-muted mb-0">
                        <i class="bi bi-box-seam"></i> 
                        Tồn kho: <span id="productStockInfo" class="fw-bold">--</span>
                    </p>
                </div>

                <!-- Quantity Selector -->
                <div class="quantity-selector" id="productQuantitySection">
                    <label for="productQuantity">Số lượng</label>
                    <div class="quantity-input-group">
                        <button type="button" onclick="changeQuantity(-1)" aria-label="Giảm số lượng">-</button>
                        <input type="number" id="productQuantity" name="quantity" value="1" min="1" max="999" aria-label="Số lượng sản phẩm" onchange="validateQuantity(this)">
                        <button type="button" onclick="changeQuantity(1)" aria-label="Tăng số lượng">+</button>
                    </div>
                </div>

                <!-- Actions -->
                <?php if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin'): ?>
                <div id="productActionButtons">
                    <button type="button" class="btn btn-add-to-cart" onclick="addToCart()">Thêm vào giỏ hàng</button>
                    <button type="button" class="btn btn-buy-now" onclick="buyNow()">Mua ngay</button>
                </div>
                <div class="d-none" id="productOutOfStockButton">
                    <a href="<?= BASE_URL ?>?action=products<?= !empty($product['category_id']) ? '&category_id=' . $product['category_id'] : '' ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-grid"></i> Xem sản phẩm tương tự
                    </a>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Tài khoản quản trị chỉ có thể xem sản phẩm, không thể mua hàng.
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <!-- Reviews Section -->
    <div class="reviews-section mt-5 pt-5 border-top">
        <h2 class="section-title mb-4">Đánh giá sản phẩm</h2>
        
        <?php if (!empty($reviewStats) && $reviewStats['total_reviews'] > 0): ?>
            <div class="review-summary mb-4 p-4 bg-light rounded">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <div class="average-rating">
                            <div class="display-4 fw-bold"><?= number_format($reviewStats['average_rating'], 1) ?></div>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= round($reviewStats['average_rating']) ? '-fill text-warning' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="text-muted small">Dựa trên <?= $reviewStats['total_reviews'] ?> đánh giá</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="rating-breakdown">
                            <?php for ($star = 5; $star >= 1; $star--): 
                                $count = $reviewStats['rating_' . $star] ?? 0;
                                $percentage = $reviewStats['total_reviews'] > 0 ? ($count / $reviewStats['total_reviews']) * 100 : 0;
                            ?>
                                <div class="rating-bar-item mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 small"><?= $star ?> sao</span>
                                        <div class="progress flex-grow-1 progress-thin">
                                            <div class="progress-bar bg-warning rating-progress-bar" role="progressbar" data-progress="<?= $percentage ?>"></div>
                                        </div>
                                        <span class="ms-2 small text-muted"><?= $count ?></span>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-light text-center">
                <p class="mb-0">Chưa có đánh giá nào cho sản phẩm này.</p>
            </div>
        <?php endif; ?>

        <!-- Reviews Filters + List -->
        <?php if (!empty($reviews)): ?>
            <div class="review-filters d-flex flex-wrap gap-2 mb-3">
                <?php 
                    $totalReviews = $reviewStats['total_reviews'] ?? count($reviews);
                    $countComment = count(array_filter($reviews, fn($r) => !empty($r['comment'])));
                    $countMedia   = count(array_filter($reviews, function($r) {
                        $imgs = $r['images'] ?? [];
                        if (is_string($imgs)) {
                            $imgs = json_decode($imgs, true);
                        }
                        return !empty($imgs);
                    }));
                ?>
                <button type="button" class="btn btn-sm btn-outline-secondary review-filter-btn active" data-filter="all">
                    Tất cả (<?= $totalReviews ?>)
                </button>
                <?php for ($star = 5; $star >=1; $star--): 
                    $countStar = $reviewStats['rating_' . $star] ?? 0;
                ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary review-filter-btn" data-filter="star-<?= $star ?>">
                        <?= $star ?> Sao (<?= $countStar ?>)
                    </button>
                <?php endfor; ?>
                <button type="button" class="btn btn-sm btn-outline-secondary review-filter-btn" data-filter="comment">
                    Có bình luận (<?= $countComment ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary review-filter-btn" data-filter="media">
                    Có hình ảnh / Video (<?= $countMedia ?>)
                </button>
            </div>

            <div class="reviews-list" id="reviewsList">
                <?php foreach ($reviews as $review): 
                    $reviewImages = $review['images'] ?? [];
                    if (is_string($reviewImages)) {
                        $reviewImages = json_decode($reviewImages, true);
                        if (!is_array($reviewImages)) $reviewImages = [];
                    }
                    $hasComment = !empty($review['comment']);
                    $hasImages  = !empty($reviewImages);
                ?>
                    <div class="review-item mb-4 p-3 border rounded"
                         data-rating="<?= (int)($review['rating'] ?? 0) ?>"
                         data-has-comment="<?= $hasComment ? '1' : '0' ?>"
                         data-has-media="<?= $hasImages ? '1' : '0' ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong><?= htmlspecialchars($review['user_name'] ?? 'Khách hàng') ?></strong>
                                <div class="rating-stars mt-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill text-warning' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($review['created_at'])) ?></small>
                        </div>
                        <?php if ($hasComment): ?>
                            <p class="mb-2"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        <?php endif; ?>
                        <?php if ($hasImages): ?>
                            <div class="review-images mt-2 mb-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($reviewImages as $img): ?>
                                        <a href="<?= htmlspecialchars($img) ?>" target="_blank" class="review-image-link">
                                            <img src="<?= htmlspecialchars($img) ?>" alt="Review image" class="img-thumbnail review-thumb">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($review['reply'])): ?>
                            <div class="review-reply mt-3 p-3 bg-light rounded border-start border-3 border-dark">
                                <small class="text-muted d-block mb-1"><strong>Phản hồi từ cửa hàng:</strong></small>
                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($review['reply'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="reviewEmptyState" class="alert alert-light text-center d-none">Không có đánh giá phù hợp bộ lọc.</div>
        <?php endif; ?>
    </div>

    <!-- Similar Products -->
    <?php if (!empty($similarProducts)): ?>
        <div class="similar-products-section mt-5 pt-5 border-top">
            <h2 class="section-title">Sản phẩm tương tự</h2>
            <div class="row g-4">
                <?php foreach ($similarProducts as $similar): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <article class="product-card">
                            <div class="product-card-image-wrapper">
                                <img src="<?= $similar['image'] ?>" alt="<?= htmlspecialchars($similar['name']) ?>">
                                <div class="product-card-overlay">
                                    <div class="product-card-icon" onclick="openQuickAdd(<?= $similar['id'] ?>, '<?= htmlspecialchars($similar['name']) ?>', <?= $similar['price'] ?>, '<?= $similar['image'] ?>')" title="Thêm vào giỏ hàng">
                                        <i class="bi bi-bag-plus"></i>
                                    </div>
                                    <a href="<?= BASE_URL ?>?action=product-detail&id=<?= $similar['id'] ?>" class="product-card-icon" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <p class="text-uppercase small text-muted mb-1 similar-card-pad"><?= $similar['category'] ?></p>
                            <h3 class="h6 similar-card-pad"><?= $similar['name'] ?></h3>
                            <p class="fw-semibold similar-card-pad-bottom"><?= number_format($similar['price'], 0, ',', '.') ?> đ</p>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    const colorMap = {
        'Black': '#000',
        'White': '#fff',
        'Beige': '#f5f5dc',
        'Red': '#ff0000',
        'Blue': '#0000ff',
        'Green': '#008000',
        'Yellow': '#ffff00',
        'Pink': '#ffc0cb',
        'Gray': '#808080',
        'Brown': '#a52a2a'
    };

    // Load attributes on page load
    document.addEventListener('DOMContentLoaded', function() {
        const productId = document.getElementById('productId').value;
    document.querySelectorAll('.rating-progress-bar').forEach(bar => {
        const width = bar.dataset.progress;
        if (width !== undefined) {
            bar.style.width = `${width}%`;
        }
    });
        if (productId) {
            loadProductAttributes(productId);
        }
    });

    // Load product attributes
    function loadProductAttributes(productId) {
        fetch(`<?= BASE_URL ?>?action=product-attributes&product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    renderSizeOptions(data.data.sizes || []);
                    renderColorOptions(data.data.colors || []);
                    
                    // Set default and update image & stock
                    if (data.data.sizes && data.data.sizes.length > 0) {
                        document.querySelector('.size-option').classList.add('active');
                        document.getElementById('productSize').value = data.data.sizes[0];
                    }
                    if (data.data.colors && data.data.colors.length > 0) {
                        document.querySelector('.color-option').classList.add('active');
                        document.getElementById('productColor').value = data.data.colors[0];
                    }

                    const firstSize = (data.data.sizes && data.data.sizes.length > 0) ? data.data.sizes[0] : null;
                    const firstColor = (data.data.colors && data.data.colors.length > 0) ? data.data.colors[0] : null;
                    if (firstSize || firstColor) {
                        updateProductImages(productId, firstSize, firstColor);
                        updateProductStock(productId, firstSize, firstColor);
                    } else {
                        // Nếu không có thuộc tính, highlight thumbnail đầu tiên
                        const firstThumb = document.querySelector('.product-thumbnail');
                        if (firstThumb) {
                            firstThumb.classList.add('active');
                        }
                    }
                }
            })
            .catch(err => console.error('Error loading attributes:', err));
    }

    // Render size options
    function renderSizeOptions(sizes) {
        const container = document.getElementById('sizeOptions');
        container.innerHTML = '';
        
        sizes.forEach((size, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'size-option';
            btn.textContent = size;
            btn.dataset.size = size;
            if (index === 0) btn.classList.add('active');
            btn.addEventListener('click', function() {
                document.querySelectorAll('.size-option').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('productSize').value = size;
                const productId = document.getElementById('productId').value;
                const color = document.querySelector('.color-option.active')?.dataset.color;
                updateProductImages(productId, size, color);
                updateProductStock(productId, size, color);
            });
            container.appendChild(btn);
        });
    }

    // Render color options
    function renderColorOptions(colors) {
        const container = document.getElementById('colorOptions');
        container.innerHTML = '';
        
        colors.forEach((color, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'color-option';
            if (color.toLowerCase() === 'white') btn.classList.add('white');
            btn.style.backgroundColor = colorMap[color] || '#ccc';
            btn.dataset.color = color;
            btn.title = color;
            if (index === 0) btn.classList.add('active');
            btn.addEventListener('click', function() {
                document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('productColor').value = color;
                const productId = document.getElementById('productId').value;
                const size = document.querySelector('.size-option.active')?.dataset.size;
                updateProductImages(productId, size, color);
                updateProductStock(productId, size, color);
            });
            container.appendChild(btn);
        });
    }

    // Update product images based on selected attributes
    function updateProductImages(productId, size, color) {
        if (!productId) return;
        
        const params = new URLSearchParams({ product_id: productId });
        if (size) params.append('size', size);
        if (color) params.append('color', color);
        
        fetch(`<?= BASE_URL ?>?action=variant-images&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    // Update main image
                    const mainImageUrl = data.data[0];
                    document.getElementById('mainProductImage').src = mainImageUrl;
                    
                    // Highlight thumbnail tương ứng với variant được chọn
                    highlightVariantThumbnail(size, color, mainImageUrl);
                } else {
                    // Nếu không có ảnh variant, highlight ảnh sản phẩm chính
                    highlightVariantThumbnail(size, color, null);
                }
            })
            .catch(err => console.error('Error updating images:', err));
    }
    
    // Highlight thumbnail tương ứng với variant được chọn
    function highlightVariantThumbnail(size, color, imageUrl) {
        const thumbnails = document.querySelectorAll('.product-thumbnail');
        
        // Xóa active từ tất cả thumbnails
        thumbnails.forEach(thumb => {
            thumb.classList.remove('active');
        });
        
        // Tìm thumbnail phù hợp với size/color
        let found = false;
        let bestMatch = null;
        let bestMatchScore = 0;
        
        thumbnails.forEach(thumb => {
            const thumbSize = thumb.dataset.size || '';
            const thumbColor = thumb.dataset.color || '';
            const thumbSrc = thumb.src;
            let matchScore = 0;
            
            // Tính điểm khớp
            if (size && thumbSize === size) matchScore += 2;
            if (color && thumbColor === color) matchScore += 2;
            
            // Nếu có imageUrl, kiểm tra xem có khớp không
            if (imageUrl) {
                try {
                    const urlObj = new URL(imageUrl);
                    const thumbUrlObj = new URL(thumbSrc);
                    if (urlObj.pathname === thumbUrlObj.pathname) {
                        matchScore += 5; // Ưu tiên cao nhất nếu URL khớp
                    }
                } catch (e) {
                    // Nếu không phải URL đầy đủ, so sánh phần cuối
                    const imageFileName = imageUrl.split('/').pop().split('?')[0];
                    const thumbFileName = thumbSrc.split('/').pop().split('?')[0];
                    if (imageFileName === thumbFileName) {
                        matchScore += 5;
                    }
                }
            }
            
            // Lưu match tốt nhất
            if (matchScore > bestMatchScore) {
                bestMatchScore = matchScore;
                bestMatch = thumb;
            }
            
            // Nếu khớp hoàn toàn (cả size và color)
            if (size && color && thumbSize === size && thumbColor === color) {
                thumb.classList.add('active');
                found = true;
                if (imageUrl) {
                    document.getElementById('mainProductImage').src = imageUrl;
                } else {
                    document.getElementById('mainProductImage').src = thumbSrc;
                }
                return;
            }
        });
        
        // Nếu không tìm thấy khớp hoàn toàn, dùng match tốt nhất
        if (!found && bestMatch && bestMatchScore > 0) {
            bestMatch.classList.add('active');
            if (imageUrl) {
                document.getElementById('mainProductImage').src = imageUrl;
            } else {
                document.getElementById('mainProductImage').src = bestMatch.src;
            }
            found = true;
        }
        
        // Nếu vẫn không tìm thấy, active thumbnail đầu tiên
        if (!found && thumbnails.length > 0) {
            thumbnails[0].classList.add('active');
        }
    }

    // Change main image
    function changeMainImage(src, element) {
        document.getElementById('mainProductImage').src = src;
        document.querySelectorAll('.product-thumbnail').forEach(el => el.classList.remove('active'));
        if (element) {
            element.classList.add('active');
        }
    }

    // Update product stock based on selected variant
    function updateProductStock(productId, size, color) {
        if (!productId || (!size && !color)) return;
        
        const params = new URLSearchParams({ product_id: productId });
        if (size) params.append('size', size);
        if (color) params.append('color', color);

        fetch(`<?= BASE_URL ?>?action=get-variant-stock&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                const stockInfo = document.getElementById('productStockInfo');
                const quantitySection = document.getElementById('productQuantitySection');
                const actionButtons = document.getElementById('productActionButtons');
                const outOfStockButton = document.getElementById('productOutOfStockButton');
                const quantityInput = document.getElementById('productQuantity');
                
                if (data.success && data.stock !== undefined) {
                    const stock = parseInt(data.stock);
                    stockInfo.textContent = stock > 0 ? stock + ' sản phẩm' : 'Hết hàng';
                    stockInfo.className = stock > 0 ? 'fw-bold text-success' : 'fw-bold text-danger';
                    
                    if (stock > 0) {
                        // Có hàng - hiện nút mua
                        quantitySection.style.display = 'flex';
                        actionButtons.classList.remove('d-none');
                        outOfStockButton.classList.add('d-none');
                        quantityInput.max = stock;
                        if (parseInt(quantityInput.value) > stock) {
                            quantityInput.value = stock;
                        }
                    } else {
                        // Hết hàng - hiện nút xem sản phẩm tương tự
                        quantitySection.style.display = 'none';
                        actionButtons.classList.add('d-none');
                        outOfStockButton.classList.remove('d-none');
                    }
                } else {
                    stockInfo.textContent = 'Không xác định';
                    stockInfo.className = 'fw-bold text-muted';
                }
            })
            .catch(err => {
                console.error('Error loading stock:', err);
                document.getElementById('productStockInfo').textContent = 'Không xác định';
            });
    }

    // Change quantity
    function changeQuantity(change) {
        const input = document.getElementById('productQuantity');
        let val = parseInt(input.value) + change;
        if (val < 1) val = 1;
        if (val > 999) val = 999;
        input.value = val;
    }

    // Validate quantity when user types manually
    function validateQuantity(input) {
        let val = parseInt(input.value);
        if (isNaN(val) || val < 1) {
            input.value = 1;
        } else if (val > 999) {
            input.value = 999;
        } else {
            input.value = val;
        }
    }

    // Add to cart
    function addToCart() {
        const productId = document.getElementById('productId').value;
        const quantity = document.getElementById('productQuantity').value;
        const size = document.querySelector('.size-option.active')?.dataset.size;
        const color = document.querySelector('.color-option.active')?.dataset.color;
        
        if (!size || !color) {
            showToast('Vui lòng chọn đầy đủ thuộc tính sản phẩm', 'warning');
            return;
        }
        
        const data = {
            product_id: productId,
            quantity: quantity,
            size: size,
            color: color
        };
        
        fetch('<?= BASE_URL ?>?action=cart-add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(res => {
            if (res.require_login) {
                showToast('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng', 'warning');
                setTimeout(() => {
                    window.location.href = '<?= BASE_URL ?>?action=show-login';
                }, 1500);
                return;
            }
            
            if (res.success) {
                updateCartCount();
                showToast('Đã thêm sản phẩm vào giỏ hàng!');
            } else {
                showToast(res.message || 'Có lỗi xảy ra', 'error');
                console.error('Add to cart error:', res);
            }
        })
        .catch(err => {
            console.error('Add to cart error:', err);
            showToast('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
        });
    }

    // Buy now - chỉ mua sản phẩm này và chuyển thẳng đến thanh toán
    function buyNow() {
        const productId = document.getElementById('productId').value;
        const quantity = document.getElementById('productQuantity').value;
        const size = document.querySelector('.size-option.active')?.dataset.size;
        const color = document.querySelector('.color-option.active')?.dataset.color;
        
        if (!size || !color) {
            showToast('Vui lòng chọn đầy đủ thuộc tính sản phẩm', 'warning');
            return;
        }
        
        const data = {
            product_id: productId,
            quantity: quantity,
            size: size,
            color: color
        };
        
        fetch('<?= BASE_URL ?>?action=cart-add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(res => {
            if (res.require_login) {
                showToast('Vui lòng đăng nhập để mua hàng', 'warning');
                setTimeout(() => {
                    window.location.href = '<?= BASE_URL ?>?action=show-login';
                }, 1500);
                return;
            }
            
            if (res.success) {
                updateCartCount();
                // Lấy cartKey từ response hoặc tạo từ productId, size, color
                const cartKey = res.cart_key || (productId + '_' + (size || 'null') + '_' + (color || 'null'));
                
                // Chỉ chọn sản phẩm này để thanh toán
                fetch('<?= BASE_URL ?>?action=cart-set-selected', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        selected_items: [cartKey]
                    })
                })
                .then(() => {
                    // Chuyển đến trang checkout với chỉ sản phẩm này được chọn
                    window.location.href = '<?= BASE_URL ?>?action=checkout';
                })
                .catch(err => {
                    console.error('Error setting selected items:', err);
                    // Vẫn chuyển đến checkout
                    window.location.href = '<?= BASE_URL ?>?action=checkout';
                });
            } else {
                showToast(res.message || 'Có lỗi xảy ra', 'error');
                console.error('Add to cart error:', res);
            }
        })
        .catch(err => {
            console.error('Add to cart error:', err);
            showToast('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
        });
    }

    // Toast notification
    function showToast(message, type = 'success') {
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1100';
            document.body.appendChild(toastContainer);
        }

        const bgClass = type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-dark';
        const icon = type === 'error' ? 'bi-x-circle-fill' : type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';

        const toastHtml = `
            <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.innerHTML = toastHtml;
        const toastEl = toastContainer.querySelector('.toast');
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }

    // Update cart count
    function updateCartCount() {
        fetch('<?= BASE_URL ?>?action=cart-count')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.textContent = data.count || 0;
                }
            })
            .catch(err => console.error('Lỗi cập nhật giỏ hàng:', err));
    }

    // Quick Add function (for similar products)
    function openQuickAdd(id, name, price, image) {
        // Redirect to detail page or open modal
        window.location.href = `<?= BASE_URL ?>?action=product-detail&id=${id}`;
    }

    // Review filters
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('.review-filter-btn');
        const reviewItems = document.querySelectorAll('.review-item');
        const emptyState = document.getElementById('reviewEmptyState');

        function applyFilter(filter) {
            let visible = 0;
            reviewItems.forEach(item => {
                const rating = parseInt(item.dataset.rating || 0);
                const hasCmt = item.dataset.hasComment === '1';
                const hasMedia = item.dataset.hasMedia === '1';

                let show = false;
                if (filter === 'all') show = true;
                else if (filter.startsWith('star-')) {
                    const star = parseInt(filter.split('-')[1] || 0);
                    show = rating === star;
                } else if (filter === 'comment') {
                    show = hasCmt;
                } else if (filter === 'media') {
                    show = hasMedia;
                }

                item.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', visible > 0);
            }
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyFilter(btn.dataset.filter);
            });
        });
    });
</script>

