<?php

// Đảm bảo controller mua hàng được load (phục vụ kiểm tra tĩnh & tránh lỗi thiếu method)
require_once PATH_CONTROLLER . 'OrderController.php';

$action = $_GET['action'] ?? '/';

match ($action) {
    '/'              => (new HomeController)->index(),
    
    // Product routes
    'products'       => (new ProductController)->index(),
    'product-detail' => (new ProductController)->detail(),
    'product-attributes' => (new ProductController)->attributes(),
    'variant-images' => (new ProductController)->variantImages(),
    'get-variant-stock' => (new ProductController)->getVariantStock(),
    'search-api'     => (new ProductController)->searchApi(),
    'search-smart'   => (new ProductController)->searchSmart(),
    
    // Notification routes
    'notifications'          => (new NotificationController)->index(),
    'notification-mark-read' => (new NotificationController)->markRead(),
    'notification-delete'    => (new NotificationController)->delete(),
    
    // Post routes
    'posts'          => (new PostController)->index(),
    'post-detail'    => (new PostController)->detail(),
    
    // Contact & Collection routes
    'contact'        => (new ContactController)->index(),
    'contact-submit' => (new ContactController)->submit(),
    'contact-history' => (new ContactController)->history(),
    'collection'     => (new CollectionController)->index(),
    
    // Cart routes
    'cart-list'      => (new CartController)->index(),
    'cart-add'       => (new CartController)->add(),
    'cart-update'    => (new CartController)->update(),
    'cart-delete'    => (new CartController)->delete(),
    'cart-delete-multiple' => (new CartController)->deleteMultiple(),
    'cart-count'     => (new CartController)->count(),
    'cart-set-selected' => (new CartController)->setSelected(),
    
    // Checkout routes
    'checkout'       => (new CheckoutController)->index(),
    'checkout-process' => (new CheckoutController)->process(),
    'set-default-address' => (new CheckoutController)->setDefaultAddress(),
    'save-address' => (new CheckoutController)->saveAddress(),
    
    // VNPay routes
    'vnpay-return'   => (new VnPayController)->return(),
    'vnpay-ipn'      => (new VnPayController)->ipn(),
    
    // Address routes
    'address-provinces' => (new AddressController)->getProvinces(),
    'address-districts' => (new AddressController)->getDistricts(),
    'address-wards' => (new AddressController)->getWards(),
    'address-search' => (new AddressController)->search(),
    
    // Coupon routes
    'coupon-validate' => (new CouponController)->validate(),
    'coupon-available' => (new CouponController)->getAvailable(),
    'coupon-remove' => (new CouponController)->remove(),

    // Routes đơn hàng cho user
    'order-history'  => (new OrderController)->history(),
    'order-detail'   => (new OrderController)->detail(),
    'order-cancel'   => (new OrderController)->cancel(),
    'order-confirm'  => (new OrderController)->confirmReceived(),
    'order-pay'      => (new OrderController)->pay(),
    'order-rebuy'    => (new OrderController)->rebuy(),
    
    // Routes đánh giá cho user
    'review-submit'  => (new ReviewController)->submit(),
    'review-upload-image' => (new ReviewController)->uploadImage(),
    'review-get'     => (new ReviewController)->getByProduct(),
    'review-update'  => (new ReviewController)->update(),

    // Return requests (user)
    'return-request' => (new ReturnController)->create(),
    'return-upload-shipping' => (new ReturnController)->uploadShipping(),
    'return-cancel' => (new ReturnController)->cancel(),

    // Routes quản trị
    'admin-dashboard'    => (new AdminDashboardController)->index(),
    'admin-statistics'   => (new AdminStatisticsController)->index(),
    'admin-orders'            => (new AdminOrderController)->index(),
    'admin-order-update'      => (new AdminOrderController)->updateStatus(),
    // alias used by some views
    'admin-order-update-status' => (new AdminOrderController)->updateStatus(),
    'admin-order-detail'      => (new AdminOrderController)->detail(),
    'admin-order-approve-cancel' => (new AdminOrderController)->approveCancel(),
    'admin-order-confirm'     => (new AdminOrderController)->confirmOrder(),
    'admin-order-preparing'   => (new AdminOrderController)->markPreparing(),
    'admin-order-handed-to-shipper' => (new AdminOrderController)->markHandedToShipper(),
    'admin-order-shipping'    => (new AdminOrderController)->markShipping(),
    'admin-order-cancel'      => (new AdminOrderController)->cancelOrder(),
    'admin-order-delivered'   => (new AdminOrderController)->confirmDelivered(),
    // Quản lý tài khoản
    'admin-users'        => (new AdminUserController)->index(),
    'admin-user-role'    => (new AdminUserController)->updateRole(),
    'admin-user-toggle-lock' => (new AdminUserController)->toggleLock(),
    
    // Quản lý mã giảm giá
    'admin-coupons'      => (new AdminCouponController)->index(),
    'admin-coupon-create' => (new AdminCouponController)->create(),
    'admin-coupon-update' => (new AdminCouponController)->update(),
    'admin-coupon-delete' => (new AdminCouponController)->delete(),
    'admin-coupon-edit'   => (new AdminCouponController)->edit(),
    'admin-coupons-trash' => (new AdminCouponController)->trash(),
    'admin-coupon-restore' => (new AdminCouponController)->restore(),
    'admin-coupon-force-delete' => (new AdminCouponController)->forceDelete(),
    'admin-coupon-empty-trash'  => (new AdminCouponController)->emptyTrashAction(),
    'admin-coupon-restore-all'  => (new AdminCouponController)->restoreAllAction(),
    'admin-coupon-bulk-trash'   => (new AdminCouponController)->bulkTrashAction(),
    
    // Quản lý danh mục
    'admin-categories'      => (new AdminCategoryController)->index(),
    'admin-category-create' => (new AdminCategoryController)->create(),
    'admin-category-edit'   => (new AdminCategoryController)->edit(),
    'admin-category-store'  => (new AdminCategoryController)->store(),
    'admin-category-update' => (new AdminCategoryController)->update(),
    'admin-category-delete' => (new AdminCategoryController)->delete(),
    
    // Quản lý thuộc tính
    'admin-attributes'      => (new AdminAttributeController)->index(),
    'admin-attribute-store' => (new AdminAttributeController)->store(),
    'admin-attribute-update' => (new AdminAttributeController)->update(),
    'admin-attribute-delete' => (new AdminAttributeController)->delete(),
    'admin-attribute-value-store' => (new AdminAttributeController)->storeValue(),
    'admin-attribute-value-update' => (new AdminAttributeController)->updateValue(),
    'admin-attribute-value-delete' => (new AdminAttributeController)->deleteValue(),
    
    // Quản lý sản phẩm
    'admin-products'      => (new AdminProductController)->index(),
    'admin-products-trash' => (new AdminProductController)->trash(),
    'admin-product-create' => (new AdminProductController)->create(),
    'admin-product-edit'   => (new AdminProductController)->edit(),
    'admin-product-detail' => (new AdminProductController)->detail(),
    'admin-product-store'  => (new AdminProductController)->store(),
    'admin-product-update' => (new AdminProductController)->update(),
    'admin-product-delete' => (new AdminProductController)->delete(),
    'admin-product-restore' => (new AdminProductController)->restore(),
    'admin-product-force-delete' => (new AdminProductController)->forceDelete(),
    'admin-product-empty-trash' => (new AdminProductController)->emptyTrashAction(),
    'admin-product-restore-all' => (new AdminProductController)->restoreAllAction(),
    'admin-product-bulk-trash'  => (new AdminProductController)->bulkTrashAction(),
    'admin-product-variant-store' => (new AdminProductController)->storeVariant(),
    'admin-product-variant-update' => (new AdminProductController)->updateVariant(),
    'admin-product-variant-delete' => (new AdminProductController)->deleteVariant(),
    
    // Quản lý đánh giá/bình luận
    'admin-reviews'       => (new AdminReviewController)->index(),
    'admin-review-reply' => (new AdminReviewController)->reply(),
    'admin-review-toggle-hidden' => (new AdminReviewController)->toggleHidden(),
    'admin-review-delete' => (new AdminReviewController)->delete(),
    'admin-review-detail' => (new AdminReviewController)->detail(),
    // Return requests (admin)
    'admin-return-approve' => (new AdminReturnController)->approve(),
    'admin-return-reject' => (new AdminReturnController)->reject(),
    'admin-return-receive' => (new AdminReturnController)->receive(),
    'admin-return-refund' => (new AdminReturnController)->refund(),
    
    // Quản lý liên hệ (admin)
    'admin-contacts' => (new AdminContactController)->index(),
    'admin-contact-show' => (new AdminContactController)->show(),
    'admin-contact-reply' => (new AdminContactController)->reply(),
    'admin-contact-delete' => (new AdminContactController)->delete(),

    // Quản lý bài viết (admin)
    'admin-posts'         => (new AdminPostController)->index(),
    'admin-post-create'   => (new AdminPostController)->create(),
    'admin-post-store'    => (new AdminPostController)->store(),
    'admin-post-edit'     => (new AdminPostController)->edit(),
    'admin-post-update'   => (new AdminPostController)->update(),
    'admin-post-delete'   => (new AdminPostController)->delete(),
    'admin-post-trash'        => (new AdminPostController)->trash(),
    'admin-post-restore'      => (new AdminPostController)->restore(),
    'admin-post-force-delete' => (new AdminPostController)->forceDelete(),
    'admin-post-empty-trash'  => (new AdminPostController)->emptyTrashAction(),
    'admin-post-restore-all'  => (new AdminPostController)->restoreAllAction(),
    'admin-post-bulk-trash'   => (new AdminPostController)->bulkTrashAction(),
    
    // Auth routes
    'show-login'         => (new AuthController)->showLogin(),
    'show-register'      => (new AuthController)->showRegister(),
    'show-forgot'        => (new AuthController)->showForgotPassword(),
    'show-reset-password'=> (new AuthController)->showResetPassword(),
    'verify-account'     => (new AuthController)->verifyAccount(),
    'login'              => (new AuthController)->login(),
    'register'           => (new AuthController)->register(),
    'logout'             => (new AuthController)->logout(),
    'forgot-password'    => (new AuthController)->handleForgotPassword(),
    'reset-password'     => (new AuthController)->resetPassword(),
    'profile'            => (new AuthController)->showProfile(),
    'update-profile'     => (new AuthController)->updateProfile(),
    
    default          => (new HomeController)->index(),
};