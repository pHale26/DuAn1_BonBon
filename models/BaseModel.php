<?php

class BaseModel
{
    protected $table;
    protected $pdo;

    // Kết nối CSDL
    public function __construct()
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);

        try {
            $this->pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
        } catch (PDOException $e) {
            // Xử lý lỗi kết nối
            die("Kết nối cơ sở dữ liệu thất bại: {$e->getMessage()}. Vui lòng thử lại sau.");
        }
    }

    // Hủy kết nối CSDL
    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * Loại bỏ các trường PRIMARY KEY (id, order_id, etc.) khỏi data array
     * Đảm bảo không bao giờ insert PRIMARY KEY vào database
     * 
     * @param array $data Dữ liệu đầu vào
     * @param string|null $tableName Tên bảng (để xác định PRIMARY KEY chính xác)
     * @return array Data đã được loại bỏ PRIMARY KEY
     */
    protected function removePrimaryKeyFromData(array $data, ?string $tableName = null): array
    {
        // Danh sách các tên có thể là PRIMARY KEY
        $primaryKeyNames = ['id', 'order_id', 'orderId', 'user_id', 'product_id', 'category_id', 
                           'attribute_id', 'value_id', 'variant_id', 'cart_id', 'cart_item_id',
                           'coupon_id', 'review_id', 'address_id', 'post_id', 'reset_id',
                           'image_id', 'product_image_id', 'order_item_id'];
        
        // Nếu có tableName, thử lấy PRIMARY KEY thực tế từ database
        if ($tableName) {
            try {
                $stmt = $this->pdo->query("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
                $primaryKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($primaryKeys as $pk) {
                    if (isset($pk['Column_name'])) {
                        $primaryKeyNames[] = $pk['Column_name'];
                    }
                }
            } catch (PDOException $e) {
                // Bỏ qua nếu không lấy được PRIMARY KEY
            }
        }
        
        // Loại bỏ tất cả các key có thể là PRIMARY KEY
        foreach ($primaryKeyNames as $pkName) {
            if (isset($data[$pkName])) {
                unset($data[$pkName]);
                error_log("BaseModel::removePrimaryKeyFromData - Removed PRIMARY KEY '$pkName' from data");
            }
        }
        
        return $data;
    }

    /**
     * Loại bỏ PRIMARY KEY khỏi danh sách columns khi build INSERT statement
     * 
     * @param array $columns Danh sách tên cột
     * @param string|null $tableName Tên bảng
     * @return array Danh sách cột đã loại bỏ PRIMARY KEY
     */
    protected function removePrimaryKeyFromColumns(array $columns, ?string $tableName = null): array
    {
        $primaryKeyNames = ['id', 'order_id', 'orderId'];
        
        // Nếu có tableName, lấy PRIMARY KEY thực tế
        if ($tableName) {
            try {
                $stmt = $this->pdo->query("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
                $primaryKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($primaryKeys as $pk) {
                    if (isset($pk['Column_name'])) {
                        $primaryKeyNames[] = $pk['Column_name'];
                    }
                }
            } catch (PDOException $e) {
                // Bỏ qua
            }
        }
        
        // Loại bỏ PRIMARY KEY khỏi danh sách columns
        return array_filter($columns, function($col) use ($primaryKeyNames) {
            foreach ($primaryKeyNames as $pkName) {
                if (strcasecmp($col, $pkName) === 0) {
                    error_log("BaseModel::removePrimaryKeyFromColumns - Removed PRIMARY KEY column '$col'");
                    return false;
                }
            }
            return true;
        });
    }
}
