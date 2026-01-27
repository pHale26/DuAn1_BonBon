<section class="reset-page">
    <div class="reset-grid">
        <div class="reset-hero">
            <h1>Đặt lại mật khẩu</h1>
        </div>

        <div class="reset-card">
            <h2>Nhập OTP & mật khẩu mới</h2>

            <form action="<?= BASE_URL ?>?action=reset-password" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($resetToken ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label" for="otp">Mã OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" placeholder="Nhập mã 6 chữ số" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu mới</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Tạo mật khẩu mới" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Nhập lại mật khẩu" required>
                </div>

                <button type="submit" class="reset-button">Cập nhật mật khẩu</button>
            </form>

            <a href="<?= BASE_URL ?>?action=show-login" class="back-link">← Quay lại đăng nhập</a>
        </div>
    </div>
</section>

