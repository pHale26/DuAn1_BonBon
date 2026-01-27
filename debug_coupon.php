<?php
// Debug script để kiểm tra dữ liệu mã giảm giá trong database

require_once __DIR__ . '/configs/env.php';

try {
    // Kết nối database trực tiếp
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
        DB_USERNAME,
        DB_PASSWORD,
        DB_OPTIONS
    );
    
    // Lấy mã NEWUSER
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = 'NEWUSER'");
    $stmt->execute();
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='UTF-8'><title>Debug Coupon</title>";
    echo "<style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto;} pre{background:#f4f4f4;padding:10px;border-radius:5px;overflow-x:auto;}</style>";
    echo "</head><body>";
    
    echo "<h2>Debug Mã Giảm Giá NEWUSER</h2>";
    echo "<pre>";
    print_r($coupon);
    echo "</pre>";
    
    if ($coupon) {
        echo "<h3>Phân tích:</h3>";
        echo "<ul>";
        echo "<li><strong>Mã:</strong> " . htmlspecialchars($coupon['code']) . "</li>";
        echo "<li><strong>Tên:</strong> " . htmlspecialchars($coupon['name']) . "</li>";
        echo "<li><strong>Loại giảm giá (discount_type):</strong> <span style='color:blue;font-weight:bold;'>" . htmlspecialchars($coupon['discount_type']) . "</span></li>";
        echo "<li><strong>Giá trị giảm (discount_value):</strong> <span style='color:blue;font-weight:bold;'>" . htmlspecialchars($coupon['discount_value']) . "</span></li>";
        echo "<li><strong>Đơn tối thiểu:</strong> " . number_format($coupon['min_order_amount'], 0, ',', '.') . " đ</li>";
        echo "<li><strong>Giảm tối đa:</strong> " . ($coupon['max_discount_amount'] ? number_format($coupon['max_discount_amount'], 0, ',', '.') . " đ" : "Không giới hạn") . "</li>";
        echo "</ul>";
        
        echo "<h3>Hiển thị như trong danh sách:</h3>";
        if ($coupon['discount_type'] === 'percentage') {
            echo "<p style='font-size:18px;'><strong>Giá trị:</strong> <span style='color:green;font-weight:bold;'>" . number_format($coupon['discount_value'], 0) . "%</span></p>";
        } else {
            echo "<p style='font-size:18px;'><strong>Giá trị:</strong> <span style='color:red;font-weight:bold;'>" . number_format($coupon['discount_value'], 0, ',', '.') . " đ</span></p>";
        }
        
        echo "<h3>Vấn đề:</h3>";
        if ($coupon['discount_type'] !== 'percentage' && $coupon['discount_value'] == 40) {
            echo "<div style='background:#ffebee;padding:15px;border-left:4px solid #f44336;margin:10px 0;'>";
            echo "<p style='color: #d32f2f;margin:0;'><strong>❌ Phát hiện vấn đề:</strong> Loại giảm giá đang là '<strong>" . $coupon['discount_type'] . "</strong>' nhưng giá trị là 40. Nếu bạn muốn giảm 40%, cần sửa discount_type thành 'percentage'.</p>";
            echo "</div>";
            
            echo "<h3>Giải pháp:</h3>";
            echo "<p>Chạy câu lệnh SQL sau để sửa:</p>";
            echo "<pre>UPDATE coupons SET discount_type = 'percentage' WHERE code = 'NEWUSER';</pre>";
            
            echo "<p><a href='?fix=1' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Nhấn vào đây để tự động sửa</a></p>";
        } else if ($coupon['discount_type'] === 'percentage') {
            echo "<div style='background:#e8f5e9;padding:15px;border-left:4px solid #4caf50;margin:10px 0;'>";
            echo "<p style='color: #2e7d32;margin:0;'><strong>✓ Đúng:</strong> Loại giảm giá là 'percentage' và giá trị là " . $coupon['discount_value'] . "%</p>";
            echo "</div>";
        } else {
            echo "<div style='background:#fff3e0;padding:15px;border-left:4px solid #ff9800;margin:10px 0;'>";
            echo "<p style='color: #e65100;margin:0;'><strong>⚠ Lưu ý:</strong> Loại giảm giá là '<strong>" . $coupon['discount_type'] . "</strong>' với giá trị " . $coupon['discount_value'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background:#ffebee;padding:15px;border-left:4px solid #f44336;margin:10px 0;'>";
        echo "<p style='color: #d32f2f;margin:0;'>❌ Không tìm thấy mã NEWUSER trong database</p>";
        echo "</div>";
    }
    
    // Tự động sửa nếu có tham số ?fix=1
    if (isset($_GET['fix']) && $_GET['fix'] == 1 && $coupon && $coupon['discount_type'] !== 'percentage') {
        $stmt = $pdo->prepare("UPDATE coupons SET discount_type = 'percentage' WHERE code = 'NEWUSER'");
        $stmt->execute();
        echo "<div style='background:#e8f5e9;padding:15px;border-left:4px solid #4caf50;margin:10px 0;'>";
        echo "<p style='color: #2e7d32;font-weight: bold;margin:0;'>✓ Đã sửa thành công! <a href='debug_coupon.php'>Tải lại trang để kiểm tra</a></p>";
        echo "</div>";
    }
    
    echo "<hr><p><a href='" . BASE_URL . "?action=admin-coupons'>← Quay lại quản lý mã giảm giá</a></p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='UTF-8'><title>Lỗi</title></head><body>";
    echo "<div style='background:#ffebee;padding:15px;border-left:4px solid #f44336;margin:10px 0;'>";
    echo "<p style='color: #d32f2f;'>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "</div>";
    echo "</body></html>";
}
?>
