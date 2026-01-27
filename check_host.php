<?php
// check_host.php - File kiểm tra lỗi Hosting
// Upload file này lên thư mục gốc (ngang hàng index.php) và chạy: https://phuongha.online/check_host.php

// 1. Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Kiểm tra cấu hình Hosting</h1>";

// 2. Kiểm tra phiên bản PHP
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// 3. Kiểm tra file cấu hình
$envFile = __DIR__ . '/configs/env.php';
if (!file_exists($envFile)) {
    echo "<p style='color:red;'>[LỖI] Không tìm thấy file <code>configs/env.php</code>!</p>";
    echo "<p>Vui lòng đổi tên file <code>configs/env.hosting.php</code> thành <code>configs/env.php</code>.</p>";
    die;
} else {
    echo "<p style='color:green;'>[OK] Đã tìm thấy file <code>configs/env.php</code>.</p>";
    require_once $envFile;
}

// 4. Kiểm tra hằng số quan trọng
echo "<p><strong>DB_HOST:</strong> " . (defined('DB_HOST') ? DB_HOST : 'Chưa định nghĩa') . "</p>";
echo "<p><strong>DB_USERNAME:</strong> " . (defined('DB_USERNAME') ? DB_USERNAME : 'Chưa định nghĩa') . "</p>";

// 5. Thử kết nối Database
echo "<h3>Kiểm tra kết nối Database...</h3>";
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8';
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
    echo "<p style='color:green;'>[OK] Kết nối Database thành công!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>[LỖI] Kết nối Database thất bại:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p>Vui lòng kiểm tra lại Username, Password và Tên Database trong file <code>configs/env.php</code>.</p>";
}

// 6. Kiểm tra quyền ghi thư mục uploads
$uploadPath = PATH_ASSETS_UPLOADS;
if (is_writable($uploadPath)) {
    echo "<p style='color:green;'>[OK] Thư mục <code>assets/uploads</code> có quyền ghi.</p>";
} else {
    echo "<p style='color:orange;'>[CẢNH BÁO] Thư mục <code>assets/uploads</code> KHÔNG có quyền ghi. Bạn cần CHMOD 755 hoặc 777.</p>";
}

echo "<hr><p>Sau khi sửa xong lỗi, hãy xóa file này đi để bảo mật.</p>";
