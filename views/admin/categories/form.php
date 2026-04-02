<?php
$isEditing = isset($category);
$categoryId = $isEditing ? (int)$category['category_id'] : null;
?>

<div class="admin-page-header">
    <h2><?= $isEditing ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' ?></h2>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>?action=admin-categories" class="btn btn-light-soft">Quay lại</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" action="<?= BASE_URL ?>?action=<?= $isEditing ? 'admin-category-update' : 'admin-category-store' ?>">
        <?php if ($isEditing): ?>
            <input type="hidden" name="category_id" value="<?= $categoryId ?>">
        <?php endif; ?>
        
        <div class="mb-4">
            <label class="form-label" for="name">Tên danh mục <span class="text-danger">*</span></label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="form-control" 
                required
                value="<?= htmlspecialchars($category['category_name'] ?? $formData['category_name'] ?? '') ?>"
                placeholder="Nhập tên danh mục"
            >
        </div>

        <div class="mb-4">
            <label class="form-label" for="description">Mô tả</label>
            <textarea 
                id="description" 
                name="description" 
                class="form-control"
                placeholder="Nhập mô tả danh mục (tùy chọn)"
            ><?= htmlspecialchars($category['description'] ?? $formData['description'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>?action=admin-categories" class="btn-cancel">Hủy</a>
            <button type="submit" class="btn-submit">
                <?= $isEditing ? 'Cập nhật' : 'Thêm mới' ?>
            </button>
        </div>
    </form>
</div>




