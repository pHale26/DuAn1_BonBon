<div class="admin-page-header">
    <h2 class="d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-star"></i>
        <span>Quản lý đánh giá</span>
    </h2>
</div>

<!-- Form tìm kiếm và lọc -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= BASE_URL ?>" id="searchForm">
            <input type="hidden" name="action" value="admin-reviews">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-uppercase fw-bold">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="keyword" 
                               class="form-control" 
                               placeholder="Tên người dùng, sản phẩm hoặc bình luận..." 
                               value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                               id="searchKeyword">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-uppercase fw-bold">Số sao</label>
                    <select name="rating" class="form-select" id="ratingFilter">
                        <option value="">Tất cả</option>
                        <option value="5" <?= ($_GET['rating'] ?? '') === '5' ? 'selected' : '' ?>>5 sao</option>
                        <option value="4" <?= ($_GET['rating'] ?? '') === '4' ? 'selected' : '' ?>>4 sao</option>
                        <option value="3" <?= ($_GET['rating'] ?? '') === '3' ? 'selected' : '' ?>>3 sao</option>
                        <option value="2" <?= ($_GET['rating'] ?? '') === '2' ? 'selected' : '' ?>>2 sao</option>
                        <option value="1" <?= ($_GET['rating'] ?? '') === '1' ? 'selected' : '' ?>>1 sao</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-uppercase fw-bold">Trạng thái</label>
                    <select name="status" class="form-select" id="statusFilter">
                        <option value="">Tất cả</option>
                        <option value="visible" <?= ($_GET['status'] ?? '') === 'visible' ? 'selected' : '' ?>>Hiển thị</option>
                        <option value="hidden" <?= ($_GET['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Đã ẩn</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Tìm
                        </button>
                        <a href="<?= BASE_URL ?>?action=admin-reviews" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bảng đánh giá -->
<div class="card">
    <div class="card-body">
        <?php if (empty($reviews)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox empty-icon-lg"></i>
                <p class="text-muted mt-3">Chưa có đánh giá nào.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Sản phẩm</th>
                            <th>Đánh giá</th>
                            <th>Bình luận</th>
                            <th>Ảnh</th>
                            <th>Phản hồi</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): 
                            // Parse images từ JSON
                            $reviewImages = [];
                            if (!empty($review['images'])) {
                                $images = json_decode($review['images'], true);
                                $reviewImages = is_array($images) ? $images : [];
                            }
                        ?>
                            <tr class="<?= $review['is_hidden'] ? 'table-secondary' : '' ?>">
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($review['user_name'] ?? 'N/A') ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($review['user_email'] ?? '') ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($review['product_name'] ?? 'N/A') ?></strong>
                                        <?php if (!empty($review['variant_size']) || !empty($review['variant_color'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php if (!empty($review['variant_size'])): ?>
                                                    Size: <?= htmlspecialchars($review['variant_size']) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($review['variant_color'])): ?>
                                                    <?= !empty($review['variant_size']) ? ', ' : '' ?>Màu: <?= htmlspecialchars($review['variant_color']) ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill text-warning' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1"><?= $review['rating'] ?>/5</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($review['comment'])): ?>
                                        <div class="comment-preview">
                                            <?= nl2br(htmlspecialchars(mb_substr($review['comment'], 0, 100))) ?>
                                            <?= mb_strlen($review['comment']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Không có bình luận</span>
                                    <?php endif; ?>
                                    <?php 
                                        $history = [];
                                        if (!empty($review['comment_history'])) {
                                            $decoded = json_decode($review['comment_history'], true);
                                            $history = is_array($decoded) ? $decoded : [];
                                        }
                                    ?>
                                    <?php if (!empty($history)): ?>
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1">Lịch sử sửa:</small>
                                            <ul class="list-unstyled mb-0 small text-muted">
                                                <?php foreach (array_reverse($history) as $h): ?>
                                                    <li>
                                                        <span class="fw-semibold"><?= htmlspecialchars($h['comment'] ?? '') ?></span>
                                                        <br>
                                                        <span><?= htmlspecialchars($h['edited_at'] ?? '') ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($reviewImages)): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach (array_slice($reviewImages, 0, 3) as $img): 
                                                $imgUrl = getProductImageUrl($img, false);
                                                $imgUrlOriginal = $img; // Giữ URL gốc cho link
                                            ?>
                                                <a href="<?= htmlspecialchars($imgUrlOriginal) ?>" target="_blank" class="review-image-thumbnail">
                                                    <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                                         alt="Review image" 
                                                         class="img-thumbnail review-thumb-sm"
                                                         onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                                                </a>
                                            <?php endforeach; ?>
                                            <?php if (count($reviewImages) > 3): ?>
                                                <span class="badge bg-secondary" title="Còn <?= count($reviewImages) - 3 ?> ảnh">+<?= count($reviewImages) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($review['reply'])): ?>
                                        <div class="reply-preview border-start border-3 border-primary ps-2">
                                            <small class="text-muted d-block mb-1">
                                                <i class="bi bi-reply-fill"></i> <strong>Phản hồi từ cửa hàng:</strong>
                                            </small>
                                            <div class="text-dark">
                                                <?= nl2br(htmlspecialchars(mb_substr($review['reply'], 0, 150))) ?>
                                                <?= mb_strlen($review['reply']) > 150 ? '...' : '' ?>
                                            </div>
                                            <button type="button" 
                                                    class="btn btn-sm btn-link p-0 mt-1"
                                                    onclick="openReplyModal(<?= $review['review_id'] ?>, '<?= htmlspecialchars(addslashes($review['reply'] ?? '')) ?>')">
                                                <i class="bi bi-pencil"></i> Sửa phản hồi
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Chưa phản hồi</span>
                                        <br>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary mt-1" 
                                                onclick="openReplyModal(<?= $review['review_id'] ?>, '')">
                                            <i class="bi bi-reply"></i> Phản hồi ngay
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($review['is_hidden']): ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Hiển thị</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a class="btn btn-outline-info"
                                           href="<?= BASE_URL ?>?action=admin-review-detail&id=<?= $review['review_id'] ?>"
                                           title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-primary" 
                                                onclick="openReplyModal(<?= $review['review_id'] ?>, '<?= htmlspecialchars(addslashes($review['reply'] ?? '')) ?>')"
                                                title="Phản hồi">
                                            <i class="bi bi-reply"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-<?= $review['is_hidden'] ? 'success' : 'warning' ?>" 
                                                onclick="toggleHidden(<?= $review['review_id'] ?>, <?= $review['is_hidden'] ? 'true' : 'false' ?>)"
                                                title="<?= $review['is_hidden'] ? 'Hiển thị' : 'Ẩn' ?>">
                                            <i class="bi bi-eye<?= $review['is_hidden'] ? '' : '-slash' ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Phản hồi -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-reply-fill me-2"></i>Phản hồi đánh giá
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <input type="hidden" id="replyReviewId" name="review_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-chat-left-text me-1"></i>Nội dung phản hồi
                        </label>
                        <textarea class="form-control" 
                                  id="replyContent" 
                                  name="reply" 
                                  rows="5" 
                                  placeholder="Nhập nội dung phản hồi cho khách hàng..." 
                                  required></textarea>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Phản hồi này sẽ hiển thị công khai dưới đánh giá của khách hàng.
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="submitReply()">
                    <i class="bi bi-send me-1"></i>Gửi phản hồi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết đánh giá -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>Chi tiết đánh giá
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <small class="text-muted">Người dùng</small>
                            <div id="detailUser"></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Sản phẩm</small>
                            <div id="detailProduct"></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Đánh giá</small>
                            <div id="detailRating"></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Trạng thái</small>
                            <div id="detailStatus"></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Thời gian</small>
                            <div id="detailTime"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted">Bình luận hiện tại</small>
                            <div class="border rounded p-2 bg-light" id="detailComment"></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Ảnh</small>
                            <div class="d-flex flex-wrap gap-2" id="detailImages"></div>
                        </div>
                        <div>
                            <small class="text-muted">Phản hồi của admin</small>
                            <div class="border rounded p-2 bg-light" id="detailReply"></div>
                        </div>
                    </div>
                </div>
                <hr>
                <div>
                    <small class="text-muted d-block mb-1">Lịch sử sửa bình luận</small>
                    <div id="detailHistory"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
let replyModal;

document.addEventListener('DOMContentLoaded', function() {
    replyModal = new bootstrap.Modal(document.getElementById('replyModal'));
});

function openReplyModal(reviewId, currentReply) {
    document.getElementById('replyReviewId').value = reviewId;
    document.getElementById('replyContent').value = currentReply || '';
    // Focus vào textarea khi mở modal
    replyModal.show();
    setTimeout(() => {
        document.getElementById('replyContent').focus();
    }, 300);
}

function submitReply() {
    const reviewId = document.getElementById('replyReviewId').value;
    const reply = document.getElementById('replyContent').value.trim();
    
    if (!reply) {
        alert('Vui lòng nhập nội dung phản hồi');
        return;
    }
    
    fetch('<?= BASE_URL ?>?action=admin-review-reply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: parseInt(reviewId),
            reply: reply
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Phản hồi thành công!');
            replyModal.hide();
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}

function toggleHidden(reviewId, isCurrentlyHidden) {
    const actionLabel = isCurrentlyHidden ? 'hiển thị' : 'ẩn';
    if (!confirm('Bạn có chắc chắn muốn ' + actionLabel + ' đánh giá này?')) {
        return;
    }
    
    fetch('<?= BASE_URL ?>?action=admin-review-toggle-hidden', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: parseInt(reviewId)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}
</script>

