-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 10, 2025 lúc 05:32 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `bonbon_shop`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attributes`
--


DROP TABLE IF EXISTS `attributes`;

CREATE TABLE IF NOT EXISTS `attributes` (
  `attribute_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `attributes`
--

INSERT INTO `attributes` (`attribute_id`, `attribute_name`) VALUES
(1, 'Size'),
(2, 'Color');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attribute_values`
--

CREATE TABLE IF NOT EXISTS `attribute_values` (
  `value_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `attribute_values`
--

INSERT INTO `attribute_values` (`value_id`, `attribute_id`, `value_name`) VALUES
(1, 1, 'S'),
(2, 1, 'M'),
(3, 1, 'L'),
(4, 1, 'XL'),
(5, 2, 'Black'),
(6, 2, 'White'),
(7, 2, 'Red'),
(8, 2, 'Blue'),
(9, 2, 'Green'),
(10, 2, 'Navy'),
(11, 2, 'Beige'),
(12, 2, 'Gray');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE IF NOT EXISTS `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `created_at`, `updated_at`) VALUES
(5, 4, '2025-12-10 11:22:04', NULL),
(6, 1, '2025-12-10 14:46:01', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE IF NOT EXISTS `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `variant_id`, `quantity`, `created_at`, `updated_at`) VALUES
(57, 5, 19, 65, 2, NULL, NULL),
(76, 5, 1, 5, 1, NULL, NULL),
(77, 5, 3, 12, 2, NULL, NULL),
(78, 5, 14, 56, 2, NULL, NULL),
(79, 5, 10, 41, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Áo', 'Các loại áo', NULL),
(2, 'Áo Polo', 'Áo polo nam nữ', NULL),
(3, 'Áo Khoác', 'Áo khoác thời trang', NULL),
(4, 'Hoodie', 'Áo hoodie', NULL),
(5, 'Quần', 'Các loại quần', NULL),
(6, 'Áo Sơ Mi', 'Áo sơ mi công sở', NULL);

-- --------------------------------------------------------

--
--

CREATE TABLE IF NOT EXISTS `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `discount_type` enum('fixed','percent') DEFAULT 'fixed',
  `discount_value` decimal(10,2) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupon_usage`
--

CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `coupon_usage`
--

INSERT INTO `coupon_usage` (`id`, `coupon_id`, `user_id`, `order_id`, `discount_amount`, `used_at`) VALUES
(1, 5, 3, 16, 30000.00, '2025-12-06 23:11:00'),
(2, 2, 3, 17, 100000.00, '2025-12-06 23:14:22'),
(3, 4, 3, 18, 15000.00, '2025-12-07 14:09:01'),
(4, 2, 3, 19, 55900.00, '2025-12-07 14:29:39'),
(5, 2, 3, 20, 100000.00, '2025-12-07 14:33:47'),
(6, 2, 3, 21, 100000.00, '2025-12-07 14:35:37'),
(7, 2, 3, 22, 84800.00, '2025-12-07 14:58:28'),
(8, 2, 3, 23, 100000.00, '2025-12-07 21:06:19'),
(9, 2, 3, 24, 97800.00, '2025-12-07 22:51:17'),
(10, 2, 3, 25, 100000.00, '2025-12-08 10:38:29'),
(11, 2, 3, 27, 100000.00, '2025-12-10 00:12:21');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `meta` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `content`, `action_url`, `meta`, `is_read`, `created_at`) VALUES
(7, 2, 'product', 'Sản phẩm mới: ggghh', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=16', '{\"product_id\":16,\"name\":\"ggghh\",\"price\":100000,\"image_url\":null}', 0, '2025-12-07 09:04:45'),
(10, 3, 'review_reply', 'Shop đã phản hồi đánh giá của bạn', 'okeeeeee', 'http://localhost/webduan1/?action=product-detail&id=9#reviews', '{\"order_id\":20,\"order_item_id\":38,\"product_id\":9,\"review_id\":6}', 0, '2025-12-07 09:14:41'),
(12, 3, 'coupon', 'Mã giảm giá mới: G9', 'mã khuya', NULL, '{\"coupon_id\":6,\"code\":\"G9\",\"name\":\"mã khuya\",\"discount_type\":\"fixed\",\"discount_value\":\"50000.00\",\"min_order_amount\":\"400000.00\",\"max_discount_amount\":null,\"start_date\":\"2025-12-07 23:15:00\",\"end_date\":\"2026-01-06 23:15:00\",\"status\":\"active\"}', 1, '2025-12-07 09:16:30'),
(13, 2, 'coupon', 'Mã giảm giá mới: G9', 'mã khuya', NULL, '{\"coupon_id\":6,\"code\":\"G9\",\"name\":\"mã khuya\",\"discount_type\":\"fixed\",\"discount_value\":\"50000.00\",\"min_order_amount\":\"400000.00\",\"max_discount_amount\":null,\"start_date\":\"2025-12-07 23:15:00\",\"end_date\":\"2026-01-06 23:15:00\",\"status\":\"active\"}', 0, '2025-12-07 09:16:30'),
(14, 3, 'order_status', 'Đơn BB693648356649 cập nhật trạng thái', 'Trạng thái mới: Đang Giao', 'http://localhost/webduan1/?action=order-detail&id=25', '{\"order_id\":25,\"order_code\":\"BB693648356649\",\"status\":\"to_ship\",\"status_label\":\"Đang Giao\",\"payment_method\":\"cod\"}', 1, '2025-12-07 20:40:40'),
(15, 3, 'order_status', 'Đơn BB693648356649 cập nhật trạng thái', 'Trạng thái mới: Đã Giao', 'http://localhost/webduan1/?action=order-detail&id=25', '{\"order_id\":25,\"order_code\":\"BB693648356649\",\"status\":\"delivered\",\"status_label\":\"Đã Giao\",\"payment_method\":\"cod\"}', 0, '2025-12-07 20:41:35'),
(16, 3, 'order_status', 'Đơn BB6938576C5525 cập nhật trạng thái', 'Trạng thái mới: Đang Giao', 'http://localhost/webduan1/?action=order-detail&id=26', '{\"order_id\":26,\"order_code\":\"BB6938576C5525\",\"status\":\"to_ship\",\"status_label\":\"Đang Giao\",\"payment_method\":\"cod\"}', 0, '2025-12-09 10:08:14'),
(17, 3, 'order_status', 'Đơn BB6938576C5525 cập nhật trạng thái', 'Trạng thái mới: Đã Giao', 'http://localhost/webduan1/?action=order-detail&id=26', '{\"order_id\":26,\"order_code\":\"BB6938576C5525\",\"status\":\"delivered\",\"status_label\":\"Đã Giao\",\"payment_method\":\"cod\"}', 0, '2025-12-09 10:08:16'),
(18, 3, 'order_status', 'Đơn BB6938576C5525 cập nhật trạng thái', 'Trạng thái mới: Hoàn Thành', 'http://localhost/webduan1/?action=order-detail&id=26', '{\"order_id\":26,\"order_code\":\"BB6938576C5525\",\"status\":\"completed\",\"status_label\":\"Hoàn Thành\",\"payment_method\":\"cod\"}', 0, '2025-12-09 10:08:26'),
(19, 3, 'order_status', 'Đơn BB693858753324 cập nhật trạng thái', 'Trạng thái mới: Đang Giao', 'http://localhost/webduan1/?action=order-detail&id=27', '{\"order_id\":27,\"order_code\":\"BB693858753324\",\"status\":\"to_ship\",\"status_label\":\"Đang Giao\",\"payment_method\":\"cod\"}', 0, '2025-12-09 10:12:30'),
(20, 3, 'order_status', 'Đơn BB693858753324 cập nhật trạng thái', 'Trạng thái mới: Đã Giao', 'http://localhost/webduan1/?action=order-detail&id=27', '{\"order_id\":27,\"order_code\":\"BB693858753324\",\"status\":\"delivered\",\"status_label\":\"Đã Giao\",\"payment_method\":\"cod\"}', 1, '2025-12-09 10:12:33'),
(21, 3, 'order_status', 'Đơn BB693858753324 cập nhật trạng thái', 'Trạng thái mới: Hoàn Thành', 'http://localhost/webduan1/?action=order-detail&id=27', '{\"order_id\":27,\"order_code\":\"BB693858753324\",\"status\":\"completed\",\"status_label\":\"Hoàn Thành\",\"payment_method\":\"cod\"}', 0, '2025-12-09 19:11:26'),
(23, 3, 'product', 'Sản phẩm mới: h', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=17', '{\"product_id\":17,\"name\":\"h\",\"price\":200000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765340249_6938f4597c802.jpg\"}', 0, '2025-12-10 04:17:29'),
(24, 2, 'product', 'Sản phẩm mới: h', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=17', '{\"product_id\":17,\"name\":\"h\",\"price\":200000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765340249_6938f4597c802.jpg\"}', 0, '2025-12-10 04:17:29'),
(26, 3, 'product', 'Sản phẩm mới: áo thun đen', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=18', '{\"product_id\":18,\"name\":\"áo thun đen\",\"price\":100000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765353148_693926bccbd43.jpg\"}', 0, '2025-12-10 07:52:28'),
(27, 2, 'product', 'Sản phẩm mới: áo thun đen', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=18', '{\"product_id\":18,\"name\":\"áo thun đen\",\"price\":100000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765353148_693926bccbd43.jpg\"}', 0, '2025-12-10 07:52:28'),
(29, 3, 'product', 'Sản phẩm mới: áo thun đen', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=19', '{\"product_id\":19,\"name\":\"áo thun đen\",\"price\":100000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765353475_693928033140b.jpg\"}', 0, '2025-12-10 07:57:55'),
(30, 2, 'product', 'Sản phẩm mới: áo thun đen', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=19', '{\"product_id\":19,\"name\":\"áo thun đen\",\"price\":100000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765353475_693928033140b.jpg\"}', 0, '2025-12-10 07:57:55'),
(32, 3, 'product', 'Sản phẩm mới: áo thun', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=20', '{\"product_id\":20,\"name\":\"áo thun\",\"price\":100000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765354855_69392d67aa4f9.jpg\"}', 0, '2025-12-10 08:20:55'),
(33, 2, 'product', 'Sản phẩm mới: áo thun', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=20', '{\"product_id\":20,\"name\":\"áo thun\",\"price\":100000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765354855_69392d67aa4f9.jpg\"}', 0, '2025-12-10 08:20:55'),
(35, 3, 'product', 'Sản phẩm mới: quân', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=21', '{\"product_id\":21,\"name\":\"quân\",\"price\":10000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765356266_693932ea62dad.jpg\"}', 0, '2025-12-10 08:44:26'),
(36, 2, 'product', 'Sản phẩm mới: quân', 'Khám phá ngay sản phẩm vừa ra mắt.', 'http://localhost/webduan1/?action=product-detail&id=21', '{\"product_id\":21,\"name\":\"quân\",\"price\":10000,\"image_url\":\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/products\\/product_1765356266_693932ea62dad.jpg\"}', 0, '2025-12-10 08:44:26'),
(70, 4, 'order_status', 'Đơn BB6939934E3991 cập nhật trạng thái', 'Trạng thái mới: Thanh toán thất bại', 'http://localhost/webduan1/?action=order-detail&id=41', '{\"order_id\":41,\"order_code\":\"BB6939934E3991\",\"status\":\"payment_failed\",\"status_label\":\"Thanh toán thất bại\",\"payment_method\":\"banking\"}', 0, '2025-12-10 15:46:28'),
(71, 4, 'order_status', 'Đơn BB6939969A5598 cập nhật trạng thái', 'Trạng thái mới: Đã Thanh Toán', 'http://localhost/webduan1/?action=order-detail&id=44', '{\"order_id\":44,\"order_code\":\"BB6939969A5598\",\"status\":\"paid\",\"status_label\":\"Đã Thanh Toán\",\"payment_method\":\"banking\"}', 0, '2025-12-10 15:50:17'),
(72, 4, 'order_status', 'Đơn BB6939969A5598 cập nhật trạng thái', 'Trạng thái mới: Chờ Xác Nhận', 'http://localhost/webduan1/?action=order-detail&id=44', '{\"order_id\":44,\"order_code\":\"BB6939969A5598\",\"status\":\"pending\",\"status_label\":\"Chờ Xác Nhận\",\"payment_method\":\"banking\"}', 0, '2025-12-10 15:50:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `coupon_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders_new`
--

CREATE TABLE IF NOT EXISTS `orders_new` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `coupon_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `coupon_name` varchar(255) DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders_new`
--

INSERT INTO `orders_new` (`id`, `order_code`, `user_id`, `fullname`, `email`, `phone`, `address`, `city`, `district`, `ward`, `note`, `payment_method`, `status`, `total_amount`, `coupon_id`, `discount_amount`, `coupon_code`, `coupon_name`, `cancel_reason`, `created_at`, `updated_at`) VALUES
(20, 'BB69352DDB7207', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'cod', 'delivered', 1357000.00, NULL, 100000.00, 'SUMMER20', 'mã chào hè', NULL, '2025-12-07 00:33:47', '2025-12-07 00:37:18'),
(21, 'BB69352E494527', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'banking', 'completed', 1449000.00, NULL, 100000.00, 'SUMMER20', 'mã chào hè', NULL, '2025-12-07 00:35:37', '2025-12-07 07:29:23'),
(23, 'BB693589DB4981', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'cod', 'cancelled', 2155000.00, NULL, 100000.00, 'SUMMER20', 'mã chào hè', 'Thay đổi phương thức thanh toán', '2025-12-07 07:06:19', '2025-12-07 07:09:41'),
(24, 'BB6935A2755959', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'cod', 'delivered', 880200.00, NULL, 97800.00, 'SUMMER20', 'mã chào hè', NULL, '2025-12-07 08:51:17', '2025-12-07 08:56:00'),
(25, 'BB693648356649', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'cod', 'delivered', 1428000.00, NULL, 100000.00, 'SUMMER20', 'mã chào hè', NULL, '2025-12-07 20:38:29', '2025-12-07 20:41:35'),
(26, 'BB6938576C5525', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'cod', 'completed', 200000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-09 10:07:56', '2025-12-09 10:08:25'),
(27, 'BB693858753324', NULL, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '0343748764', 'Thanh Hóa', '', '', '', '', 'cod', 'completed', 900000.00, NULL, 100000.00, 'SUMMER20', 'mã chào hè', NULL, '2025-12-09 10:12:21', '2025-12-09 19:11:26'),
(28, 'BB693938D58466', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'delivered', 2000000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:09:41', '2025-12-10 14:53:18'),
(29, 'BB693938FA6332', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'completed', 51000000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:10:18', '2025-12-10 14:23:28'),
(30, 'BB6939393C5948', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'banking', 'unpaid', 99999999.99, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:11:24', '2025-12-10 09:11:24'),
(31, 'BB693939634436', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'banking', 'unpaid', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:12:03', '2025-12-10 09:12:03'),
(32, 'BB693939936156', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'banking', 'unpaid', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:12:51', '2025-12-10 09:12:51'),
(33, 'BB6939399B4918', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'banking', 'unpaid', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:12:59', '2025-12-10 09:12:59'),
(34, 'BB69393A249278', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'completed', 1000000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 09:15:16', '2025-12-10 09:16:57'),
(35, 'BB6939872C8058', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'completed', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 14:43:56', '2025-12-10 15:03:27'),
(36, 'BB69398BC91542', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'completed', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:03:37', '2025-12-10 15:04:57'),
(37, 'BB69398C232136', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'cancelled', 729000.00, NULL, 0.00, NULL, NULL, 'k thích', '2025-12-10 15:05:07', '2025-12-10 15:06:02'),
(38, 'BB69398D457492', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'completed', 2916000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:09:57', '2025-12-10 15:10:26'),
(39, 'BB69398D6D2716', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'completed', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:10:37', '2025-12-10 15:34:14'),
(40, 'BB693993090252', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'pending', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:34:33', '2025-12-10 15:34:33'),
(41, 'BB6939934E3991', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'banking', 'payment_failed', 1458000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:35:42', '2025-12-10 15:46:28'),
(42, 'BB6939962A7279', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'pending', 1000000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:47:54', '2025-12-10 15:47:54'),
(43, 'BB6939968D8027', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'pending', 549000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:49:33', '2025-12-10 15:49:33'),
(44, 'BB6939969A5598', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'banking', 'pending', 549000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:49:46', '2025-12-10 15:50:17'),
(45, 'BB693997A82809', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'pending', 1278000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:54:16', '2025-12-10 15:54:16'),
(46, 'BB693997B48222', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'pending', 729000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 15:54:28', '2025-12-10 15:54:28'),
(47, 'BB693999494166', NULL, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', '', '', '', '', 'cod', 'pending', 799000.00, NULL, 0.00, NULL, NULL, NULL, '2025-12-10 16:01:13', '2025-12-10 16:01:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE IF NOT EXISTS `order_details` (
  `detail_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `variant_size` varchar(50) DEFAULT NULL,
  `variant_color` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant_size`, `variant_color`, `quantity`, `unit_price`, `image_url`) VALUES
(1, 1, 9, 'Áo Polo Premium', NULL, NULL, 1, 499000.00, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?auto=format&fit=crop&w=600&q=80'),
(2, 1, 3, 'Áo Hoodie Urban', 'S', 'Black', 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(3, 1, 1, 'Áo Polo Essential', NULL, NULL, 1, 399000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(4, 1, 1, 'Áo Polo Essential', 'L', 'Black', 2, 399000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(5, 2, 9, 'Áo Polo Premium', NULL, NULL, 1, 499000.00, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?auto=format&fit=crop&w=600&q=80'),
(6, 2, 3, 'Áo Hoodie Urban', 'S', 'Black', 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(7, 2, 1, 'Áo Polo Essential', NULL, NULL, 1, 399000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(8, 2, 1, 'Áo Polo Essential', 'L', 'Black', 2, 399000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(9, 3, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(10, 3, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 659000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(11, 4, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(12, 4, 2, 'Áo Khoác Dệt Kim', 'M', 'Beige', 1, 659000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(13, 5, 10, 'Áo Khoác Bomber', 'L', 'Black', 1, 799000.00, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=600&q=80'),
(14, 6, 9, 'Áo Polo Premium', 'M', 'Black', 1, 499000.00, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?auto=format&fit=crop&w=600&q=80'),
(15, 7, 10, 'Áo Khoác Bomber', 'L', 'Black', 1, 799000.00, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=600&q=80'),
(16, 8, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 659000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(17, 9, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 659000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(18, 10, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(19, 11, 5, 'Áo Sơ Mi Cotton', NULL, NULL, 1, 429000.00, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80'),
(20, 12, 6, 'Áo Polo Stripe', 'L', 'Navy', 1, 419000.00, 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=600&q=80'),
(21, 13, 14, 'Áo Khoác Denim', 'S', 'Black', 1, 729000.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=600&q=80'),
(22, 14, 13, 'Áo Sơ Mi Oxford', 'L', 'Navy', 1, 47900000.00, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80'),
(23, 14, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 99999999.99, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(24, 14, 14, 'Áo Khoác Denim', 'L', 'Black', 1, 72900000.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=600&q=80'),
(25, 15, 13, 'Áo Sơ Mi Oxford', 'L', 'Navy', 1, 47900000.00, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80'),
(26, 15, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 99999999.99, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(27, 15, 14, 'Áo Khoác Denim', 'L', 'Black', 1, 72900000.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=600&q=80'),
(28, 15, 2, 'Áo Khoác Dệt Kim', 'L', 'Beige', 1, 99999999.99, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(29, 16, 13, 'Áo Sơ Mi Oxford', NULL, NULL, 1, 479000.00, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80'),
(30, 16, 14, 'Áo Khoác Denim', 'L', 'Black', 1, 729000.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=600&q=80'),
(31, 16, 13, 'Áo Sơ Mi Oxford', 'L', 'Navy', 1, 479000.00, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80'),
(32, 16, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 2, 1000000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(33, 17, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(34, 17, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 1000000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(35, 18, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(36, 18, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 1000000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(37, 19, 8, 'Quần Jean Darkwash', NULL, NULL, 1, 559000.00, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80'),
(38, 20, 9, 'Áo Polo Premium', NULL, NULL, 1, 499000.00, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?auto=format&fit=crop&w=600&q=80'),
(39, 20, 13, 'Áo Sơ Mi Oxford', NULL, NULL, 2, 479000.00, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80'),
(40, 21, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(41, 21, 2, 'Áo Khoác Dệt Kim', NULL, NULL, 1, 1000000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(42, 22, 6, 'Áo Polo Stripe', 'L', 'Navy', 1, 419000.00, 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=600&q=80'),
(43, 22, 5, 'Áo Sơ Mi Cotton', NULL, NULL, 1, 429000.00, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80'),
(44, 23, 5, 'Áo Sơ Mi Cotton', NULL, NULL, 2, 429000.00, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80'),
(45, 23, 6, 'Áo Polo Stripe', 'L', 'Navy', 2, 419000.00, 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=600&q=80'),
(46, 23, 8, 'Quần Jean Darkwash', '29', 'Blue', 1, 559000.00, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80'),
(47, 24, 5, 'Áo Sơ Mi Cotton', NULL, NULL, 1, 429000.00, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80'),
(48, 24, 3, 'Áo Hoodie Urban', NULL, NULL, 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(49, 25, 14, 'Áo Khoác Denim', 'L', 'Black', 1, 729000.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=600&q=80'),
(50, 25, 10, 'Áo Khoác Bomber', NULL, NULL, 1, 799000.00, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=600&q=80'),
(51, 26, 15, 'Quần Short Kakoo', '30', 'Navy', 1, 200000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765032266_6934414a9e609.webp'),
(52, 27, 1, 'Áo Polo Essential', 'L', 'Black', 1, 1000000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(53, 28, 20, 'áo thun', 'L', 'Black', 20, 100000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765354873_69392d7959528.jpg'),
(54, 29, 2, 'Áo Khoác Dệt Kim', 'M', 'Beige', 51, 1000000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(55, 30, 2, 'Áo Khoác Dệt Kim', 'M', 'Green', 100, 1000000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?auto=format&fit=crop&w=600&q=80'),
(56, 31, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(57, 32, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(58, 33, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(59, 34, 1, 'Áo Polo Essential', 'L', 'Red', 1, 1000000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(60, 35, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(61, 36, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(62, 37, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(63, 38, 14, 'Áo Khoác Denim', 'L', 'Gray', 4, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(64, 39, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(65, 40, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(66, 41, 14, 'Áo Khoác Denim', 'L', 'Gray', 2, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(67, 42, 1, 'Áo Polo Essential', 'L', 'Red', 1, 1000000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(68, 43, 3, 'Áo Hoodie Urban', 'L', 'Green', 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(69, 44, 3, 'Áo Hoodie Urban', 'L', 'Green', 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(70, 45, 3, 'Áo Hoodie Urban', 'L', 'Green', 1, 549000.00, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80'),
(71, 45, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(72, 46, 14, 'Áo Khoác Denim', 'L', 'Gray', 1, 729000.00, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg'),
(73, 47, 10, 'Áo Khoác Bomber', 'L', 'Blue', 1, 799000.00, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=600&q=80');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token`, `otp_code`, `expires_at`, `is_used`, `created_at`) VALUES
(1, 5, 'e43ed36d4002eb26ae9bdeee9970fa636769b88d959f47a035b66af7cb0a8295', '664262', '2025-11-25 20:34:33', 1, '2025-11-26 02:04:33'),
(2, NULL, '2892bcc692a7fa1ed81b7524092293843b3f94d9455bd7fe0e07fbdf38d0f134', '827510', '2025-12-06 22:01:50', 0, '2025-12-06 21:31:50'),
(3, NULL, 'c1f347a0739a297af8aecc900fb034e31a1a619fb2d8dc091b6c6d3b3470ed55', '322790', '2025-12-10 00:11:06', 1, '2025-12-09 23:41:06');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `title`, `excerpt`, `slug`, `content`, `thumbnail`, `created_at`, `updated_at`, `status`, `is_featured`) VALUES
(1, NULL, 'BST Polo Thu Đông 2025', NULL, 'bst-polo-thu-dong-2025', 'Bộ sưu tập Polo Thu Đông 2025 của BonBonwear mang đến phong cách tối giản, chất liệu mềm mại cùng gam màu trung tính. Được thiết kế dành cho những người đàn ông hiện đại, yêu thích sự lịch lãm nhưng vẫn thoải mái trong mọi hoàn cảnh.\n\nCác sản phẩm trong BST được làm từ cotton cao cấp, thấm hút mồ hôi tốt, phù hợp cho cả thời tiết se lạnh của mùa thu và những ngày đông ấm áp. Gam màu chủ đạo là đen, trắng, navy và xám - những tông màu dễ phối đồ và luôn hợp thời trang.', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=600&q=80', '2025-11-26 18:07:33', '2025-11-26 18:07:33', 'published', 0),
(2, NULL, 'Cách phối áo Polo đa dạng', NULL, 'cach-phoi-ao-polo-da-dang', 'Áo Polo là món đồ vô cùng linh hoạt trong tủ đồ của mọi quý ông. Từ công sở đến dạo phố, Polo luôn phù hợp mọi hoàn cảnh.\n\n**Phối đồ công sở:** Kết hợp áo Polo với quần kaki hoặc quần tây, giày da lịch sự. Chọn màu trung tính như navy, đen hoặc trắng để tạo vẻ chuyên nghiệp.\n\n**Phối đồ dạo phố:** Mix cùng quần jean, sneaker trắng và áo khoác bomber để có set đồ năng động, trẻ trung.\n\n**Phối đồ cuối tuần:** Áo Polo + quần short + sandal = outfit hoàn hảo cho những buổi gặp gỡ bạn bè.', 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80', '2025-11-26 18:07:33', '2025-11-26 18:07:33', 'published', 1),
(3, NULL, 'Chất liệu tái chế bền vững', NULL, 'chat-lieu-tai-che-ben-vung', 'BonBonwear tiên phong đưa cotton tái chế vào dòng sản phẩm chủ lực, góp phần bảo vệ môi trường và phát triển bền vững.\n\nCotton tái chế được sản xuất từ vải cotton cũ, giảm thiểu lượng nước và năng lượng tiêu thụ so với cotton truyền thống. Chất liệu này vẫn đảm bảo độ mềm mại, thoáng mát và bền bỉ như cotton thông thường.\n\nChúng tôi cam kết 50% sản phẩm năm 2025 sẽ sử dụng chất liệu tái chế hoặc thân thiện với môi trường.', 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=600&q=80', '2025-11-26 18:07:33', '2025-11-26 18:07:33', 'published', 1),
(4, NULL, 'Xu hướng thời trang nam 2025', NULL, 'xu-huong-thoi-trang-nam-2025', 'Năm 2025 đánh dấu sự trở lại của phong cách minimalism - tối giản nhưng tinh tế. Các gam màu trung tính như be, xám, navy tiếp tục thống trị.\n\nOversized vẫn là xu hướng được yêu thích, đặc biệt là áo khoác và hoodie. Tuy nhiên, sự kết hợp giữa oversized và tailored (may đo) tạo nên sự cân bằng hoàn hảo.\n\nChất liệu tự nhiên, thân thiện môi trường ngày càng được ưa chuộng. Người tiêu dùng không chỉ quan tâm đến thiết kế mà còn về nguồn gốc và quy trình sản xuất.', 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?auto=format&fit=crop&w=600&q=80', '2025-11-26 18:07:33', '2025-11-26 18:07:33', 'published', 0),
(5, NULL, 'Bí quyết chọn size áo phù hợp', NULL, 'bi-quyet-chon-size-ao-phu-hop', 'Chọn đúng size áo là yếu tố quan trọng để bạn trông thật phong cách và tự tin.\n\n**Đo chính xác:** Sử dụng thước dây để đo vòng ngực, vai, và chiều dài áo. So sánh với bảng size của từng thương hiệu.\n\n**Thử trước khi mua:** Nếu có thể, hãy thử áo trực tiếp. Kiểm tra độ rộng vai, chiều dài tay, và độ ôm ở thân áo.\n\n**Lưu ý chất liệu:** Một số chất liệu như cotton có thể co lại sau khi giặt, nên chọn size lớn hơn một chút.\n\nBonBonwear cung cấp bảng size chi tiết và dịch vụ tư vấn miễn phí để bạn chọn được size hoàn hảo.', 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=600&q=80', '2025-11-26 18:07:33', '2025-11-26 18:07:33', 'published', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `price`, `original_price`, `sale_price`, `stock`, `category_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Áo Polo Essential', 'Áo polo nam thiết kế tối giản, chất liệu cotton cao cấp, thoáng mát', 1000000.00, 1000000.00, NULL, 100, 2, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(3, 'Áo Hoodie Urban', 'Hoodie phong cách đường phố, chất liệu nỉ mềm mại', 549000.00, 549000.00, NULL, 80, 4, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(4, 'Quần Kaki Slimfit', 'Quần kaki ôm vừa phải, form dáng hiện đại', 489000.00, 489000.00, NULL, 120, 5, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(5, 'Áo Sơ Mi Cotton', 'Áo sơ mi cotton 100%, phù hợp công sở', 429000.00, 429000.00, NULL, 90, 6, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(6, 'Áo Polo Stripe', 'Áo polo họa tiết sọc ngang, trẻ trung năng động', 419000.00, 419000.00, NULL, 70, 2, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(7, 'Áo Khoác Utility', 'Áo khoác nhiều túi tiện dụng, phong cách quân đội', 699000.00, 699000.00, NULL, 40, 3, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(8, 'Quần Jean Darkwash', 'Quần jean màu tối, bền đẹp theo thời gian', 559000.00, 559000.00, NULL, 60, 5, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(9, 'Áo Polo Premium', 'Áo polo cao cấp, chất liệu pique cotton, logo thêu tinh tế', 499000.00, 499000.00, NULL, 55, 2, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(10, 'Áo Khoác Bomber', 'Áo khoác bomber phong cách pilot, chống gió tốt', 799000.00, 799000.00, NULL, 35, 3, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(11, 'Áo Hoodie Zip', 'Hoodie có khóa kéo, tiện lợi và năng động', 589000.00, 589000.00, NULL, 65, 4, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(12, 'Quần Jogger', 'Quần jogger thể thao, co giãn thoải mái', 449000.00, 449000.00, NULL, 85, 5, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(13, 'Áo Sơ Mi Oxford', 'Áo sơ mi vải oxford cao cấp, form regular fit', 479000.00, 479000.00, NULL, 70, 6, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(14, 'Áo Khoác Denim', 'Áo khoác jean classic, phong cách bất hủ', 729000.00, 729000.00, NULL, 0, 3, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(15, 'Quần Short Kakoo', 'Quần short kaki mùa hè, thoáng mát', 200000.00, 200000.00, NULL, 0, 5, '2025-11-26 18:07:33', '2025-12-10 16:00:56', NULL),
(20, 'áo thun', 'eee', 100000.00, 100000.00, NULL, 0, 1, NULL, '2025-12-10 16:00:56', NULL),
(21, 'quân', 'lọ', 10000.00, 100000.00, 10000.00, 0, 5, NULL, '2025-12-10 16:01:16', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_attribute_values`
--

CREATE TABLE IF NOT EXISTS `product_attribute_values` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `value_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_attribute_values`
--

INSERT INTO `product_attribute_values` (`id`, `product_id`, `variant_id`, `value_id`) VALUES
(1, 1, 1, 2),
(2, 1, 1, 6),
(3, 1, 2, 3),
(4, 1, 2, 6),
(5, 1, 3, 4),
(6, 1, 3, 6),
(7, 1, 4, 2),
(8, 1, 4, 8),
(9, 1, 5, 3),
(10, 1, 5, 7),
(19, 3, 10, 1),
(20, 3, 10, 6),
(21, 3, 11, 2),
(22, 3, 11, 6),
(23, 3, 12, 3),
(24, 3, 12, 9),
(25, 3, 13, 4),
(26, 3, 13, 8),
(27, 4, 14, 14),
(28, 4, 14, 11),
(29, 4, 15, 15),
(30, 4, 15, 11),
(31, 4, 16, 16),
(32, 4, 16, 8),
(33, 4, 17, 14),
(34, 4, 17, 6),
(35, 5, 18, 2),
(36, 5, 18, 7),
(37, 5, 19, 3),
(38, 5, 19, 7),
(39, 5, 20, 4),
(40, 5, 20, 12),
(41, 5, 21, 3),
(42, 5, 21, 8),
(43, 6, 22, 2),
(44, 6, 22, 8),
(45, 6, 23, 3),
(46, 6, 23, 8),
(47, 6, 24, 4),
(48, 6, 24, 10),
(49, 6, 25, 2),
(50, 6, 25, 7),
(51, 7, 26, 2),
(52, 7, 26, 6),
(53, 7, 27, 3),
(54, 7, 27, 6),
(55, 7, 28, 4),
(56, 7, 28, 8),
(57, 7, 29, 5),
(58, 7, 29, 9),
(59, 8, 30, 13),
(60, 8, 30, 12),
(61, 8, 31, 14),
(62, 8, 31, 12),
(63, 8, 32, 15),
(64, 8, 32, 12),
(65, 8, 33, 16),
(66, 8, 33, 12),
(67, 8, 34, 17),
(68, 8, 34, 12),
(69, 9, 35, 2),
(70, 9, 35, 6),
(71, 9, 36, 3),
(72, 9, 36, 8),
(73, 9, 37, 4),
(74, 9, 37, 7),
(75, 9, 38, 2),
(76, 9, 38, 8),
(77, 10, 39, 2),
(78, 10, 39, 6),
(79, 10, 40, 3),
(80, 10, 40, 6),
(81, 10, 41, 3),
(82, 10, 41, 8),
(83, 10, 42, 4),
(84, 10, 42, 8),
(85, 11, 43, 2),
(86, 11, 43, 6),
(87, 11, 44, 3),
(88, 11, 44, 9),
(89, 11, 45, 4),
(90, 11, 45, 6),
(91, 11, 46, 1),
(92, 11, 46, 9),
(93, 12, 47, 13),
(94, 12, 47, 9),
(95, 12, 48, 14),
(96, 12, 48, 6),
(97, 12, 49, 15),
(98, 12, 49, 9),
(99, 12, 50, 16),
(100, 12, 50, 6),
(101, 13, 51, 2),
(102, 13, 51, 7),
(103, 13, 52, 3),
(104, 13, 52, 8),
(105, 13, 53, 4),
(106, 13, 53, 12),
(107, 13, 54, 3),
(108, 13, 54, 7),
(109, 14, 55, 2),
(110, 14, 55, 12),
(111, 14, 56, 3),
(112, 14, 56, 12),
(113, 14, 57, 4),
(114, 14, 57, 6),
(115, 14, 58, 3),
(116, 14, 58, 6),
(125, NULL, NULL, NULL),
(126, NULL, NULL, NULL),
(127, NULL, NULL, NULL),
(128, NULL, NULL, NULL),
(129, NULL, NULL, NULL),
(130, NULL, NULL, NULL),
(131, NULL, NULL, NULL),
(132, NULL, NULL, NULL),
(133, NULL, NULL, NULL),
(134, NULL, NULL, NULL),
(135, NULL, NULL, NULL),
(136, NULL, NULL, NULL),
(137, NULL, NULL, NULL),
(138, NULL, NULL, NULL),
(139, NULL, NULL, NULL),
(140, NULL, NULL, NULL),
(141, NULL, NULL, NULL),
(142, NULL, NULL, NULL),
(143, NULL, NULL, NULL),
(144, NULL, NULL, NULL),
(149, 20, 66, 3),
(150, 20, 66, 5);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE IF NOT EXISTS `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_url`, `is_primary`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80', 1),
(3, 3, 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80', 1),
(4, 4, 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=600&q=80', 1),
(5, 5, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80', 1),
(6, 6, 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=600&q=80', 1),
(7, 7, 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=600&q=80', 1),
(8, 8, 'https://images.unsplash.com/photo-1475180098004-ca77a66827be?auto=format&fit=crop&w=600&q=80', 1),
(9, 9, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?auto=format&fit=crop&w=600&q=80', 1),
(10, 10, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=600&q=80', 1),
(11, 11, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=600&q=80', 1),
(12, 12, 'https://images.unsplash.com/photo-1555689502-c4b22d76c56f?auto=format&fit=crop&w=600&q=80', 1),
(13, 13, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80', 1),
(14, 14, 'http://localhost/webduan1/assets/uploads/products/product_1765353078_693926760883d.jpg', 1),
(15, 15, 'http://localhost/webduan1/assets/uploads/products/product_1765340080_6938f3b0d9f8e.jpg', 1),
(16, 1, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(17, 1, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(20, 3, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(21, 3, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(22, 4, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(23, 4, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(24, 5, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(25, 5, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(26, 6, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(27, 6, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(28, 7, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(29, 7, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(30, 8, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(31, 8, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(32, 9, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(33, 9, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(34, 10, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(35, 10, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(36, 11, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(37, 11, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(38, 12, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(39, 12, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(40, 13, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(41, 13, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(42, 14, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(43, 14, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(44, 15, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=600&q=80', 0),
(45, 15, 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=600&q=80', 0),
(46, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765340249_6938f4597c802.jpg', 1),
(47, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765352781_6939254de175f.jpg', 1),
(48, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765352838_693925866fb81.jpg', 1),
(49, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765353034_6939264a3901c.jpg', 1),
(50, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765353148_693926bccbd43.jpg', 1),
(51, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765353475_693928033140b.jpg', 1),
(52, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765353764_69392924dd441.jpg', 1),
(53, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765354855_69392d67aa4f9.jpg', 1),
(54, NULL, 'http://localhost/webduan1/assets/uploads/products/product_1765355107_69392e638eee1.jpg', 1),
(55, 20, 'http://localhost/webduan1/assets/uploads/products/product_1765355565_6939302d8b91f.jpg', 1),
(56, 21, 'http://localhost/webduan1/assets/uploads/products/product_1765356266_693932ea62dad.jpg', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE IF NOT EXISTS `product_variants` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `additional_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `sku`, `additional_price`, `stock`, `image_url`) VALUES
(1, 1, 'POLO-ESS-M-BLK', 0.00, 20, NULL),
(2, 1, 'POLO-ESS-L-BLK', 0.00, 15, NULL),
(3, 1, 'POLO-ESS-XL-BLK', 0.00, 10, NULL),
(4, 1, 'POLO-ESS-M-NAV', 0.00, 18, NULL),
(5, 1, 'POLO-ESS-L-WHT', 0.00, 10, NULL),
(10, 3, 'HOODIE-URB-S-BLK', 0.00, 15, NULL),
(11, 3, 'HOODIE-URB-M-BLK', 0.00, 20, NULL),
(12, 3, 'HOODIE-URB-L-GRY', 0.00, 16, NULL),
(13, 3, 'HOODIE-URB-XL-NAV', 0.00, 10, NULL),
(14, 4, 'PANT-KAKI-30-BEI', 0.00, 25, NULL),
(15, 4, 'PANT-KAKI-31-BEI', 0.00, 20, NULL),
(16, 4, 'PANT-KAKI-32-NAV', 0.00, 15, NULL),
(17, 4, 'PANT-KAKI-30-BLK', 0.00, 18, NULL),
(18, 5, 'SHIRT-COT-M-WHT', 0.00, 22, NULL),
(19, 5, 'SHIRT-COT-L-WHT', 0.00, 20, NULL),
(20, 5, 'SHIRT-COT-XL-BLU', 0.00, 12, NULL),
(21, 5, 'SHIRT-COT-L-NAV', 0.00, 15, NULL),
(22, 6, 'POLO-STR-M-NAV', 0.00, 16, NULL),
(23, 6, 'POLO-STR-L-NAV', 0.00, 14, NULL),
(24, 6, 'POLO-STR-XL-RED', 0.00, 8, NULL),
(25, 6, 'POLO-STR-M-WHT', 0.00, 12, NULL),
(26, 7, 'JACKET-UTL-M-BLK', 0.00, 10, NULL),
(27, 7, 'JACKET-UTL-L-BLK', 0.00, 8, NULL),
(28, 7, 'JACKET-UTL-XL-NAV', 0.00, 6, NULL),
(29, 7, 'JACKET-UTL-XXL-GRY', 0.00, 4, NULL),
(30, 8, 'JEAN-DRK-29-BLU', 0.00, 12, NULL),
(31, 8, 'JEAN-DRK-30-BLU', 0.00, 18, NULL),
(32, 8, 'JEAN-DRK-31-BLU', 0.00, 15, NULL),
(33, 8, 'JEAN-DRK-32-BLU', 0.00, 10, NULL),
(34, 8, 'JEAN-DRK-33-BLU', 0.00, 8, NULL),
(35, 9, 'POLO-PRM-M-BLK', 0.00, 18, NULL),
(36, 9, 'POLO-PRM-L-NAV', 0.00, 15, NULL),
(37, 9, 'POLO-PRM-XL-WHT', 0.00, 12, NULL),
(38, 9, 'POLO-PRM-M-NAV', 0.00, 16, NULL),
(39, 10, 'BOMBER-M-BLK', 0.00, 10, NULL),
(40, 10, 'BOMBER-L-BLK', 0.00, 9, NULL),
(41, 10, 'BOMBER-L-NAV', 0.00, 8, NULL),
(42, 10, 'BOMBER-XL-NAV', 0.00, 7, NULL),
(43, 11, 'HOODIE-ZIP-M-BLK', 0.00, 14, NULL),
(44, 11, 'HOODIE-ZIP-L-GRY', 0.00, 12, NULL),
(45, 11, 'HOODIE-ZIP-XL-BLK', 0.00, 10, NULL),
(46, 11, 'HOODIE-ZIP-S-GRY', 0.00, 8, NULL),
(47, 12, 'JOGGER-29-GRY', 0.00, 20, NULL),
(48, 12, 'JOGGER-30-BLK', 0.00, 18, NULL),
(49, 12, 'JOGGER-31-GRY', 0.00, 16, NULL),
(50, 12, 'JOGGER-32-BLK', 0.00, 14, NULL),
(51, 13, 'SHIRT-OXF-M-WHT', 0.00, 20, NULL),
(52, 13, 'SHIRT-OXF-L-NAV', 0.00, 16, NULL),
(53, 13, 'SHIRT-OXF-XL-BLU', 0.00, 12, NULL),
(54, 13, 'SHIRT-OXF-L-WHT', 0.00, 14, NULL),
(55, 14, 'DENIM-M-BLU', 0.00, 12, NULL),
(56, 14, 'DENIM-L-BLU', 0.00, 1, NULL),
(57, 14, 'DENIM-XL-BLK', 0.00, 9, NULL),
(58, 14, 'DENIM-L-BLK', 0.00, 8, NULL),
(66, 20, 'BB001', 0.00, 0, 'http://localhost/webduan1/assets/uploads/products/product_1765354873_69392d7959528.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `return_requests`
--

CREATE TABLE IF NOT EXISTS `return_requests` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'requested',
  `shipping_code` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `return_requests`
--

INSERT INTO `return_requests` (`id`, `order_id`, `user_id`, `reason`, `note`, `images`, `status`, `shipping_code`, `created_at`, `updated_at`, `approved_at`, `rejected_at`, `reject_reason`, `received_at`, `refunded_at`) VALUES
(1, 21, 3, 'Sản phẩm giao sai', NULL, NULL, 'rejected', NULL, '2025-12-07 07:37:22', '2025-12-07 08:32:46', NULL, '2025-12-07 08:32:46', NULL, NULL, NULL),
(2, 21, 3, 'Thiếu phụ kiện', NULL, '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/returns\\/return_1765121608_69359e484eaa1.webp\"]', 'rejected', NULL, '2025-12-07 08:33:28', '2025-12-07 08:36:13', NULL, '2025-12-07 08:36:13', NULL, NULL, NULL),
(3, 21, 3, 'Sản phẩm bị lỗi', NULL, '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/returns\\/return_1765121799_69359f0760a8a.webp\"]', 'rejected', NULL, '2025-12-07 08:36:39', '2025-12-07 08:41:01', NULL, '2025-12-07 08:41:01', 'hhhhhh', NULL, NULL),
(4, 24, 3, 'Sản phẩm giao sai', 'yh', '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/returns\\/return_1765123004_6935a3bc4fa4e.webp\"]', 'received', 'BB6935A2755959', '2025-12-07 08:56:44', '2025-12-07 08:58:51', '2025-12-07 08:57:10', NULL, NULL, '2025-12-07 08:58:51', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `comment_history` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`review_id`, `order_id`, `order_item_id`, `user_id`, `product_id`, `rating`, `comment`, `comment_history`, `images`, `reply`, `is_hidden`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 5, 'okjkkkkkkkkkkkkkkkkkkkkk', NULL, NULL, NULL, 0, NULL, NULL),
(4, 17, 33, 3, 3, 5, 'uusuususs==s', '[{\"comment\":\"uusuususs\",\"edited_at\":\"2025-12-07 13:21:43\",\"user_id\":3},{\"comment\":\"uusuususs==s\",\"edited_at\":\"2025-12-07 13:38:25\",\"user_id\":3},{\"comment\":\"uusuususs==s\",\"edited_at\":\"2025-12-07 13:42:36\",\"user_id\":3}]', '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/reviews\\/review_1765087210_693517ea95020.webp\"]', 'cảm ơn bạn đã ủng hộ shop', 0, NULL, '2025-12-07 23:06:52'),
(5, 17, 34, 3, 2, 4, 'đssss', NULL, '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/reviews\\/review_1765090576_693525102b1d5.webp\"]', NULL, 0, NULL, NULL),
(6, 20, 38, 3, 9, 5, 'vggg', NULL, '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/reviews\\/review_1765095098_693536ba52918.webp\"]', 'okeeeeee', 0, NULL, '2025-12-07 23:14:41'),
(7, 21, 40, 3, 3, 3, 'tốttttt', '[{\"comment\":\"t\\u1ed1ttttt\",\"rating\":4,\"edited_at\":\"2025-12-07 21:14:37\",\"user_id\":3}]', NULL, NULL, 0, NULL, '2025-12-07 21:14:37'),
(8, 24, 48, 3, 3, 4, 'tuyệt', NULL, NULL, 'cảm ơn', 0, NULL, '2025-12-07 23:13:53'),
(9, 34, 59, 4, 1, 2, 'nhu cac', '[{\"comment\":\"ok\",\"rating\":5,\"edited_at\":\"2025-12-10 16:26:39\",\"user_id\":4}]', '[\"http:\\/\\/localhost\\/webduan1\\/assets\\/uploads\\/reviews\\/review_1765358760_69393ca8d4b1f.JPG\"]', NULL, 0, NULL, '2025-12-10 16:26:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `session_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `gender` enum('female','male') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `rank` varchar(20) NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `phone`, `address`, `role`, `session_token`, `session_expires`, `created_at`, `first_name`, `last_name`, `gender`, `birthday`, `is_locked`, `rank`) VALUES
(1, 'fff rể', 'le3221981@gmail.com', '$2y$10$pfMflz91BmTYXGRLRd6rP.jE97atHKYzaq/AFn.Rb87JvWNp0EOEi', '0393561314', 'Hà Nội', 'admin', NULL, NULL, '2025-11-27 10:34:49', 'fff', 'rể', 'male', '2007-11-01', 0, 'customer'),
(2, 'ee re', 'nguyenvanlinh25062006@gmail.com', '$2y$10$7d.4VUybvBcNL6thEsZb0.v9u554mW2Z377NpwHtaohJ67e2DW7Ay', '0333044840', 'Hà Nội', 'customer', '6ee393e0b6c046db44e7ecb65de566bfad15df7492cff3707877d1295675fabb', '2025-11-28 05:29:48', '2025-11-27 11:29:48', 'ee', 're', 'female', '2000-02-23', 0, 'customer'),
(3, 'Lê Phương Hà', 'phuongha9112006@gmail.com', '$2y$10$uBEzaOf/IQF3O53pHU4NqeSfdH.uiDcnjvtxKNtP10QEKfNc0vHCq', '0343748764', 'Thanh Hóa', 'customer', NULL, NULL, '2025-11-27 21:01:53', 'Lê', 'Phương Hà', 'female', '2006-11-09', 0, 'customer'),
(4, 'Phạm Văn Hiệp', 'phamvanhiep210306@gmail.com', '$2y$10$RwV7yFPY7.AKBdiEjR9MROkPj.k9sZu.Nb57XK97Aj.745qjaRLSe', '9749264441', 'sn:245 đường thanh chương phố tân trọng phường quảng phú', 'customer', NULL, NULL, '2025-11-28 11:02:33', 'Phạm', 'Văn Hiệp', 'male', '2006-03-21', 0, 'customer');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_addresses`
--

CREATE TABLE IF NOT EXISTS `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`attribute_id`);

--
-- Chỉ mục cho bảng `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD PRIMARY KEY (`value_id`),
  ADD KEY `attribute_id` (`attribute_id`);

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_usage_coupon` (`coupon_id`),
  ADD KEY `idx_coupon_usage_user` (`user_id`),
  ADD KEY `idx_coupon_usage_order` (`order_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Chỉ mục cho bảng `orders_new`
--
ALTER TABLE `orders_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Chỉ mục cho bảng `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`);

--
-- Chỉ mục cho bảng `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `attributes`
--
ALTER TABLE `attributes`
  MODIFY `attribute_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `attribute_values`
--
ALTER TABLE `attribute_values`
  MODIFY `value_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders_new`
--
ALTER TABLE `orders_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT cho bảng `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT cho bảng `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','read','replied') DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`contact_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
