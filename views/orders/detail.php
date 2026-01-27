<!-- Trang chi tiết đơn hàng hiển thị cho user -->
<?php
// Đảm bảo order_id luôn có sẵn - ưu tiên từ URL parameter
$currentOrderId = 0;
if (isset($_GET['id']) && $_GET['id']) {
    $currentOrderId = (int)$_GET['id'];
}
// Nếu không có từ URL, lấy từ order array
if (!$currentOrderId) {
    $currentOrderId = (int)($order['id'] ?? $order['order_id'] ?? 0);
}
// Đảm bảo order có key 'id' để dùng trong các phần khác
if (!isset($order['id']) && $currentOrderId > 0) {
    $order['id'] = $currentOrderId;
}
// Debug log (có thể xóa sau)
error_log("Order detail page - currentOrderId: $currentOrderId, order['id']: " . ($order['id'] ?? 'not set') . ", order['order_id']: " . ($order['order_id'] ?? 'not set'));
?>
<section class="order-detail-page">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase text-muted mb-1 small">Mã đơn: <?= htmlspecialchars($order['order_code'] ?? '#' . $currentOrderId) ?></p>
                <h2 class="fw-bold">Chi tiết đơn hàng</h2>
                <p class="text-muted mb-0">Đặt lúc <?= isset($order['created_at']) && $order['created_at'] ? date('d/m/Y H:i', strtotime($order['created_at'])) : '-' ?></p>
            </div>
            <a href="<?= BASE_URL ?>?action=order-history" class="btn btn-outline-dark">Quay lại danh sách</a>
        </div>

        <?php if (($order['status'] ?? '') === OrderModel::STATUS_UNPAID && strtolower($order['payment_method'] ?? '') !== 'cod'): ?>
            <div class="alert alert-warning d-flex align-items-center justify-content-between">
                <div>
                    <strong>Đơn đang chờ thanh toán.</strong> Vui lòng thanh toán trong 24h, quá hạn hệ thống sẽ tự hủy.
                </div>
                <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>?action=order-pay&id=<?= $order['id'] ?>">Thanh toán ngay</a>
            </div>
        <?php elseif (($order['status'] ?? '') === OrderModel::STATUS_PAYMENT_FAILED && strtolower($order['payment_method'] ?? '') !== 'cod'): ?>
            <div class="alert alert-danger d-flex align-items-center justify-content-between">
                <div>
                    <strong>Thanh toán thất bại.</strong> Vui lòng thanh toán lại, quá hạn 24h đơn sẽ bị hủy.
                </div>
                <a class="btn btn-sm btn-outline-warning" href="<?= BASE_URL ?>?action=order-pay&id=<?= $order['id'] ?>">Thanh toán lại</a>
            </div>
        <?php endif; ?>

        <?php if (($order['status'] ?? '') === OrderModel::STATUS_DELIVERED): ?>
            <div class="alert alert-info d-flex align-items-center justify-content-between">
                <div>
                    <strong>Đơn đã giao.</strong> Bấm "Tôi đã nhận hàng" để hoàn tất và có thể đánh giá sản phẩm.
                </div>
                <form method="POST" action="<?= BASE_URL ?>?action=order-confirm" class="mb-0">
                    <input type="hidden" name="order_id" value="<?= $currentOrderId ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Tôi đã nhận hàng</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($canReview): 
            // Kiểm tra xem còn sản phẩm nào chưa đánh giá không
            $hasUnreviewedItems = false;
            $firstUnreviewedItemId = null;
            $unreviewedCount = 0;
            foreach ($order['items'] as $item) {
                $orderItemId = 0;
                if (isset($item['id']) && $item['id']) {
                    $orderItemId = (int)$item['id'];
                } elseif (isset($item['order_item_id']) && $item['order_item_id']) {
                    $orderItemId = (int)$item['order_item_id'];
                }
                if ($orderItemId > 0 && !($item['has_reviewed'] ?? false)) {
                    $hasUnreviewedItems = true;
                    $unreviewedCount++;
                    if (!$firstUnreviewedItemId) {
                        $firstUnreviewedItemId = $orderItemId;
                    }
                }
            }
            
            // Kiểm tra xem có tham số review=true trong URL không (khi admin vừa cập nhật status hoặc vừa đặt hàng)
            $showReviewPrompt = isset($_GET['review']) && $_GET['review'] === 'true';
            // Tự động hiển thị thông báo đánh giá khi trạng thái là completed và có sản phẩm chưa đánh giá
            $autoShowReview = $hasUnreviewedItems;
        ?>
            <?php if ($hasUnreviewedItems): ?>
                <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-4 border-warning shadow-sm" role="alert" id="reviewNotification">
                    <i class="bi bi-star-fill me-3 fs-3 text-warning"></i>
                    <div class="flex-grow-1">
                        <strong class="text-dark fs-5">🎉 Đơn hàng đã hoàn thành!</strong>
                        <p class="mb-0 text-dark mt-1">
                            <?php if ($unreviewedCount > 1): ?>
                                Bạn có <strong><?= $unreviewedCount ?> sản phẩm</strong> chưa được đánh giá. 
                            <?php else: ?>
                                Bạn có <strong>1 sản phẩm</strong> chưa được đánh giá.
                            <?php endif; ?>
                            Vui lòng đánh giá sản phẩm bạn đã mua để giúp chúng tôi cải thiện dịch vụ.
                        </p>
                        <a href="#reviewItem_<?= $firstUnreviewedItemId ?>" class="btn btn-warning btn-sm mt-2">
                            <i class="bi bi-star-fill"></i> Đánh giá ngay
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Cột trái: thông tin giao nhận + trạng thái + hủy -->
            <div class="col-lg-4">
                <div class="order-summary-card mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-truck me-2"></i>Thông tin giao hàng
                    </h5>
                    <div class="mb-2">
                        <strong class="text-muted small">Người nhận:</strong>
                        <p class="mb-0"><?= htmlspecialchars($order['fullname']) ?></p>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted small">Số điện thoại:</strong>
                        <p class="mb-0"><?= htmlspecialchars($order['phone']) ?></p>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted small">Email:</strong>
                        <p class="mb-0"><?= htmlspecialchars($order['email']) ?></p>
                    </div>
                    <div class="mb-0">
                        <strong class="text-muted small">Địa chỉ:</strong>
                        <p class="mb-0"><?= htmlspecialchars($order['address']) ?></p>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars(($order['ward'] ?? '') . ', ' . ($order['district'] ?? '') . ', ' . ($order['city'] ?? '')) ?></p>
                    </div>
                </div>

                <?php if (!empty($returnData)): ?>
                    <div class="order-summary-card mb-4">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-info-circle me-2"></i>Hoàn hàng
                        </h5>
                        <div class="alert alert-warning py-2 mb-3">
                            <div class="d-flex flex-column gap-1">
                                <div><strong>Hoàn hàng:</strong> <?= htmlspecialchars(ReturnRequestModel::statusLabel($returnData['status'])) ?></div>
                                <?php if (!empty($returnData['reason'])): ?>
                                    <div class="small text-muted">Lý do: <?= htmlspecialchars($returnData['reason']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($returnData['shipping_code'])): ?>
                                    <div class="small">Mã vận chuyển: <?= htmlspecialchars($returnData['shipping_code']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($returnData['reject_reason'])): ?>
                                    <div class="small text-danger">Bị từ chối: <?= nl2br(htmlspecialchars($returnData['reject_reason'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (($order['status'] ?? '') === OrderModel::STATUS_CANCELLED): ?>
                    <div class="order-summary-card mb-4">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a href="<?= BASE_URL ?>?action=order-rebuy&id=<?= $order['id'] ?>" class="btn btn-outline-dark">
                                Mua lại
                            </a>
                        </div>
                        <?php if (!empty($order['cancel_reason'])): ?>
                            <div class="alert alert-light border mt-3">
                                <strong>Lý do hủy:</strong>
                                <div><?= htmlspecialchars($order['cancel_reason']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php
                    // Chỉ cho phép yêu cầu hoàn hàng khi đơn đã COMPLETED (sau khi user ấn "Tôi đã nhận hàng")
                    $canReturn = ($order['status'] ?? '') === OrderModel::STATUS_COMPLETED;
                    $deliveredAt = $order['updated_at'] ?? $order['created_at'] ?? null;
                    $within3Days = $deliveredAt ? (time() - strtotime($deliveredAt) <= 3 * 24 * 3600) : false;
                    $returnAllowed = $canReturn && $within3Days && empty($returnData);
                ?>
                <?php if ($returnAllowed): ?>
                    <div class="order-summary-card mt-4">
                        <h5 class="fw-bold mb-3">Yêu cầu hoàn hàng</h5>
                        <form id="returnRequestForm" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?= $currentOrderId ?>">
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Lý do hoàn hàng</label>
                                <select class="form-select" name="reason" required>
                                    <option value="">-- Chọn lý do --</option>
                                    <option value="Sản phẩm bị lỗi">Sản phẩm bị lỗi</option>
                                    <option value="Sản phẩm giao sai">Sản phẩm giao sai</option>
                                    <option value="Thiếu phụ kiện">Thiếu phụ kiện</option>
                                    <option value="Hàng giả/nhái">Hàng giả/nhái</option>
                                    <option value="Không giống mô tả">Không giống mô tả</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Ghi chú (tùy chọn)</label>
                                <textarea class="form-control" name="note" rows="3" placeholder="Mô tả thêm (không bắt buộc)"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Ảnh/Video minh chứng <span class="text-danger">*</span></label>
                                <input type="file" name="evidences[]" class="form-control" accept="image/*,video/*" multiple required>
                                <small class="text-muted">Tối đa 5 file, mỗi file ≤ 10MB. Hỗ trợ ảnh/video.</small>
                            </div>
                            <button type="submit" class="btn btn-outline-dark w-100">Gửi yêu cầu hoàn hàng</button>
                        </form>
                    </div>
                <?php elseif (!empty($returnData) && !in_array($returnData['status'], ['refunded', 'rejected', 'cancelled'], true)): ?>
                    <div class="order-summary-card mt-4">
                        <h5 class="fw-bold mb-3">Trạng thái hoàn hàng</h5>
                        <p class="mb-2">Trạng thái: <strong><?= htmlspecialchars(ReturnRequestModel::statusLabel($returnData['status'])) ?></strong></p>
                        <?php if (!empty($returnData['reject_reason'])): ?>
                            <p class="text-danger small mb-2">Lý do từ chối: <?= nl2br(htmlspecialchars($returnData['reject_reason'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($returnData['shipping_code'])): ?>
                            <p class="small mb-2">Mã vận chuyển: <?= htmlspecialchars($returnData['shipping_code']) ?></p>
                        <?php endif; ?>
                        <?php if ($returnData['status'] === 'approved'): ?>
                            <form id="returnShippingForm">
                                <input type="hidden" name="order_id" value="<?= $currentOrderId ?>">
                                <div class="mb-3">
                                    <label class="form-label small">Nhập mã vận chuyển hoàn</label>
                                    <input type="text" name="shipping_code" class="form-control" placeholder="Mã vận chuyển" required>
                                </div>
                                <button type="submit" class="btn btn-outline-primary w-100">Gửi mã vận chuyển</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cột phải: danh sách sản phẩm + ghi chú -->
            <div class="col-lg-8">
                <div class="order-items-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Sản phẩm</h5>
                        <div class="text-muted">
                            <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                <div class="text-end">
                                    <div class="small text-muted">Tạm tính: <?= number_format($order['total_amount'] + $order['discount_amount'], 0, ',', '.') ?> đ</div>
                                    <?php if (!empty($order['coupon_code'])): ?>
                                        <div class="small text-dark">Mã giảm giá: <?= htmlspecialchars($order['coupon_code']) ?> (<?= htmlspecialchars($order['coupon_name'] ?? '') ?>)</div>
                                        <div class="small text-dark">Giảm: -<?= number_format($order['discount_amount'], 0, ',', '.') ?> đ</div>
                                    <?php endif; ?>
                                    <div class="fw-bold">Tổng cộng: <?= number_format($order['total_amount'], 0, ',', '.') ?> đ</div>
                                </div>
                            <?php else: ?>
                                <div class="text-end">Tổng cộng: <strong><?= number_format($order['total_amount'], 0, ',', '.') ?> đ</strong></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Thuộc tính</th>
                                    <th>Số lượng</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): 
                                    // Lấy order_item_id từ nhiều nguồn
                                    $orderItemId = 0;
                                    if (isset($item['id']) && $item['id']) {
                                        $orderItemId = (int)$item['id'];
                                    } elseif (isset($item['order_item_id']) && $item['order_item_id']) {
                                        $orderItemId = (int)$item['order_item_id'];
                                    }
                                    
                                    $productId = (int)($item['product_id'] ?? 0);
                                    $hasReviewed = $item['has_reviewed'] ?? false;
                                    $existingReview = $item['review'] ?? null;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $itemImage = getProductImageUrl($item['image_url'] ?? '');
                                                if (!empty($itemImage)): 
                                                ?>
                                                    <img src="<?= htmlspecialchars($itemImage) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                         class="me-3 thumb-80"
                                                         onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'; this.onerror=null;">
                                                <?php else: ?>
                                                    <div class="me-3 placeholder-80-box">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <?php if ($productId > 0): ?>
                                                        <a href="<?= BASE_URL ?>?action=product-detail&id=<?= $productId ?>" 
                                                           class="text-muted small text-decoration-none">
                                                            Xem sản phẩm <i class="bi bi-arrow-right"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <strong>Size:</strong> <?= htmlspecialchars($item['variant_size'] ?? '-') ?> <br>
                                                <strong>Màu:</strong> <?= htmlspecialchars($item['variant_color'] ?? '-') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $item['quantity'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= OrderModel::statusBadge($order['status']) ?>">
                                                <?= OrderModel::statusLabel($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= number_format($item['quantity'] * $item['unit_price'], 0, ',', '.') ?> đ</strong>
                                            <div class="small text-muted"><?= number_format($item['unit_price'], 0, ',', '.') ?> đ/SP</div>
                                        </td>
                                    </tr>
                                    <?php if ($canReview && $orderItemId): ?>
                                        <tr class="review-row" id="reviewItem_<?= $orderItemId ?>">
                                            <td colspan="5" class="border-top-0 pt-0">
                                                <?php if ($hasReviewed && $existingReview): 
                                                    $reviewImages = [];
                                                    if (!empty($existingReview['images'])) {
                                                        $reviewImages = is_array($existingReview['images']) ? $existingReview['images'] : json_decode($existingReview['images'], true);
                                                        if (!is_array($reviewImages)) $reviewImages = [];
                                                    }
                                                ?>
                                                    <div class="review-submitted p-3 bg-light rounded">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center mb-1">
                                                                    <strong>Đánh giá của bạn:</strong>
                                                                    <div class="ms-2">
                                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                            <i class="bi bi-star<?= $i <= $existingReview['rating'] ? '-fill text-warning' : '' ?>"></i>
                                                                        <?php endfor; ?>
                                                                    </div>
                                                                </div>
                                                                <?php if (!empty($existingReview['comment'])): ?>
                                                                    <p class="mb-2 text-muted mb-1"><?= nl2br(htmlspecialchars($existingReview['comment'])) ?></p>
                                                                <?php endif; ?>
                                                                <?php if (!empty($reviewImages)): ?>
                                                                    <div class="review-images mt-2 mb-2">
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <?php foreach ($reviewImages as $img): ?>
                                                                                <a href="<?= htmlspecialchars($img) ?>" target="_blank" class="review-image-thumbnail">
                                                                                    <img src="<?= htmlspecialchars($img) ?>" alt="Review image" class="img-thumbnail thumb-80">
                                                                                </a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($existingReview['reply'])): ?>
                                                                    <div class="mt-2 p-2 bg-white rounded border-start border-3 border-dark">
                                                                        <small class="text-muted d-block mb-1"><strong>Phản hồi từ cửa hàng:</strong></small>
                                                                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($existingReview['reply'])) ?></p>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <button class="btn btn-outline-dark btn-sm mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#editReview_<?= $orderItemId ?>">
                                                                    <i class="bi bi-pencil"></i> Sửa đánh giá
                                                                </button>
                                                                <div class="collapse mt-3" id="editReview_<?= $orderItemId ?>">
                                                                    <form class="review-edit-form" data-review-id="<?= $existingReview['review_id'] ?>">
                                                                        <input type="hidden" name="review_id" value="<?= $existingReview['review_id'] ?>">
                                                                        <div class="mb-2">
                                                                            <label class="form-label small">Cập nhật số sao</label>
                                                                            <div class="rating-input">
                                                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                                                    <input type="radio" name="rating" id="edit_rating_<?= $orderItemId ?>_<?= $i ?>" value="<?= $i ?>" <?= ($existingReview['rating'] ?? 0) == $i ? 'checked' : '' ?>>
                                                                                    <label for="edit_rating_<?= $orderItemId ?>_<?= $i ?>" class="star-label">
                                                                                        <i class="bi bi-star-fill"></i>
                                                                                    </label>
                                                                                <?php endfor; ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label small">Cập nhật bình luận</label>
                                                                            <textarea name="comment" class="form-control" rows="3"><?= htmlspecialchars($existingReview['comment'] ?? '') ?></textarea>
                                                                            <small class="text-muted">Có thể sửa số sao hoặc nội dung; để trống nếu chỉ đổi số sao.</small>
                                                                        </div>
                                                                        <button type="submit" class="btn btn-dark btn-sm">Lưu chỉnh sửa</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted"><?= date('d/m/Y', strtotime($existingReview['created_at'])) ?></small>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="review-form-container p-3 bg-light rounded">
                                                        <h6 class="mb-3">Đánh giá sản phẩm</h6>
                                                        <form class="review-form" 
                                                              data-order-item-id="<?= $orderItemId ?>" 
                                                              data-order-id="<?= $currentOrderId ?>" 
                                                              data-product-id="<?= $productId ?>" 
                                                              enctype="multipart/form-data">
                                                            <!-- Hidden inputs để đảm bảo các ID luôn có -->
                                                            <input type="hidden" name="order_id" value="<?= $currentOrderId ?>">
                                                            <input type="hidden" name="order_item_id" value="<?= $orderItemId ?>">
                                                            <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label small">Đánh giá sao <span class="text-danger">*</span></label>
                                                                <div class="rating-input">
                                                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                                                        <input type="radio" name="rating" id="rating_<?= $orderItemId ?>_<?= $i ?>" value="<?= $i ?>" required>
                                                                        <label for="rating_<?= $orderItemId ?>_<?= $i ?>" class="star-label">
                                                                            <i class="bi bi-star-fill"></i>
                                                                        </label>
                                                                    <?php endfor; ?>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small">Bình luận (tùy chọn)</label>
                                                                <textarea name="comment" class="form-control" rows="3" placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small">Upload ảnh (tùy chọn, tối đa 1 ảnh)</label>
                                                                <input type="file" class="form-control review-image-input" accept="image/*" data-order-item-id="<?= $orderItemId ?>">
                                                                <small class="text-muted">Chấp nhận: JPG, PNG, GIF, WEBP (tối đa 5MB/ảnh)</small>
                                                                <div class="review-images-preview mt-2 d-flex flex-wrap gap-2" id="reviewImagesPreview_<?= $orderItemId ?>"></div>
                                                            </div>
                                                            <button type="submit" class="btn btn-dark btn-sm">Gửi đánh giá</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($canCancel): ?>
                        <hr class="my-4">
                        <div class="mt-4">
                            <h6 class="fw-bold mb-3">Hủy đơn hàng</h6>
                            <form method="POST" action="<?= BASE_URL ?>?action=order-cancel" onsubmit="return validateCancelReason()">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small text-uppercase">Lý do hủy <span class="text-danger">*</span></label>
                                        <select class="form-select" name="reason_predefined" id="cancelReasonSelect" required>
                                            <option value="">-- Chọn lý do --</option>
                                            <option value="Chọn nhầm sản phẩm">Chọn nhầm sản phẩm</option>
                                            <option value="Muốn cập nhật địa chỉ/SDT">Muốn cập nhật địa chỉ/SDT</option>
                                            <option value="Thay đổi phương thức thanh toán">Thay đổi phương thức thanh toán</option>
                                            <option value="Thời gian giao hàng không phù hợp">Thời gian giao hàng không phù hợp</option>
                                            <option value="Lý do khác">Lý do khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3" id="cancelReasonOtherWrap" style="display:none;">
                                        <label class="form-label small text-uppercase">Lý do khác</label>
                                        <textarea class="form-control" name="reason_other" rows="3" placeholder="Nhập lý do khác (tối thiểu 5 ký tự)"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-outline-danger">Hủy đơn hàng</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($order['note'])): ?>
                    <div class="order-items-card">
                        <h6 class="fw-bold mb-2">Ghi chú của bạn</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['note'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Modal thông báo thanh toán thành công -->
<div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-labelledby="paymentSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pb-5">
                <div class="mb-4">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-3">Thanh toán thành công!</h3>
                <p class="text-muted mb-4">
                    Cảm ơn bạn đã đặt hàng. Đơn hàng <strong><?= htmlspecialchars($order['order_code'] ?? '#' . $currentOrderId) ?></strong> của bạn đã được xác nhận.<br>
                    Chúng tôi sẽ xử lý và giao hàng cho bạn sớm nhất có thể.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?= BASE_URL ?>?action=order-history" class="btn btn-outline-dark">Xem tất cả đơn hàng</a>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Tiếp tục mua sắm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tự động hiển thị modal khi có tham số payment_success=1 trong URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('payment_success') === '1') {
        const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
        modal.show();
        
        // Xóa tham số payment_success khỏi URL sau khi hiển thị modal
        urlParams.delete('payment_success');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }
});
</script>

<?php if ($canReview): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tự động cuộn đến phần đánh giá khi trạng thái là "delivered" hoặc "completed" và có sản phẩm chưa đánh giá
    <?php if ($hasUnreviewedItems && $firstUnreviewedItemId): ?>
    // QUAN TRỌNG: Tự động cuộn đến form đánh giá khi:
    // 1. Trạng thái đơn hàng là "delivered" hoặc "completed"
    // 2. Có tham số review=true trong URL (khi admin vừa cập nhật status)
    // 3. Có thông báo đánh giá hiển thị
    const urlParams = new URLSearchParams(window.location.search);
    const orderStatus = '<?= $order['status'] ?>';
    const hasReviewParam = urlParams.get('review') === 'true';
    const hasNotification = document.getElementById('reviewNotification') !== null;
    
    // Tự động cuộn nếu trạng thái là delivered/completed hoặc có tham số review=true
    const shouldAutoScroll = (orderStatus === 'delivered' || orderStatus === 'completed') || hasReviewParam || hasNotification;
    
    if (shouldAutoScroll && (orderStatus === 'delivered' || orderStatus === 'completed')) {
        // Đợi một chút để đảm bảo DOM đã load xong
        setTimeout(() => {
            const reviewElement = document.getElementById('reviewItem_<?= $firstUnreviewedItemId ?>');
            if (reviewElement) {
                // Cuộn đến form đánh giá
                reviewElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Highlight form đánh giá với animation nổi bật
                reviewElement.style.transition = 'all 0.5s ease';
                reviewElement.style.backgroundColor = '#fff3cd';
                reviewElement.style.border = '2px solid #ffc107';
                reviewElement.style.borderRadius = '8px';
                reviewElement.style.padding = '15px';
                reviewElement.style.boxShadow = '0 0 25px rgba(255, 193, 7, 0.5)';
                
                // Tạo hiệu ứng pulse để thu hút sự chú ý
                let pulseCount = 0;
                const pulseInterval = setInterval(() => {
                    if (pulseCount < 4) {
                        reviewElement.style.transform = 'scale(1.03)';
                        setTimeout(() => {
                            reviewElement.style.transform = 'scale(1)';
                        }, 250);
                        pulseCount++;
                    } else {
                        clearInterval(pulseInterval);
                    }
                }, 500);
                
                // Focus vào input rating đầu tiên
                const firstRatingInput = reviewElement.querySelector('input[name="rating"]');
                if (firstRatingInput) {
                    setTimeout(() => {
                        firstRatingInput.focus();
                    }, 1000);
                }
                
                // Xóa highlight sau 4 giây nhưng giữ form visible
                setTimeout(() => {
                    reviewElement.style.backgroundColor = '';
                    reviewElement.style.border = '';
                    reviewElement.style.borderRadius = '';
                    reviewElement.style.padding = '';
                    reviewElement.style.boxShadow = '';
                    reviewElement.style.transform = '';
                }, 4000);
            }
        }, 1000); // Đợi 1 giây để đảm bảo trang đã load hoàn toàn
    }
    <?php endif; ?>
    
    // Toast Notification Functions (định nghĩa trước để dùng ở mọi nơi)
    function showSuccessToast(message) {
        showToast(message, 'success');
    }
    
    function showErrorToast(message) {
        showToast(message, 'danger');
    }
    
    function showToast(message, type = 'success') {
        // Tạo toast container nếu chưa có
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1100';
            document.body.appendChild(toastContainer);
        }

        // Tạo toast element
        const toastId = 'toast_' + Date.now();
        const iconClass = type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        
        const toastHtml = `
            <div id="${toastId}" class="toast ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${bgClass} text-white border-0">
                    <i class="bi ${iconClass} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Thành công' : 'Lỗi'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body text-white">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { 
            delay: type === 'success' ? 3000 : 5000,
            autohide: true
        });
        
        toast.show();
        
        // Xóa toast element sau khi ẩn
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }
    
    // Xử lý upload ảnh
    const imageInputs = document.querySelectorAll('.review-image-input');
    imageInputs.forEach(input => {
        input.addEventListener('change', async function(e) {
            const orderItemId = this.dataset.orderItemId;
            const previewContainer = document.getElementById('reviewImagesPreview_' + orderItemId);
            const files = Array.from(this.files);
            if (files.length > 1) {
                showErrorToast('Chỉ được tải lên 1 ảnh cho mỗi đánh giá');
                this.value = '';
                return;
            }

            previewContainer.innerHTML = '';
            const uploadedImages = [];
            
            for (let file of files) {
                // Kiểm tra kích thước
                if (file.size > 5 * 1024 * 1024) {
                    showErrorToast(`Ảnh ${file.name} vượt quá 5MB. Vui lòng chọn ảnh khác.`);
                    continue;
                }
                
                // Hiển thị preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.cssText = 'width: 80px; height: 80px; object-fit: cover; margin-right: 5px;';
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
                
                // Upload ảnh
                const formData = new FormData();
                formData.append('image', file);
                
                try {
                    const uploadResponse = await fetch('<?= BASE_URL ?>?action=review-upload-image', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const uploadData = await uploadResponse.json();
                    if (uploadData.success) {
                        uploadedImages.push(uploadData.url);
                        // Lưu vào data attribute (chỉ 1 ảnh)
                        const form = input.closest('.review-form');
                        form.dataset.uploadedImages = JSON.stringify(uploadedImages.slice(0,1));
                        showSuccessToast('Upload ảnh thành công!');
                    } else {
                        showErrorToast('Lỗi upload ảnh: ' + (uploadData.message || 'Vui lòng thử lại'));
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    showErrorToast('Lỗi upload ảnh. Vui lòng thử lại.');
                }
            }
        });
    });
    
    // Xử lý submit form đánh giá mới
    const reviewForms = document.querySelectorAll('.review-form');
    
    reviewForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Lấy order_id từ nhiều nguồn: data attribute hoặc hidden input
            let orderItemId = parseInt(this.dataset.orderItemId || 0);
            let orderId = parseInt(this.dataset.orderId || 0);
            let productId = parseInt(this.dataset.productId || 0);
            
            // Nếu không có từ data attribute, thử lấy từ hidden input
            if (!orderId || orderId <= 0) {
                const hiddenOrderId = this.querySelector('input[name="order_id"]');
                if (hiddenOrderId && hiddenOrderId.value) {
                    orderId = parseInt(hiddenOrderId.value);
                    console.log('Got orderId from hidden input:', orderId);
                }
            }
            
            if (!orderItemId || orderItemId <= 0) {
                const hiddenOrderItemId = this.querySelector('input[name="order_item_id"]');
                if (hiddenOrderItemId && hiddenOrderItemId.value) {
                    orderItemId = parseInt(hiddenOrderItemId.value);
                    console.log('Got orderItemId from hidden input:', orderItemId);
                }
            }
            
            if (!productId || productId <= 0) {
                const hiddenProductId = this.querySelector('input[name="product_id"]');
                if (hiddenProductId && hiddenProductId.value) {
                    productId = parseInt(hiddenProductId.value);
                    console.log('Got productId from hidden input:', productId);
                }
            }
            
            const rating = parseInt(this.querySelector('input[name="rating"]:checked')?.value || 0);
            const comment = this.querySelector('textarea[name="comment"]')?.value.trim() || '';
            const images = this.dataset.uploadedImages ? JSON.parse(this.dataset.uploadedImages) : [];
            
            // Debug: Log tất cả giá trị đã lấy được
            console.log('Extracted values:', {
                orderItemId,
                orderId,
                productId,
                rating,
                hasComment: comment.length > 0,
                imagesCount: images.length
            });
            
            // Validate dữ liệu trước khi gửi
            if (!orderItemId || orderItemId <= 0) {
                showErrorToast('Lỗi: Không tìm thấy order_item_id. Vui lòng tải lại trang.');
                console.error('Missing orderItemId. Form data:', {
                    orderItemId: this.dataset.orderItemId,
                    orderId: this.dataset.orderId,
                    productId: this.dataset.productId
                });
                return;
            }
            
            if (!orderId || orderId <= 0) {
                showErrorToast('Lỗi: Không tìm thấy order_id. Vui lòng tải lại trang.');
                return;
            }
            
            if (!productId || productId <= 0) {
                showErrorToast('Lỗi: Không tìm thấy product_id. Vui lòng tải lại trang.');
                return;
            }
            
            if (!rating || rating < 1 || rating > 5) {
                showErrorToast('Vui lòng chọn số sao đánh giá từ 1 đến 5');
                return;
            }
            
            // Log để debug - hiển thị tất cả thông tin
            console.log('Submitting review:', {
                orderItemId,
                orderId,
                productId,
                rating,
                commentLength: comment.length,
                imagesCount: images.length,
                formDataAttributes: {
                    orderItemIdAttr: this.dataset.orderItemId,
                    orderIdAttr: this.dataset.orderId,
                    productIdAttr: this.dataset.productId
                },
                hiddenInputs: {
                    orderIdInput: this.querySelector('input[name="order_id"]')?.value,
                    orderItemIdInput: this.querySelector('input[name="order_item_id"]')?.value,
                    productIdInput: this.querySelector('input[name="product_id"]')?.value
                }
            });
            
            // Kiểm tra lại một lần nữa trước khi gửi
            if (!orderId || orderId <= 0) {
                console.error('Final check failed - orderId is invalid:', orderId);
                showErrorToast('Lỗi: Không tìm thấy order_id. Vui lòng tải lại trang.');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang gửi...';
            
            // Đảm bảo tất cả dữ liệu đều có giá trị hợp lệ
            const reviewData = {
                order_item_id: orderItemId,
                order_id: orderId,
                product_id: productId,
                rating: rating,
                comment: comment || '',
                images: images || []
            };
            
            console.log('Sending review data:', reviewData);
            
            try {
                const response = await fetch('<?= BASE_URL ?>?action=review-submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(reviewData)
                });
                
                // Kiểm tra response status
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Hiển thị thông báo thành công bằng toast notification
                    showSuccessToast('Đánh giá của bạn đã được gửi thành công!');
                    // Đợi một chút để người dùng thấy thông báo, sau đó reload
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Hiển thị thông báo lỗi cụ thể từ server
                    showErrorToast(data.message || 'Có lỗi xảy ra khi gửi đánh giá');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                // Kiểm tra xem có phải lỗi parse JSON không
                if (error instanceof SyntaxError) {
                    showErrorToast('Lỗi phản hồi từ server. Vui lòng thử lại.');
                } else {
                    showErrorToast('Có lỗi xảy ra. Vui lòng thử lại.');
                }
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });

    // Xử lý submit form chỉnh sửa bình luận/số sao
    const editForms = document.querySelectorAll('.review-edit-form');
    editForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const reviewId = parseInt(this.dataset.reviewId || this.querySelector('input[name="review_id"]')?.value || 0);
            const comment = this.querySelector('textarea[name="comment"]')?.value.trim() || '';
            const ratingInput = this.querySelector('input[name="rating"]:checked');
            const rating = ratingInput ? parseInt(ratingInput.value, 10) : null;

            if (!reviewId) {
                showErrorToast('Không tìm thấy review_id');
                return;
            }
            if (!comment && !rating) {
                showErrorToast('Vui lòng nhập bình luận hoặc chọn số sao');
                return;
            }
            if (rating && (rating < 1 || rating > 5)) {
                showErrorToast('Số sao phải từ 1 đến 5');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const original = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang lưu...';

            try {
                const resp = await fetch('<?= BASE_URL ?>?action=review-update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ review_id: reviewId, comment, rating })
                });
                const data = await resp.json();
                if (data.success) {
                    showSuccessToast('Đã lưu chỉnh sửa đánh giá');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showErrorToast(data.message || 'Có lỗi khi lưu chỉnh sửa');
                    submitBtn.disabled = false;
                    submitBtn.textContent = original;
                }
            } catch (err) {
                console.error(err);
                showErrorToast('Có lỗi xảy ra. Vui lòng thử lại.');
                submitBtn.disabled = false;
                submitBtn.textContent = original;
            }
        });
    });
});
</script>
<script>
// Return request (simple)
document.addEventListener('DOMContentLoaded', function() {
    const fallbackError = (msg) => alert(msg || 'Có lỗi xảy ra');
    const fallbackSuccess = (msg) => alert(msg || 'Thành công');
    const toastError = typeof showErrorToast === 'function' ? showErrorToast : fallbackError;
    const toastSuccess = typeof showSuccessToast === 'function' ? showSuccessToast : fallbackSuccess;

    const returnForm = document.getElementById('returnRequestForm');
    if (returnForm) {
        returnForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const orderId = parseInt(this.querySelector('input[name="order_id"]')?.value || 0);
            const reason = this.querySelector('select[name="reason"]')?.value || '';
            const note = this.querySelector('textarea[name="note"]')?.value || '';
            const filesInput = this.querySelector('input[name="evidences[]"]');
            const files = filesInput?.files || [];
            if (!orderId || !reason) {
                toastError('Vui lòng chọn lý do');
                return;
            }
            if (!files || files.length === 0) {
                toastError('Vui lòng tải lên ít nhất 1 file minh chứng');
                return;
            }
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Đang gửi...';
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('reason', reason);
                formData.append('note', note);
                for (let i = 0; i < files.length; i++) {
                    formData.append('evidences[]', files[i]);
                }
                const resp = await fetch('<?= BASE_URL ?>?action=return-request', {
                    method: 'POST',
                    body: formData
                });
                const data = await resp.json();
                if (data.success) {
                    toastSuccess('Đã gửi yêu cầu hoàn hàng');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastError(data.message || 'Không thể gửi yêu cầu');
                    btn.disabled = false;
                    btn.textContent = 'Gửi yêu cầu hoàn hàng';
                }
            } catch (err) {
                console.error(err);
                toastError('Có lỗi xảy ra');
                btn.disabled = false;
                btn.textContent = 'Gửi yêu cầu hoàn hàng';
            }
        });
    }

    const shippingForm = document.getElementById('returnShippingForm');
    if (shippingForm) {
        shippingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const orderId = parseInt(this.querySelector('input[name="order_id"]')?.value || 0);
            const code = this.querySelector('input[name="shipping_code"]')?.value.trim() || '';
            if (!orderId || !code) {
                toastError('Vui lòng nhập mã vận chuyển');
                return;
            }
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Đang gửi...';
            try {
                const resp = await fetch('<?= BASE_URL ?>?action=return-upload-shipping', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, shipping_code: code })
                });
                const data = await resp.json();
                if (data.success) {
                    toastSuccess('Đã cập nhật mã vận chuyển');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastError(data.message || 'Không thể cập nhật');
                    btn.disabled = false;
                    btn.textContent = 'Gửi mã vận chuyển';
                }
            } catch (err) {
                console.error(err);
                toastError('Có lỗi xảy ra');
                btn.disabled = false;
                btn.textContent = 'Gửi mã vận chuyển';
            }
        });
    }
});
</script>
<?php endif; ?>

<script>
// Hiển thị ô "Lý do khác" khi chọn
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('cancelReasonSelect');
    const otherWrap = document.getElementById('cancelReasonOtherWrap');
    if (select) {
        select.addEventListener('change', function() {
            if (this.value === 'Lý do khác') {
                otherWrap.style.display = '';
            } else {
                otherWrap.style.display = 'none';
            }
        });
    }
});

function validateCancelReason() {
    const select = document.getElementById('cancelReasonSelect');
    const otherWrap = document.getElementById('cancelReasonOtherWrap');
    if (!select || !select.value) {
        alert('Vui lòng chọn lý do hủy');
        return false;
    }
    if (select.value === 'Lý do khác') {
        const other = document.querySelector('textarea[name="reason_other"]');
        if (!other || other.value.trim().length < 5) {
            alert('Vui lòng nhập lý do khác (tối thiểu 5 ký tự)');
            return false;
        }
    }
    return true;
}
</script>

