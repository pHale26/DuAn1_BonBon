<div class="profile-page">
    <div class="profile-container">
        <div class="profile-header">
            <h1>Thông tin cá nhân</h1>
            <p>Cập nhật thông tin tài khoản của bạn</p>
        </div>

        <div class="profile-card">
            <div class="profile-card-header">
                <h2 class="profile-card-title">Thông tin cơ bản</h2>
            </div>

            <form action="<?= BASE_URL ?>?action=update-profile" method="POST" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname" class="form-label">Họ</label>
                        <input 
                            type="text" 
                            id="firstname" 
                            name="firstname" 
                            class="form-input" 
                            value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="lastname" class="form-label">Tên</label>
                        <input 
                            type="text" 
                            id="lastname" 
                            name="lastname" 
                            class="form-input" 
                            value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" 
                            required
                        >
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Giới tính</label>
                    <div class="gender-group">
                        <div class="gender-option">
                            <input 
                                type="radio" 
                                id="gender-female" 
                                name="gender" 
                                value="female" 
                                <?= ($user['gender'] ?? 'female') === 'female' ? 'checked' : '' ?>
                            >
                            <label for="gender-female">Nữ</label>
                        </div>
                        <div class="gender-option">
                            <input 
                                type="radio" 
                                id="gender-male" 
                                name="gender" 
                                value="male" 
                                <?= ($user['gender'] ?? '') === 'male' ? 'checked' : '' ?>
                            >
                            <label for="gender-male">Nam</label>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="birthday" class="form-label">Ngày sinh</label>
                    <input 
                        type="date" 
                        id="birthday" 
                        name="birthday" 
                        class="form-input" 
                        value="<?= htmlspecialchars($user['birthday'] ?? '') ?>"
                        max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input" 
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                            pattern="[0-9]{10,11}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input input-disabled-static" 
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                            disabled
                        >
                        <small class="text-muted small mt-1 d-block">Email không thể thay đổi</small>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="address" class="form-label">Địa chỉ</label>
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        class="form-input" 
                        value="<?= htmlspecialchars($user['address'] ?? '') ?>" 
                        required
                    >
                </div>

                <div class="form-group full-width mt-3">
                    <button type="submit" class="btn-submit">Cập nhật thông tin</button>
                </div>
            </form>
            
            <div class="mt-4 pt-4 border-top">
                <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary text-decoration-none">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</div>

