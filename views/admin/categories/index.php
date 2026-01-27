<div class="admin-page-header">
    <h1>Quản lý danh mục</h1>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-category-create" class="btn btn-light-soft">
            <i class="bi bi-plus-lg"></i>
            Thêm danh mục mới
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-3" method="GET" action="<?= BASE_URL ?>">
            <input type="hidden" name="action" value="admin-categories">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-uppercase">Tìm danh mục</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="keyword" class="form-control" placeholder="Tên danh mục..." value="<?= htmlspecialchars($searchKeyword ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>?action=admin-categories"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="admin-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Mô tả</th>
                <th>Hình ảnh</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox empty-icon-lg"></i>
                        <div>Chưa có danh mục nào</div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($category['category_id']) ?></strong></td>
                        <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                        <td><?= htmlspecialchars($category['description'] ?? '') ?: '<span class="text-muted">-</span>' ?></td>
                        <td>
                            <?php if (!empty($category['image_url'])): ?>
                                <img src="<?= htmlspecialchars($category['image_url']) ?>" alt="<?= htmlspecialchars($category['category_name']) ?>" class="category-image">
                            <?php else: ?>
                                <span class="no-image">Không có</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge active">Hoạt động</span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?= BASE_URL ?>?action=admin-category-edit&id=<?= $category['category_id'] ?>" class="btn-edit" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>?action=admin-category-delete" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                                    <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                                    <button type="submit" class="btn-delete" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>




