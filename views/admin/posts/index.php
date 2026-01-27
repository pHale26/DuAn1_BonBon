<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Quản lý bài viết</h1>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>?action=admin-post-trash" class="btn btn-outline-secondary">
                <i class="bi bi-trash me-2"></i>Thùng rác
            </a>
            <a href="<?= BASE_URL ?>?action=admin-post-create" class="btn btn-dark">
                <i class="bi bi-plus-lg me-2"></i>Thêm bài viết mới
            </a>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="action" value="admin-posts">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="keyword" 
                               value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" 
                               placeholder="Tìm kiếm theo tiêu đề...">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width: 5%">ID</th>
                            <th style="width: 100px">Hình ảnh</th>
                            <th style="width: 30%">Tiêu đề</th>
                            <th>Nổi bật</th>
                            <th>Tác giả</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Không tìm thấy bài viết nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td class="ps-4">#<?= $post['post_id'] ?></td>
                                    <td>
                                        <?php if (!empty($post['thumbnail'])): ?>
                                            <img src="<?= htmlspecialchars(getProductImageUrl($post['thumbnail'])) ?>" 
                                                 alt="" class="rounded" 
                                                 style="width: 60px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" 
                                                 style="width: 60px; height: 40px;">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-truncate" style="max-width: 300px;">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($post['is_featured'])): ?>
                                            <i class="bi bi-star-fill text-warning" title="Nổi bật"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($post['author_name'] ?? 'Ẩn danh') ?></td>
                                    <td>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                                Published
                                            </span>
                                        <?php elseif ($post['status'] === 'hidden'): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">
                                                Hidden
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">
                                                Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="<?= BASE_URL ?>?action=admin-post-edit&id=<?= $post['post_id'] ?>" 
                                               class="btn btn-outline-secondary btn-sm"
                                               title="Chỉnh sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>?action=admin-post-delete&id=<?= $post['post_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này không?');"
                                               title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
