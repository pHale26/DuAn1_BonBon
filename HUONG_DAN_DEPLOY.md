# HƯỚNG DẪN ĐẨY WEBSITE LÊN HOSTING

Chào bạn, đây là các bước chi tiết để đưa website Bonbon Shop lên hosting (DirectAdmin, cPanel, Plesk đều tương tự).

## BƯỚC 1: CHUẨN BỊ DATABASE
1. Truy cập `http://localhost/phpmyadmin` trên máy tính của bạn.
2. Chọn database `bonbon_shop` ở cột bên trái.
3. Bấm vào tab **Export (Xuất)** ở menu trên cùng.
4. Chọn định dạng **SQL** và bấm **Go (Thực hiện)** để tải file về (ví dụ: `bonbon_shop.sql`).
   *Đây là file chứa toàn bộ dữ liệu và bảng (sản phẩm, đơn hàng, mã giảm giá...) mới nhất của bạn.*

## BƯỚC 2: UPLOAD CODE
1. Trên máy tính, vào thư mục `c:\xampp\htdocs\webduan1`.
2. Chọn tất cả các file và thư mục (assets, configs, controllers, models, views, index.php...).
3. Nén tất cả lại thành một file `.zip` (ví dụ `bonbon_code.zip`).
4. Đăng nhập vào trình quản lý File Manager của Hosting.
5. Upload file `bonbon_code.zip` lên thư mục gốc của web (thường là `public_html` hoặc `www`).
6. Chuột phải vào file zip vừa up, chọn **Extract (Giải nén)**.

## BƯỚC 3: TẠO DATABASE TRÊN HOSTING
1. Trong trang quản trị Hosting, tìm mục **MySQL Databases** (hoặc Cơ sở dữ liệu).
2. Tạo một database mới (ví dụ: `u12345_bonbon`).
3. Tạo một user database mới và mật khẩu (nhớ lưu lại thông tin này).
4. **Cực kỳ quan trọng**: Gán (Add) user vào database và cấp **ALL PRIVILEGES**.
5. Vào **phpMyAdmin** trên Hosting, chọn database vừa tạo.
6. Vào tab **Import (Nhập)**, chọn file `bonbon_shop.sql` bạn đã tải ở Bước 1 và bấm **Go**.

## BƯỚC 4: CẤU HÌNH KẾT NỐI
1. Trên File Manager của Hosting, tìm đến thư mục `configs`.
2. Bạn sẽ thấy file `env.hosting.php` mà tôi vừa tạo cho bạn.
3. Xóa file `env.php` cũ đi.
4. Đổi tên file `env.hosting.php` thành **`env.php`**.
5. Mở file `env.php` (vừa đổi tên) lên để chỉnh sửa.
6. Điền thông tin user, mật khẩu và tên database mà bạn đã tạo ở Bước 3 vào các dòng tương ứng:

```php
define('DB_USERNAME', '...'); // Điền user database
define('DB_PASSWORD', '...'); // Điền mật khẩu
define('DB_NAME',     '...'); // Điền tên database
```
*Lưu ý: Tên miền `phuongha.online` đã được cài sẵn trong file này rồi.*

## BƯỚC 5: PHÂN QUYỀN (CHMOD)
Để website có thể upload ảnh sản phẩm, bạn cần cấp quyền ghi cho thư mục uploads.
1. Tìm thư mục `assets/uploads`.
2. Chuột phải, chọn **Permissions (Phân quyền)** hoặc **Change Mode**.
3. Set thành **755** (hoặc 777 nếu 755 bị lỗi không up được ảnh).

## KIỂM TRA
- Truy cập vào tên miền của bạn để xem kết quả.
- Thử đăng nhập Admin (`https://ten-mien-cua-ban.com/?action=show-login`).
- Thử đặt hàng để test database.

---
**LƯU Ý:**
- Nếu gặp lỗi, hãy kiểm tra file `php_error.log` trên hosting.
- Đảm bảo phiên bản PHP trên hosting là **7.4 trở lên** (tốt nhất là 8.0 - 8.2).
- Nếu trang web bị lỗi 404 khi bấm link, hãy chắc chắn hosting hỗ trợ file `.htaccess` (Apache/LiteSpeed). Project này đang dùng cấu trúc URL query params (`?action=...`) nên khá an toàn với mọi hosting.
