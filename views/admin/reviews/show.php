<div class="admin-page-header">
    <div>
        <h2 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-eye"></i> Chi tiết đánh giá
        </h2>
        <p class="text-muted mb-0">Xem bình luận và lịch sử chỉnh sửa</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-light-soft" href="<?= BASE_URL ?>?action=admin-reviews">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <small class="text-muted d-block">Người dùng</small>
                    <div class="fw-bold"><?= htmlspecialchars($review['user_name'] ?? 'N/A') ?></div>
                    <?php if (!empty($review['user_email'])): ?>
                        <div class="text-muted"><?= htmlspecialchars($review['user_email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Sản phẩm</small>
                    <div class="fw-bold"><?= htmlspecialchars($review['product_name'] ?? 'N/A') ?></div>
                    <?php
                        $variantParts = [];
                        if (!empty($review['variant_size'])) $variantParts[] = 'Size: ' . $review['variant_size'];
                        if (!empty($review['variant_color'])) $variantParts[] = 'Màu: ' . $review['variant_color'];
                    ?>
                    <?php if (!empty($variantParts)): ?>
                        <div class="text-muted"><?= htmlspecialchars(implode(', ', $variantParts)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Đánh giá</small>
                    <div class="d-flex align-items-center gap-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= ($review['rating'] ?? 0) ? '-fill text-warning' : '' ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-1"><?= (int)($review['rating'] ?? 0) ?>/5</span>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Trạng thái</small>
                    <?php if (!empty($review['is_hidden'])): ?>
                        <span class="badge bg-secondary">Đã ẩn</span>
                    <?php else: ?>
                        <span class="badge bg-success">Hiển thị</span>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Thời gian</small>
                    <div>
                        <?php if (!empty($review['created_at'])): ?>
                            <div>Tạo: <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($review['updated_at'])): ?>
                            <div>Cập nhật: <?= date('d/m/Y H:i', strtotime($review['updated_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <small class="text-muted d-block">Bình luận hiện tại</small>
                    <div class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars($review['comment'] ?? 'Không có bình luận')) ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Ảnh</small>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (empty($images)): ?>
                            <span class="text-muted">-</span>
                        <?php else: ?>
                            <?php foreach ($images as $idx => $img): ?>
                                <?php if ($idx >= 5) break; 
                                $imgUrl = getProductImageUrl($img, false);
                                $imgUrlOriginal = $img; // Giữ URL gốc cho link
                                ?>
                                <a href="<?= htmlspecialchars($imgUrlOriginal) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                         class="img-thumbnail review-thumb-sm" 
                                         alt="Review image <?= $idx + 1 ?>"
                                         onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                                </a>
                            <?php endforeach; ?>
                            <?php if (count($images) > 5): ?>
                                <span class="badge bg-secondary">+<?= count($images) - 5 ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Phản hồi của admin</small>
                    <div class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars($review['reply'] ?? 'Chưa phản hồi')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Lịch sử sửa đánh giá</h5>
        <?php if (empty($history)): ?>
            <p class="text-muted mb-0">Chưa có lịch sử sửa.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach (array_reverse($history) as $h): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <strong><?= isset($h['rating']) ? (int)$h['rating'] . '/5' : '' ?></strong>
                            <div class="text-muted small"><?= htmlspecialchars($h['edited_at'] ?? '') ?></div>
                        </div>
                        <?php if (!empty($h['comment'])): ?>
                            <div class="fw-semibold"><?= nl2br(htmlspecialchars($h['comment'])) ?></div>
                        <?php else: ?>
                            <div class="text-muted">Không có bình luận</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

