<section class="register-page">
    <div class="register-grid">
        <div class="register-hero">
            <h1>Tạo tài khoản</h1>
        </div>

        <div class="register-form">
            <form action="<?= BASE_URL ?>?action=register" method="POST">
                <label for="firstname">Họ</label>
                <input type="text" id="firstname" name="firstname" placeholder="Nhập họ" required>

                <label for="lastname">Tên</label>
                <input type="text" id="lastname" name="lastname" placeholder="Nhập tên" required>

                <div class="gender-group">
                    <label class="d-flex align-items-center gap-1 mb-0">
                        <input type="radio" name="gender" value="female" checked> Nữ
                    </label>
                    <label class="d-flex align-items-center gap-1 mb-0">
                        <input type="radio" name="gender" value="male"> Nam
                    </label>
                </div>

                <label for="birthday">Ngày sinh</label>
                <input type="date" id="birthday" name="birthday" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                <small id="age-error" class="age-error">Bạn phải trên 18 tuổi để đăng ký tài khoản.</small>

                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" placeholder="0123456789" pattern="[0-9]{10,11}" required>

                <label for="address">Địa chỉ</label>
                <input type="text" id="address" name="address" placeholder="Nhập địa chỉ của bạn" required>

                <label for="email">Gmail</label>
                <input type="email" id="email" name="email" placeholder="tennguoidung@gmail.com" required>

                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Tạo mật khẩu" required>

                <p class="recaptcha-hint">This site is protected by reCAPTCHA.</p>

                <button class="register-button" type="submit">Đăng ký</button>
            </form>
            
            <script>
                document.getElementById('birthday').addEventListener('change', function() {
                    const birthday = new Date(this.value);
                    const today = new Date();
                    const age = today.getFullYear() - birthday.getFullYear();
                    const monthDiff = today.getMonth() - birthday.getMonth();
                    const dayDiff = today.getDate() - birthday.getDate();
                    
                    let actualAge = age;
                    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                        actualAge--;
                    }
                    
                    const ageError = document.getElementById('age-error');
                    if (actualAge < 18) {
                        ageError.style.display = 'block';
                        this.setCustomValidity('Bạn phải trên 18 tuổi để đăng ký tài khoản.');
                    } else {
                        ageError.style.display = 'none';
                        this.setCustomValidity('');
                    }
                });
            </script>

            <a href="<?= BASE_URL ?>" class="back-link">← Quay lại trang chủ</a>
        </div>
    </div>
</section>

