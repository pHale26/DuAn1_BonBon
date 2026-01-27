<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Home' ?></title>

    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">

    <!-- Latest compiled JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-white">

    <header class="border-bottom">
        <div class="container header-container d-flex justify-content-between align-items-center">
            <a class="navbar-brand d-flex align-items-center text-decoration-none" href="<?= BASE_URL ?>">
                <?php 
                $logoPath = PATH_ROOT . 'assets/images/logo.png';
                if (file_exists($logoPath)): 
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoSrc = 'data:image/png;base64,' . $logoData;
                ?>
                    <img class="logo-image" src="<?= $logoSrc ?>" alt="BonBonwear">
                <?php else: ?>
                    <span class="logo-text">BONBONWEAR</span>
                <?php endif; ?>
            </a>

            <nav class="nav text-uppercase small">
                <a class="text-decoration-none text-dark" href="<?= BASE_URL ?>?action=products">Sản phẩm</a>
                <a class="text-decoration-none text-dark" href="<?= BASE_URL ?>?action=collection">Bộ sưu tập</a>
                <a class="text-decoration-none text-dark" href="<?= BASE_URL ?>?action=posts">Tin tức</a>
                <a class="text-decoration-none text-dark" href="<?= BASE_URL ?>?action=contact">Liên hệ</a>
            </nav>

            <div class="d-flex align-items-center header-icons">
                <div class="header-search-wrapper" id="headerSearchWrapper">
                    <div class="header-search-input-container">
                        <label for="headerSearchInput" class="visually-hidden">Tìm kiếm sản phẩm</label>
                        <input 
                            type="text" 
                            class="header-search-input" 
                            id="headerSearchInput" 
                            name="search"
                            placeholder="Tìm kiếm..."
                            autocomplete="off"
                            aria-label="Tìm kiếm sản phẩm"
                        >
                        <button type="button" class="header-search-btn" id="headerSearchBtn" title="Tìm kiếm">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <a class="text-dark search-icon-toggle" href="#" id="searchIconToggle" title="Tìm kiếm"><i class="bi bi-search"></i></a>
                
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="notification-wrapper" id="notificationWrapper">
                        <button type="button" class="notif-bell" id="notificationBell" aria-label="Thông báo">
                            <i class="bi bi-bell"></i>
                            <span class="notif-badge d-none" id="notificationBadge">0</span>
                        </button>
                        <div class="notification-dropdown" id="notificationDropdown" aria-label="Danh sách thông báo">
                            <div class="notification-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Thông báo</strong>
                                    <span class="notif-unread text-muted small" id="notificationUnread"></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="clearAllNotifBtn">Xóa tất cả</button>
                                </div>
                            </div>
                            <div class="notification-actions-bar">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="notifSelectAll">
                                    <label class="form-check-label small" for="notifSelectAll">Chọn tất cả</label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="deleteSelectedNotifBtn">Xóa đã chọn</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="deleteReadNotifBtn">Xóa đã đọc</button>
                                </div>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="text-center text-muted small py-3">Đang tải...</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user'])): ?>
                    <div class="dropdown">
                        <a class="text-dark dropdown-toggle text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Tài khoản">
                            <i class="bi bi-person-circle"></i>
                            <span class="ms-1 small"><?= $_SESSION['user']['fullname'] ?? 'User' ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>?action=profile">Thông tin cá nhân</a></li>
                            <?php if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>?action=order-history">Đơn hàng của tôi</a></li>
                            <?php endif; ?>
                            <?php if (($_SESSION['user']['role'] ?? null) === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>?action=admin-dashboard">Quản lý</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="text-dark" href="<?= BASE_URL ?>?action=show-login" title="Đăng nhập"><i class="bi bi-person-circle"></i></a>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin'): ?>
                <?php
                $cartCount = 0;
                if (isset($_SESSION['user']) && isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        $cartCount += $item['quantity'];
                    }
                }
                ?>
                <a class="text-dark cart-icon-wrapper" href="<?= BASE_URL ?>?action=cart-list" title="Giỏ hàng">
                    <i class="bi bi-bag"></i>
                    <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php $flash = get_flash(); ?>
    <!-- Hiển thị flash message 1 lần -->
    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                <?php
                $message = htmlspecialchars($flash['message']);
                // Nếu có order_id trong flash data, thay "tại đây" bằng link
                if (!empty($flash['data']['order_id'])) {
                    $orderId = (int)$flash['data']['order_id'];
                    $orderDetailUrl = BASE_URL . '?action=order-detail&id=' . $orderId;
                    $message = str_replace(
                        'tại đây',
                        '<a href="' . htmlspecialchars($orderDetailUrl) . '" class="alert-link fw-bold">tại đây</a>',
                        $message
                    );
                }
                echo $message;
                ?>
            </div>
        </div>
    <?php endif; ?>

    <main>
        <?php
        if (isset($view)) {
            require_once PATH_VIEW . $view . '.php';
        }
        ?>
    </main>

    <?php
        // Footer logo (base64) for all pages
        $footerLogoSrc = '';
        $footerLogoPath = PATH_ROOT . 'assets/images/logo.png';
        if (file_exists($footerLogoPath)) {
            $footerLogoData = base64_encode(file_get_contents($footerLogoPath));
            $footerLogoSrc = 'data:image/png;base64,' . $footerLogoData;
        }
    ?>
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <?php if ($footerLogoSrc): ?>
                        <img src="<?= $footerLogoSrc ?>" alt="BonBonWear Logo" class="footer-logo-img">
                    <?php endif; ?>
                    <p>Định hình phong cách thời trang đương đại. Sự kết hợp hoàn hảo giữa tính ứng dụng và vẻ đẹp nghệ thuật.</p>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-tiktok"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Links Column 1 -->
                <div>
                    <h4 class="footer-heading">MUA SẮM</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>?action=products">Sản Phẩm Mới</a></li>
                        <li><a href="<?= BASE_URL ?>?action=products">Bán Chạy Nhất</a></li>
                        <li><a href="<?= BASE_URL ?>?action=collection">Bộ Sưu Tập</a></li>
                        <li><a href="#">Khuyến Mãi</a></li>
                    </ul>
                </div>

                <!-- Links Column 2 -->
                <div>
                    <h4 class="footer-heading">HỖ TRỢ</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>?action=order-history">Trạng Thái Đơn Hàng</a></li>
                        <li><a href="#">Chính Sách Đổi Trả</a></li>
                        <li><a href="#">Hướng Dẫn Chọn Size</a></li>
                        <li><a href="<?= BASE_URL ?>?action=contact">Liên Hệ</a></li>
                    </ul>
                </div>

                <!-- Newsletter Column -->
                <div>
                    <h4 class="footer-heading">BẢN TIN</h4>
                    <p class="footer-note-muted">Đăng ký để nhận thông tin về bộ sưu tập mới và ưu đãi độc quyền.</p>
                    <form class="newsletter-form">
                        <label for="newsletterEmail" class="visually-hidden">Email đăng ký nhận tin</label>
                        <input type="email" class="newsletter-input" id="newsletterEmail" name="email" placeholder="Email của bạn" aria-label="Email đăng ký nhận tin">
                        <button type="submit" class="newsletter-btn" aria-label="Gửi email đăng ký">GỬI</button>
                    </form>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© <?= date('Y') ?> BonBonWear. All rights reserved.</p>
                <div class="payment-methods">
                    <span class="me-3">Privacy Policy</span>
                    <span>Terms of Service</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const headerSearchWrapper = document.getElementById('headerSearchWrapper');
        const headerSearchInput = document.getElementById('headerSearchInput');
        const headerSearchBtn = document.getElementById('headerSearchBtn');
        const searchIconToggle = document.getElementById('searchIconToggle');

        // Mở thanh search (trượt ra) với hiệu ứng mượt mà
        function openHeaderSearch() {
            // Xóa class slide-in nếu có
            headerSearchWrapper.classList.remove('slide-in');
            
            // Đảm bảo wrapper có visibility để có thể animate
            headerSearchWrapper.style.visibility = 'visible';
            
            // Force reflow để đảm bảo animation chạy
            void headerSearchWrapper.offsetWidth;
            
            // Thêm class show để trigger animation
            headerSearchWrapper.classList.add('show');
            searchIconToggle.classList.add('hide');
            
            // Focus vào input sau khi animation hoàn thành
            setTimeout(() => {
                headerSearchInput.focus();
            }, 300);
        }

        // Đóng thanh search với hiệu ứng slide vào
        function closeHeaderSearch() {
            headerSearchWrapper.classList.remove('show');
            headerSearchWrapper.classList.add('slide-in');
            searchIconToggle.classList.remove('hide');
            
            setTimeout(() => {
                headerSearchWrapper.classList.remove('slide-in');
                headerSearchWrapper.style.visibility = '';
                headerSearchInput.value = '';
            }, 500);
        }

        // Toggle search khi click icon
        searchIconToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (headerSearchWrapper.classList.contains('show')) {
                closeHeaderSearch();
            } else {
                openHeaderSearch();
            }
        });

        // Tìm kiếm khi nhấn Enter hoặc click nút search
        function performHeaderSearch() {
            const keyword = headerSearchInput.value.trim();
            if (keyword.length < 1) {
                return;
            }
            
            // Hiệu ứng slide vào trước khi chuyển trang
            headerSearchWrapper.classList.add('slide-in');
            searchIconToggle.classList.remove('hide');
            
            // Gọi API để tìm kiếm
            fetch(`<?= BASE_URL ?>?action=search-smart&q=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    setTimeout(() => {
                        headerSearchWrapper.classList.remove('show', 'slide-in');
                        headerSearchWrapper.style.visibility = '';
                        headerSearchInput.value = '';
                        
                        if (data.success) {
                            if (data.type === 'product' && data.product_id) {
                                // Nếu tìm thấy 1 sản phẩm → chuyển đến chi tiết
                                window.location.href = `<?= BASE_URL ?>?action=product-detail&id=${data.product_id}`;
                            } else if (data.type === 'category' && data.category_id) {
                                // Nếu tìm thấy danh mục → chuyển đến danh mục
                                window.location.href = `<?= BASE_URL ?>?action=products&category_id=${data.category_id}`;
                            } else {
                                // Nếu nhiều kết quả → chuyển đến danh sách
                                window.location.href = `<?= BASE_URL ?>?action=products&q=${encodeURIComponent(keyword)}`;
                            }
                        } else {
                            // Không tìm thấy → chuyển đến danh sách với từ khóa
                            window.location.href = `<?= BASE_URL ?>?action=products&q=${encodeURIComponent(keyword)}`;
                        }
                    }, 400);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    setTimeout(() => {
                        headerSearchWrapper.classList.remove('show', 'slide-in');
                        headerSearchWrapper.style.visibility = '';
                        headerSearchInput.value = '';
                        window.location.href = `<?= BASE_URL ?>?action=products&q=${encodeURIComponent(keyword)}`;
                    }, 400);
                });
        }

        // Event listeners
        headerSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            performHeaderSearch();
        });
        
        headerSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performHeaderSearch();
            }
        });

        // Đóng khi click bên ngoài
        document.addEventListener('click', function(e) {
            if (headerSearchWrapper.classList.contains('show') && 
                !headerSearchWrapper.contains(e.target) && 
                !searchIconToggle.contains(e.target)) {
                closeHeaderSearch();
            }
        });

        // Đóng khi nhấn ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && headerSearchWrapper.classList.contains('show')) {
                closeHeaderSearch();
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bell = document.getElementById('notificationBell');
            const dropdown = document.getElementById('notificationDropdown');
            const listEl = document.getElementById('notificationList');
            if (!bell || !dropdown || !listEl) return;

            const badgeEl = document.getElementById('notificationBadge');
            const unreadEl = document.getElementById('notificationUnread');
            const selectAllEl = document.getElementById('notifSelectAll');
            const deleteSelectedBtn = document.getElementById('deleteSelectedNotifBtn');
            const deleteReadBtn = document.getElementById('deleteReadNotifBtn');
            const clearAllBtn = document.getElementById('clearAllNotifBtn');
            const markAllBtn = document.getElementById('markAllNotifBtn');
            const baseUrl = '<?= BASE_URL ?>';
            let notifications = [];
            let dropdownOpen = false;

            const icons = {
                order_status: 'bi bi-box-seam',
                product: 'bi bi-stars',
                coupon: 'bi bi-ticket-perforated',
                review_reply: 'bi bi-chat-dots',
                default: 'bi bi-bell'
            };

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return value.toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function formatTime(timeString) {
                const date = new Date(timeString);
                if (isNaN(date.getTime())) return '';
                const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
                if (seconds < 60) return 'Vừa xong';
                const minutes = Math.floor(seconds / 60);
                if (minutes < 60) return `${minutes} phút trước`;
                const hours = Math.floor(minutes / 60);
                if (hours < 24) return `${hours} giờ trước`;
                const days = Math.floor(hours / 24);
                if (days < 7) return `${days} ngày trước`;
                return date.toLocaleDateString('vi-VN');
            }

            function updateBadge(unread) {
                if (!badgeEl) return;
                const safeUnread = Number(unread) || 0;
                badgeEl.textContent = safeUnread > 9 ? '9+' : safeUnread;
                badgeEl.classList.toggle('d-none', safeUnread === 0);
                if (unreadEl) {
                    unreadEl.textContent = safeUnread > 0 ? `${safeUnread} chưa đọc` : '';
                }
            }

            function renderCouponDetail(meta) {
                const discountValue = meta.discount_value ?? '';
                const discountType = meta.discount_type === 'percent' ? '%' : 'đ';
                const minOrder = meta.min_order_amount ? Number(meta.min_order_amount).toLocaleString('vi-VN') + ' đ' : 'Không yêu cầu';
                const endDate = meta.end_date ? new Date(meta.end_date).toLocaleDateString('vi-VN') : 'Không giới hạn';
                const maxDiscount = meta.max_discount_amount ? Number(meta.max_discount_amount).toLocaleString('vi-VN') + ' đ' : 'Không giới hạn';

                return `
                    <div class="coupon-meta-row">
                        <span class="badge bg-dark me-2">${escapeHtml(meta.code || '')}</span>
                        <span>${escapeHtml(meta.name || 'Ưu đãi mới')}</span>
                    </div>
                    <div class="small text-muted mt-1">Giảm ${escapeHtml(discountValue)}${discountType} • ĐH tối thiểu ${minOrder}</div>
                    <div class="small text-muted">Giảm tối đa ${maxDiscount}</div>
                    <div class="small text-muted">Hạn dùng: ${endDate}</div>
                `;
            }

            function renderList() {
                if (!notifications.length) {
                    listEl.innerHTML = '<div class="text-center text-muted small py-3">Chưa có thông báo</div>';
                    updateBadge(0);
                    return;
                }

                const html = notifications.map((item) => {
                    const icon = icons[item.type] || icons.default;
                    const readClass = item.is_read ? 'read' : 'unread';

                    return `
                        <div class="notification-item ${readClass}" data-id="${item.id}" data-type="${item.type}">
                            <div class="notification-main">
                                <div class="notif-icon"><i class="${icon}"></i></div>
                                <div class="notif-body">
                                    <div class="notif-title">${escapeHtml(item.title || 'Thông báo')}</div>
                                    ${item.content ? `<div class="notif-content">${escapeHtml(item.content)}</div>` : ''}
                                    <div class="notif-meta">${formatTime(item.created_at)}</div>
                                </div>
                                <div class="notif-actions">
                                    <input type="checkbox" class="form-check-input notif-check" value="${item.id}">
                                    <button type="button" class="btn btn-link p-0 text-danger notif-delete-one" aria-label="Xóa thông báo"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                            <div class="notification-extra d-none" data-id="${item.id}"></div>
                        </div>
                    `;
                }).join('');

                listEl.innerHTML = html;
                const unreadCount = notifications.filter(n => !n.is_read).length;
                updateBadge(unreadCount);
            }

            async function fetchNotifications() {
                try {
                    const res = await fetch(baseUrl + '?action=notifications');
                    const data = await res.json();
                    if (data.success) {
                        notifications = data.items || [];
                        renderList();
                        updateBadge(data.unread ?? notifications.filter(n => !n.is_read).length);
                    }
                } catch (error) {
                    console.error('Không thể tải thông báo', error);
                }
            }

            async function markRead(ids = [], markAll = false) {
                if (!markAll && (!ids || ids.length === 0)) return;
                try {
                    const res = await fetch(baseUrl + '?action=notification-mark-read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(markAll ? { all: true } : { ids })
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (markAll) {
                            notifications = notifications.map(n => ({ ...n, is_read: 1 }));
                            renderList();
                        }
                        updateBadge(data.unread ?? notifications.filter(n => !n.is_read).length);
                    }
                } catch (error) {
                    console.error('Không thể đánh dấu đã đọc', error);
                }
            }

            async function deleteNotifications(options) {
                try {
                    const res = await fetch(baseUrl + '?action=notification-delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(options)
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (options.mode === 'all') {
                            notifications = [];
                        } else if (options.mode === 'read') {
                            notifications = notifications.filter(n => !n.is_read);
                        } else if (options.ids && options.ids.length) {
                            const removeIds = new Set(options.ids.map(Number));
                            notifications = notifications.filter(n => !removeIds.has(Number(n.id)));
                        }
                        renderList();
                    }
                } catch (error) {
                    console.error('Không thể xóa thông báo', error);
                }
            }

            function getSelectedIds() {
                return Array.from(listEl.querySelectorAll('.notif-check:checked')).map((input) => parseInt(input.value, 10)).filter(Boolean);
            }

            function setDropdown(open) {
                dropdownOpen = open;
                dropdown.classList.toggle('show', open);
            }

            bell.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setDropdown(!dropdownOpen);
                if (dropdownOpen) {
                    fetchNotifications();
                }
            });

            document.addEventListener('click', function (e) {
                if (!dropdownOpen) return;
                if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
                    setDropdown(false);
                }
            });

            listEl.addEventListener('click', function (e) {
                const itemEl = e.target.closest('.notification-item');
                if (!itemEl) return;
                const notifId = parseInt(itemEl.dataset.id, 10);
                const notif = notifications.find(n => Number(n.id) === notifId);
                if (!notif) return;

                if (e.target.classList.contains('notif-check')) {
                    e.stopPropagation();
                    return;
                }

                if (e.target.classList.contains('notif-delete-one') || e.target.closest('.notif-delete-one')) {
                    e.preventDefault();
                    deleteNotifications({ ids: [notifId] });
                    return;
                }

                // Đánh dấu đã đọc ngay tại UI, không render lại để giữ trạng thái mở rộng
                notifications = notifications.map(n => Number(n.id) === notifId ? { ...n, is_read: 1 } : n);
                itemEl.classList.remove('unread');
                itemEl.classList.add('read');
                updateBadge(notifications.filter(n => !n.is_read).length);
                markRead([notifId]);

                if (notif.type === 'coupon') {
                    const extra = itemEl.querySelector('.notification-extra');
                    if (extra) {
                        if (!extra.innerHTML.trim()) {
                            extra.innerHTML = renderCouponDetail(notif.meta || {});
                        }
                        extra.classList.toggle('d-none');
                    }
                    return;
                }

                if (notif.action_url) {
                    window.location.href = notif.action_url;
                }
            });

            if (selectAllEl) {
                selectAllEl.addEventListener('change', function () {
                    const checked = selectAllEl.checked;
                    listEl.querySelectorAll('.notif-check').forEach((input) => {
                        input.checked = checked;
                    });
                });
            }

            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function () {
                    const ids = getSelectedIds();
                    if (ids.length) {
                        deleteNotifications({ ids });
                    }
                });
            }

            if (deleteReadBtn) {
                deleteReadBtn.addEventListener('click', function () {
                    deleteNotifications({ mode: 'read' });
                });
            }

            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function () {
                    deleteNotifications({ mode: 'all' });
                });
            }

            if (markAllBtn) {
                markAllBtn.addEventListener('click', function () {
                    markRead([], true);
                });
            }

            fetchNotifications();
            setInterval(fetchNotifications, 60000);
        });
    </script>

</body>

</html>