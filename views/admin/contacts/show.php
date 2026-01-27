<section class="admin-contact-detail-page">
    <div class="container">
        <div class="admin-page-header mb-4">
            <div class="title-wrap">
                <p class="text-uppercase mb-1 small">Bảng điều khiển</p>
                <h2 class="fw-bold mb-0">Chi tiết liên hệ</h2>
            </div>
            <div class="admin-page-actions">
                <a href="<?= BASE_URL ?>?action=admin-contacts" class="btn btn-light-soft">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Thông tin liên hệ -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Thông tin liên hệ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Họ và tên</label>
                                <div class="fw-semibold"><?= htmlspecialchars($contact['name']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Email</label>
                                <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($contact['email']) ?></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Số điện thoại</label>
                                <div>
                                    <?php if ($contact['phone']): ?>
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($contact['phone']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Không có</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Chủ đề</label>
                                <div>
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
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nội dung tin nhắn</label>
                            <div class="border rounded p-3 bg-light">
                                <?= nl2br(htmlspecialchars($contact['message'])) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Ngày gửi</label>
                                <div><i class="bi bi-calendar me-1"></i><?= date('d/m/Y H:i:s', strtotime($contact['created_at'])) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Trạng thái</label>
                                <div>
                                    <?php
                                    $statusBadges = [
                                        'pending' => ['bg' => 'warning', 'text' => 'Chưa đọc'],
                                        'read' => ['bg' => 'info', 'text' => 'Đã đọc'],
                                        'replied' => ['bg' => 'success', 'text' => 'Đã phản hồi'],
                                    ];
                                    $statusInfo = $statusBadges[$contact['status']] ?? ['bg' => 'secondary', 'text' => $contact['status']];
                                    ?>
                                    <span class="badge bg-<?= $statusInfo['bg'] ?>"><?= $statusInfo['text'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phản hồi của admin (nếu có) -->
                <?php if ($contact['status'] === 'replied' && $contact['admin_reply']): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Phản hồi đã gửi</h5>
                        </div>
                        <div class="card-body">
                            <div class="border rounded p-3 bg-light mb-3">
                                <?= nl2br(htmlspecialchars($contact['admin_reply'])) ?>
                            </div>
                            <?php if ($contact['replied_at']): ?>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    Phản hồi lúc: <?= date('d/m/Y H:i:s', strtotime($contact['replied_at'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Form phản hồi -->
                <?php if ($contact['status'] !== 'replied'): ?>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-reply me-2"></i>Phản hồi liên hệ</h5>
                        </div>
                        <div class="card-body">
                            <form id="replyForm">
                                <input type="hidden" name="contact_id" value="<?= $contact['contact_id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nội dung phản hồi <span class="text-danger">*</span></label>
                                    <textarea name="reply" class="form-control" rows="8" required placeholder="Nhập nội dung phản hồi..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-1"></i> Gửi phản hồi
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-success fs-1 mb-3"></i>
                            <p class="text-muted">Đã phản hồi liên hệ này</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(replyForm);
            const submitBtn = replyForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Đang gửi...';
            
            try {
                const response = await fetch('<?= BASE_URL ?>?action=admin-contact-reply', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra khi gửi phản hồi.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
});
</script>


