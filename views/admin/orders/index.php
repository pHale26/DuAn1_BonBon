<section class="admin-orders-page">
    <div class="container">
        <div class="admin-page-header mb-4">
            <div class="title-wrap">
                <p class="text-uppercase mb-1 small">Bảng điều khiển</p>
                <h2 class="fw-bold mb-0">Quản lý đơn hàng</h2>
            </div>
            <div class="admin-page-actions">
                <a href="<?= BASE_URL ?>" class="btn btn-light-soft">Xem cửa hàng</a>
            </div>
        </div>

        <!-- Form tìm kiếm và lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= BASE_URL ?>" id="searchForm">
                    <input type="hidden" name="action" value="admin-orders">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase fw-bold">Tìm kiếm</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" 
                                       name="keyword" 
                                       class="form-control" 
                                       placeholder="Mã đơn, tên khách, số điện thoại..." 
                                       value="<?= htmlspecialchars($keyword ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-uppercase fw-bold">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <?php foreach ($statusMap as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($status ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search"></i> Tìm
                                </button>
                                <a href="<?= BASE_URL ?>?action=admin-orders" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <!-- Bảng danh sách đơn hàng -->
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="white-space:nowrap">Mã đơn</th>
                            <th class="text-center" style="white-space:nowrap">Khách hàng</th>
                            <th class="text-center" style="white-space:nowrap">Liên hệ</th>
                            <th class="text-center" style="white-space:nowrap">Giá trị</th>
                            <th class="text-center" style="white-space:nowrap">Trạng thái đơn</th>
                            <th class="text-center" style="white-space:nowrap">Thanh toán</th>
                            <th class="text-center" style="white-space:nowrap">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Không có đơn hàng nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="fw-semibold"><?= htmlspecialchars($order['order_code']) ?></div>
                                        <?php if (isset($order['created_at']) && $order['created_at']): ?>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($order['fullname']) ?></td>
                                    <td class="text-center">
                                        <div><?= htmlspecialchars($order['phone']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                    </td>
                                    <td class="text-center fw-bold"><?= number_format($order['total_amount'], 0, ',', '.') ?> đ</td>
                                    <td class="text-center">
                                        <?php
                                            $pm = strtolower($order['payment_method'] ?? 'cod');
                                            $status = $order['status'];
                                            $ret = $returnMap[$order['id']] ?? null;
                                            // determine next statuses for dropdown (same logic as controller)
                                            $terminal = [
                                                OrderModel::STATUS_DELIVERED,
                                                OrderModel::STATUS_COMPLETED,
                                                OrderModel::STATUS_CANCELLED,
                                                OrderModel::STATUS_RETURNED,
                                            ];
                                            $nextStatuses = [];
                                            if (!in_array($status, $terminal, true)) {
                                                foreach (OrderModel::statuses() as $key => $label) {
                                                    if ($key !== $status && OrderModel::isValidTransition($status, $key, $pm)) {
                                                        $nextStatuses[$key] = $label;
                                                    }
                                                }
                                            }
                                        ?>
                                        <div class="d-flex flex-column gap-2 align-items-center">
                                            <!-- Status Badge - Fixed Width & Centered -->
                                            <?php if (empty($nextStatuses)): ?>
                                            <span class="badge bg-<?= OrderModel::statusBadge($status) ?> py-2 d-block" style="width: 210px;">
                                                <?= OrderModel::statusLabel($status) ?>
                                            </span>
                                            <?php endif; ?>

                                            <!-- Return Status Badge -->
                                            <?php if ($ret): ?>
                                                <div class="d-flex flex-wrap gap-1 justify-content-center" style="width: 210px;">
                                                    <span class="badge bg-warning text-dark px-2 py-1">
                                                        Trả: <?= htmlspecialchars(ReturnRequestModel::statusLabel($ret['status'])) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($status === OrderModel::STATUS_CANCEL_REQUEST): ?>
                                                <form method="POST" action="<?= BASE_URL ?>?action=admin-order-approve-cancel" class="mb-0">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger w-100">Xác Nhận Hủy</button>
                                                </form>
                                                <?php if (!empty($order['cancel_reason'])): ?>
                                                    <div class="mt-2 small text-danger">
                                                        <i class="bi bi-exclamation-octagon"></i>
                                                        <?= htmlspecialchars($order['cancel_reason']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($status === OrderModel::STATUS_CANCELLED && !empty($order['cancel_reason'])): ?>
                                                <div class="mt-2 small text-danger">
                                                    <i class="bi bi-x-octagon"></i>
                                                    Lý do hủy: <?= htmlspecialchars($order['cancel_reason']) ?>
                                                </div>
                                            <?php else: ?>
                                                <?php
                                                    // Chỉ hiển thị điều khiển khi đơn chưa ở trạng thái cuối
                                                    // không cho thao tác với delivered/completed/cancelled/returned
                                                    $terminal = [
                                                        OrderModel::STATUS_DELIVERED,
                                                        OrderModel::STATUS_COMPLETED,
                                                        OrderModel::STATUS_CANCELLED,
                                                        OrderModel::STATUS_RETURNED,
                                                    ];
                                                    $nextStatuses = [];
                                                    if (!in_array($status, $terminal, true)) {
                                                        $allStatuses = OrderModel::statuses();
                                                        foreach ($allStatuses as $key => $label) {
                                                            if ($key !== $status && OrderModel::isValidTransition($status, $key, $pm)) {
                                                                $nextStatuses[$key] = $label;
                                                            }
                                                        }
                                                    }
                                                ?>
                                                <?php if (!empty($nextStatuses)): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>?action=admin-order-update" class="mb-0 status-dropdown-form">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <input type="hidden" name="status" value="">
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-<?= OrderModel::statusBadge($status) ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:210px;font-size:0.8rem;">
                                                                <?= OrderModel::statusLabel($status) ?>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <?php foreach ($nextStatuses as $key => $label): ?>
                                                                    <li><a class="dropdown-item status-option" href="#" data-value="<?= $key ?>"><?= $label ?></a></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    </form>
                                                    <script>
                                                        document.querySelectorAll('.status-dropdown-form').forEach(function(form) {
                                                            form.querySelectorAll('.status-option').forEach(function(link) {
                                                                link.addEventListener('click', function(e) {
                                                                    e.preventDefault();
                                                                    var val = this.getAttribute('data-value');
                                                                    if (val) {
                                                                        form.querySelector('input[name=status]').value = val;
                                                                        form.submit();
                                                                    }
                                                                });
                                                            });
                                                        });
                                                    </script>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            $isPaid = 
                                                // Banking: đã thanh toán online
                                                (($order['payment_method'] ?? '') === 'banking' && in_array($status, [
                                                    OrderModel::STATUS_PAID,
                                                    OrderModel::STATUS_PENDING,
                                                    OrderModel::STATUS_TO_SHIP,
                                                    OrderModel::STATUS_DELIVERED,
                                                    OrderModel::STATUS_COMPLETED,
                                                ], true))
                                                ||
                                                // Đơn hoàn thành = đã thanh toán (kể cả COD)
                                                $status === OrderModel::STATUS_COMPLETED;
                                        ?>
                                        <?php if ($status === OrderModel::STATUS_UNPAID): ?>
                                            <span class="badge bg-<?= OrderModel::statusBadge($status) ?> px-3 py-2">
                                                <i class="bi bi-clock me-1"></i><?= OrderModel::statusLabel($status) ?>
                                            </span>
                                        <?php elseif ($status === OrderModel::STATUS_PAYMENT_FAILED): ?>
                                            <span class="badge bg-<?= OrderModel::statusBadge($status) ?> px-3 py-2">
                                                <i class="bi bi-x-circle me-1"></i><?= OrderModel::statusLabel($status) ?>
                                            </span>
                                        <?php elseif ($isPaid): ?>
                                            <span class="badge bg-success px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>Đã thanh toán
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>?action=admin-order-detail&id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary">Xem</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

