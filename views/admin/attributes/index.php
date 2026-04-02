<div class="attributes-container">
    <div class="attributes-header">
        <h1>Quản lý thuộc tính</h1>
        <button type="button" class="btn-add" onclick="showAddAttributeModal()">
            <i class="bi bi-plus-lg"></i>
            Thêm thuộc tính mới
        </button>
    </div>

    <?php if (empty($attributes)): ?>
    <div class="empty-state">
        <i class="bi bi-inbox empty-icon-lg"></i>
        <div>Chưa có thuộc tính nào. Hãy thêm thuộc tính đầu tiên (ví dụ: Size, Màu sắc)</div>
    </div>
<?php else: ?>
    <?php foreach ($attributes as $attribute): ?>
        <div class="attribute-card">
            <div class="attribute-header">
                <div class="attribute-name"><?= htmlspecialchars($attribute['attribute_name']) ?></div>
                <div class="attribute-actions">
                    <button type="button" class="btn-sm btn-edit-sm" onclick="showEditAttributeModal(<?= $attribute['attribute_id'] ?>, '<?= htmlspecialchars($attribute['attribute_name'], ENT_QUOTES) ?>')">
                        <i class="bi bi-pencil"></i> Sửa
                    </button>
                    <form method="POST" action="<?= BASE_URL ?>?action=admin-attribute-delete" class="d-inline" onsubmit="return confirm('Xóa thuộc tính này sẽ xóa tất cả giá trị liên quan. Bạn có chắc?');">
                        <input type="hidden" name="attribute_id" value="<?= $attribute['attribute_id'] ?>">
                        <button type="submit" class="btn-sm btn-delete-sm">
                            <i class="bi bi-trash"></i> Xóa
                        </button>
                    </form>
                </div>
            </div>

            <div class="values-list">
                <?php if (empty($attribute['values'])): ?>
                    <div class="grid-span-full text-slate fst-italic">Chưa có giá trị nào</div>
                <?php else: ?>
                    <?php foreach ($attribute['values'] as $value): ?>
                        <div class="value-item">
                            <span class="value-name"><?= htmlspecialchars($value['value_name']) ?></span>
                            <div class="value-actions">
                                <button type="button" class="btn-icon btn-icon-warning" onclick="showEditValueModal(<?= $value['value_id'] ?>, '<?= htmlspecialchars($value['value_name'], ENT_QUOTES) ?>')" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="<?= BASE_URL ?>?action=admin-attribute-value-delete" class="d-inline" onsubmit="return confirm('Xóa giá trị này?');">
                                    <input type="hidden" name="value_id" value="<?= $value['value_id'] ?>">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="add-value-form">
                <form method="POST" action="<?= BASE_URL ?>?action=admin-attribute-value-store" class="form-inline">
                    <input type="hidden" name="attribute_id" value="<?= $attribute['attribute_id'] ?>">
                    <div class="form-group">
                        <label>Thêm giá trị mới</label>
                        <input type="text" name="value_name" class="form-control" placeholder="Ví dụ: S, M, L hoặc Đỏ, Xanh..." required>
                    </div>
                    <button type="submit" class="btn-add-value">
                        <i class="bi bi-plus"></i> Thêm
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal thêm/sửa thuộc tính -->
<div id="attributeModal" class="modal-form" onclick="if(event.target === this) closeAttributeModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 id="modalTitle" class="modal-title-spacing">Thêm thuộc tính</h3>
        <form method="POST" id="attributeForm">
            <input type="hidden" name="attribute_id" id="modalAttributeId">
            <div class="modal-field">
                <label class="modal-label-strong">Tên thuộc tính</label>
                <input type="text" name="name" id="modalAttributeName" class="form-control" required placeholder="Ví dụ: Size, Màu sắc">
            </div>
            <div class="modal-field" id="initialValueField">
                <label class="modal-label-strong">Giá trị đầu tiên</label>
                <input type="text" name="initial_value_name" id="modalInitialValueName" class="form-control" required placeholder="Ví dụ: S hoặc Đỏ">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAttributeModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal sửa giá trị -->
<div id="valueModal" class="modal-form" onclick="if(event.target === this) closeValueModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="modal-title-spacing">Sửa giá trị</h3>
        <form method="POST" id="valueForm" action="<?= BASE_URL ?>?action=admin-attribute-value-update">
            <input type="hidden" name="value_id" id="modalValueId">
            <div class="modal-field">
                <label class="modal-label-strong">Tên giá trị</label>
                <input type="text" name="value_name" id="modalValueName" class="form-control" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeValueModal()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu</button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
function showAddAttributeModal() {
    document.getElementById('modalTitle').textContent = 'Thêm thuộc tính';
    document.getElementById('attributeForm').action = '<?= BASE_URL ?>?action=admin-attribute-store';
    document.getElementById('modalAttributeId').value = '';
    document.getElementById('modalAttributeName').value = '';
    document.getElementById('modalInitialValueName').value = '';
    document.getElementById('initialValueField').style.display = 'block';
    document.getElementById('attributeModal').classList.add('active');
}

function showEditAttributeModal(id, name) {
    document.getElementById('modalTitle').textContent = 'Sửa thuộc tính';
    document.getElementById('attributeForm').action = '<?= BASE_URL ?>?action=admin-attribute-update';
    document.getElementById('modalAttributeId').value = id;
    document.getElementById('modalAttributeName').value = name;
    document.getElementById('initialValueField').style.display = 'none';
    document.getElementById('attributeModal').classList.add('active');
}

function closeAttributeModal() {
    document.getElementById('attributeModal').classList.remove('active');
}

function showEditValueModal(id, name) {
    document.getElementById('modalValueId').value = id;
    document.getElementById('modalValueName').value = name;
    document.getElementById('valueModal').classList.add('active');
}

function closeValueModal() {
    document.getElementById('valueModal').classList.remove('active');
}
</script>

