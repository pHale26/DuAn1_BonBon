<div class="container cart-page">
    <div class="cart-header">
        <h1 class="cart-title">Giỏ Hàng Của Bạn</h1>
        <p class="text-muted"><?= count($cart) ?> sản phẩm</p>
    </div>

    <?php if (empty($cart)): ?>
        <div class="empty-cart">
            <i class="bi bi-cart-x"></i>
            <h3>Giỏ hàng đang trống</h3>
            <p class="text-muted mb-4">Bạn chưa thêm sản phẩm nào vào giỏ hàng.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-dark text-uppercase px-4 py-2">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th class="cart-checkbox-col">
                                    <input type="checkbox" id="selectAll" name="select_all" onchange="toggleSelectAll(this)" aria-label="Chọn tất cả">
                                    <label for="selectAll" class="visually-hidden">Chọn tất cả</label>
                                </th>
                                <th class="cart-product-col">Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Tổng</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $cartKey => $item): 
                                $itemTotal = $item['price'] * $item['quantity'];
                            ?>
                                <tr data-cart-key="<?= htmlspecialchars($cartKey) ?>" data-product-id="<?= (int)$item['id'] ?>" data-item-price="<?= $item['price'] ?>" data-item-quantity="<?= $item['quantity'] ?>" data-item-total="<?= $itemTotal ?>">
                                    <td>
                                        <input type="checkbox" class="cart-item-checkbox" id="cartItem_<?= htmlspecialchars($cartKey) ?>" name="cart_items[]" value="<?= htmlspecialchars($cartKey) ?>" checked onchange="handleCheckboxChange(this)">
                                        <label for="cartItem_<?= htmlspecialchars($cartKey) ?>" class="visually-hidden">Chọn sản phẩm <?= htmlspecialchars($item['name']) ?></label>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Xử lý ảnh sử dụng helper function
                                            $imgSrc = getProductImageUrl($item['image'] ?? '', true);
                                            
                                            // Nếu ảnh vẫn rỗng và có product_id, thử lấy từ database
                                            if (empty($imgSrc) && !empty($item['id'])) {
                                                require_once PATH_MODEL . 'ProductModel.php';
                                                $productModel = new ProductModel();
                                                $product = $productModel->getProductById((int)$item['id']);
                                                if ($product) {
                                                    $productImage = $product['image'] ?? '';
                                                    if (!$productImage) {
                                                        $images = $productModel->getProductImages((int)$item['id']);
                                                        if (!empty($images)) {
                                                            $productImage = $images[0]['image_url'] ?? '';
                                                        }
                                                    }
                                                    
                                                    // Nếu có variant, ưu tiên ảnh variant
                                                    if (!empty($item['variant_id'])) {
                                                        $variantImages = $productModel->getVariantImages((int)$item['variant_id']);
                                                        if (!empty($variantImages)) {
                                                            $productImage = $variantImages[0]['image_url'] ?? $productImage;
                                                        }
                                                    } elseif (!empty($item['size']) || !empty($item['color'])) {
                                                        $variant = $productModel->getVariantByValueNames((int)$item['id'], $item['size'] ?? null, $item['color'] ?? null);
                                                        if ($variant && !empty($variant['image_url'])) {
                                                            $productImage = $variant['image_url'];
                                                        }
                                                    }
                                                    
                                                    if ($productImage) {
                                                        $imgSrc = getProductImageUrl($productImage, true);
                                                    }
                                                }
                                            }
                                            
                                            // Nếu vẫn không có ảnh, dùng placeholder
                                            if (empty($imgSrc)) {
                                                $imgSrc = BASE_URL . 'assets/images/logo.png';
                                            }
                                            ?>
                                            <img src="<?= $imgSrc ?>" alt="<?= $item['name'] ?>" class="cart-product-img" onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                                            <div>
                                                <a href="#" class="cart-product-name"><?= $item['name'] ?></a>
                                                <small class="text-muted d-block">Size: <?= $item['size'] ?? 'M' ?> | Màu: <?= $item['color'] ?? 'Black' ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= number_format($item['price'], 0, ',', '.') ?><span style="vertical-align: baseline; margin-left: 2px;">đ</span></td>
                                    <td style="width: 120px;">
                                        <div class="d-flex align-items-center justify-content-center mx-auto" style="border: 1px solid #e0e0e0; width: fit-content;">
                                            <button class="btn btn-link text-dark text-decoration-none p-0 rounded-0 d-flex align-items-center justify-content-center" 
                                                    type="button" 
                                                    style="width: 30px; height: 32px; border-right: 1px solid #e0e0e0;" 
                                                    onclick="updateQty('<?= htmlspecialchars($cartKey) ?>', -1)">-</button>
                                            <input type="number" class="form-control text-center qty-input border-0 rounded-0 shadow-none p-0" 
                                                   value="<?= $item['quantity'] ?>" min="1" 
                                                   style="width: 40px; height: 32px; font-weight: 500;"
                                                   onchange="updateQtyManual('<?= htmlspecialchars($cartKey) ?>', this)" 
                                                   data-original-qty="<?= $item['quantity'] ?>">
                                            <button class="btn btn-link text-dark text-decoration-none p-0 rounded-0 d-flex align-items-center justify-content-center" 
                                                    type="button" 
                                                    style="width: 30px; height: 32px; border-left: 1px solid #e0e0e0;" 
                                                    onclick="updateQty('<?= htmlspecialchars($cartKey) ?>', 1)">+</button>
                                        </div>
                                    </td>
                                    <td class="fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?><span style="vertical-align: baseline; margin-left: 2px;">đ</span></td>
                                    <td>
                                        <button type="button" class="remove-btn" onclick="deleteSingleItem('<?= htmlspecialchars($cartKey) ?>')" title="Xóa sản phẩm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <input type="checkbox" id="selectAllBottom" onchange="toggleSelectAll(this)">
                            <label for="selectAllBottom" class="ms-2 fw-semibold">
                                Chọn tất cả (<span id="selectedCount">0</span>)
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-danger" id="deleteSelectedBtn" onclick="deleteSelected()" disabled>
                            <i class="bi bi-trash me-1"></i> Xóa đã chọn
                        </button>
                    </div>
                    <a href="<?= BASE_URL ?>" class="text-decoration-none text-dark fw-semibold">
                        <i class="bi bi-arrow-left me-2"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-summary">
                    <!-- Mã giảm giá -->
                    <div class="coupon-section mb-4">
                        <h5 class="mb-3 text-uppercase fs-6 fw-bold">Mã giảm giá</h5>
                        
                        <!-- Lựa chọn tốt nhất (hiển thị trong khung) -->
                        <div id="bestCouponSuggestionInBox" class="coupon-display-none mb-3 p-3 bg-light rounded border border-warning">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark coupon-badge-small">
                                    <i class="bi bi-trophy-fill"></i> Lựa chọn tốt nhất
                                </span>
                                <strong id="bestCouponCodeInBox" class="fs-6"></strong>
                            </div>
                            <div class="text-dark fw-bold mb-1" id="bestCouponDiscountInBox"></div>
                            <small class="text-muted d-block mb-2" id="bestCouponNameInBox"></small>
                            <button type="button" class="btn btn-sm btn-dark" id="applyBestCouponBtnInBox" onclick="applyBestCoupon()">
                                Áp dụng ngay
                            </button>
                        </div>
                        
                        <!-- Form nhập mã -->
                        <div class="mb-3">
                            <div id="cccccccccc" class="input-group">
                                <input type="text" 
                                       class="form-control coupon-input-uppercase" 
                                       id="couponCodeInput" 
                                       placeholder="Nhập mã giảm giá">
                                <button type="button" 
                                        class="btn btn-outline-dark" 
                                        id="applyCouponBtn"
                                        onclick="applyCoupon()">
                                    Áp dụng
                                </button>
                            </div>
                            <div id="couponMessage" class="mt-2 small"></div>
                        </div>
                        
                        <!-- Danh sách mã khả dụng -->
                        <div id="availableCouponsSection" class="coupon-display-none">
                            <p class="small text-muted mb-2">Hoặc chọn mã giảm giá:</p>
                            <div id="availableCouponsList" class="list-group"></div>
                        </div>
                        
                        <!-- Mã đã áp dụng -->
                        <div id="appliedCouponDisplay" class="coupon-display-none mt-3 p-2 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong id="appliedCouponCode"></strong>
                                    <small class="d-block text-dark" id="appliedCouponDiscount"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCoupon()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <style>
                        #cccccccccc {
                            border-radius: 60px !important;
                            overflow: hidden !important; /* đảm bảo góc bo tròn hoạt động đúng */
                        }
                        #cccccccccc .form-control {
                            border-top-left-radius: 60px !important;
                            border-bottom-left-radius: 60px !important;
                            border-top-right-radius: 0 !important;
                            border-bottom-right-radius: 0 !important;
                        }
                        #cccccccccc .btn {
                            border-top-left-radius: 0 !important;
                            border-bottom-left-radius: 0 !important;
                            border-top-right-radius: 60px !important;
                            border-bottom-right-radius: 60px !important;
                        }
                        #cccccccccc .form-control::placeholder {
                            color: #666 !important;
                            opacity: 1 !important;
                            font-size: 0.75rem !important; /* giảm font-size để placeholder nhỏ lại */
                            letter-spacing: 0.3px !important;
                        }
                    </style>
                    <h4 class="mb-4 text-uppercase fs-6 fw-bold">Tóm tắt đơn hàng</h4>
                    <div class="summary-row">
                        <span>Tạm tính</span>
                        <span id="subtotal"><?= number_format($total, 0, ',', '.') ?><span style="vertical-align: baseline; margin-left: 2px;">đ</span></span>
                    </div>
                    <div class="summary-row coupon-display-none" id="discountRow">
                        <span>Giảm giá</span>
                        <span id="discountAmount" class="discount-amount">-0<span style="vertical-align: baseline; margin-left: 2px;">đ</span></span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển</span>
                        <span>Miễn phí</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Tổng cộng</span>
                        <span id="grandTotal"><?= number_format($total, 0, ',', '.') ?><span style="vertical-align: baseline; margin-left: 2px;">đ</span></span>
                    </div>
                    <button type="button" class="btn-checkout" id="checkoutBtn" onclick="proceedToCheckout()">Thanh toán ngay</button>
                    
                    <div class="mt-4 small text-muted">
                        <p class="mb-2"><i class="bi bi-shield-check me-2"></i> Bảo mật thanh toán 100%</p>
                        <p class="mb-0"><i class="bi bi-truck me-2"></i> Miễn phí vận chuyển toàn quốc</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQty(cartKey, change) {
    const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
    const input = row.querySelector('.qty-input');
    let currentQty = parseInt(input.value);
    if (isNaN(currentQty)) currentQty = 1;
    
    let newQty = currentQty + change;
    
    if (newQty < 1) return;
    
    input.value = newQty;
    
    // Gọi API cập nhật
    fetch('<?= BASE_URL ?>?action=cart-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_key: cartKey,
            quantity: newQty
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật data attributes và tổng tiền
            const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
            const itemTotal = itemPrice * newQty;
            row.dataset.itemQuantity = newQty;
            row.dataset.itemTotal = itemTotal;
            
            // Cập nhật hiển thị tổng tiền của item
            const totalCell = row.querySelector('td:nth-child(5)');
            if (totalCell) {
                totalCell.innerHTML = formatCurrency(itemTotal);
            }
            
            updateBuyTotal();
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        } else {
            const errorMsg = data.message || 'Có lỗi xảy ra khi cập nhật số lượng';
            alert(errorMsg);
            
            if (data.available_stock !== undefined) {
                const availableStock = parseInt(data.available_stock);
                if (availableStock > 0) {
                    input.value = availableStock;
                    updateQtyManual(cartKey, input); // Gọi lại để update về max stock
                } else {
                    input.value = currentQty;
                }
            } else {
                input.value = currentQty;
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        input.value = currentQty;
    });
}

// Update quantity when user types manually
function updateQtyManual(cartKey, input) {
    let newQty = parseInt(input.value);
    const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
    // const originalQty = parseInt(row.dataset.itemQuantity) || parseInt(input.value);
    
    if (isNaN(newQty) || newQty < 1) {
        input.value = 1;
        newQty = 1;
    }
    
    // Call API to update
    fetch('<?= BASE_URL ?>?action=cart-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_key: cartKey,
            quantity: newQty
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update data attributes and total
            const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
            const itemTotal = itemPrice * newQty;
            row.dataset.itemQuantity = newQty;
            row.dataset.itemTotal = itemTotal;
            
            // Update total display
            const totalCell = row.querySelector('td:nth-child(5)');
            if (totalCell) {
                totalCell.innerHTML = formatCurrency(itemTotal);
            }
            
            // Update buy total if item is selected
            updateBuyTotal();
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        } else {
            // Hiển thị thông báo lỗi chi tiết
            const errorMsg = data.message || 'Có lỗi xảy ra khi cập nhật số lượng';
            alert(errorMsg);
            
            // Nếu có thông tin về stock, tự động điều chỉnh số lượng
            if (data.available_stock !== undefined) {
                const availableStock = parseInt(data.available_stock);
                if (availableStock > 0) {
                    input.value = availableStock;
                    // Fix: Đệ quy gián tiếp có thể gây lặp vô hạn nếu logic không đúng.
                    // Tốt nhất chỉ update UI và reload nếu cần.
                    // Ở đây đơn giản update UI về stock max.
                    // updateQtyManual(cartKey, input); // Avoid recursion
                    row.dataset.itemQuantity = availableStock; 
                     // Cập nhật lại với số lượng đã điều chỉnh bằng cách gọi API trực tiếp (nhưng không gọi lại function này)
                     fetch('<?= BASE_URL ?>?action=cart-update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cart_key: cartKey, quantity: availableStock })
                    }).then(r => r.json()).then(d => {
                        if (d.success) {
                             const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
                             const itemTotal = itemPrice * availableStock;
                             row.dataset.itemQuantity = availableStock;
                             row.dataset.itemTotal = itemTotal;
                             const totalCell = row.querySelector('td:nth-child(5)');
                             if (totalCell) totalCell.innerHTML = formatCurrency(itemTotal);
                             updateBuyTotal();
                        }
                    });
                } else {
                    input.value = 1; // Default
                }
            } else {
                 // Revert to old valid value? Khó biết, tạm thời để nguyên hoặc xử lý sau.
                 // Tốt nhất là reload page để sync.
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Có lỗi xảy ra khi cập nhật số lượng');
    });
}

// Chọn/bỏ chọn tất cả
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox');
    const isChecked = checkbox.checked;
    
    checkboxes.forEach(cb => {
        cb.checked = isChecked;
    });
    
    // Đồng bộ cả 2 checkbox "Chọn tất cả"
    document.getElementById('selectAll').checked = isChecked;
    document.getElementById('selectAllBottom').checked = isChecked;
    
    updateSelectAllState();
    updateBuyTotal();
}

// Xử lý khi checkbox thay đổi
function handleCheckboxChange(checkbox) {
    updateSelectAllState();
    updateBuyTotal();
}

// Cập nhật trạng thái checkbox "Chọn tất cả" và nút xóa
function updateSelectAllState() {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox');
    const checkedBoxes = document.querySelectorAll('.cart-item-checkbox:checked');
    const totalCount = checkboxes.length;
    const selectedCount = checkedBoxes.length;
    
    // Cập nhật số lượng đã chọn
    document.getElementById('selectedCount').textContent = selectedCount;
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    const allChecked = totalCount > 0 && selectedCount === totalCount;
    document.getElementById('selectAll').checked = allChecked;
    document.getElementById('selectAllBottom').checked = allChecked;
    
    // Bật/tắt nút xóa
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    deleteBtn.disabled = selectedCount === 0;
}

// Xóa các sản phẩm đã chọn
function deleteSelected() {
    const checkedBoxes = document.querySelectorAll('.cart-item-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một sản phẩm để xóa');
        return;
    }
    
    if (!confirm(`Bạn có chắc muốn xóa ${checkedBoxes.length} sản phẩm đã chọn?`)) {
        return;
    }
    
    const cartKeys = Array.from(checkedBoxes).map(cb => cb.value);
    
    // Gọi API xóa
    fetch('<?= BASE_URL ?>?action=cart-delete-multiple', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_keys: cartKeys
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            const errorMsg = data.message || 'Có lỗi xảy ra khi xóa sản phẩm';
            alert(errorMsg);
            console.error('Delete error:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa sản phẩm. Vui lòng thử lại.');
    });
}

// Xóa một sản phẩm đơn lẻ
function deleteSingleItem(cartKey) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }
    
    fetch('<?= BASE_URL ?>?action=cart-delete-multiple', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_keys: [cartKey]
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            const errorMsg = data.message || 'Có lỗi xảy ra khi xóa sản phẩm';
            alert(errorMsg);
            console.error('Delete error:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa sản phẩm. Vui lòng thử lại.');
    });
}

// Cập nhật tổng tiền dựa trên các sản phẩm đã chọn
function updateBuyTotal() {
    // Tìm mã tốt nhất khi tổng tiền thay đổi
    findBestCoupon();
    const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
    let subtotal = 0;
    
    checkboxes.forEach(checkbox => {
        const cartKey = checkbox.value;
        const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
        if (row) {
            // Lấy giá trị từ data attribute hoặc tính lại từ giá và số lượng
            let itemTotal = parseFloat(row.dataset.itemTotal) || 0;
            
            // Nếu itemTotal không hợp lệ, tính lại từ giá và số lượng
            if (isNaN(itemTotal) || itemTotal <= 0) {
                const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
                const itemQuantity = parseInt(row.dataset.itemQuantity) || 0;
                itemTotal = itemPrice * itemQuantity;
                // Cập nhật lại data attribute
                row.dataset.itemTotal = itemTotal;
            }
            
            subtotal += itemTotal;
        }
    });
    
    // Cập nhật hiển thị
    document.getElementById('subtotal').innerHTML = formatCurrency(subtotal);
    
    // Nếu có mã giảm giá đã áp dụng, validate lại
    if (appliedCouponData) {
        const code = appliedCouponData.code;
        fetch('<?= BASE_URL ?>?action=coupon-validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: code,
                order_amount: subtotal
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appliedCouponData.discount_amount = data.discount_amount;
                updateTotalsWithCoupon(data.discount_amount);
            } else {
                // Mã không còn hợp lệ, xóa đi
                removeCoupon();
                document.getElementById('grandTotal').innerHTML = formatCurrency(subtotal);
            }
        })
        .catch(error => {
            console.error('Error validating coupon:', error);
            document.getElementById('grandTotal').innerHTML = formatCurrency(subtotal);
        });
    } else if (pendingCouponCode && subtotal > 0) {
        // Nếu có mã đang chờ và tổng tiền > 0, thử áp dụng tự động
        autoApplyPendingCoupon(subtotal);
    } else {
        document.getElementById('grandTotal').innerHTML = formatCurrency(subtotal);
        document.getElementById('discountRow').style.display = 'none';
    }
    
    // Tải lại danh sách mã giảm giá khả dụng
    loadAvailableCoupons();
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    const allCheckboxes = document.querySelectorAll('.cart-item-checkbox');
    const checkedBoxes = document.querySelectorAll('.cart-item-checkbox:checked');
    const allChecked = allCheckboxes.length > 0 && checkedBoxes.length === allCheckboxes.length;
    document.getElementById('selectAll').checked = allChecked;
    document.getElementById('selectAllBottom').checked = allChecked;
    
    // Bật/tắt nút thanh toán
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = checkedBoxes.length === 0;
    if (checkedBoxes.length === 0) {
        checkoutBtn.textContent = 'Vui lòng chọn sản phẩm để mua';
    } else {
        checkoutBtn.textContent = 'Thanh toán ngay';
    }
}

// Định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(amount)) + '<span style="vertical-align: baseline; margin-left: 2px;">đ</span>';
}

// Mã giảm giá
let appliedCouponData = null;
let pendingCouponCode = null; // Mã đang chờ đủ điều kiện

// Tải danh sách mã giảm giá khả dụng
function loadAvailableCoupons() {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
    let subtotal = 0;
    
    checkboxes.forEach(checkbox => {
        const cartKey = checkbox.value;
        const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
        if (row) {
            // Lấy giá trị từ data attribute hoặc tính lại từ giá và số lượng
            let itemTotal = parseFloat(row.dataset.itemTotal) || 0;
            
            // Nếu itemTotal không hợp lệ, tính lại từ giá và số lượng
            if (isNaN(itemTotal) || itemTotal <= 0) {
                const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
                const itemQuantity = parseInt(row.dataset.itemQuantity) || 0;
                itemTotal = itemPrice * itemQuantity;
                // Cập nhật lại data attribute
                row.dataset.itemTotal = itemTotal;
            }
            
            subtotal += itemTotal;
        }
    });
    
    // Tính tổng tiền (có thể = 0 nếu chưa chọn sản phẩm)
    // Vẫn tải danh sách mã để hiển thị các mã không yêu cầu đơn tối thiểu
    const orderAmount = subtotal > 0 ? subtotal : 0;
    
    fetch(`<?= BASE_URL ?>?action=coupon-available&order_amount=${orderAmount}`)
        .then(response => response.json())
        .then(data => {
            console.log('loadAvailableCoupons response:', data);
            if (data.success && data.coupons && data.coupons.length > 0) {
                // Tính discount_amount cho mỗi mã và sắp xếp
                const couponPromises = data.coupons.map(coupon => {
                    return fetch('<?= BASE_URL ?>?action=coupon-validate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            coupon_code: coupon.code,
                            order_amount: orderAmount
                        })
                    })
                    .then(res => res.json())
                    .then(result => {
                        console.log('Coupon validate result for', coupon.code, ':', result);
                        if (result.success) {
                            return {
                                coupon: coupon,
                                discount_amount: result.discount_amount
                            };
                        }
                        return null;
                    })
                    .catch((error) => {
                        console.error('Error validating coupon', coupon.code, ':', error);
                        return null;
                    });
                });
                
                Promise.all(couponPromises).then(results => {
                    const validCoupons = results.filter(r => r !== null);
                    console.log('loadAvailableCoupons validCoupons:', validCoupons);
                    
                    if (validCoupons.length > 0) {
                        // Sắp xếp theo discount_amount giảm dần (mã giảm nhiều nhất ở đầu, ít nhất ở cuối)
                        validCoupons.sort((a, b) => b.discount_amount - a.discount_amount);
                        
                        // Loại bỏ mã "Lựa chọn tốt nhất" khỏi danh sách nếu đã hiển thị ở trên
                        const filteredCoupons = validCoupons.filter((itemData, index) => {
                            // Nếu là mã đầu tiên (lựa chọn tốt nhất) và đã được hiển thị ở trên, loại bỏ
                            if (index === 0 && bestCouponCode && itemData.coupon.code === bestCouponCode) {
                                return false;
                            }
                            return true;
                        });
                        
                        const listContainer = document.getElementById('availableCouponsList');
                        listContainer.innerHTML = '';
                        
                        // Hiển thị các mã giảm giá theo thứ tự giảm dần (mã giảm nhiều nhất ở trên, ít nhất ở dưới)
                        // Mã tốt nhất đã được hiển thị ở trên cùng với badge "Lựa chọn tốt nhất"
                        filteredCoupons.forEach((itemData, index) => {
                            const coupon = itemData.coupon;
                            const discountAmount = itemData.discount_amount;
                            // Không còn hiển thị badge "Lựa chọn tốt nhất" trong danh sách vì đã hiển thị ở trên
                            const isBest = false;
                            
                            const item = document.createElement('div');
                            item.className = 'coupon-item p-2';
                            item.onclick = () => selectCoupon(coupon);
                            
                            const discountText = coupon.discount_type === 'percent' 
                                ? `Giảm ${coupon.discount_value}%` 
                                : `Giảm ${formatCurrency(coupon.discount_value)}`;
                            
                            const maxDiscount = coupon.discount_type === 'fixed' ? '' : 
                                (coupon.max_discount_amount 
                                    ? ` (Tối đa ${formatCurrency(coupon.max_discount_amount)})` 
                                    : '');
                            
                            const minOrderText = coupon.min_order_amount > 0
                                ? `Đơn tối thiểu: ${formatCurrency(coupon.min_order_amount)}`
                                : '';
                            
                            // Hiển thị số tiền tiết kiệm thực tế
                            const saveText = `Tiết kiệm ${formatCurrency(discountAmount)}`;
                            
                            item.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="coupon-item-flex">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            ${isBest ? `<span class="badge bg-warning text-dark coupon-badge-small"><i class="bi bi-trophy-fill"></i> Lựa chọn tốt nhất</span>` : ''}
                                            <strong>${coupon.code}</strong>
                                        </div>
                                        <small class="d-block text-muted">${coupon.name || ''}</small>
                                        <small class="text-dark d-block fw-bold">${saveText}</small>
                                        <small class="text-muted d-block">${discountText}${maxDiscount}</small>
                                        ${minOrderText ? `<small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> ${minOrderText}</small>` : ''}
                                    </div>
                                    <i class="bi bi-chevron-right ms-2"></i>
                                </div>
                            `;
                            
                            listContainer.appendChild(item);
                        });
                        
                        const availableSection = document.getElementById('availableCouponsSection');
                        if (availableSection) {
                            availableSection.classList.remove('coupon-display-none');
                            availableSection.classList.add('coupon-display-block');
                        }
                    } else {
                        const availableSection = document.getElementById('availableCouponsSection');
                        if (availableSection) {
                            availableSection.classList.add('coupon-display-none');
                            availableSection.classList.remove('coupon-display-block');
                        }
                    }
                });
            } else {
                const availableSection = document.getElementById('availableCouponsSection');
                if (availableSection) {
                    availableSection.classList.add('coupon-display-none');
                    availableSection.classList.remove('coupon-display-block');
                }
            }
        })
        .catch(error => {
            console.error('Error loading coupons:', error);
            // Ẩn section nếu có lỗi
            const section = document.getElementById('availableCouponsSection');
            if (section) {
                section.classList.add('coupon-display-none');
                section.classList.remove('coupon-display-block');
            }
        });
}

// Áp dụng mã giảm giá từ input
function applyCoupon() {
    const code = document.getElementById('couponCodeInput').value.trim().toUpperCase();
    const messageDiv = document.getElementById('couponMessage');
    const applyBtn = document.getElementById('applyCouponBtn');
    
    if (!code) {
        messageDiv.innerHTML = '<span class="coupon-message error">Vui lòng nhập mã giảm giá</span>';
        return;
    }
    
    const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
    let subtotal = 0;
    const productIds = [];
    
    checkboxes.forEach(checkbox => {
        const cartKey = checkbox.value;
        const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
        if (row) {
            // Lấy giá trị từ data attribute hoặc tính lại từ giá và số lượng
            let itemTotal = parseFloat(row.dataset.itemTotal) || 0;
            
            // Nếu itemTotal không hợp lệ, tính lại từ giá và số lượng
            if (isNaN(itemTotal) || itemTotal <= 0) {
                const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
                const itemQuantity = parseInt(row.dataset.itemQuantity) || 0;
                itemTotal = itemPrice * itemQuantity;
                // Cập nhật lại data attribute
                row.dataset.itemTotal = itemTotal;
            }
            
            subtotal += itemTotal;

            const pid = parseInt(row.dataset.productId || '0', 10);
            if (!isNaN(pid) && pid > 0) {
                productIds.push(pid);
            }
        }
    });
    
    // Cho phép áp dụng mã ngay cả khi chưa chọn sản phẩm (để validate mã không yêu cầu đơn tối thiểu)
    // Nếu subtotal = 0, vẫn gửi request để validate, server sẽ kiểm tra điều kiện
    const orderAmount = subtotal > 0 ? subtotal : 0;
    
    if (orderAmount === 0) {
        // Nếu chưa chọn sản phẩm, vẫn cho phép validate nhưng sẽ có thông báo nếu mã yêu cầu đơn tối thiểu
        console.log('Áp dụng mã với orderAmount = 0 (chưa chọn sản phẩm)');
    }
    
    applyBtn.disabled = true;
    applyBtn.textContent = 'Đang kiểm tra...';
    messageDiv.innerHTML = '';
    
    fetch('<?= BASE_URL ?>?action=coupon-validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            coupon_code: code,
            order_amount: orderAmount,
            product_ids: productIds
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('applyCoupon response:', data);
        if (data.success) {
            appliedCouponData = data.coupon;
            appliedCouponData.discount_amount = data.discount_amount;
            pendingCouponCode = null; // Xóa mã chờ vì đã áp dụng thành công
            
            // Nếu orderAmount = 0 (chưa chọn sản phẩm), chỉ lưu mã vào pending
            if (orderAmount === 0) {
                // Mã hợp lệ nhưng chưa có sản phẩm, lưu vào pending
                pendingCouponCode = code;
                document.getElementById('couponCodeInput').value = code;
                messageDiv.innerHTML = '<span class="coupon-message success">✓ Mã hợp lệ! Vui lòng chọn sản phẩm để áp dụng mã giảm giá.</span>';
                applyBtn.disabled = false;
                applyBtn.textContent = 'Áp dụng';
                // Không hiển thị applied coupon vì chưa có sản phẩm
            } else {
                // Có sản phẩm, áp dụng mã ngay
                showAppliedCoupon(data.coupon.code, data.coupon.name, data.discount_amount);
                updateTotalsWithCoupon(data.discount_amount);
                
                document.getElementById('couponCodeInput').value = data.coupon.code;
                document.getElementById('couponCodeInput').disabled = true;
                applyBtn.disabled = true;
                applyBtn.textContent = 'Đã áp dụng';
                
                messageDiv.innerHTML = '<span class="coupon-message success">✓ ' + data.message + '</span>';
            }
        } else {
            // Lưu mã vào pending để tự động áp dụng khi đủ điều kiện
            pendingCouponCode = code;
            messageDiv.innerHTML = '<span class="coupon-message error">' + data.message + '</span>';
            applyBtn.disabled = false;
            applyBtn.textContent = 'Áp dụng';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.innerHTML = '<span class="coupon-message error">Có lỗi xảy ra. Vui lòng thử lại.</span>';
        applyBtn.disabled = false;
        applyBtn.textContent = 'Áp dụng';
    });
}

// Chọn mã giảm giá từ danh sách
function selectCoupon(coupon) {
    appliedCouponData = {
        id: coupon.id,
        code: coupon.code,
        name: coupon.name,
        discount_amount: coupon.discount_amount
    };
    pendingCouponCode = null; // Xóa mã chờ vì đã chọn mã khác
    
    showAppliedCoupon(coupon.code, coupon.name, coupon.discount_amount);
    updateTotalsWithCoupon(coupon.discount_amount);
    
    // Gửi request để lưu vào session
    fetch('<?= BASE_URL ?>?action=coupon-validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            coupon_code: coupon.code,
            order_amount: getSelectedSubtotal()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('couponCodeInput').value = coupon.code;
            document.getElementById('couponCodeInput').disabled = true;
            document.getElementById('applyCouponBtn').disabled = true;
            document.getElementById('applyCouponBtn').textContent = 'Đã áp dụng';
            document.getElementById('couponMessage').innerHTML = '<span class="coupon-message success">✓ ' + data.message + '</span>';
        }
    });
}

// Hiển thị mã giảm giá đã áp dụng
function showAppliedCoupon(code, name, discountAmount) {
    document.getElementById('appliedCouponCode').textContent = code + ' - ' + name;
    document.getElementById('appliedCouponDiscount').innerHTML = 'Giảm ' + formatCurrency(discountAmount);
    const appliedDisplay = document.getElementById('appliedCouponDisplay');
    const availableSection = document.getElementById('availableCouponsSection');
    if (appliedDisplay) {
        appliedDisplay.classList.remove('coupon-display-none');
        appliedDisplay.classList.add('coupon-display-block');
    }
    if (availableSection) {
        availableSection.classList.add('coupon-display-none');
        availableSection.classList.remove('coupon-display-block');
    }
}

// Xóa mã giảm giá
function removeCoupon() {
    appliedCouponData = null;
    pendingCouponCode = null; // Xóa cả mã chờ
    
    fetch('<?= BASE_URL ?>?action=coupon-remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        const appliedDisplay = document.getElementById('appliedCouponDisplay');
        if (appliedDisplay) {
            appliedDisplay.classList.add('coupon-display-none');
            appliedDisplay.classList.remove('coupon-display-block');
        }
        document.getElementById('couponCodeInput').value = '';
        document.getElementById('couponCodeInput').disabled = false;
        document.getElementById('applyCouponBtn').disabled = false;
        document.getElementById('applyCouponBtn').textContent = 'Áp dụng';
        document.getElementById('couponMessage').innerHTML = '';
        
        updateBuyTotal();
        loadAvailableCoupons();
    });
}

// Tự động áp dụng mã đang chờ khi đủ điều kiện
function autoApplyPendingCoupon(subtotal) {
    if (!pendingCouponCode) return;
    
    fetch('<?= BASE_URL ?>?action=coupon-validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            coupon_code: pendingCouponCode,
            order_amount: subtotal
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Đủ điều kiện, áp dụng tự động
            appliedCouponData = data.coupon;
            appliedCouponData.discount_amount = data.discount_amount;
            pendingCouponCode = null; // Xóa mã chờ vì đã áp dụng thành công
            
            showAppliedCoupon(data.coupon.code, data.coupon.name, data.discount_amount);
            updateTotalsWithCoupon(data.discount_amount);
            
            // Cập nhật UI
            document.getElementById('couponCodeInput').value = data.coupon.code;
            document.getElementById('couponCodeInput').disabled = true;
            document.getElementById('applyCouponBtn').disabled = true;
            document.getElementById('applyCouponBtn').textContent = 'Đã áp dụng';
            
            // Hiển thị thông báo thành công
            document.getElementById('couponMessage').innerHTML = '<span class="coupon-message success">✓ ' + data.message + '</span>';
        } else {
            // Vẫn chưa đủ điều kiện, giữ nguyên mã chờ
            document.getElementById('grandTotal').innerHTML = formatCurrency(subtotal);
            document.getElementById('discountRow').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error auto-applying coupon:', error);
        document.getElementById('grandTotal').innerHTML = formatCurrency(subtotal);
        document.getElementById('discountRow').style.display = 'none';
    });
}

// Lấy tổng tiền các sản phẩm đã chọn
function getSelectedSubtotal() {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
    let subtotal = 0;
    
    checkboxes.forEach(checkbox => {
        const cartKey = checkbox.value;
        const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
        if (row) {
            // Lấy giá trị từ data attribute hoặc tính lại từ giá và số lượng
            let itemTotal = parseFloat(row.dataset.itemTotal) || 0;
            
            // Nếu itemTotal không hợp lệ, tính lại từ giá và số lượng
            if (isNaN(itemTotal) || itemTotal <= 0) {
                const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
                const itemQuantity = parseInt(row.dataset.itemQuantity) || 0;
                itemTotal = itemPrice * itemQuantity;
                // Cập nhật lại data attribute
                row.dataset.itemTotal = itemTotal;
            }
            
            subtotal += itemTotal;
        }
    });
    
    return subtotal;
}

// Cập nhật tổng tiền với mã giảm giá
function updateTotalsWithCoupon(discountAmount) {
    const subtotal = getSelectedSubtotal();
    const finalTotal = subtotal - discountAmount;
    
    document.getElementById('subtotal').innerHTML = formatCurrency(subtotal);
    
    if (discountAmount > 0) {
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('discountAmount').innerHTML = '-' + formatCurrency(discountAmount);
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
    
    document.getElementById('grandTotal').innerHTML = formatCurrency(Math.max(0, finalTotal));
}

// Chuyển đến trang thanh toán với các sản phẩm đã chọn
function proceedToCheckout() {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Vui lòng chọn ít nhất một sản phẩm để mua');
        return;
    }
    
    const selectedItems = Array.from(checkboxes).map(cb => cb.value);
    
    // Lưu vào sessionStorage để checkout page có thể đọc
    sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
    
    // Lưu vào session qua API
    fetch('<?= BASE_URL ?>?action=cart-set-selected', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            selected_items: selectedItems
        })
    })
    .then(() => {
        // Chuyển đến trang checkout
        window.location.href = '<?= BASE_URL ?>?action=checkout';
    })
    .catch(err => {
        console.error('Error saving selected items:', err);
        // Vẫn chuyển đến checkout
        window.location.href = '<?= BASE_URL ?>?action=checkout';
    });
}

// Lưu trạng thái checkbox vào sessionStorage
function saveBuyCheckboxes() {
    const checkedBoxes = document.querySelectorAll('.cart-item-checkbox:checked');
    const selectedItems = Array.from(checkedBoxes).map(cb => cb.value);
    sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
}

// Khôi phục trạng thái checkbox từ sessionStorage
function restoreBuyCheckboxes() {
    try {
        const saved = sessionStorage.getItem('selectedCartItems');
        if (saved) {
            const selectedItems = JSON.parse(saved);
            selectedItems.forEach(cartKey => {
                const checkbox = document.querySelector(`input.cart-item-checkbox[value="${cartKey}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        } else {
            // Nếu không có trong sessionStorage, check tất cả mặc định
            document.querySelectorAll('.cart-item-checkbox').forEach(cb => {
                cb.checked = true;
            });
        }
    } catch (e) {
        console.error('Error restoring checkboxes:', e);
        // Nếu có lỗi, check tất cả mặc định
        document.querySelectorAll('.cart-item-checkbox').forEach(cb => {
            cb.checked = true;
        });
    }
}

// Cập nhật event listener cho các checkbox
document.addEventListener('DOMContentLoaded', function() {
    updateSelectAllState();
    restoreBuyCheckboxes();
    updateBuyTotal();
    
    // Lưu trạng thái khi checkbox thay đổi
    document.querySelectorAll('.cart-item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            saveBuyCheckboxes();
            updateBuyTotal();
        });
    });
    
    // Lưu trạng thái khi "chọn tất cả" thay đổi
    const selectAll = document.getElementById('selectAll');
    const selectAllBottom = document.getElementById('selectAllBottom');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            saveBuyCheckboxes();
        });
    }
    if (selectAllBottom) {
        selectAllBottom.addEventListener('change', function() {
            saveBuyCheckboxes();
        });
    }
});

// Tìm mã giảm giá tốt nhất
let bestCouponData = null;
let bestCouponCode = null; // Lưu mã code của "Lựa chọn tốt nhất" để loại bỏ khỏi danh sách

function findBestCoupon() {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
    let subtotal = 0;
    
    checkboxes.forEach(checkbox => {
        const cartKey = checkbox.value;
        const row = document.querySelector(`tr[data-cart-key="${cartKey}"]`);
        if (row) {
            // Lấy giá trị từ data attribute hoặc tính lại từ giá và số lượng
            let itemTotal = parseFloat(row.dataset.itemTotal) || 0;
            
            // Nếu itemTotal không hợp lệ, tính lại từ giá và số lượng
            if (isNaN(itemTotal) || itemTotal <= 0) {
                const itemPrice = parseFloat(row.dataset.itemPrice) || 0;
                const itemQuantity = parseInt(row.dataset.itemQuantity) || 0;
                itemTotal = itemPrice * itemQuantity;
                // Cập nhật lại data attribute
                row.dataset.itemTotal = itemTotal;
            }
            
            subtotal += itemTotal;
        }
    });
    
    // Nếu đã có mã áp dụng, không hiển thị gợi ý
    if (appliedCouponData) {
        const suggestionDiv = document.getElementById('bestCouponSuggestionInBox');
        if (suggestionDiv) {
            suggestionDiv.classList.add('coupon-display-none');
            suggestionDiv.classList.remove('coupon-display-block');
        }
        return;
    }
    
    // Sử dụng subtotal, nếu = 0 thì vẫn tìm mã (có thể có mã không yêu cầu đơn tối thiểu)
    const orderAmount = subtotal > 0 ? subtotal : 0;
    
    fetch(`<?= BASE_URL ?>?action=coupon-available&order_amount=${orderAmount}`)
        .then(response => response.json())
        .then(data => {
            console.log('findBestCoupon response:', data);
            if (data.success && data.coupons && data.coupons.length > 0) {
                // Tính discount cho mỗi mã và tìm mã tốt nhất
                const couponPromises = data.coupons.map(coupon => {
                    return fetch('<?= BASE_URL ?>?action=coupon-validate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            coupon_code: coupon.code,
                            order_amount: orderAmount
                        })
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            return {
                                coupon: coupon,
                                discount_amount: result.discount_amount,
                                final_amount: result.final_amount
                            };
                        }
                        return null;
                    })
                    .catch(() => null);
                });
                
                Promise.all(couponPromises).then(results => {
                    const validCoupons = results.filter(r => r !== null);
                    console.log('findBestCoupon validCoupons:', validCoupons);
                    if (validCoupons.length > 0) {
                        // Sắp xếp theo discount_amount giảm dần (mã giảm nhiều nhất ở trên, ít nhất ở dưới)
                        validCoupons.sort((a, b) => b.discount_amount - a.discount_amount);
                        
                        // Lấy mã đầu tiên (giảm nhiều nhất) - Lựa chọn tốt nhất
                        // Mã này sẽ được hiển thị ở trên cùng với badge "Lựa chọn tốt nhất"
                        const best = validCoupons[0];
                        
                        bestCouponData = best;
                        displayBestCoupon(best);
                    } else {
                        const suggestionDiv = document.getElementById('bestCouponSuggestionInBox');
                        if (suggestionDiv) {
                            suggestionDiv.classList.add('coupon-display-none');
                            suggestionDiv.classList.remove('coupon-display-block');
                        }
                    }
                });
            } else {
                const suggestionDiv = document.getElementById('bestCouponSuggestionInBox');
                if (suggestionDiv) {
                    suggestionDiv.classList.add('coupon-display-none');
                    suggestionDiv.classList.remove('coupon-display-block');
                }
            }
        })
        .catch(error => {
            console.error('Error finding best coupon:', error);
            const suggestionDiv = document.getElementById('bestCouponSuggestionInBox');
            if (suggestionDiv) {
                suggestionDiv.classList.add('coupon-display-none');
                suggestionDiv.classList.remove('coupon-display-block');
            }
        });
}

function displayBestCoupon(best) {
    console.log('displayBestCoupon called with:', best);
    // Lưu mã code để loại bỏ khỏi danh sách
    bestCouponCode = best.coupon.code;
    
    // Hiển thị trong khung mã giảm giá
    const suggestionDivInBox = document.getElementById('bestCouponSuggestionInBox');
    const codeDivInBox = document.getElementById('bestCouponCodeInBox');
    const discountDivInBox = document.getElementById('bestCouponDiscountInBox');
    const nameDivInBox = document.getElementById('bestCouponNameInBox');
    
    console.log('Elements found:', {
        suggestionDivInBox: !!suggestionDivInBox,
        codeDivInBox: !!codeDivInBox,
        discountDivInBox: !!discountDivInBox,
        nameDivInBox: !!nameDivInBox
    });
    
    if (suggestionDivInBox && codeDivInBox && discountDivInBox && nameDivInBox) {
        codeDivInBox.textContent = best.coupon.code;
        discountDivInBox.innerHTML = `Tiết kiệm ${formatCurrency(best.discount_amount)}`;
        nameDivInBox.textContent = best.coupon.name || '';
        suggestionDivInBox.classList.remove('coupon-display-none');
        suggestionDivInBox.classList.add('coupon-display-block');
        console.log('Best coupon displayed');
    } else {
        console.error('Missing elements for displaying best coupon');
    }
    
    // Tải lại danh sách mã giảm giá để loại bỏ mã đã hiển thị ở trên
    loadAvailableCoupons();
}

function applyBestCoupon() {
    if (!bestCouponData) return;
    
    // Đặt mã vào input và áp dụng
    document.getElementById('couponCodeInput').value = bestCouponData.coupon.code;
    applyCoupon();
}

// Tải danh sách mã giảm giá khi trang load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Loading coupons...');
    loadAvailableCoupons();
    findBestCoupon();
    
    // Cho phép nhấn Enter để áp dụng mã
    document.getElementById('couponCodeInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyCoupon();
        }
    });
    
    // Kiểm tra mã giảm giá đã áp dụng từ session (nếu có)
    <?php if (isset($_SESSION['applied_coupon'])): ?>
    appliedCouponData = <?= json_encode($_SESSION['applied_coupon'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const subtotal = getSelectedSubtotal();
    if (subtotal > 0) {
        fetch('<?= BASE_URL ?>?action=coupon-validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: appliedCouponData.code,
                order_amount: subtotal
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appliedCouponData.discount_amount = data.discount_amount;
                showAppliedCoupon(data.coupon.code, data.coupon.name, data.discount_amount);
                updateTotalsWithCoupon(data.discount_amount);
                document.getElementById('couponCodeInput').value = data.coupon.code;
                document.getElementById('couponCodeInput').disabled = true;
                document.getElementById('applyCouponBtn').disabled = true;
                document.getElementById('applyCouponBtn').textContent = 'Đã áp dụng';
            } else {
                // Mã không còn hợp lệ, xóa khỏi session
                fetch('<?= BASE_URL ?>?action=coupon-remove', { method: 'POST' });
            }
        });
    }
    <?php endif; ?>
});
</script>
