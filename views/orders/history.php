<!-- Trang lịch sử đơn hàng của user -->
<section class="orders-page">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase text-muted mb-1 small">BonBonwear</p>
                <h2 class="fw-bold">Đơn hàng của tôi</h2>
            </div>
            <a href="<?= BASE_URL ?>" class="btn btn-outline-dark">Tiếp tục mua sắm</a>
        </div>

        <!-- Tabs trạng thái đơn hàng -->
        <div class="order-status-tabs mb-4">
            <div class="status-tabs-wrapper">
                <a href="<?= BASE_URL ?>?action=order-history" 
                   class="status-tab <?= !isset($_GET['status']) ? 'active' : '' ?>">
                    Tất cả
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_UNPAID ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_UNPAID ? 'active' : '' ?>">
                    Chờ Thanh Toán
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_PAID ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_PAID ? 'active' : '' ?>">
                    Đã Thanh Toán
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_PAYMENT_FAILED ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_PAYMENT_FAILED ? 'active' : '' ?>">
                    Thanh toán thất bại
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_PENDING ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_PENDING ? 'active' : '' ?>">
                    Chờ Xác Nhận
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_CONFIRMED ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_CONFIRMED ? 'active' : '' ?>">
                    Xác Nhận Đơn Hàng
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_PREPARING ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_PREPARING ? 'active' : '' ?>">
                    Đang Chuẩn Bị
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_HANDED_TO_SHIPPER ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_HANDED_TO_SHIPPER ? 'active' : '' ?>">
                    Đã Giao Cho Đơn Vị Vận Chuyển
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_SHIPPING ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_SHIPPING ? 'active' : '' ?>">
                    Đang Vận Chuyển
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_TO_SHIP ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_TO_SHIP ? 'active' : '' ?>">
                    Đang Giao
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_DELIVERED ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_DELIVERED ? 'active' : '' ?>">
                    Đã Giao
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_COMPLETED ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_COMPLETED ? 'active' : '' ?>">
                    Hoàn Thành
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_CANCELLED ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_CANCELLED ? 'active' : '' ?>">
                    Đã Hủy
                </a>
                <a href="<?= BASE_URL ?>?action=order-history&status=<?= OrderModel::STATUS_RETURNED ?>" 
                   class="status-tab <?= ($_GET['status'] ?? '') === OrderModel::STATUS_RETURNED ? 'active' : '' ?>">
                    Trả Hàng / Hoàn Tiền
                </a>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <!-- Trường hợp chưa có đơn nào -->
            <div class="text-center py-5 bg-light rounded-3">
                <i class="bi bi-inbox empty-icon-lg"></i>
                <p class="mb-3 fs-5">Bạn chưa có đơn hàng nào.</p>
                <a href="<?= BASE_URL ?>" class="btn btn-dark">
                    <i class="bi bi-bag me-2"></i>Khám phá sản phẩm
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                $orderItems = $order['items'] ?? [];
                $itemCount = count($orderItems);
            ?>
                <div class="order-card mb-4 border rounded p-4">
                    <!-- Thông tin chính của từng đơn -->
                    <div class="row g-3 align-items-start mb-3">
                        <div class="col-md-3">
                            <div class="order-code text-uppercase small text-muted mb-1">Mã đơn</div>
                            <h5 class="mb-1"><?= htmlspecialchars($order['order_code'] ?? '#' . ($order['id'] ?? $order['order_id'] ?? '')) ?></h5>
                            <div class="order-meta small text-muted">Ngày đặt: <?= isset($order['created_at']) && $order['created_at'] ? date('d/m/Y H:i', strtotime($order['created_at'])) : '-' ?></div>
                        </div>
                        <div class="col-md-2">
                            <div class="order-code text-uppercase small text-muted mb-1">Trạng thái</div>
                            <span class="badge bg-<?= OrderModel::statusBadge($order['status']) ?> px-3 py-2">
                                <?= OrderModel::statusLabel($order['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <div class="order-code text-uppercase small text-muted mb-1">Số lượng</div>
                            <h6 class="mb-0"><?= $itemCount ?> sản phẩm</h6>
                        </div>
                        <div class="col-md-2">
                            <div class="order-code text-uppercase small text-muted mb-1">Tổng tiền</div>
                            <h5 class="mb-0 text-primary"><?= number_format($order['total_amount'], 0, ',', '.') ?> đ</h5>
                        </div>
                        <div class="col-md-3 order-actions text-md-end">
                            <a href="<?= BASE_URL ?>?action=order-detail&id=<?= $order['id'] ?>" class="btn btn-dark mb-2 d-block">
                                Xem chi tiết
                            </a>
                            <?php if ($order['status'] === OrderModel::STATUS_UNPAID): ?>
                                <a href="<?= BASE_URL ?>?action=order-pay&id=<?= $order['id'] ?>" class="btn btn-primary mb-2 d-block">
                                    Thanh toán ngay
                                </a>
                            <?php elseif ($order['status'] === OrderModel::STATUS_PAYMENT_FAILED): ?>
                                <a href="<?= BASE_URL ?>?action=order-pay&id=<?= $order['id'] ?>" class="btn btn-outline-warning mb-2 d-block">
                                    Thanh toán lại
                                </a>
                            <?php elseif ($order['status'] === OrderModel::STATUS_DELIVERED): ?>
                                <a href="<?= BASE_URL ?>?action=order-detail&id=<?= $order['id'] ?>&review=true" class="btn btn-outline-dark mb-2 d-block">
                                    <i class="bi bi-star-fill"></i> Đánh giá ngay
                                </a>
                            <?php elseif ($order['status'] === OrderModel::STATUS_CANCELLED): ?>
                                <a href="<?= BASE_URL ?>?action=order-rebuy&id=<?= $order['id'] ?>" class="btn btn-outline-dark mb-2 d-block">
                                    Mua lại
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Danh sách sản phẩm trong đơn hàng -->
                    <?php if (!empty($orderItems)): ?>
                        <div class="order-items-preview border-top pt-3">
                            <h6 class="mb-3 fw-bold">
                                <i class="bi bi-box-seam me-2"></i>Sản phẩm trong đơn hàng
                            </h6>
                            <div class="row g-3">
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-center border rounded p-2 bg-light">
                                            <?php 
                                            $itemImage = getProductImageUrl($item['image_url'] ?? '');
                                            if (!empty($itemImage)): 
                                            ?>
                                                <img src="<?= htmlspecialchars($itemImage) ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                     class="me-2 thumb-60"
                                                     onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'">
                                            <?php else: ?>
                                                <div class="me-2 placeholder-60-box bg-white text-muted">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1 flex-min-0">
                                                <div class="fw-semibold small text-truncate" title="<?= htmlspecialchars($item['product_name']) ?>">
                                                    <?= htmlspecialchars($item['product_name']) ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                                                        <?php if (!empty($item['variant_size'])): ?>
                                                            Size: <?= htmlspecialchars($item['variant_size']) ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['variant_color'])): ?>
                                                            <?= !empty($item['variant_size']) ? ', ' : '' ?>Màu: <?= htmlspecialchars($item['variant_color']) ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small">
                                                    <strong><?= number_format($item['unit_price'], 0, ',', '.') ?> đ</strong>
                                                    <span class="text-muted"> x <?= $item['quantity'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

