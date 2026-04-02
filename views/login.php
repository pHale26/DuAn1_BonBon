<section class="login-page">
    <div class="login-grid">
        <div class="login-hero">
            <h1>Đăng nhập</h1>
        </div>

        <div class="login-card">
            <h2>Thông tin tài khoản</h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-dark">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>?action=login" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small text-muted" for="remember">Ghi nhớ lần đăng nhập này</label>
                </div>

                <a href="<?= BASE_URL ?>?action=show-forgot" class="forgot-link">Quên mật khẩu?</a>

                <button type="submit" class="login-button">Đăng nhập</button>

                <div class="register-cta">
                    <span>Chưa có tài khoản?</span>
                    <a href="<?= BASE_URL ?>?action=show-register">Đăng ký ngay</a>
                </div>
            </form>

            <a href="<?= BASE_URL ?>" class="back-link">
                <span>←</span> Quay lại trang chủ
            </a>
        </div>
    </div>
</section>
