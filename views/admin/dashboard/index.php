<div class="admin-dashboard">
    <!-- Admin Panel Banner -->
    <div class="admin-banner">
        <div class="admin-banner-left">
            <div class="admin-banner-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <h1 class="admin-banner-title">Trung tâm quản trị</h1>
                <p class="admin-banner-desc">Trung tâm quản trị hệ thống - Quản lý tất cả các chức năng của website</p>
            </div>
        </div>
    </div>

    <!-- Statistical Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-number"><?= number_format($stats['total_users']) ?></p>
                    <p class="stat-label">Tổng người dùng</p>
                </div>
                <div class="stat-icon blue">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-number"><?= number_format($stats['total_orders']) ?></p>
                    <p class="stat-label">Tổng đơn hàng</p>
                </div>
                <div class="stat-icon green">
                    <i class="bi bi-cart"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-number"><?= number_format($stats['total_admins']) ?></p>
                    <p class="stat-label">Quản trị viên</p>
                </div>
                <div class="stat-icon red">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-number"><?= number_format($stats['total_customers']) ?></p>
                    <p class="stat-label">Khách hàng</p>
                </div>
                <div class="stat-icon orange">
                    <i class="bi bi-person"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Quick-Link Cards -->
    <div class="management-grid">
        <a href="<?= BASE_URL ?>?action=admin-users" class="management-card">
            <div class="management-card-header">
                <div class="management-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h2 class="management-title">Quản lý người dùng</h2>
            </div>
            <p class="management-desc">Xem, chỉnh sửa, xóa và phân quyền cho người dùng trong hệ thống</p>
        </a>

        <a href="<?= BASE_URL ?>?action=admin-orders" class="management-card">
            <div class="management-card-header">
                <div class="management-icon">
                    <i class="bi bi-cart"></i>
                </div>
                <h2 class="management-title">Quản lý đơn hàng</h2>
            </div>
            <p class="management-desc">Xem tất cả đơn hàng, cập nhật trạng thái và quản lý giao hàng</p>
        </a>
    </div>
</div>

