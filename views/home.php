
<?php
// Helper to get base64 image
function getBase64Image($path) {
    if (file_exists($path)) {
        $data = file_get_contents($path);
        $type = pathinfo($path, PATHINFO_EXTENSION);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return ''; // Return empty or placeholder
}

$banner1 = getBase64Image(PATH_ROOT . 'assets/images/banner-hero.jpg');
$banner2Path = PATH_ROOT . 'assets/images/banner2.jpg';
// Nếu chưa có banner2, dùng tạm banner1 hoặc placeholder
$banner2 = file_exists($banner2Path) ? getBase64Image($banner2Path) : $banner1;
// Sử dụng đường dẫn tương đối để tránh lỗi BASE_URL
$videoUrl = './assets/images/vdbanner.mp4';
$footerLogo = getBase64Image(PATH_ROOT . 'assets/images/logo.png');
?>

<section class="hero" id="heroSlideshow">
    <!-- Slide 1: Video -->
    <div class="hero-slide active" data-type="video" data-duration="video">
        <video class="slide-bg video-bg" id="heroVideo" muted autoplay playsinline preload="auto">
            <source src="<?= $videoUrl ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <p class="hero-subtitle">Bộ Sưu Tập Mới 2025</p>
            <h1 class="hero-title">PHONG CÁCH<br>ĐƯƠNG ĐẠI</h1>
            <p class="hero-description">Khám phá sự kết hợp hoàn hảo giữa thời trang cao cấp và sự thoải mái tuyệt đối. Video Campaign chính thức.</p>
            <div class="hero-cta">
                <a href="<?= BASE_URL ?>?action=products" class="btn-hero">Xem Ngay</a>
            </div>
        </div>

      
        <div class="hero-controls-gucci">
            <div class="control-item play-pause-wrapper">
                <svg class="progress-ring" width="48" height="48">
                    <circle class="progress-ring__bg" stroke-width="2" fill="transparent" r="22" cx="24" cy="24"/>
                    <circle class="progress-ring__circle" id="progressRingCircle" stroke-width="2" fill="transparent" r="22" cx="24" cy="24"/>
                </svg>
                <button class="control-btn-circle" id="playPauseBtn">
                    <i class="bi bi-pause-fill"></i>
                </button>
            </div>
            <button class="control-btn-circle nav-btn" id="prevSlideBtn">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="control-btn-circle nav-btn" id="nextSlideBtn">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Slide 2: Image 1 -->
    <div class="hero-slide" data-type="image" data-duration="2000">
        <img src="<?= $banner1 ?>" class="slide-bg" alt="Banner 1">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <p class="hero-subtitle">Xu Hướng Mùa Hè</p>
            <h1 class="hero-title">THANH LỊCH<br>& TỰ DO</h1>
            <p class="hero-description">Thiết kế tối giản, chất liệu cao cấp mang lại trải nghiệm khác biệt.</p>
            <div class="hero-cta">
                <a href="<?= BASE_URL ?>?action=products" class="btn-hero">Mua Sắm</a>
                <a href="<?= BASE_URL ?>?action=products" class="btn-hero-secondary">Chi Tiết</a>
            </div>
        </div>
    </div>

    <!-- Slide 3: Image 2 (Placeholder for banner2.png) -->
    <div class="hero-slide" data-type="image" data-duration="2000">
        <img src="<?= $banner2 ?>" class="slide-bg" alt="Banner 2">
        <div class="hero-overlay"></div>
        <div class="hero-content text-dark">
            <p class="hero-subtitle">Limited Edition</p>
            <h1 class="hero-title">ĐẲNG CẤP<br>RIÊNG BIỆT</h1>
            <p class="hero-description">Bộ sưu tập giới hạn dành cho những người dẫn đầu xu hướng.</p>
            <div class="hero-cta">
                <a href="<?= BASE_URL ?>?action=collection" class="btn-hero">Khám Phá</a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="hero-nav">
        <div class="nav-dot active" onclick="goToSlide(0)"></div>
        <div class="nav-dot" onclick="goToSlide(1)"></div>
        <div class="nav-dot" onclick="goToSlide(2)"></div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.nav-dot');
        const video = document.getElementById('heroVideo');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const prevSlideBtn = document.getElementById('prevSlideBtn');
        const nextSlideBtn = document.getElementById('nextSlideBtn');
        const progressCircle = document.getElementById('progressRingCircle');
        
        // Setup Progress Ring
        const radius = progressCircle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        
        progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
        progressCircle.style.strokeDashoffset = circumference;

        function setProgress(percent) {
            const offset = circumference - (percent / 100) * circumference;
            progressCircle.style.strokeDashoffset = offset;
        }
        
        let currentSlide = 0;
        let slideInterval;
        let isVideoPlaying = false;

        // --- Video Controls ---
        function togglePlay() {
            if (video.paused) {
                video.play();
                playPauseBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                isVideoPlaying = true;
            } else {
                video.pause();
                playPauseBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                isVideoPlaying = false;
            }
        }

        playPauseBtn.addEventListener('click', togglePlay);
        
        // Navigation Buttons
        prevSlideBtn.addEventListener('click', () => {
            // If on video slide, pause it
            if (currentSlide === 0) {
                video.pause();
                isVideoPlaying = false;
                playPauseBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
            }
            showSlide(currentSlide - 1);
        });

        nextSlideBtn.addEventListener('click', () => {
            // If on video slide, pause it
            if (currentSlide === 0) {
                video.pause();
                isVideoPlaying = false;
                playPauseBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
            }
            nextSlide();
        });

        video.addEventListener('timeupdate', () => {
            const percent = (video.currentTime / video.duration) * 100;
            setProgress(percent);
        });

        // --- Slideshow Logic ---
        function showSlide(index) {
            // Reset current slide
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');
            
            // Handle Video Slide Exit
            if (currentSlide === 0) {
                video.pause();
                video.currentTime = 0;
            }

            // Update index
            currentSlide = index;
            if (currentSlide >= slides.length) currentSlide = 0;
            if (currentSlide < 0) currentSlide = slides.length - 1;

            // Activate new slide
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');

            // Handle Video Slide Enter
            if (currentSlide === 0) {
                video.play();
                playPauseBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                isVideoPlaying = true;
                clearInterval(slideInterval); // Stop auto-slide timer, let video finish
            } else {
                // Start auto-slide timer for images
                startSlideTimer();
            }
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function startSlideTimer() {
            clearInterval(slideInterval);
            const duration = slides[currentSlide].getAttribute('data-duration') || 5000;
            slideInterval = setTimeout(nextSlide, duration);
        }

        // Global function for dots
        window.goToSlide = function(index) {
            showSlide(index);
        };

        // Video End Event -> Next Slide
        video.addEventListener('ended', () => {
            nextSlide();
        });

        // Initial Setup
        // Try to play video immediately
        const playPromise = video.play();
        if (playPromise !== undefined) {
            playPromise.then(_ => {
                isVideoPlaying = true;
                playPauseBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
            }).catch(error => {
                console.error("Video play error:", error);
                // Auto-play was prevented
                video.muted = true;
                video.play().then(() => {
                     isVideoPlaying = true;
                     playPauseBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                }).catch(e => console.error("Video play error (muted):", e));
            });
        }
        
        video.addEventListener('error', function(e) {
            console.error("Video loading error:", video.error);
        });
    });
    
    // Cập nhật số lượng giỏ hàng
    function updateCartCount() {
        // Gọi API lấy số lượng sản phẩm trong giỏ
        fetch('<?= BASE_URL ?>?action=cart-count')
            .then(response => response.json())
            .then(data => {
                // Cập nhật badge số lượng ở icon giỏ hàng
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.textContent = data.count;
                }
            })
            .catch(err => console.error('Lỗi cập nhật giỏ hàng:', err));
    }
    
// Cập nhật số lượng khi load trang
// Được gọi sau khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    // Existing initialization code ... (keep existing code above)
    // ...
    // Cuối cùng, cập nhật số lượng giỏ hàng
    updateCartCount();
});

// Remove previous standalone call to updateCartCount();

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
                        togglePmActions(true);
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
        if (!container) return;
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
        if (!container) return;
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
        setPmStock('Đang tải...', 'muted');
        fetch(`<?= BASE_URL ?>?action=get-variant-stock&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stock = parseInt(data.stock ?? data.data?.stock ?? 0, 10);
                    const label = stock > 0 ? `Còn ${stock} sản phẩm` : 'Hết hàng';
                    setPmStock(label, stock > 0 ? 'success' : 'danger');
                    togglePmActions(stock > 0);
                    const qty = document.getElementById('pmQuantity');
                    if (stock > 0 && qty) {
                        qty.max = stock;
                        if (parseInt(qty.value, 10) > stock) qty.value = stock;
                    }
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

        const data = {
            product_id: productId,
            quantity: quantity,
            size: sizeEl.dataset.size,
            color: colorEl.dataset.color
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
                    const cartKey = res.cart_key || (productId + '_' + (sizeEl.dataset.size || 'null') + '_' + (colorEl.dataset.color || 'null'));
                    
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

    function quickView(productId) {
        window.location.href = '<?= BASE_URL ?>?action=product-detail&id=' + productId;
    }
</script>

<section class="container products-section">
    <h2 class="section-title">Sản phẩm mới</h2>

    <div class="row g-4">
        <?php if (isset($products) && is_array($products) && !empty($products)) : ?>
            <?php foreach ($products as $product) : ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <article class="product-card">
                    <?php if (isset($product['id']) && isset($newProductIds) && in_array((int)$product['id'], $newProductIds, true)) : ?>
                        <span class="product-badge">New</span>
                    <?php endif; ?>
                        <?php
                            $productImage = getProductImageUrl((string)($product['image'] ?? $product['image_url'] ?? ''), false);
                        ?>
                        <div class="product-card-image-wrapper">
                            <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars($product['name'] ?? '') ?>" onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                            <div class="product-card-overlay">
                                <a class="product-card-icon" href="<?= BASE_URL ?>?action=product-detail&id=<?= $product['id'] ?? 0 ?>" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin'): ?>
                                <div class="product-card-icon" onclick="openProductModal(<?= $product['id'] ?? 0 ?>, '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($productImage, ENT_QUOTES) ?>', <?= $product['category_id'] ?? 0 ?>)" title="Thêm vào giỏ hàng">
                                    <i class="bi bi-bag-plus"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <p class="text-uppercase small text-muted mb-1"><?= htmlspecialchars($product['category'] ?? 'ÁO POLO') ?></p>
                    <h3 class="h6"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="fw-semibold"><?= number_format($product['price'], 0, ',', '.') ?> đ</p>
                </article>
            </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12">
                <p class="text-center text-muted">Chưa có sản phẩm nào.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-4">
    <a href="<?= BASE_URL ?>?action=products" class="btn btn-outline-dark">Xem thêm</a>
    </div>
</section>

<section class="container">
    <h2 class="section-title">Tin tức</h2>

    <div class="row g-4">
        <?php foreach ($news as $item) : ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <article class="news-card">
                    <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $item['post_id'] ?>">
                        <?php 
                            $imgSrc = !empty($item['thumbnail']) ? getProductImageUrl($item['thumbnail']) : BASE_URL . 'assets/images/default.jpg';
                        ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    </a>
                    <div class="news-card-body">
                        <p class="news-card-date">
                            <i class="bi bi-calendar3 me-2"></i><?= date('d/m/Y', strtotime($item['created_at'])) ?>
                        </p>
                        <h3 class="news-card-title">
                            <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $item['post_id'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </h3>
                        <p class="news-card-excerpt"><?= htmlspecialchars($item['excerpt']) ?></p>
                        <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $item['post_id'] ?>" class="news-card-link">
                            Đọc thêm <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>?action=posts" class="btn btn-outline-dark">Xem thêm</a>
    </div>
</section>

<!-- Product Modal (same as product page) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pm-modal shadow-lg border-0 overflow-hidden">
            <div class="row g-0 h-100">
                <div class="col-md-5 pm-left" style="height: 400px; min-height: 400px; max-height: 400px;">
                    <img id="pmProductImage" src="" alt="Product" class="pm-image" style="width: 100%; height: 100%; object-fit: cover; object-position: center; border-radius: 8px; display: block;">
                </div>
                <div class="col-md-7 pm-right position-relative">
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
