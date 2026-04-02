

<section class="stats-page">
    <div class="container-fluid">
        <div class="stats-header">
            <div>
                <h1>Thống kê</h1>
                <p>Xem tổng quan về hoạt động của hệ thống</p>
            </div>
            <a href="<?= BASE_URL ?>?action=admin-users" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Quay lại quản lý người dùng
            </a>
        </div>

        <!-- Overview Cards -->
        <div class="overview-grid">
            <div class="overview-card">
                <div class="overview-card-header">
                    <p class="overview-card-title">Tổng đơn hàng</p>
                    <span class="overview-card-change <?= ($stats['order_change'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <i class="bi bi-<?= ($stats['order_change'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= number_format(abs($stats['order_change'] ?? 0), 1) ?>%
                    </span>
                </div>
                <div class="overview-card-value"><?= number_format($stats['total_orders'] ?? 0) ?></div>
            </div>

            <div class="overview-card">
                <div class="overview-card-header">
                    <p class="overview-card-title">Tổng doanh thu</p>
                    <span class="overview-card-change <?= ($stats['revenue_change'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <i class="bi bi-<?= ($stats['revenue_change'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= number_format(abs($stats['revenue_change'] ?? 0), 1) ?>%
                    </span>
                </div>
                <div class="overview-card-value"><?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?>₫</div>
            </div>

            <div class="overview-card">
                <div class="overview-card-header">
                    <p class="overview-card-title">Tổng người dùng</p>
                    <span class="overview-card-change <?= ($stats['user_change'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <i class="bi bi-<?= ($stats['user_change'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= number_format(abs($stats['user_change'] ?? 0), 1) ?>%
                    </span>
                </div>
                <div class="overview-card-value"><?= number_format($stats['total_users'] ?? 0) ?></div>
            </div>

            <div class="overview-card">
                <div class="overview-card-header">
                    <p class="overview-card-title">Sản phẩm bán chạy</p>
                    <span class="overview-card-change <?= ($stats['product_change'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <i class="bi bi-<?= ($stats['product_change'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= number_format(abs($stats['product_change'] ?? 0), 1) ?>%
                    </span>
                </div>
                <div class="overview-card-value fs-125"><?= htmlspecialchars($stats['best_selling_product'] ?? 'Chưa có') ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">Doanh thu theo tháng</h3>
                    <div>
                        <div class="chart-card-value"><?= number_format($stats['monthly_revenue'] ?? 0, 0, ',', '.') ?>₫</div>
                        <span class="overview-card-change positive small">
                            <i class="bi bi-arrow-up"></i> 1.5%
                        </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">Sản phẩm</h3>
                    <div>
                        <div class="chart-card-value"><?= count($stats['product_stats'] ?? []) ?> Sản Phẩm</div>
                        <span class="overview-card-change positive small">
                            <i class="bi bi-arrow-up"></i> 1.5%
                        </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="productChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="orders-table-card">
            <div class="orders-table-header">
                <h2 class="orders-table-title">Đơn hàng của tôi</h2>
                <input type="text" class="search-box" placeholder="Tìm kiếm đơn hàng...">
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Tên khách hàng</th>
                            <th>Ngày</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['recent_orders'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Chưa có đơn hàng nào
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($stats['recent_orders'], 0, 6) as $order): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['order_code'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['fullname'] ?? 'N/A') ?></td>
                                    <td><?= !empty($order['created_at']) ? date('Y-m-d', strtotime($order['created_at'])) : date('Y-m-d') ?></td>
                                    <td><?= number_format($order['total_amount'] ?? 0, 0, ',', '.') ?>₫</td>
                                    <td>
                                        <?php
                                        $status = $order['status'] ?? '';
                                        $statusLabel = OrderModel::statusLabel($status);
                                        $statusBadge = OrderModel::statusBadge($status);
                                        $badgeClass = match($statusBadge) {
                                            'success' => 'success',
                                            'danger' => 'danger',
                                            'warning' => 'warning',
                                            'info' => 'info',
                                            default => 'info'
                                        };
                                        ?>
                                        <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>?action=order-detail&id=<?= $order['id'] ?? ($order['order_id'] ?? '') ?>" class="btn-view">Xem chi tiết</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <div class="pagination-info">Showing 1-6 of <?= count($stats['recent_orders'] ?? []) ?></div>
                <div class="pagination-controls">
                    <button class="pagination-btn">Previous</button>
                    <button class="pagination-btn active">1</button>
                    <button class="pagination-btn">2</button>
                    <button class="pagination-btn">3</button>
                    <button class="pagination-btn">Next</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    const revenueData = <?= json_encode(array_column($stats['revenue_by_month'] ?? [], 'revenue')) ?>;
    const revenueLabels = <?= json_encode(array_map(function($m) { return date('M', strtotime($m['month'] . '-01')); }, $stats['revenue_by_month'] ?? [])) ?>;

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Doanh thu',
                data: revenueData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                }
            }
        }
    });

    // Product Chart
    const productCtx = document.getElementById('productChart');
    const productData = <?= json_encode(array_column($stats['product_stats'] ?? [], 'total_quantity')) ?>;
    const productLabels = <?= json_encode(array_column($stats['product_stats'] ?? [], 'product_name')) ?>;

    new Chart(productCtx, {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Số lượng',
                data: productData,
                backgroundColor: productData.map((_, i) => i === productData.length - 1 ? '#3b82f6' : '#e2e8f0'),
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

