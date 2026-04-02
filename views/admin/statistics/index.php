<?php
require_once PATH_MODEL . 'OrderModel.php';
?>

<div class="statistics-page">
    <!-- Header -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1">Dashboard Tổng Quan</h1>
            <p class="page-subtitle mb-0">Thống kê realtime và phân tích dữ liệu</p>
        </div>
    </div>

    <!-- Bộ lọc thời gian -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="admin-statistics">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Preset</label>
                    <select name="preset" id="presetSelect" class="form-select" onchange="handlePresetChange(this)">
                        <option value="today" <?= ($filterPreset ?? '') === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                        <option value="yesterday" <?= ($filterPreset ?? '') === 'yesterday' ? 'selected' : '' ?>>Hôm qua</option>
                        <option value="7d" <?= ($filterPreset ?? '') === '7d' ? 'selected' : '' ?>>7 ngày gần nhất</option>
                        <option value="this_month" <?= ($filterPreset ?? '') === 'this_month' ? 'selected' : '' ?>>Tháng này</option>
                        <option value="last_month" <?= ($filterPreset ?? '') === 'last_month' ? 'selected' : '' ?>>Tháng trước</option>
                        <option value="quarter" <?= ($filterPreset ?? '') === 'quarter' ? 'selected' : '' ?>>Quý hiện tại</option>
                        <option value="custom" <?= ($filterPreset ?? '') === 'custom' ? 'selected' : '' ?>>Tùy chọn</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Từ ngày</label>
                    <input type="date" name="from_date" id="fromDate" class="form-control" value="<?= htmlspecialchars($filterFrom ?? '') ?>" onchange="document.getElementById('presetSelect').value='custom';">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Đến ngày</label>
                    <input type="date" name="to_date" id="toDate" class="form-control" value="<?= htmlspecialchars($filterTo ?? '') ?>" onchange="document.getElementById('presetSelect').value='custom';">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
                </div>
                <div class="col-md-2">
                    <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>?action=admin-statistics"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards - Chỉ số chính -->
    <div class="row g-3 mb-4">
        <!-- Tổng doanh thu -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Tổng Doanh Thu</p>
                            <h3 class="mb-0 fw-bold text-primary"><?= number_format($rangeStats['revenue'] ?? 0, 0, ',', '.') ?>₫</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-2 rounded">
                            <i class="bi bi-currency-dollar text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tổng đơn hàng -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Tổng Đơn Hàng</p>
                            <h3 class="mb-0 fw-bold text-success"><?= number_format($rangeStats['orders'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-2 rounded">
                            <i class="bi bi-cart-check text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

                        <!-- Đơn thành công -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Đơn Thành Công</p>
                            <h3 class="mb-0 fw-bold text-info"><?= number_format(($statusCounts['delivered'] ?? 0) + ($statusCounts['completed'] ?? 0)) ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-2 rounded">
                            <i class="bi bi-check-circle text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đơn hủy -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Đơn Hủy</p>
                            <h3 class="mb-0 fw-bold text-danger"><?= number_format($statusCounts['cancelled'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-2 rounded">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Người dùng mới -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Người Dùng Mới</p>
                            <h3 class="mb-0 fw-bold text-warning"><?= number_format($newUsersCount ?? 0) ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-2 rounded">
                            <i class="bi bi-person-plus text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm bán ra -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Sản Phẩm Bán Ra</p>
                            <h3 class="mb-0 fw-bold text-secondary"><?= number_format($productsSold ?? 0) ?></h3>
                        </div>
                        <div class="bg-secondary bg-opacity-10 p-2 rounded">
                            <i class="bi bi-box-seam text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đơn hoàn tiền -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Đơn Hoàn Tiền</p>
                            <h3 class="mb-0 fw-bold text-dark"><?= number_format($returnedCount ?? 0) ?></h3>
                        </div>
                        <div class="bg-dark bg-opacity-10 p-2 rounded">
                            <i class="bi bi-arrow-return-left text-dark fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lợi nhuận ròng -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Lợi Nhuận Ròng (40%)</p>
                            <h3 class="mb-0 fw-bold text-success"><?= number_format($netProfit ?? 0, 0, ',', '.') ?>₫</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-2 rounded">
                            <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="row g-3 mb-4">
        <!-- Doanh thu theo thời gian -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Doanh Thu Theo Ngày</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Đơn hàng theo ngày -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Đơn Hàng Theo Ngày</h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Tỉ lệ thanh toán -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Tỉ Lệ Thanh Toán</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Top sản phẩm -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Sản Phẩm Bán Chạy</h5>
                </div>
                <div class="card-body">
                    <canvas id="topProductChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Biểu đồ hoàn tiền - đơn hủy -->
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Hoàn Tiền & Đơn Hủy Theo Ngày</h5>
                </div>
                <div class="card-body">
                    <canvas id="returnCancelChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê chi tiết -->
    <div class="row g-3 mb-4">
        <!-- Thống kê đơn hàng -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cart"></i> Thống Kê Đơn Hàng</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Tổng số đơn:</td>
                            <td class="fw-bold text-end"><?= number_format($rangeStats['orders'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Đơn hoàn tất:</td>
                            <td class="fw-bold text-end text-success"><?= number_format(($statusCounts['delivered'] ?? 0) + ($statusCounts['completed'] ?? 0)) ?></td>
                        </tr>
                        <tr>
                            <td>Đơn hủy:</td>
                            <td class="fw-bold text-end text-danger"><?= number_format($statusCounts['cancelled'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Đơn đang giao:</td>
                            <td class="fw-bold text-end text-info"><?= number_format($statusCounts['to_ship'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Đơn hoàn tiền:</td>
                            <td class="fw-bold text-end text-warning"><?= number_format($returnedCount ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Giá trị đơn TB (AOV):</td>
                            <td class="fw-bold text-end text-primary"><?= number_format($orderMetrics['aov'] ?? 0, 0, ',', '.') ?>₫</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Thống kê khách hàng -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Thống Kê Khách Hàng</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Tổng khách hàng:</td>
                            <td class="fw-bold text-end"><?= number_format($totalCustomers ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Khách mới (kỳ này):</td>
                            <td class="fw-bold text-end text-success"><?= number_format($newUsersCount ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Tỷ lệ quay lại:</td>
                            <td class="fw-bold text-end text-info"><?= number_format($returningRate ?? 0, 1) ?>%</td>
                        </tr>
                        <tr>
                            <td>Khách thường:</td>
                            <td class="fw-bold text-end"><?= number_format($customerSegmentation['customer'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Khách VIP:</td>
                            <td class="fw-bold text-end text-warning"><?= number_format($customerSegmentation['vip'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Khách thân thiết:</td>
                            <td class="fw-bold text-end text-primary"><?= number_format($customerSegmentation['loyal'] ?? 0) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top khách hàng & Doanh thu theo phương thức -->
    <div class="row g-3 mb-4">
        <!-- Top 5 khách hàng -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-star"></i> Top 5 Khách Hàng Chi Tiêu Nhiều</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên</th>
                                <th>Đơn</th>
                                <th class="text-end">Tổng chi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCustomers)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                            <?php else: ?>
                                <?php foreach ($topCustomers as $idx => $customer): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><?= htmlspecialchars($customer['fullname'] ?? $customer['email'] ?? 'N/A') ?></td>
                                        <td><?= number_format($customer['orders'] ?? 0) ?></td>
                                        <td class="text-end fw-bold"><?= number_format($customer['total_spent'] ?? 0, 0, ',', '.') ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Doanh thu theo phương thức thanh toán -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Doanh Thu Theo Phương Thức</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Phương thức</th>
                                <th class="text-center">Đơn</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($revenueByPayment)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                            <?php else: ?>
                                <?php foreach ($revenueByPayment as $payment): ?>
                                    <tr>
                                        <td class="text-uppercase fw-bold"><?= htmlspecialchars($payment['payment_method'] ?? 'N/A') ?></td>
                                        <td class="text-center"><?= number_format($payment['order_count'] ?? 0) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($payment['revenue'] ?? 0, 0, ',', '.') ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê sản phẩm -->
    <div class="row g-3 mb-4">
        <!-- Sản phẩm sắp hết hàng -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Sản Phẩm Sắp Hết Hàng</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Danh mục</th>
                                <th class="text-end">Tồn kho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lowStockProducts)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Tất cả sản phẩm đều đủ hàng</td></tr>
                            <?php else: ?>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                        <td class="text-end fw-bold text-danger"><?= number_format($product['stock'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sản phẩm bán chậm -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Top 5 Sản Phẩm Bán Chậm</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Đã bán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($slowSellingProducts)): ?>
                                <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                            <?php else: ?>
                                <?php foreach ($slowSellingProducts as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></td>
                                        <td class="text-end fw-bold"><?= number_format($product['qty'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Số lượng bán theo danh mục -->
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-grid"></i> Số Lượng Sản Phẩm Bán Theo Danh Mục</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th class="text-center">Số lượng bán</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($soldByCategory)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                            <?php else: ?>
                                <?php foreach ($soldByCategory as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['category_name'] ?? 'N/A') ?></td>
                                        <td class="text-center fw-bold"><?= number_format($cat['qty'] ?? 0) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($cat['revenue'] ?? 0, 0, ',', '.') ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê mã giảm giá -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-ticket-perforated"></i> Thống Kê Mã Giảm Giá</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Số lượt sử dụng:</td>
                            <td class="fw-bold text-end"><?= number_format($couponStats['usage_count'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Tổng tiền giảm:</td>
                            <td class="fw-bold text-end text-danger"><?= number_format($couponStats['total_discount'] ?? 0, 0, ',', '.') ?>₫</td>
                        </tr>
                        <tr>
                            <td>Số mã đã hết hạn:</td>
                            <td class="fw-bold text-end text-warning"><?= number_format($couponStats['expired_count'] ?? 0) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Mã Được Dùng Nhiều</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th class="text-center">Lượt dùng</th>
                                <th class="text-end">Tiền giảm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topUsedCoupons)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                            <?php else: ?>
                                <?php foreach ($topUsedCoupons as $coupon): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($coupon['code'] ?? 'N/A') ?></td>
                                        <td class="text-center"><?= number_format($coupon['usage_count'] ?? 0) ?></td>
                                        <td class="text-end text-danger fw-bold"><?= number_format($coupon['total_discount'] ?? 0, 0, ',', '.') ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Xử lý preset change
    function handlePresetChange(select) {
        const preset = select.value;
        const fromDateInput = document.getElementById('fromDate');
        const toDateInput = document.getElementById('toDate');
        const now = new Date();
        
        let fromDate = '';
        let toDate = '';
        
        switch(preset) {
            case 'today':
                fromDate = toDate = formatDate(now);
                break;
            case 'yesterday':
                const yesterday = new Date(now);
                yesterday.setDate(yesterday.getDate() - 1);
                fromDate = toDate = formatDate(yesterday);
                break;
            case '7d':
                toDate = formatDate(now);
                const sevenDaysAgo = new Date(now);
                sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 6);
                fromDate = formatDate(sevenDaysAgo);
                break;
            case 'this_month':
                fromDate = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                toDate = formatDate(lastDay);
                break;
            case 'last_month':
                const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                fromDate = formatDate(lastMonth);
                const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
                toDate = formatDate(lastDayLastMonth);
                break;
            case 'quarter':
                const month = now.getMonth() + 1;
                const quarter = Math.ceil(month / 3);
                const startMonth = (quarter - 1) * 3 + 1;
                fromDate = now.getFullYear() + '-' + String(startMonth).padStart(2, '0') + '-01';
                const quarterEnd = new Date(now.getFullYear(), startMonth + 2, 0);
                toDate = formatDate(quarterEnd);
                break;
            case 'custom':
                // Không thay đổi date, để user tự nhập
                return;
        }
        
        if (fromDate && toDate) {
            fromDateInput.value = fromDate;
            toDateInput.value = toDate;
            // Tự động submit form
            select.form.submit();
        }
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
</script>
<script>
    // Doanh thu theo ngày (Line Chart - Biểu đồ đường)
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const dailyRevenueData = <?= json_encode($dailyRevenue ?? []) ?>;
        
        // Xử lý labels và data
        const revenueLabels = dailyRevenueData.map(item => {
            try {
                const date = new Date(item.d + 'T00:00:00'); // Thêm time để tránh timezone issues
                return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
            } catch (e) {
                return item.d;
            }
        });
        const revenueData = dailyRevenueData.map(item => parseFloat(item.revenue) || 0);
        
        new Chart(revenueCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Doanh thu (₫)',
                    data: revenueData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#2563eb',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 3,
                    stepped: false,
                    spanGaps: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.85)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        callbacks: {
                            title: function(context) {
                                return 'Ngày: ' + context[0].label;
                            },
                            label: function(context) {
                                const value = context.parsed.y;
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M ₫';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K ₫';
                                }
                                return new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
                            },
                            font: {
                                size: 11,
                                family: 'Inter, sans-serif'
                            },
                            padding: 8
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 11,
                                family: 'Inter, sans-serif'
                            },
                            padding: 10
                        }
                    }
                },
                elements: {
                    line: {
                        borderJoinStyle: 'round',
                        borderCapStyle: 'round'
                    },
                    point: {
                        hoverRadius: 8
                    }
                }
            }
        });
    }

    // Đơn hàng theo ngày (Bar Chart)
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($dailyOrders ?? [], 'd')) ?>,
            datasets: [{
                label: 'Số đơn',
                data: <?= json_encode(array_map('intval', array_column($dailyOrders ?? [], 'orders'))) ?>,
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Tỉ lệ thanh toán (Pie Chart)
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_map('strtoupper', array_column($paymentBreakdown ?? [], 'payment_method'))) ?>,
            datasets: [{
                data: <?= json_encode(array_map('intval', array_column($paymentBreakdown ?? [], 'orders'))) ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Top sản phẩm (Horizontal Bar Chart)
    const topProductCtx = document.getElementById('topProductChart').getContext('2d');
    new Chart(topProductCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($topProducts ?? [], 'product_name')) ?>,
            datasets: [{
                label: 'Số lượng bán',
                data: <?= json_encode(array_map('intval', array_column($topProducts ?? [], 'qty'))) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { x: { beginAtZero: true } }
        }
    });

    // Hoàn tiền & Đơn hủy (Line Chart)
    const returnCancelCtx = document.getElementById('returnCancelChart').getContext('2d');
    new Chart(returnCancelCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($returnCancelChart ?? [], 'd')) ?>,
            datasets: [
                {
                    label: 'Hoàn tiền',
                    data: <?= json_encode(array_map('intval', array_column($returnCancelChart ?? [], 'returned'))) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Đơn hủy',
                    data: <?= json_encode(array_map('intval', array_column($returnCancelChart ?? [], 'cancelled'))) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
