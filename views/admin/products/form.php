<?php
$isEditing = isset($product);
$productId = $isEditing ? (int)$product['id'] : null;
?>

<div class="form-header">
    <h2><?= $isEditing ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?></h2>
    <a href="<?= BASE_URL ?>?action=admin-products" class="btn-cancel">Quay lại danh sách</a>
</div>

<!-- Form thông tin sản phẩm -->
<div class="form-card">
    <h3 class="mb-4 fs-125 fw-bold">Thông tin cơ bản</h3>
    <form method="POST" action="<?= BASE_URL ?>?action=<?= $isEditing ? 'admin-product-update' : 'admin-product-store' ?>" enctype="multipart/form-data">
        <?php if ($isEditing): ?>
            <input type="hidden" name="product_id" value="<?= $productId ?>">
        <?php endif; ?>
        
        <div class="form-grid-2">
            <div>
                <label class="form-label" for="name">Tên sản phẩm <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>" placeholder="Nhập tên sản phẩm">
            </div>
            <div>
                <label class="form-label" for="category_id">Danh mục <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <option value="" disabled <?= empty($product['category_id']) ? 'selected' : '' ?>>— Chọn danh mục —</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>"
                            <?= ($product['category_id'] ?? null) == $category['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label" for="description">Mô tả <span class="text-danger">*</span></label>
            <textarea id="description" name="description" class="form-control" rows="4" required placeholder="Mô tả chi tiết sản phẩm"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

        <?php $hasVariants = !empty($variants ?? []); ?>
        <div class="form-grid-3">
            <div>
                <label class="form-label" for="original_price">Giá gốc (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" id="original_price" name="original_price" min="0" step="1000" class="form-control" required value="<?= htmlspecialchars((string)($product['original_price'] ?? $product['price'] ?? '')) ?>" placeholder="Nhập giá gốc">
                <small class="text-muted">Giá gốc của sản phẩm</small>
            </div>
            <div>
                <label class="form-label" for="sale_price">Giá giảm giá (VNĐ)</label>
                <input type="number" id="sale_price" name="sale_price" min="0" step="1000" class="form-control" value="<?= htmlspecialchars((string)($product['sale_price'] ?? '')) ?>" placeholder="Để trống nếu không giảm giá">
                <small class="text-muted">Giá bán sau khi giảm (tùy chọn)</small>
            </div>
            <div>
                <?php if ($hasVariants): ?>
                    <label class="form-label" for="stock">Tồn kho sản phẩm cha</label>
                    <input type="number" id="stock" name="stock" min="0" class="form-control" value="<?= htmlspecialchars($product['stock'] ?? 0) ?>" readonly>
                    <small class="text-muted">Sản phẩm có biến thể. Tồn kho được quản lý ở từng biến thể.</small>
                <?php else: ?>
                    <label class="form-label" for="stock">Tồn kho</label>
                    <input type="number" id="stock" name="stock" min="0" class="form-control"
                           value="<?= htmlspecialchars((string)($product['stock'] ?? '')) ?>" placeholder="Nhập số lượng tồn kho">
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-3" id="pricePreview" style="padding: 0.75rem; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #3b82f6;">
            <small class="text-muted d-block mb-1"><strong>Xem trước giá hiển thị:</strong></small>
            <div id="priceDisplay">
                <span class="text-success fw-bold" id="displayPrice"><?= number_format((float)($product['sale_price'] ?? $product['original_price'] ?? $product['price'] ?? 0), 0, ',', '.') ?> đ</span>
                <?php if (!empty($product['sale_price']) || (!empty($product['original_price']) && !empty($product['sale_price']))): ?>
                    <span class="text-muted text-decoration-line-through ms-2" id="displayOriginalPrice"><?= number_format((float)($product['original_price'] ?? $product['price'] ?? 0), 0, ',', '.') ?> đ</span>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        // Cập nhật preview giá khi người dùng nhập
        document.addEventListener('DOMContentLoaded', function() {
            const originalPriceInput = document.getElementById('original_price');
            const salePriceInput = document.getElementById('sale_price');
            const displayPrice = document.getElementById('displayPrice');
            const displayOriginalPrice = document.getElementById('displayOriginalPrice');
            const pricePreview = document.getElementById('pricePreview');
            
            function updatePricePreview() {
                const originalPrice = parseFloat(originalPriceInput.value) || 0;
                const salePrice = parseFloat(salePriceInput.value) || null;
                
                if (salePrice && salePrice > 0 && salePrice < originalPrice) {
                    // Có giá giảm giá
                    displayPrice.textContent = formatPrice(salePrice) + ' đ';
                    if (!displayOriginalPrice) {
                        const originalSpan = document.createElement('span');
                        originalSpan.className = 'text-muted text-decoration-line-through ms-2';
                        originalSpan.id = 'displayOriginalPrice';
                        displayPrice.parentElement.appendChild(originalSpan);
                    }
                    document.getElementById('displayOriginalPrice').textContent = formatPrice(originalPrice) + ' đ';
                    pricePreview.style.display = 'block';
                } else {
                    // Không có giá giảm giá
                    displayPrice.textContent = formatPrice(originalPrice) + ' đ';
                    if (displayOriginalPrice) {
                        displayOriginalPrice.remove();
                    }
                    pricePreview.style.display = 'block';
                }
            }
            
            function formatPrice(price) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(price));
            }
            
            if (originalPriceInput) {
                originalPriceInput.addEventListener('input', updatePricePreview);
            }
            if (salePriceInput) {
                salePriceInput.addEventListener('input', updatePricePreview);
            }
        });
        </script>

        <div style="margin-bottom: 1.5rem;">
            <label class="form-label" for="image">Ảnh đại diện sản phẩm <span class="text-danger">*</span></label>
            <div class="image-upload-wrapper">
                <?php 
                // Lấy ảnh hiện tại (ưu tiên 'image', sau đó 'image_url')
                $currentImage = $product['image'] ?? $product['image_url'] ?? null;
                if ($isEditing && !empty($currentImage)): 
                ?>
                    <div class="current-image-preview">
                        <img src="<?= htmlspecialchars($currentImage) ?>" alt="Current image" style="max-width: 200px; max-height: 200px; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #e2e8f0;" onerror="this.style.display='none';">
                        <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem;">Ảnh hiện tại</div>
                    </div>
                <?php endif; ?>
                <input type="file" id="image" name="image" class="form-control" accept="image/*" <?= (!$isEditing || empty($currentImage)) ? 'required' : '' ?> 
                       onchange="previewImage(this)" style="padding: 0.5rem;">
                <div id="imagePreview" style="margin-top: 1rem;"></div>
                <small style="color: #64748b; display: block; margin-top: 0.5rem;">
                    <i class="bi bi-info-circle"></i> Chọn file ảnh từ máy tính (JPG, PNG, GIF, WEBP). Kích thước tối đa: 5MB
                </small>
            </div>
        </div>

        <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.style.border = '1px solid #e2e8f0';
                    preview.appendChild(img);
                    
                    const label = document.createElement('div');
                    label.textContent = 'Ảnh mới (chưa lưu)';
                    label.style.fontSize = '0.85rem';
                    label.style.color = '#3b82f6';
                    label.style.marginTop = '0.5rem';
                    label.style.fontWeight = '600';
                    preview.appendChild(label);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        </script>

        <div class="flex-end-actions">
            <a href="<?= BASE_URL ?>?action=admin-products" class="btn-cancel">Hủy</a>
            <button type="submit" class="btn-submit">
                <?= $isEditing ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm' ?>
            </button>
        </div>
    </form>
</div>

<?php if ($isEditing && !empty($attributes)): ?>
    <!-- Form thêm biến thể -->
    <div class="form-card">
        <h3 class="mb-4 fs-125 fw-bold">Thêm biến thể mới (Size/Màu sắc)</h3>
        <form method="POST" action="<?= BASE_URL ?>?action=admin-product-variant-store" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= $productId ?>">
            
            <div class="form-grid-3-compact">
                <div>
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control" placeholder="Mã SKU" required>
                </div>
                <div>
                    <label class="form-label">Giá cộng thêm (VNĐ)</label>
                    <input type="number" step="500" name="additional_price" class="form-control" value="0">
                </div>
                <div>
                    <label class="form-label">Tồn kho</label>
                    <input type="number" min="0" name="stock" class="form-control" value="0" required>
                </div>
                <div>
                    <label class="form-label">Ảnh biến thể <span class="text-danger">*</span></label>
                    <input type="file" name="variant_image" accept="image/*" class="form-control" required>
                    <small class="text-muted">Bắt buộc. JPG/PNG/GIF, tối đa 5MB</small>
                </div>
            </div>

            <?php 
            // Lọc chỉ lấy các attribute có values
            $attributesWithValues = array_filter($attributes, function($attr) {
                return !empty($attr['values']);
            });
            if (!empty($attributesWithValues)): 
            ?>
            <div class="dynamic-attributes-grid cols-<?= min(count($attributesWithValues), 3) ?>">
                <?php foreach ($attributesWithValues as $attribute): ?>
                    <div>
                        <label class="form-label"><?= htmlspecialchars($attribute['attribute_name']) ?> <span class="text-danger">*</span></label>
                        <select class="form-select" name="attribute_values[<?= $attribute['attribute_id'] ?>]" required>
                            <option value="">— Chọn —</option>
                            <?php foreach ($attribute['values'] as $value): ?>
                                <option value="<?= $value['value_id'] ?>"><?= htmlspecialchars($value['value_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit w-100">
                <i class="bi bi-plus-lg"></i> Thêm biến thể
            </button>
        </form>
    </div>

    <!-- Danh sách biến thể hiện có -->
    <div class="form-card variants-section">
        <h3 class="mb-4 fs-125 fw-bold">
            Biến thể hiện có 
            <span class="text-slate small">(<?= count($variants ?? []) ?>)</span>
        </h3>
        
        <?php if (empty($variants)): ?>
            <div class="empty-variants">
                <i class="bi bi-inbox empty-icon-lg"></i>
                <div>Chưa có biến thể nào. Hãy thêm biến thể đầu tiên.</div>
            </div>
        <?php else: ?>
            <?php foreach ($variants as $variant): 
                $variantAttrs = [];
                $variantAttrMap = [];
                foreach ($variant['attributes'] ?? [] as $attr) {
                    $variantAttrs[] = $attr['value_name'] ?? '';
                    $variantAttrMap[$attr['attribute_id']] = $attr['value_id'] ?? null;
                }
                $variantName = implode(' / ', $variantAttrs);
            ?>
                <div class="variant-item">
                    <div class="variant-header">
                        <div class="variant-info">
                            <?= htmlspecialchars($variantName ?: 'Biến thể #' . $variant['variant_id']) ?>
                            <?php if ($variant['sku']): ?>
                                <span class="text-slate small">(SKU: <?= htmlspecialchars($variant['sku']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?= BASE_URL ?>?action=admin-product-variant-delete" 
                              class="d-inline" 
                              onsubmit="return confirm('Xóa biến thể này?');">
                            <input type="hidden" name="variant_id" value="<?= $variant['variant_id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $productId ?>">
                            <button type="submit" class="btn-sm btn-danger-sm">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </form>
                    </div>
                    <div class="dynamic-attributes-grid cols-3 small">
                        <div>
                            <strong class="text-slate">Giá cộng thêm:</strong><br>
                            <?= number_format((float)($variant['additional_price'] ?? 0), 0, ',', '.') ?> VNĐ
                        </div>
                        <div>
                            <strong class="text-slate">Tồn kho:</strong><br>
                            <?= htmlspecialchars($variant['stock'] ?? 0) ?>
                        </div>
                        <div>
                            <strong class="text-slate">ID:</strong><br>
                            #<?= htmlspecialchars($variant['variant_id']) ?>
                        </div>
                        <div>
                            <strong class="text-slate">Ảnh:</strong><br>
                            <?php if (!empty($variant['image_url'])): ?>
                                <img src="<?= htmlspecialchars($variant['image_url']) ?>" alt="Variant image" style="max-width:90px; max-height:90px; border-radius:6px; border:1px solid #e2e8f0;">
                            <?php else: ?>
                                <span class="text-muted">Chưa có</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form class="mt-3 variant-edit-form" method="POST" action="<?= BASE_URL ?>?action=admin-product-variant-update" enctype="multipart/form-data">
                        <input type="hidden" name="variant_id" value="<?= $variant['variant_id'] ?>">
                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                        <div class="form-grid-3-compact">
                            <div>
                                <label class="form-label">SKU</label>
                                <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($variant['sku'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="form-label">Giá cộng thêm (VNĐ)</label>
                                <input type="number" step="500" name="additional_price" class="form-control" value="<?= htmlspecialchars($variant['additional_price'] ?? 0) ?>">
                            </div>
                            <div>
                                <label class="form-label">Tồn kho</label>
                                <input type="number" min="0" name="stock" class="form-control" value="<?= htmlspecialchars($variant['stock'] ?? 0) ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Ảnh biến thể (tùy chọn)</label>
                                <input type="file" name="variant_image" accept="image/*" class="form-control">
                                <small class="text-muted">Bỏ trống nếu giữ ảnh cũ</small>
                            </div>
                        </div>

                        <?php 
                        $attributesWithValues = array_filter($attributes, function($attr) {
                            return !empty($attr['values']);
                        });
                        if (!empty($attributesWithValues)): 
                        ?>
                        <div class="dynamic-attributes-grid cols-<?= min(count($attributesWithValues), 3) ?> mt-3">
                            <?php foreach ($attributesWithValues as $attribute): ?>
                                <div>
                                    <label class="form-label"><?= htmlspecialchars($attribute['attribute_name']) ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" name="attribute_values[<?= $attribute['attribute_id'] ?>]" required>
                                        <option value="">— Chọn —</option>
                                        <?php foreach ($attribute['values'] as $value): ?>
                                            <option value="<?= $value['value_id'] ?>" <?= ($variantAttrMap[$attribute['attribute_id']] ?? null) == $value['value_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($value['value_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-3 text-end">
                            <button type="submit" class="btn-submit btn-sm">
                                <i class="bi bi-save"></i> Lưu biến thể
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php elseif (!$isEditing && !empty($attributes)): ?>
    <!-- Hướng dẫn khi tạo mới -->
    <div class="form-card info-card-alt">
        <h4 class="mb-3 text-primary">
            <i class="bi bi-info-circle"></i> Hướng dẫn
        </h4>
        <ol class="mb-0 ps-4 text-slate">
            <li>Nhập thông tin cơ bản của sản phẩm (tên, giá, mô tả...)</li>
            <li>Nhấn "Tạo sản phẩm" để lưu</li>
            <li>Sau khi tạo thành công, bạn sẽ có thể thêm các biến thể (Size, Màu sắc...)</li>
            <li>Quản lý thuộc tính (Size, Màu sắc) tại <a href="<?= BASE_URL ?>?action=admin-attributes" class="text-primary fw-semibold">Quản lý thuộc tính</a></li>
        </ol>
    </div>
<?php endif; ?>
