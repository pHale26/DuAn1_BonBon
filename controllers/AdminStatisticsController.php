<?php

class AdminStatisticsController
{
    private OrderModel $orderModel;
    private ProductModel $productModel;
    private UserModel $userModel;

    public function __construct()
    {
        require_once PATH_MODEL . 'OrderModel.php';
        require_once PATH_MODEL . 'ProductModel.php';
        require_once PATH_MODEL . 'UserModel.php';
        
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $this->requireAdmin();

        require_once PATH_MODEL . 'CouponModel.php';
        $couponModel = new CouponModel();

        $preset = $_GET['preset'] ?? 'today';
        $statusFilter = $_GET['status_filter'] ?? null;
        $paymentFilter = $_GET['payment_filter'] ?? null;
        $categoryFilter = $_GET['category_filter'] ?? null;

        // Preset time ranges - xử lý trước khi lấy from_date/to_date từ GET
        $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $fromDate = null;
        $toDate = null;
        
        // Nếu có preset được chọn và không phải custom, tính toán date range từ preset
        if (!empty($preset) && $preset !== 'custom') {
            switch ($preset) {
                case 'yesterday':
                    $yesterday = clone $now;
                    $yesterday->modify('-1 day');
                    $fromDate = $yesterday->format('Y-m-d');
                    $toDate = $yesterday->format('Y-m-d');
                    break;
                case '7d':
                    $toDate = $now->format('Y-m-d');
                    $fromDateObj = clone $now;
                    $fromDateObj->modify('-6 days');
                    $fromDate = $fromDateObj->format('Y-m-d');
                    break;
                case 'this_month':
                    $fromDate = $now->format('Y-m-01');
                    $toDate = $now->format('Y-m-t');
                    break;
                case 'last_month':
                    $firstDayLastMonth = new DateTime('first day of last month', new DateTimeZone('Asia/Ho_Chi_Minh'));
                    $lastDayLastMonth = new DateTime('last day of last month', new DateTimeZone('Asia/Ho_Chi_Minh'));
                    $fromDate = $firstDayLastMonth->format('Y-m-d');
                    $toDate = $lastDayLastMonth->format('Y-m-d');
                    break;
                case 'quarter':
                    $month = (int)$now->format('n');
                    $quarter = (int)ceil($month / 3);
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $fromDate = sprintf("%s-%02d-01", $now->format('Y'), $startMonth);
                    $quarterStart = new DateTime($fromDate, new DateTimeZone('Asia/Ho_Chi_Minh'));
                    $quarterEnd = clone $quarterStart;
                    $quarterEnd->modify('+2 months');
                    $toDate = $quarterEnd->format('Y-m-t');
                    break;
                case 'today':
                default:
                    $fromDate = $now->format('Y-m-d');
                    $toDate = $now->format('Y-m-d');
                    break;
            }
        } else {
            // Nếu preset là custom hoặc không có, lấy từ GET
            if (!empty($_GET['from_date'])) {
                $fromDate = $_GET['from_date'];
            }
            if (!empty($_GET['to_date'])) {
                $toDate = $_GET['to_date'];
            }
        }

        // Mặc định khung thời gian: hôm nay nếu không có date
        if (!$fromDate || !$toDate) {
            $fromDate = date('Y-m-d');
            $toDate   = date('Y-m-d');
            $preset = 'today'; // Reset preset về today nếu không có date
        }
        
        // Đảm bảo fromDate <= toDate
        if ($fromDate > $toDate) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        // === KPI CHÍNH ===
        $rangeStats = $this->orderModel->getStatsByRange($fromDate, $toDate);
        $statusCounts = $this->orderModel->getStatusCounts($fromDate, $toDate);
        $newUsersCount = $this->userModel->getNewCustomersCount($fromDate, $toDate);
        $productsSold = $this->orderModel->getTotalProductsSold($fromDate, $toDate);
        $topProducts = $this->productModel->getTopSelling($fromDate, $toDate, 5);
        $topCustomers = $this->orderModel->getTopCustomers($fromDate, $toDate, 5);
        $revenueByPayment = $this->orderModel->getRevenueByPaymentMethod($fromDate, $toDate);
        $returnedCount = $this->orderModel->getReturnedOrdersCount($fromDate, $toDate);

        // Tính lợi nhuận ròng (giả sử 40% là lợi nhuận)
        $netProfit = $rangeStats['revenue'] * 0.4;

        // === BIỂU ĐỒ ===
        $dailyRevenue = $this->orderModel->getDailyRevenue($fromDate, $toDate);
        $dailyOrders = $this->orderModel->getDailyOrders($fromDate, $toDate);
        $paymentBreakdown = $this->orderModel->getPaymentBreakdown($fromDate, $toDate);
        $returnCancelChart = $this->orderModel->getReturnCancelChart($fromDate, $toDate);

        // === THỐNG KÊ ĐƠN HÀNG ===
        $orderMetrics = $this->orderModel->getOrderMetrics($fromDate, $toDate);
        
        // === THỐNG KÊ KHÁCH HÀNG ===
        $totalCustomers = $this->userModel->getCustomerCount();
        $returningRate = $this->orderModel->getReturningCustomerRate($fromDate, $toDate);
        $customerSegmentation = $this->userModel->getCustomerSegmentation();

        // === THỐNG KÊ SẢN PHẨM ===
        $totalStock = $this->productModel->countAllProducts();
        $lowStockProducts = $this->productModel->getLowStockProducts(10, 10);
        $slowSellingProducts = $this->productModel->getSlowSelling($fromDate, $toDate, 5);
        $soldByCategory = $this->productModel->getSoldByCategory($fromDate, $toDate);
        $productProfitEstimate = $this->productModel->getProductProfitEstimate($fromDate, $toDate, 10);

        // === THỐNG KÊ MÃ GIẢM GIÁ ===
        $couponStats = $couponModel->getCouponStats($fromDate, $toDate);
        $topUsedCoupons = $couponModel->getTopUsedCoupons($fromDate, $toDate, 5);

        // Lấy doanh thu theo tháng (12 tháng gần nhất)
        $monthlyRevenue = $this->orderModel->getMonthlyRevenue(12);

        // Lấy số lượng sản phẩm theo tháng
        $monthlyProducts = $this->productModel->getMonthlyProducts(12);

        // Lấy đơn hàng gần nhất
        $orders = $this->orderModel->getAll();

        $title = 'Thống kê & Dashboard';
        $view = 'admin/statistics/index';
        $filterFrom = $fromDate;
        $filterTo = $toDate;
        $filterPreset = $preset;

        require_once PATH_VIEW . 'admin/layout.php';
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
            set_flash('danger', 'Bạn cần quyền quản trị để truy cập trang này.');
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}

