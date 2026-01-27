<section class="container products-section">
    <h2 class="section-title">Tất cả sản phẩm</h2>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5>Tìm kiếm</h5>
                <form method="GET" action="<?= BASE_URL ?>">
                    <input type="hidden" name="action" value="products">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        <button class="btn btn-dark" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <?php if (isset($_GET['category_id'])): ?>
                        <input type="hidden" name="category_id" value="<?= (int)$_GET['category_id'] ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['price'])): ?>
                        <input type="hidden" name="price" value="<?= htmlspecialchars($_GET['price']) ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Danh mục</h5>
                <form method="GET" action="<?= BASE_URL ?>" id="categoryForm">
                    <input type="hidden" name="action" value="products">
                    <select name="category_id" class="form-select" onchange="document.getElementById('categoryForm').submit();">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories ?? [] as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($_GET['q'])): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($_GET['q']) ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['price'])): ?>
                        <input type="hidden" name="price" value="<?= htmlspecialchars($_GET['price']) ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Mức giá</h5>
                <form method="GET" action="<?= BASE_URL ?>" id="priceForm">
                    <input type="hidden" name="action" value="products">
                    <select name="price" class="form-select" onchange="document.getElementById('priceForm').submit();">
                        <option value="">Tất cả mức giá</option>
                        <option value="under300" <?= (isset($_GET['price']) && $_GET['price'] == 'under300') ? 'selected' : '' ?>>Dưới 300.000 đ</option>
                        <option value="300-500" <?= (isset($_GET['price']) && $_GET['price'] == '300-500') ? 'selected' : '' ?>>300.000 - 500.000 đ</option>
                        <option value="500-800" <?= (isset($_GET['price']) && $_GET['price'] == '500-800') ? 'selected' : '' ?>>500.000 - 800.000 đ</option>
                        <option value="above800" <?= (isset($_GET['price']) && $_GET['price'] == 'above800') ? 'selected' : '' ?>>Trên 800.000 đ</option>
                    </select>
                    <?php if (isset($_GET['q'])): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($_GET['q']) ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['category_id'])): ?>
                        <input type="hidden" name="category_id" value="<?= (int)$_GET['category_id'] ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Không tìm thấy sản phẩm nào.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product) : ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <article class="product-card">
                        <?php if (isset($product['id']) && isset($newProductIds) && in_array((int)$product['id'], $newProductIds, true)) : ?>
                            <span class="product-badge">New</span>
                        <?php endif; ?>
                        <div class="product-card-image-wrapper">
                            <?php 
                            $productImage = $product['image'] ?? $product['image_url'] ?? '';
                            $productImageUrl = !empty($productImage) ? getProductImageUrl($productImage, false) : (BASE_URL . 'assets/images/logo.png');
                            ?>
                            <img src="<?= htmlspecialchars($productImageUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                            <div class="product-card-overlay">
                                <a href="<?= BASE_URL ?>?action=product-detail&id=<?= $product['id'] ?>" class="product-card-icon" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin'): ?>
                                <button type="button" class="product-card-icon" title="Thêm vào giỏ" onclick="openProductModal(<?= $product['id'] ?? 0 ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($productImageUrl) ?>', <?= (int)($product['category_id'] ?? 0) ?>)">
                                    <i class="bi bi-bag-plus"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-uppercase small text-muted mb-1"><?= $product['category'] ?></p>
                        <h3 class="h6"><?= $product['name'] ?></h3>
                        <div class="product-price">
                            <?php 
                            $salePrice = $product['sale_price'] ?? null;
                            $originalPrice = $product['original_price'] ?? $product['price'] ?? 0;
                            $displayPrice = $salePrice && $salePrice > 0 && $salePrice < $originalPrice ? $salePrice : $originalPrice;
                            ?>
                            <span class="fw-semibold text-success"><?= number_format($displayPrice, 0, ',', '.') ?> đ</span>
                            <?php if ($salePrice && $salePrice > 0 && $salePrice < $originalPrice): ?>
                                <span class="text-muted text-decoration-line-through small ms-2"><?= number_format($originalPrice, 0, ',', '.') ?> đ</span>
                                <span class="badge bg-danger ms-2">-<?= round((($originalPrice - $salePrice) / $originalPrice) * 100) ?>%</span>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(36, (int)($_GET['per_page'] ?? 12)));
    $totalPages = ceil(($totalProducts ?? 0) / $perPage);
    if ($totalPages > 1):
    ?>
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>?action=products&page=<?= $currentPage - 1 ?><?= isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . (int)$_GET['category_id'] : '' ?><?= isset($_GET['price']) ? '&price=' . urlencode($_GET['price']) : '' ?>" aria-label="Trang trước">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>?action=products&page=<?= $i ?><?= isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . (int)$_GET['category_id'] : '' ?><?= isset($_GET['price']) ? '&price=' . urlencode($_GET['price']) : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>?action=products&page=<?= $currentPage + 1 ?><?= isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . (int)$_GET['category_id'] : '' ?><?= isset($_GET['price']) ? '&price=' . urlencode($_GET['price']) : '' ?>" aria-label="Trang sau">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<!-- Product Quick Modal (new) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content pm-modal shadow-lg border-0 overflow-hidden">
            <div class="row g-0 h-100">
                <div class="col-md-6 pm-left" style="height: 400px; min-height: 400px; max-height: 400px;">
                    <img id="pmProductImage" src="" alt="Product" class="pm-image" style="width: 100%; height: 100%; object-fit: cover; object-position: center; border-radius: 8px; display: block;">
                </div>
                <div class="col-md-6 pm-right position-relative">
                    <button type="button" class="btn-close pm-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    <div class="pm-header">
                        <h3 id="pmProductName" class="pm-title">Product Name</h3>
                        <p id="pmProductPrice" class="pm-price">0 đ</p>
                    </div>

                    <form id="productModalForm" class="pm-form">
                        <input type="hidden" id="pmProductId">
                        <input type="hidden" id="pmCategoryId">

                        <div class="pm-field" id="pmSizeField">
                            <label class="pm-label">Kích thước</label>
                            <div class="pm-options" id="pmSizeOptions"></div>
                        </div>

                        <div class="pm-field" id="pmColorField">
                            <label class="pm-label">Màu sắc</label>
                            <div class="pm-options" id="pmColorOptions"></div>
                        </div>

                        <div class="pm-field">
                            <label class="pm-label">Tồn kho</label>
                            <div id="pmStockInfo" class="text-muted small">--</div>
                        </div>

                        <div class="pm-field">
                            <label class="pm-label">Số lượng</label>
                            <div class="input-group input-group-compact pm-quantity">
                                <button class="btn btn-outline-secondary rounded-0" type="button" onclick="changePmQty(-1)" aria-label="Giảm số lượng">-</button>
                                <input type="number" class="form-control text-center border-secondary border-start-0 border-end-0" id="pmQuantity" value="1" min="1" max="999" aria-label="Số lượng sản phẩm" onchange="validatePmQuantity(this)">
                                <button class="btn btn-outline-secondary rounded-0" type="button" onclick="changePmQty(1)" aria-label="Tăng số lượng">+</button>
                            </div>
                        </div>

                        <div class="d-grid gap-2" id="pmActions">
                            <button type="button" class="btn btn-dark rounded-0 py-3 text-uppercase fw-bold" onclick="submitProductModal('cart')">Thêm vào giỏ hàng</button>
                            <button type="button" class="btn btn-outline-dark rounded-0 py-3 text-uppercase fw-bold" onclick="submitProductModal('checkout')">Mua ngay</button>
                        </div>
                        <div class="d-grid gap-2 d-none" id="pmSimilar">
                            <a id="pmSimilarLink" class="btn btn-outline-secondary rounded-0 py-3 text-uppercase fw-bold" href="<?= BASE_URL ?>?action=products">
                                Sản phẩm tương tự
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const pmColorMap = {
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

    function openProductModal(id, name, price, image, categoryId = 0) {
        document.getElementById('pmProductId').value = id;
        document.getElementById('pmCategoryId').value = categoryId || 0;
        document.getElementById('pmProductName').textContent = name;
        document.getElementById('pmProductPrice').textContent = new Intl.NumberFormat('vi-VN').format(price) + ' đ';
        document.getElementById('pmProductImage').src = image;
        document.getElementById('pmQuantity').value = 1;
        togglePmActions(true);
        setPmStock('--');

        loadPmAttributes(id);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
        modal.show();
    }

    function loadPmAttributes(productId) {
        fetch(`<?= BASE_URL ?>?action=product-attributes&product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    renderPmSizeOptions(data.data.sizes || []);
                    renderPmColorOptions(data.data.colors || []);

                    const firstSize = (data.data.sizes && data.data.sizes.length > 0) ? data.data.sizes[0] : null;
                    const firstColor = (data.data.colors && data.data.colors.length > 0) ? data.data.colors[0] : null;
                    if (firstSize || firstColor) {
                        updatePmImage(productId, firstSize, firstColor);
                        updatePmStock(productId, firstSize, firstColor);
                    } else {
                        setPmStock('--');
                    }
                }
            })
            .catch(err => {
                console.error('Error loading attributes:', err);
                renderPmSizeOptions([]);
                renderPmColorOptions([]);
                setPmStock('--');
            });
    }

    function renderPmSizeOptions(sizes) {
        const container = document.getElementById('pmSizeOptions');
        container.innerHTML = '';
        if (!sizes.length) {
            container.innerHTML = '<span class="text-muted small">Không có kích thước</span>';
            return;
        }
        sizes.forEach((size, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pm-size';
            btn.textContent = size;
            btn.dataset.size = size;
            if (index === 0) btn.classList.add('active');
            btn.addEventListener('click', function() {
                document.querySelectorAll('.pm-size').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                const productId = document.getElementById('pmProductId').value;
                const color = document.querySelector('.pm-color.active')?.dataset.color;
                updatePmImage(productId, size, color);
                updatePmStock(productId, size, color);
            });
            container.appendChild(btn);
        });
    }

    function renderPmColorOptions(colors) {
        const container = document.getElementById('pmColorOptions');
        container.innerHTML = '';
        if (!colors.length) {
            container.innerHTML = '<span class="text-muted small">Không có màu sắc</span>';
            return;
        }
        colors.forEach((color, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pm-color';
            if (color.toLowerCase() === 'white') btn.classList.add('white');
            btn.style.backgroundColor = pmColorMap[color] || '#ccc';
            btn.dataset.color = color;
            btn.title = color;
            if (index === 0) btn.classList.add('active');
            btn.addEventListener('click', function() {
                document.querySelectorAll('.pm-color').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                const productId = document.getElementById('pmProductId').value;
                const size = document.querySelector('.pm-size.active')?.dataset.size;
                updatePmImage(productId, size, color);
                updatePmStock(productId, size, color);
            });
            container.appendChild(btn);
        });
    }

    function updatePmImage(productId, size, color) {
        if (!productId) return;
        const params = new URLSearchParams({ product_id: productId });
        if (size) params.append('size', size);
        if (color) params.append('color', color);

        fetch(`<?= BASE_URL ?>?action=variant-images&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    document.getElementById('pmProductImage').src = data.data[0];
                }
            })
            .catch(err => console.error('Error updating image:', err));
    }

    function updatePmStock(productId, size, color) {
        if (!productId) return;
        const params = new URLSearchParams({ product_id: productId });
        if (size) params.append('size', size);
        if (color) params.append('color', color);
        setPmStock('Đang tải...');
        fetch(`<?= BASE_URL ?>?action=get-variant-stock&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stock = data.stock ?? data.data?.stock ?? 0;
                    const label = stock > 0 ? `Còn ${stock} sản phẩm` : 'Hết hàng';
                    setPmStock(label, stock > 0 ? 'success' : 'danger');
                    togglePmActions(stock > 0);
                } else {
                    setPmStock('Không lấy được tồn kho', 'muted');
                    togglePmActions(true);
                }
            })
            .catch(err => {
                console.error('Error updating stock:', err);
                setPmStock('Không lấy được tồn kho', 'muted');
                togglePmActions(true);
            });
    }

    function setPmStock(text, status = 'muted') {
        const el = document.getElementById('pmStockInfo');
        if (!el) return;
        el.textContent = text;
        el.classList.remove('text-success', 'text-danger', 'text-muted');
        if (status === 'success') el.classList.add('text-success');
        else if (status === 'danger') el.classList.add('text-danger');
        else el.classList.add('text-muted');
    }

    function togglePmActions(inStock) {
        const actions = document.getElementById('pmActions');
        const similar = document.getElementById('pmSimilar');
        const catId = parseInt(document.getElementById('pmCategoryId')?.value || '0', 10);
        const similarLink = document.getElementById('pmSimilarLink');
        if (similarLink) {
            const url = catId > 0
                ? '<?= BASE_URL ?>?action=products&category_id=' + catId
                : '<?= BASE_URL ?>?action=products';
            similarLink.href = url;
        }
        if (inStock) {
            actions?.classList.remove('d-none');
            similar?.classList.add('d-none');
        } else {
            actions?.classList.add('d-none');
            similar?.classList.remove('d-none');
        }
    }

    function changePmQty(delta) {
        const input = document.getElementById('pmQuantity');
        let val = parseInt(input.value) + delta;
        if (isNaN(val) || val < 1) val = 1;
        if (val > 999) val = 999;
        input.value = val;
    }

    function validatePmQuantity(input) {
        let val = parseInt(input.value);
        if (isNaN(val) || val < 1) {
            input.value = 1;
        } else if (val > 999) {
            input.value = 999;
        } else {
            input.value = val;
        }
    }

    function submitProductModal(action) {
        const productId = document.getElementById('pmProductId').value;
        const quantity = document.getElementById('pmQuantity').value;
        const sizeEl = document.querySelector('.pm-size.active');
        const colorEl = document.querySelector('.pm-color.active');

        if (!sizeEl || !colorEl) {
            showPmToast('Vui lòng chọn đầy đủ thuộc tính sản phẩm', 'warning');
            return;
        }

        const size = sizeEl.dataset.size;
        const color = colorEl.dataset.color;

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
        .then(response => response.json())
        .then(res => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            if (res.require_login) {
                if (modal) modal.hide();
                showPmToast('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng', 'warning');
                setTimeout(() => {
                    window.location.href = '<?= BASE_URL ?>?action=show-login';
                }, 1000);
                return;
            }

            if (res.success) {
                if (modal) modal.hide();
                updateCartCount();
                if (action === 'cart') {
                    showPmToast('Đã thêm sản phẩm vào giỏ hàng!', 'success');
                } else {
                    // Mua ngay - chỉ chọn sản phẩm này để thanh toán
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
                }
            } else {
                showPmToast(res.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showPmToast('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
        });
    }

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

    function showPmToast(message, type = 'success') {
        const colors = {
            success: 'bg-success text-white',
            error: 'bg-danger text-white',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        };
        let container = document.getElementById('pmToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'pmToastContainer';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1100';
            document.body.appendChild(container);
        }
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center ${colors[type] || colors.success} border-0 mb-2`;
        toastEl.role = 'alert';
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
</script>

