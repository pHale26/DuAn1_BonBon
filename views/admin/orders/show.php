<div class="admin-page-header mb-4">
    <div>
        <p class="text-uppercase text-muted mb-1 small">Đơn hàng</p>
        <h2 class="fw-bold mb-0">Chi tiết đơn #<?= htmlspecialchars($order['order_code'] ?? $order['id']) ?></h2>
        <?php if (!empty($order['created_at'])): ?>
            <div class="text-muted small">Đặt lúc <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
        <?php endif; ?>
    </div>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-orders" class="btn btn-light-soft">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

<!-- Thông tin khách hàng ở trên đầu -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Khách hàng</h5>
        <div class="row">
            <div class="col-md-6 mb-2">
                <strong>Tên:</strong> <?= htmlspecialchars($order['fullname'] ?? 'N/A') ?>
            </div>
            <div class="col-md-6 mb-2">
                <strong>Điện thoại:</strong> <?= htmlspecialchars($order['phone'] ?? 'N/A') ?>
            </div>
            <div class="col-md-6 mb-2">
                <strong>Email:</strong> <?= htmlspecialchars($order['email'] ?? 'N/A') ?>
            </div>
            <div class="col-md-12 mb-0">
                <strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address'] ?? '') ?>
                <div class="text-muted small"><?= htmlspecialchars(($order['ward'] ?? '') . ', ' . ($order['district'] ?? '') . ', ' . ($order['city'] ?? '')) ?></div>
            </div>
        </div>
        <?php if (!empty($order['cancel_reason'])): ?>
            <div class="alert alert-danger mt-3 mb-0">
                <strong>Lý do hủy:</strong>
                <div class="mb-0"><?= nl2br(htmlspecialchars($order['cancel_reason'])) ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($returnRequest)): ?>
            <div class="mt-3 p-3 bg-light border rounded">
                <div class="fw-semibold mb-2">Yêu cầu trả hàng: <?= htmlspecialchars(ReturnRequestModel::statusLabel($returnRequest['status'])) ?></div>
                <?php if (!empty($returnRequest['reason'])): ?>
                    <div class="small text-muted mb-1">Lý do: <?= htmlspecialchars($returnRequest['reason']) ?></div>
                <?php endif; ?>
                <?php if (!empty($returnRequest['shipping_code'])): ?>
                    <div class="small mb-1">Mã vận chuyển: <?= htmlspecialchars($returnRequest['shipping_code']) ?></div>
                <?php endif; ?>
                <?php if (!empty($returnRequest['images'])): ?>
                    <div class="small mt-2 mb-1">Minh chứng:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($returnRequest['images'] as $img): ?>
                            <a href="<?= htmlspecialchars($img) ?>" target="_blank" class="badge bg-secondary text-decoration-none"><?= basename($img) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($returnRequest['reject_reason'])): ?>
                    <div class="small text-danger mt-2">Lý do từ chối: <?= nl2br(htmlspecialchars($returnRequest['reject_reason'])) ?></div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php if ($returnRequest['status'] === 'requested'): ?>
                        <form method="POST" action="<?= BASE_URL ?>?action=admin-return-approve">
                            <input type="hidden" name="return_id" value="<?= $returnRequest['id'] ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button class="btn btn-sm btn-primary">Duyệt</button>
                        </form>
                        <form method="POST" action="<?= BASE_URL ?>?action=admin-return-reject">
                            <input type="hidden" name="return_id" value="<?= $returnRequest['id'] ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <div class="mb-2">
                                <textarea name="reject_reason" class="form-control form-control-sm" rows="2" placeholder="Lý do từ chối" required></textarea>
                            </div>
                            <button class="btn btn-sm btn-outline-danger w-100">Từ chối</button>
                        </form>
                    <?php elseif ($returnRequest['status'] === 'shipping'): ?>
                        <form method="POST" action="<?= BASE_URL ?>?action=admin-return-receive">
                            <input type="hidden" name="return_id" value="<?= $returnRequest['id'] ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button class="btn btn-sm btn-dark">Xác nhận đã nhận hàng</button>
                        </form>
                    <?php elseif ($returnRequest['status'] === 'received'): ?>
                        <form method="POST" action="<?= BASE_URL ?>?action=admin-return-refund">
                            <input type="hidden" name="return_id" value="<?= $returnRequest['id'] ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button class="btn btn-sm btn-success">Đánh dấu hoàn tiền</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Phần sản phẩm -->
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                    // hiển thị form cho phép thay đổi trạng thái trên trang chi tiết
                    $currentStatus = $order['status'];
                    $pm = strtolower($order['payment_method'] ?? 'cod');
                    $nextStatuses = [];
                    foreach (OrderModel::statuses() as $key => $label) {
                        if ($key !== $currentStatus && OrderModel::isValidTransition($currentStatus, $key, $pm)) {
                            $nextStatuses[$key] = $label;
                        }
                    }
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Sản phẩm</h5>
                    <div class="text-end">
                        <div class="mb-1">
                            <?php if (empty($nextStatuses)): ?>
                            <span class="badge bg-<?= OrderModel::statusBadge($order['status']) ?> px-3 py-2">
                                <?= OrderModel::statusLabel($order['status']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="small">
                            <strong>Tổng tiền:</strong> <?= number_format($order['total_amount'] ?? 0, 0, ',', '.') ?> đ
                            <?php if (!empty($order['coupon_code'])): ?>
                                <br><span class="text-muted">Mã giảm giá: <?= htmlspecialchars($order['coupon_code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                    // return to default if finalized
                    $terminal = [
                        OrderModel::STATUS_DELIVERED,
                        OrderModel::STATUS_COMPLETED,
                        OrderModel::STATUS_CANCELLED,
                        OrderModel::STATUS_RETURNED,
                    ];
                    if (!in_array($order['status'], $terminal, true)):
                ?>
                    <?php if (!empty($nextStatuses)): ?>
                        <form method="POST" action="<?= BASE_URL ?>?action=admin-order-update" class="mb-4 status-dropdown-form">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="status" value="">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <label class="form-label small mb-1">Cập nhật trạng thái</label>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-<?= OrderModel::statusBadge($order['status']) ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:210px;font-size:0.8rem;">
                                        <?= OrderModel::statusLabel($order['status']) ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($nextStatuses as $key => $label): ?>
                                            <li><a class="dropdown-item status-option" href="#" data-value="<?= $key ?>"><?= $label ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
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
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Thuộc tính</th>
                                <th class="text-center">SL</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] ?? [] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['image_url'])): ?>
                                                <img src="<?= htmlspecialchars($item['image_url']) ?>" class="me-2" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                            <?php else: ?>
                                                <div class="me-2 placeholder-60-box bg-light text-muted d-flex align-items-center justify-content-center" style="width:60px;height:60px;border-radius:6px;">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($item['product_name'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small">
                                        <div><strong>Size:</strong> <?= htmlspecialchars($item['variant_size'] ?? '-') ?></div>
                                        <div><strong>Màu:</strong> <?= htmlspecialchars($item['variant_color'] ?? '-') ?></div>
                                    </td>
                                    <td class="text-center"><?= (int)($item['quantity'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge bg-<?= OrderModel::statusBadge($order['status']) ?>">
                                            <?= OrderModel::statusLabel($order['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-semibold"><?= number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 0, ',', '.') ?> đ</div>
                                        <div class="text-muted small"><?= number_format($item['unit_price'] ?? 0, 0, ',', '.') ?> đ/SP</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

