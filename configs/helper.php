<?php

if (!function_exists('debug')) {
    function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }
}

if (!function_exists('upload_file')) {
    function upload_file($folder, $file)
    {
        // Tạo thư mục nếu chưa có
        $uploadDir = PATH_ASSETS_UPLOADS . $folder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Tạo tên file unique
        $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $filename = time() . '-' . uniqid() . '.' . $extension;
        $targetFile = $folder . '/' . $filename;
        $fullPath = PATH_ASSETS_UPLOADS . $targetFile;

        // Upload file
        if (move_uploaded_file($file["tmp_name"], $fullPath)) {
            return $targetFile;
        }

        throw new Exception('Upload file không thành công! Path: ' . $fullPath);
    }
}

if (!function_exists('set_flash')) {
    /**
     * Thiết lập flash message
     * @param string $type Loại message: 'success', 'danger', 'warning', 'info'
     * @param string $message Nội dung message
     * @param array $data Dữ liệu bổ sung (optional)
     */
    function set_flash(string $type, string $message, array $data = []): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
            'data' => $data
        ];
    }
}

if (!function_exists('get_flash')) {
    /**
     * Lấy flash message và xóa nó sau khi lấy
     * Hỗ trợ cả hệ thống mới (flash) và hệ thống cũ (success/error)
     * @return array|null Mảng chứa 'type', 'message', 'data' hoặc null nếu không có
     */
    function get_flash(): ?array
    {
        // Ưu tiên hệ thống flash mới
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        
        // Hỗ trợ hệ thống cũ với $_SESSION['success'] và $_SESSION['error']
        if (isset($_SESSION['success'])) {
            $message = $_SESSION['success'];
            unset($_SESSION['success']);
            return [
                'type' => 'success',
                'message' => $message,
                'data' => []
            ];
        }
        
        if (isset($_SESSION['error'])) {
            $message = $_SESSION['error'];
            unset($_SESSION['error']);
            return [
                'type' => 'danger',
                'message' => $message,
                'data' => []
            ];
        }
        
        return null;
    }
}

if (!function_exists('send_mail')) {
    /**
     * Gửi email sử dụng PHPMailer hoặc mail() của PHP
     * @param string $toEmail Email người nhận
     * @param string $subject Tiêu đề email
     * @param string $htmlBody Nội dung HTML của email
     * @param string $toName Tên người nhận (optional)
     * @return bool True nếu gửi thành công, False nếu thất bại
     */
    function send_mail(string $toEmail, string $subject, string $htmlBody, string $toName = ''): bool
    {
        // Thử sử dụng PHPMailer nếu có
        // Kiểm tra nhiều đường dẫn có thể có của PHPMailer
        $possiblePaths = [
            PATH_ROOT . 'phpmailer/src/PHPMailer.php',  // Trong webduan1/phpmailer
            __DIR__ . '/../phpmailer/src/PHPMailer.php', // Từ configs/../phpmailer
            dirname(PATH_ROOT) . '/phpmailer/src/PHPMailer.php', // Từ parent của webduan1
        ];
        
        $phpmailerPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $phpmailerPath = $path;
                break;
            }
        }
        
        if ($phpmailerPath) {
            try {
                $phpmailerDir = dirname($phpmailerPath);
                require_once $phpmailerDir . '/PHPMailer.php';
                require_once $phpmailerDir . '/SMTP.php';
                require_once $phpmailerDir . '/Exception.php';

                // Kiểm tra class có tồn tại không
                if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                    throw new \Exception('PHPMailer class not found');
                }

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                // SMTP config
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'le3221981@gmail.com';
                $mail->Password   = 'pslo nbcf htvf ftij';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                // From
                $mail->setFrom('le3221981@gmail.com', 'BonBonWear');

                // To
                $mail->addAddress($toEmail, $toName ?: $toEmail);

                // Subject & Body
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = $htmlBody;

                // Gửi mail
                return $mail->send();
            } catch (\Exception $e) {
                error_log("PHPMailer error: " . $e->getMessage());
                // Fallback về mail() của PHP
            }
        }

        // Fallback: Sử dụng mail() của PHP
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: BonBonWear <noreply@bonbonwear.com>\r\n";
        $headers .= "Reply-To: noreply@bonbonwear.com\r\n";

        $to = $toName ? "$toName <$toEmail>" : $toEmail;

        return @mail($to, $subject, $htmlBody, $headers);
    }
}

if (!function_exists('getProductImageUrl')) {
    /**
     * Xử lý đường dẫn ảnh sản phẩm
     * @param string $imagePath Đường dẫn ảnh (có thể là URL đầy đủ hoặc đường dẫn tương đối)
     * @param bool $isRelativePath Nếu true, coi như đường dẫn tương đối trong uploads và thêm BASE_ASSETS_UPLOADS
     * @return string URL đầy đủ của ảnh
     */
    function getProductImageUrl(string $imagePath, bool $isRelativePath = true): string
    {
        $defaultImage = BASE_URL . 'assets/images/logo.png';

        // Nếu rỗng, trả về ảnh mặc định
        if (empty($imagePath)) {
            return $defaultImage;
        }

        $finalUrl = '';

        // Nếu đã là URL đầy đủ (bắt đầu bằng http:// hoặc https://)
        if (preg_match('/^https?:\/\//i', $imagePath)) {
            $finalUrl = $imagePath;
        }

        if ($finalUrl === '') {
            // Nếu isRelativePath = false, coi như đường dẫn đã đầy đủ từ root
            if (!$isRelativePath) {
                // Nếu đã bắt đầu bằng / hoặc BASE_URL, trả về như cũ
                if (strpos($imagePath, '/') === 0) {
                    // Đường dẫn tuyệt đối từ root
                    $finalUrl = BASE_URL . ltrim($imagePath, '/');
                } elseif (strpos($imagePath, BASE_URL) === 0 || strpos($imagePath, 'assets/') === 0) {
                    // Nếu đã chứa BASE_URL hoặc assets, trả về như cũ
                    $finalUrl = strpos($imagePath, BASE_URL) === 0 ? $imagePath : BASE_URL . $imagePath;
                } else {
                    // Mặc định thêm BASE_URL
                    $finalUrl = BASE_URL . ltrim($imagePath, '/');
                }
            } else {
                // Nếu isRelativePath = true, coi như đường dẫn trong uploads
                // Loại bỏ BASE_ASSETS_UPLOADS nếu đã có
                $imagePath = str_replace(BASE_ASSETS_UPLOADS, '', $imagePath);
                $imagePath = str_replace('assets/uploads/', '', $imagePath);
                $imagePath = ltrim($imagePath, '/');

                // Thêm BASE_ASSETS_UPLOADS
                $finalUrl = BASE_ASSETS_UPLOADS . $imagePath;
            }
        }

        // Nếu là ảnh local của chính dự án nhưng file thật đã mất thì fallback ảnh mặc định.
        // Tránh tình trạng URL ảnh còn trong DB nhưng file đã bị xóa/mất sau khi reload trang.
        $parts = parse_url($finalUrl);
        $path = (string)($parts['path'] ?? '');
        $host = strtolower((string)($parts['host'] ?? ''));
        $baseHost = strtolower((string)(parse_url(BASE_URL, PHP_URL_HOST) ?? ''));
        $basePathRaw = (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '/');
        $basePathTrimmed = trim($basePathRaw, '/');
        $basePath = $basePathTrimmed === '' ? '/' : ('/' . $basePathTrimmed . '/');

        if ($path !== '' && ($host === '' || $host === $baseHost)) {
            $relativePath = '';

            if (strpos($path, $basePath) === 0) {
                $relativePath = ltrim(substr($path, strlen($basePath)), '/');
            } elseif (preg_match('#^/?assets/#', $path)) {
                $relativePath = ltrim($path, '/');
            }

            if ($relativePath !== '') {
                $localFile = PATH_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                if (!is_file($localFile)) {
                    return $defaultImage;
                }
            }
        }

        return $finalUrl;
    }
}

if (!function_exists('get_site_settings')) {
    function get_site_settings()
    {
        if (!isset($GLOBALS['site_settings_loaded'])) {
            try {
                $pdo = new PDO(
                    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME),
                    DB_USERNAME,
                    DB_PASSWORD,
                    DB_OPTIONS
                );
                $stmt = $pdo->query("SELECT * FROM settings");
                $settingsData = $stmt->fetchAll();
                $GLOBALS['siteSettings'] = [];
                foreach ($settingsData as $row) {
                    $GLOBALS['siteSettings'][$row['k']] = $row['v'];
                }
                $GLOBALS['site_settings_loaded'] = true;
            } catch (Exception $e) {
                $GLOBALS['siteSettings'] = [];
                $GLOBALS['site_settings_loaded'] = true;
            }
        }
        return $GLOBALS['siteSettings'];
    }
}
