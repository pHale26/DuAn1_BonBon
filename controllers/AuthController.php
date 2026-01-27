<?php

class AuthController
{
    private UserModel $userModel;
    private PasswordResetModel $passwordResetModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
    }

    /**
     * Hiển thị trang đăng nhập
     */
    public function showLogin(): void
    {
        $title = 'Đăng nhập';
        $view  = 'login';

        require_once PATH_VIEW . 'main.php';
    }

    /**
     * Trang đăng ký tài khoản
     */
    public function showRegister(): void
    {
        $title = 'Đăng ký';
        $view  = 'register';

        require_once PATH_VIEW . 'main.php';
    }

    /**
     * Trang quên mật khẩu
     */
    public function showForgotPassword(): void
    {
        $title = 'Quên mật khẩu';
        $view  = 'forgot-password';

        require_once PATH_VIEW . 'main.php';
    }

    /**
     * Xử lý đăng ký tài khoản
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('show-register');
        }

        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $gender    = $_POST['gender'] ?? 'female';
        $birthday  = $_POST['birthday'] ?? '';
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? '')); // Chuyển email về lowercase
        $password  = $_POST['password'] ?? '';

        $errors = [];

        if ($firstname === '') {
            $errors[] = 'Vui lòng nhập họ.';
        }

        if ($lastname === '') {
            $errors[] = 'Vui lòng nhập tên.';
        }

        if (!in_array($gender, ['female', 'male'], true)) {
            $errors[] = 'Giới tính không hợp lệ.';
        }

        if ($birthday === '') {
            $errors[] = 'Vui lòng chọn ngày sinh.';
        } else {
            // Kiểm tra tuổi trên 18
            $birthdayDate = new DateTime($birthday);
            $today = new DateTime();
            $age = $today->diff($birthdayDate)->y;
            
            if ($age < 18) {
                $errors[] = 'Bạn phải trên 18 tuổi để đăng ký tài khoản.';
            }
        }

        if ($phone !== '' && !preg_match('/^[0-9]{10,11}$/', $phone)) {
            $errors[] = 'Số điện thoại phải có 10 hoặc 11 chữ số.';
        }

        if ($email === '') {
            $errors[] = 'Vui lòng nhập email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Vui lòng nhập email hợp lệ.';
        } else {
            // Kiểm tra email đã tồn tại chưa (case-insensitive)
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                $errors[] = 'Email này đã được sử dụng. Vui lòng sử dụng email khác hoặc đăng nhập.';
            }
        }

        if ($password === '') {
            $errors[] = 'Vui lòng nhập mật khẩu.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }

        if ($errors) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('show-register');
        }

        try {
            // Kiểm tra lại email một lần nữa trước khi insert (tránh race condition)
            if ($this->userModel->findByEmail($email)) {
                $_SESSION['error'] = 'Email này đã được sử dụng. Vui lòng sử dụng email khác.';
                $this->redirect('show-register');
            }

            $userData = [
                'first_name' => $firstname,
                'last_name'  => $lastname,
                'gender'     => $gender,
                'birthday'   => $birthday,
                'phone'      => $phone ?: null,
                'address'    => $address ?: null,
                'email'      => $email,
                'password'   => password_hash($password, PASSWORD_BCRYPT),
            ];
            // Đảm bảo KHÔNG có id trong userData
            unset($userData['id'], $userData['user_id'], $userData['userId']);
            
            $userId = $this->userModel->create($userData);

            if (!$userId || $userId <= 0) {
                throw new Exception('Không thể tạo tài khoản. Vui lòng thử lại.');
            }

            // Tạo token xác thực
            $token     = $this->generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $this->userModel->setVerificationToken($userId, $token, $expiresAt);

            // Thử gửi email (không fail nếu không gửi được)
            $sent = false;
            try {
                $sent = $this->sendVerificationEmail($email, trim("$firstname $lastname"), $token);
            } catch (Exception $mailException) {
                error_log('Email sending failed: ' . $mailException->getMessage());
                // Tiếp tục dù email không gửi được
            }

            if ($sent) {
                $_SESSION['success'] = 'Đăng ký thành công. Vui lòng kiểm tra Gmail để xác thực tài khoản.';
            } else {
                $_SESSION['success'] = 'Đăng ký thành công. Tài khoản đã được tạo. Vui lòng liên hệ admin để kích hoạt tài khoản.';
            }
            
            $this->redirect('show-login');
        } catch (PDOException $e) {
            error_log('Registration PDO Error: ' . $e->getMessage());
            $errorMsg = $e->getMessage();
            // Hiển thị lỗi cụ thể để debug
            if (strpos($errorMsg, 'Duplicate entry') !== false) {
                // Kiểm tra xem trường nào bị duplicate
                if (stripos($errorMsg, 'email') !== false) {
                    $_SESSION['error'] = 'Email này đã được sử dụng. Vui lòng sử dụng email khác hoặc đăng nhập.';
                } elseif (stripos($errorMsg, 'phone') !== false) {
                    $_SESSION['error'] = 'Số điện thoại này đã được sử dụng. Vui lòng sử dụng số điện thoại khác.';
                } else {
                    // Hiển thị lỗi chi tiết để debug
                    $_SESSION['error'] = 'Thông tin đã được sử dụng. Vui lòng kiểm tra lại email và số điện thoại. Lỗi: ' . htmlspecialchars(substr($errorMsg, 0, 150));
                }
            } elseif (strpos($errorMsg, 'SQLSTATE') !== false) {
                // Lỗi SQL - hiển thị thông báo chi tiết để debug
                $_SESSION['error'] = 'Đăng ký thất bại. Lỗi: ' . htmlspecialchars(substr($errorMsg, 0, 200));
            } else {
                $_SESSION['error'] = 'Đăng ký thất bại. Vui lòng kiểm tra lại thông tin và thử lại.';
            }
            $this->redirect('show-register');
        } catch (Exception $e) {
            error_log('Registration Error: ' . $e->getMessage());
            $_SESSION['error'] = 'Đăng ký thất bại: ' . $e->getMessage() . '. Vui lòng thử lại.';
            $this->redirect('show-register');
        }
    }

    /**
     * Xử lý đăng nhập
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('show-login');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $_SESSION['error'] = 'Vui lòng nhập Email và mật khẩu hợp lệ.';
            $this->redirect('show-login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Email hoặc mật khẩu không đúng.';
            $this->redirect('show-login');
        }

        // Kiểm tra tài khoản có bị khóa không
        if (isset($user['is_locked']) && (bool)$user['is_locked']) {
            $_SESSION['error'] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.';
            $this->redirect('show-login');
        }

        if (!empty($user['session_token'])) {
            $_SESSION['error'] = 'Tài khoản chưa được xác thực. Vui lòng kiểm tra Gmail.';
            $this->redirect('show-login');
        }

        $userId = $user['user_id'] ?? ($user['id'] ?? null);
        
        $_SESSION['user'] = [
            'id'       => $userId,
            'fullname' => $user['full_name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'email'    => $user['email'],
            'role'     => $user['role'] ?? 'customer',
        ];

        // Nếu có cart trong session (từ trước khi đăng nhập), sync vào database
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart']) && $userId) {
            require_once PATH_MODEL . 'CartModel.php';
            $cartModel = new CartModel();
            $cartModel->syncSessionCartToDatabase((int)$userId, $_SESSION['cart']);
        }
        
        // Load cart từ database vào session (ghi đè cart trong session nếu có)
        if ($userId) {
            require_once PATH_MODEL . 'CartModel.php';
            $cartModel = new CartModel();
            $cartModel->loadCartToSession((int)$userId, false);
        }

        $_SESSION['success'] = 'Đăng nhập thành công.';
        
        // Nếu là admin, chuyển hướng về trang thống kê
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
            $this->redirect('admin-statistics');
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Đăng xuất
     */
    public function logout(): void
    {
        // Xóa cart trong session khi đăng xuất
        unset($_SESSION['cart']);
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['selected_cart_items']);
        
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $_SESSION['success'] = 'Bạn đã đăng xuất.';
        $this->redirect('/');
    }

    /**
     * Hiển thị trang thông tin cá nhân
     */
    public function showProfile(): void
    {
        if (!isset($_SESSION['user'])) {
            set_flash('danger', 'Vui lòng đăng nhập để xem thông tin cá nhân.');
            $this->redirect('show-login');
        }

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            set_flash('danger', 'Thông tin người dùng không hợp lệ.');
            $this->redirect('/');
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            set_flash('danger', 'Không tìm thấy thông tin người dùng.');
            $this->redirect('/');
        }

        $title = 'Thông tin cá nhân';
        $view  = 'profile';

        require_once PATH_VIEW . 'main.php';
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('profile');
        }

        if (!isset($_SESSION['user'])) {
            set_flash('danger', 'Vui lòng đăng nhập.');
            $this->redirect('show-login');
        }

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            set_flash('danger', 'Thông tin người dùng không hợp lệ.');
            $this->redirect('profile');
        }

        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $gender    = $_POST['gender'] ?? 'female';
        $birthday  = $_POST['birthday'] ?? '';
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        $errors = [];

        if ($firstname === '') {
            $errors[] = 'Vui lòng nhập họ.';
        }

        if ($lastname === '') {
            $errors[] = 'Vui lòng nhập tên.';
        }

        if (!in_array($gender, ['female', 'male'], true)) {
            $errors[] = 'Giới tính không hợp lệ.';
        }

        if ($phone === '') {
            $errors[] = 'Vui lòng nhập số điện thoại.';
        }

        if ($address === '') {
            $errors[] = 'Vui lòng nhập địa chỉ.';
        }

        if ($errors) {
            set_flash('danger', implode('<br>', $errors));
            $this->redirect('profile');
        }

        try {
            $this->userModel->updateProfile($userId, [
                'first_name' => $firstname,
                'last_name'  => $lastname,
                'gender'     => $gender,
                'birthday'   => $birthday,
                'phone'      => $phone,
                'address'    => $address,
            ]);

            // Cập nhật session
            $updatedUser = $this->userModel->findById($userId);
            $_SESSION['user']['fullname'] = $updatedUser['full_name'] ?? trim("$firstname $lastname");

            set_flash('success', 'Cập nhật thông tin cá nhân thành công.');
            $this->redirect('profile');
        } catch (Exception $e) {
            set_flash('danger', 'Cập nhật thông tin thất bại, vui lòng thử lại.');
            $this->redirect('profile');
        }
    }

    public function verifyAccount(): void
    {
        // Khởi tạo session độc lập (hoạt động trên mọi thiết bị)
        if (session_status() === PHP_SESSION_NONE) {
            // Cấu hình session để hoạt động trên mọi thiết bị
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        
        try {
            // Lấy token từ URL parameter (hoạt động độc lập, không phụ thuộc session)
            $rawToken = $_GET['token'] ?? '';
            
            if (empty($rawToken)) {
                // Khởi tạo session nếu chưa có để lưu thông báo lỗi
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['error'] = 'Liên kết xác thực không hợp lệ. Vui lòng kiểm tra lại link trong email.';
                $this->redirect('show-login');
                return;
            }

            // Xử lý token: loại bỏ khoảng trắng và decode URL encoding
            // Xử lý nhiều cách để đảm bảo hoạt động trên mọi thiết bị
            $token = trim($rawToken);
            
            // Thử các cách decode token (email client có thể encode nhiều lần)
            $tokenVariants = [
                $token,                                    // Token gốc
                urldecode($token),                         // Decode 1 lần
                urldecode(urldecode($token)),              // Decode 2 lần
                rawurldecode($token),                      // Raw decode
                rawurldecode(rawurldecode($token)),        // Raw decode 2 lần
            ];
            
            // Loại bỏ các variant trùng lặp và rỗng
            $tokenVariants = array_filter(array_unique($tokenVariants));
            
            // Tìm user với bất kỳ variant nào của token (hoạt động độc lập với session)
            $user = null;
            $validToken = null;
            
            foreach ($tokenVariants as $tokenVariant) {
                // Loại bỏ khoảng trắng và kiểm tra format
                $tokenVariant = trim($tokenVariant);
                
                // Token phải là hex string, độ dài 64 ký tự
                if (preg_match('/^[0-9a-f]{64}$/i', $tokenVariant)) {
                    $foundUser = $this->userModel->findByVerificationToken($tokenVariant);
                    if ($foundUser) {
                        $user = $foundUser;
                        $validToken = $tokenVariant;
                        break;
                    }
                }
            }
            
            if (!$user) {
                error_log('Verification failed - Token not found. Raw token: ' . substr($rawToken, 0, 50) . ', Length: ' . strlen($rawToken));
                // Khởi tạo session để lưu thông báo lỗi
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['error'] = 'Liên kết xác thực đã hết hạn hoặc không tồn tại. Vui lòng kiểm tra lại link trong email hoặc đăng nhập để gửi lại email xác thực.';
                $this->redirect('show-login');
                return;
            }

            // Kiểm tra token đã được sử dụng chưa (nếu session_token đã null thì đã verify rồi)
            if (empty($user['session_token'])) {
                // Tài khoản đã được xác thực trước đó
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['success'] = 'Tài khoản của bạn đã được xác thực.';
                $this->redirect('/');
                return;
            }

            // Kiểm tra hết hạn
            if (!empty($user['session_expires'])) {
                $expiresTime = strtotime($user['session_expires']);
                if ($expiresTime < time()) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['error'] = 'Liên kết xác thực đã hết hạn. Vui lòng đăng nhập để gửi lại email xác thực.';
                    $this->redirect('show-login');
                    return;
                }
            }

            // Xác thực tài khoản (hoạt động độc lập, không phụ thuộc session)
            try {
                $this->userModel->markVerified((int)$user['user_id']);
                
                error_log('Account verified successfully for user ID: ' . $user['user_id'] . ' - Token: ' . substr($validToken ?? $rawToken, 0, 20));
                
                // Khởi tạo session để lưu thông báo thành công
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['success'] = 'Tài khoản đã được xác thực thành công.';
                $this->redirect('/');
                return;
            } catch (Exception $e) {
                error_log('Error marking account as verified: ' . $e->getMessage());
                throw $e; // Re-throw để xử lý ở catch block bên ngoài
            }
        } catch (PDOException $e) {
            error_log('Verification PDO error: ' . $e->getMessage());
            error_log('Verification PDO error trace: ' . $e->getTraceAsString());
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['error'] = 'Có lỗi xảy ra khi xác thực tài khoản. Vui lòng thử lại sau hoặc liên hệ hỗ trợ.';
            $this->redirect('show-login');
        } catch (Exception $e) {
            error_log('Verification error: ' . $e->getMessage());
            error_log('Verification error trace: ' . $e->getTraceAsString());
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['error'] = 'Có lỗi xảy ra khi xác thực tài khoản. Vui lòng thử lại sau.';
            $this->redirect('show-login');
        }
    }

    /**
     * Gửi email quên mật khẩu + tạo token
     */
    public function handleForgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('show-forgot');
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Vui lòng nhập Gmail hợp lệ.';
            $this->redirect('show-forgot');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $_SESSION['error'] = 'Không tìm thấy tài khoản với Gmail này.';
            $this->redirect('show-forgot');
        }

        if (!empty($user['session_token'])) {
            $_SESSION['error'] = 'Tài khoản chưa được xác thực. Không thể đặt lại mật khẩu.';
            $this->redirect('show-login');
        }

        $token     = $this->generateToken();
        $otp       = $this->generateOtp();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $this->passwordResetModel->create((int)$user['user_id'], $token, $otp, $expiresAt);

        $sent = $this->sendForgotEmail($email, $user['full_name'] ?? $user['email'], $token, $otp);

        if (!$sent) {
            $_SESSION['error'] = 'Không thể gửi email đặt lại mật khẩu. Vui lòng thử lại sau.';
            $this->redirect('show-forgot');
        }

        $_SESSION['success'] = 'Đã gửi hướng dẫn đặt lại mật khẩu. Vui lòng kiểm tra Gmail.';
        $this->redirect('show-login');
    }

    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if ($token === '') {
            $_SESSION['error'] = 'Liên kết không hợp lệ.';
            $this->redirect('show-forgot');
        }

        $record = $this->passwordResetModel->findValidToken($token);

        if (!$record || (int)($record['is_used'] ?? 0) === 1 || strtotime($record['expires_at']) < time()) {
            $_SESSION['error'] = 'Liên kết đặt lại mật khẩu đã hết hạn hoặc không tồn tại.';
            $this->redirect('show-forgot');
        }

        $title = 'Đặt lại mật khẩu';
        $view  = 'reset-password';
        $resetToken = $token;

        require_once PATH_VIEW . 'main.php';
    }

    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('show-login');
        }

        $token       = $_POST['token'] ?? '';
        $otp         = trim($_POST['otp'] ?? '');
        $password    = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        if ($token === '' || $otp === '') {
            $_SESSION['error'] = 'Thông tin không hợp lệ.';
            $this->redirect('show-forgot');
        }

        $record = $this->passwordResetModel->findValidToken($token);

        if (
            !$record ||
            (int)($record['is_used'] ?? 0) === 1 ||
            strtotime($record['expires_at']) < time()
        ) {
            $_SESSION['error'] = 'Liên kết đặt lại mật khẩu đã hết hạn.';
            $this->redirect('show-forgot');
        }

        if ($otp !== ($record['otp_code'] ?? '')) {
            $_SESSION['error'] = 'Mã OTP không chính xác.';
            $this->redirect('show-reset-password&token=' . urlencode($token));
        }

        if ($password !== $confirmation || strlen($password) < 6) {
            $_SESSION['error'] = 'Mật khẩu phải trùng khớp và có ít nhất 6 ký tự.';
            $this->redirect('show-reset-password&token=' . urlencode($token));
        }

        $this->userModel->updatePassword((int)$record['user_id'], password_hash($password, PASSWORD_BCRYPT));
        $this->passwordResetModel->markUsed((int)$record['reset_id']);

        $_SESSION['success'] = 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.';
        $this->redirect('show-login');
    }

    /**
     * Tự động đăng nhập user sau khi xác thực tài khoản
     */
    private function autoLogin(array $user): void
    {
        $_SESSION['user'] = [
            'id'       => $user['user_id'] ?? ($user['id'] ?? null),
            'fullname' => $user['full_name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'email'    => $user['email'],
            'role'     => $user['role'] ?? 'customer',
        ];
    }

    private function redirect(string $action): void
    {
        if ($action === '/') {
            $url = BASE_URL;
        } elseif (strpos($action, '?') !== false || strpos($action, '&') !== false) {
            // Nếu action đã chứa query string, nối trực tiếp
            $url = BASE_URL . (strpos($action, '?') === 0 ? $action : '?' . $action);
        } else {
            $url = BASE_URL . '?action=' . urlencode($action);
        }
        header("Location: {$url}");
        exit;
    }

    private function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    private function generateOtp(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendVerificationEmail(string $email, string $name, string $token): bool
    {
        // Tạo link xác thực - đảm bảo token được encode đúng cách
        // Token là hex string nên urlencode sẽ giữ nguyên các ký tự 0-9a-f
        $encodedToken = urlencode($token);
        $link = BASE_URL . '?action=verify-account&token=' . $encodedToken;

        $html = "
            <p>Xin chào {$name},</p>
            <p>Cảm ơn bạn đã đăng ký BonBonWear. Nhấn vào nút bên dưới để xác thực tài khoản:</p>
            <p><a href=\"{$link}\" style=\"display:inline-block;padding:12px 20px;background:#000;color:#fff;text-decoration:none;border-radius:4px\">Xác thực tài khoản</a></p>
            <p>Hoặc copy và dán link sau vào trình duyệt:</p>
            <p style=\"word-break:break-all;color:#666;font-size:12px\">{$link}</p>
            <p>Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email.</p>
        ";

        return send_mail($email, '[BonBonWear] Xác thực tài khoản', $html, $name);
    }

    private function sendForgotEmail(string $email, string $name, string $token, string $otp): bool
    {
        $link = BASE_URL . '?action=show-reset-password&token=' . urlencode($token);

        $html = "
            <p>Xin chào {$name},</p>
            <p>Chúng tôi đã nhận yêu cầu đặt lại mật khẩu cho tài khoản BonBonWear của bạn.</p>
            <p><strong>Mã OTP:</strong> {$otp}</p>
            <p>Nhấn vào liên kết bên dưới để đặt lại mật khẩu trong vòng 30 phút:</p>
            <p><a href=\"{$link}\" style=\"display:inline-block;padding:12px 20px;background:#000;color:#fff;text-decoration:none;border-radius:4px\">Đặt lại mật khẩu</a></p>
            <p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>
        ";

        return send_mail($email, '[BonBonWear] Đặt lại mật khẩu', $html, $name);
    }
}

