<!-- Trang Lịch Sử Liên Hệ -->
<section class="contact-history-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="display-5 fw-bold mb-2">Lịch Sử Liên Hệ</h1>
                        <p class="text-muted">Xem các liên hệ bạn đã gửi và phản hồi từ chúng tôi</p>
                    </div>
                    <a href="<?= BASE_URL ?>?action=contact" class="btn btn-dark">
                        <i class="bi bi-envelope-plus me-2"></i>Gửi liên hệ mới
                    </a>
                </div>

                <?php if (empty($contacts)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">Chưa có liên hệ nào</h4>
                            <p class="text-muted">Bạn chưa gửi liên hệ nào đến chúng tôi.</p>
                            <a href="<?= BASE_URL ?>?action=contact" class="btn btn-dark mt-3">
                                <i class="bi bi-envelope-plus me-2"></i>Gửi liên hệ ngay
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($contacts as $contact): ?>
                            <div class="col-12 mb-4">
                                <div class="card <?= $contact['status'] === 'replied' ? 'border-success' : ($contact['status'] === 'read' ? 'border-info' : 'border-warning') ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center <?= $contact['status'] === 'replied' ? 'bg-success text-white' : ($contact['status'] === 'read' ? 'bg-info text-white' : 'bg-warning text-dark') ?>">
                                        <div>
                                            <h5 class="mb-0">
                                                <i class="bi bi-envelope me-2"></i>
                                                <?php
                                                $subjectMap = [
                                                    'product' => 'Câu hỏi về sản phẩm',
                                                    'order' => 'Câu hỏi về đơn hàng',
                                                    'return' => 'Đổi trả sản phẩm',
                                                    'feedback' => 'Góp ý',
                                                    'other' => 'Khác',
                                                ];
                                                $subjectLabel = $subjectMap[$contact['subject']] ?? ($contact['subject'] ?: 'Không có chủ đề');
                                                echo htmlspecialchars($subjectLabel);
                                                ?>
                                            </h5>
                                            <small class="opacity-75">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php
                                            $statusBadges = [
                                                'pending' => ['bg' => 'warning', 'text' => 'Chưa đọc', 'icon' => 'clock'],
                                                'read' => ['bg' => 'info', 'text' => 'Đã đọc', 'icon' => 'check'],
                                                'replied' => ['bg' => 'success', 'text' => 'Đã phản hồi', 'icon' => 'check-circle'],
                                            ];
                                            $statusInfo = $statusBadges[$contact['status']] ?? ['bg' => 'secondary', 'text' => $contact['status'], 'icon' => 'question'];
                                            ?>
                                            <span class="badge bg-light text-dark px-3 py-2">
                                                <i class="bi bi-<?= $statusInfo['icon'] ?> me-1"></i>
                                                <?= $statusInfo['text'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="text-muted small">Người gửi</label>
                                                <div class="fw-semibold"><?= htmlspecialchars($contact['name']) ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="text-muted small">Email</label>
                                                <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($contact['email']) ?></div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($contact['phone']): ?>
                                            <div class="mb-3">
                                                <label class="text-muted small">Số điện thoại</label>
                                                <div><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($contact['phone']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="text-muted small">Nội dung tin nhắn</label>
                                            <div class="border rounded p-3 bg-light">
                                                <?= nl2br(htmlspecialchars($contact['message'])) ?>
                                            </div>
                                        </div>

                                        <?php if ($contact['status'] === 'replied' && $contact['admin_reply']): ?>
                                            <div class="alert alert-success mt-3">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                                                    <div class="flex-grow-1">
                                                        <h6 class="alert-heading mb-2">
                                                            <i class="bi bi-reply me-1"></i>Phản hồi từ Admin
                                                        </h6>
                                                        <div class="border-start border-success border-3 ps-3 mb-2">
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
                                            </div>
                                        <?php elseif ($contact['status'] === 'read'): ?>
                                            <div class="alert alert-info mt-3">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Tin nhắn của bạn đã được đọc. Chúng tôi sẽ phản hồi sớm nhất có thể.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning mt-3">
                                                <i class="bi bi-hourglass-split me-2"></i>
                                                Tin nhắn của bạn đang chờ được xử lý.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>


