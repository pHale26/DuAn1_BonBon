<section class="forgot-page">
    <div class="forgot-grid">
        <div class="forgot-hero">
            <h1>Quên mật khẩu</h1>
        </div>

        <div class="forgot-card">
            <h2>Gửi yêu cầu đặt lại mật khẩu</h2>
            <form action="<?= BASE_URL ?>?action=forgot-password" method="POST">
                <label for="forgotEmail">Gmail</label>
                <input type="email" id="forgotEmail" name="email" placeholder="tennguoidung@gmail.com" required>

                <p class="recaptcha-hint">This site is protected by reCAPTCHA.</p>

                <button class="forgot-button" type="submit">Gửi yêu cầu</button>
            </form>

            <a href="<?= BASE_URL ?>?action=show-login" class="back-link">← Quay lại đăng nhập</a>
        </div>
    </div>
</section>

