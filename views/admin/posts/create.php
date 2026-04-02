<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Thêm bài viết mới</h1>
        <a href="<?= BASE_URL ?>?action=admin-posts" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>?action=admin-post-store" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-9">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Tiêu đề bài viết <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title" required 
                                   placeholder="Nhập tiêu đề bài viết...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-bold">Tóm tắt ngắn</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"
                                      placeholder="Mô tả ngắn gọn về nội dung bài viết..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label fw-bold">Nội dung chi tiết</label>
                            <textarea class="form-control" id="content" name="content" rows="15"></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-3">
                            <div class="card-header bg-light fw-bold">Đăng bài</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="published">Công khai (Published)</option>
                                        <option value="draft">Bản nháp (Draft)</option>
                                        <option value="hidden">Ẩn (Hidden)</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1">
                                    <label class="form-check-label" for="is_featured">Đánh dấu nổi bật</label>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Lưu bài viết
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light fw-bold">Ảnh đại diện</div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <img src="" id="previewImage" class="img-fluid rounded d-none mb-2" 
                                         style="max-height: 200px; width: 100%; object-fit: cover;">
                                    <div id="placeholderImage" class="bg-light rounded p-4 mb-2 d-flex align-items-center justify-content-center"
                                         style="height: 150px; text-align: center;">
                                        <div class="text-muted">
                                            <i class="bi bi-image fs-1"></i><br>
                                            Chưa có ảnh
                                        </div>
                                    </div>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*"
                                           onchange="previewFile()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewFile() {
    const preview = document.getElementById('previewImage');
    const placeholder = document.getElementById('placeholderImage');
    const file = document.getElementById('image').files[0];
    const reader = new FileReader();

    reader.addEventListener("load", function () {
        preview.src = reader.result;
        preview.classList.remove('d-none');
        placeholder.classList.add('d-none');
    }, false);

    if (file) {
        reader.readAsDataURL(file);
    }
}
</script>
