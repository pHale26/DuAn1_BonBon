# Thư mục Images

Thư mục này chứa các hình ảnh tĩnh của website.

## Cấu trúc

- `logo.png` - Logo chính của website BonBonwear, hiển thị trên header

## Hướng dẫn sử dụng

Để thêm ảnh mới vào thư mục này:

1. Đặt file ảnh vào thư mục `assets/images/`
2. Tham chiếu đến ảnh trong code bằng: `BASE_URL . 'assets/images/ten-file.png'`

## Ví dụ

```php
$logoUrl = BASE_URL . 'assets/images/logo.png';
```

Trong HTML/PHP view:
```html
<img src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
```
