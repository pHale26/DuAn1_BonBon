<section class="admin-contacts-page">
    <div class="container">
        <div class="admin-page-header mb-4">
            <div class="title-wrap">
                <p class="text-uppercase mb-1 small">Bảng điều khiển</p>
                <h2 class="fw-bold mb-0">Quản lý liên hệ</h2>
            </div>
            <div class="admin-page-actions">
                <a href="<?= BASE_URL ?>" class="btn btn-light-soft">Xem cửa hàng</a>
            </div>
        </div>

        <!-- Thống kê nhanh -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-envelope-exclamation text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Chưa đọc</h6>
                                <h3 class="mb-0"><?= $unreadCount ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-envelope-check text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Đã đọc</h6>
                                <h3 class="mb-0"><?= count(array_filter($contacts ?? [], fn($c) => $c['status'] === 'read')) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-envelope-heart text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Đã phản hồi</h6>
                                <h3 class="mb-0"><?= count(array_filter($contacts ?? [], fn($c) => $c['status'] === 'replied')) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form tìm kiếm và lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= BASE_URL ?>">
                    <input type="hidden" name="action" value="admin-contacts">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small text-uppercase fw-bold">Tìm kiếm</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" 
                                       name="keyword" 
                                       class="form-control" 
                                       placeholder="Tên, email, số điện thoại, nội dung..." 
                                       value="<?= htmlspecialchars($keyword ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-uppercase fw-bold">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Chưa đọc</option>
                                <option value="read" <?= ($status ?? '') === 'read' ? 'selected' : '' ?>>Đã đọc</option>
                                <option value="replied" <?= ($status ?? '') === 'replied' ? 'selected' : '' ?>>Đã phản hồi</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase fw-bold">Từ ngày</label>
                            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search"></i> Tìm
                                </button>
                                <a href="<?= BASE_URL ?>?action=admin-contacts" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Người gửi</th>
                            <th>Thông tin liên hệ</th>
                            <th>Chủ đề</th>
                            <th>Nội dung</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Không có liên hệ nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $contact): ?>
                                <tr class="<?= $contact['status'] === 'pending' ? 'table-warning' : '' ?>">
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($contact['name']) ?></div>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($contact['email']) ?></div>
                                        <?php if ($contact['phone']): ?>
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($contact['phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $subjectMap = [
                                            'product' => 'Câu hỏi về sản phẩm',
                                            'order' => 'Câu hỏi về đơn hàng',
                                            'return' => 'Đổi trả sản phẩm',
                                            'feedback' => 'Góp ý',
                                            'other' => 'Khác',
                                        ];
                                        $subjectLabel = $subjectMap[$contact['subject']] ?? ($contact['subject'] ?: 'Không có');
                                        ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($subjectLabel) ?></span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($contact['message']) ?>">
                                            <?= htmlspecialchars(mb_substr($contact['message'], 0, 100)) ?><?= mb_strlen($contact['message']) > 100 ? '...' : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadges = [
                                            'pending' => ['bg' => 'warning', 'text' => 'Chưa đọc'],
                                            'read' => ['bg' => 'info', 'text' => 'Đã đọc'],
                                            'replied' => ['bg' => 'success', 'text' => 'Đã phản hồi'],
                                        ];
                                        $statusInfo = $statusBadges[$contact['status']] ?? ['bg' => 'secondary', 'text' => $contact['status']];
                                        ?>
                                        <span class="badge bg-<?= $statusInfo['bg'] ?>"><?= $statusInfo['text'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>?action=admin-contact-show&id=<?= $contact['contact_id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Xem
                                            </a>
                                            <a href="<?= BASE_URL ?>?action=admin-contact-delete&id=<?= $contact['contact_id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Bạn có chắc muốn xóa liên hệ này?')">
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
</section>


