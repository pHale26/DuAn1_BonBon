<div class="admin-page-header">
    <div class="title-wrap">
        <p class="text-uppercase mb-1 small">Bảng điều khiển</p>
        <h2 class="d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-people"></i>
            <span>Quản lý người dùng</span>
        </h2>
    </div>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-users" class="btn btn-light-soft">
            <i class="bi bi-arrow-clockwise"></i> Làm mới
        </a>
    </div>
</div>

<?php
$totalUsers = count($users ?? []);
$adminCount = 0;
$userCount = 0;
$verifiedCount = 0;

foreach ($users ?? [] as $user) {
    if (($user['role'] ?? 'customer') === 'admin') {
        $adminCount++;
    } else {
        $userCount++;
    }
    if (empty($user['session_token'])) {
        $verifiedCount++;
    }
}
?>

<div class="users-stats">
    <div class="stat-card total">
        <div class="stat-icon">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-label">Tổng người dùng</div>
    </div>
    <div class="stat-card admin">
        <div class="stat-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="stat-value"><?= $adminCount ?></div>
        <div class="stat-label">Quản trị viên</div>
    </div>
    <div class="stat-card user">
        <div class="stat-icon">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="stat-value"><?= $userCount ?></div>
        <div class="stat-label">Khách hàng</div>
    </div>
    <div class="stat-card verified">
        <div class="stat-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="stat-value"><?= $verifiedCount ?></div>
        <div class="stat-label">Đã xác thực</div>
    </div>
</div>

<form class="admin-filter-bar mt-2" method="GET" action="<?= BASE_URL ?>">
    <input type="hidden" name="action" value="admin-users">
    <div>
        <label class="form-label">Từ ngày</label>
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
    </div>
    <div>
        <label class="form-label">Đến ngày</label>
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
    </div>
    <div>
        <label class="form-label">Trạng thái</label>
        <select name="lock_status" class="form-select">
            <option value="">Tất cả</option>
            <option value="active" <?= ($lockStatus ?? '') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="locked" <?= ($lockStatus ?? '') === 'locked' ? 'selected' : '' ?>>Đã khóa</option>
        </select>
    </div>
    <div class="admin-filter-actions">
        <button class="btn btn-primary w-100" type="submit">
            <i class="bi bi-funnel"></i> Lọc
        </button>
        <a href="<?= BASE_URL ?>?action=admin-users" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
    </div>
</form>

<div class="admin-table">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Ngày tạo</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <div>Chưa có tài khoản nào</div>
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $currentUserId = $_SESSION['user']['id'] ?? null;
                foreach ($users as $user): 
                    $userId = $user['user_id'] ?? $user['id'] ?? 0;
                    $isCurrentUser = $currentUserId && (int)$userId === (int)$currentUserId;
                    $fullName = $user['full_name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    $initials = strtoupper(substr($fullName, 0, 1) . substr($fullName, strrpos($fullName, ' ') + 1, 1));
                    // Kiểm tra trạng thái khóa - xử lý cả trường hợp NULL hoặc chưa có cột
                    $isLocked = false;
                    if (isset($user['is_locked'])) {
                        $isLocked = (bool)$user['is_locked'];
                    } elseif (array_key_exists('is_locked', $user) && $user['is_locked'] !== null) {
                        $isLocked = (bool)$user['is_locked'];
                    }
                ?>
                    <tr>
                        <td><strong class="text-muted">#<?= htmlspecialchars($userId) ?></strong></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                        </td>
                        <td>
                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                        </td>
                        <td>
                            <span class="text-muted"><?= htmlspecialchars($user['phone'] ?? '-') ?></span>
                        </td>
                        <td>
                            <span class="text-muted"><?= date('d/m/Y H:i', strtotime($user['created_at'] ?? 'now')) ?></span>
                        </td>
                        <td>
                            <span class="role-badge <?= ($user['role'] ?? 'customer') === 'admin' ? 'admin' : 'user' ?>">
                                <i class="bi bi-<?= ($user['role'] ?? 'customer') === 'admin' ? 'shield-check' : 'person' ?>"></i>
                                <?= ($user['role'] ?? 'customer') === 'admin' ? 'Admin' : 'User' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isLocked): ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-lock-fill"></i> Đã khóa
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-unlock-fill"></i> Hoạt động
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isCurrentUser): ?>
                                <span class="current-user-badge">
                                    <i class="bi bi-check-circle"></i>
                                    Tài khoản hiện tại
                                </span>
                            <?php else: ?>
                                <form method="POST" action="<?= BASE_URL ?>?action=admin-user-toggle-lock" class="d-inline" onsubmit="return confirm('<?= $isLocked ? 'Bạn có chắc muốn mở khóa tài khoản này?' : 'Bạn có chắc muốn khóa tài khoản này?' ?>');">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                                    <button type="submit" class="btn <?= $isLocked ? 'btn-success' : 'btn-warning' ?> btn-sm px-1" title="<?= $isLocked ? 'Mở khóa tài khoản' : 'Khóa tài khoản' ?>">
                                        <i class="bi bi-<?= $isLocked ? 'unlock-fill' : 'lock-fill' ?>"></i>
                                        <?= $isLocked ? 'Mở khóa' : 'Khóa' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

