<?php

require_once PATH_MODEL . 'BaseModel.php';

class CategoryModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'categories';
    }

    /**
     * Kiểm tra tên danh mục đã tồn tại (không phân biệt hoa/thường)
     */
    public function existsName(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE LOWER(category_name) = LOWER(:name)";
        $params = [':name' => $name];
        if ($excludeId !== null) {
            $sql .= " AND category_id <> :id";
            $params[':id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Tạo danh mục mới
     */
    public function createCategory(string $name, ?string $description = null): int
    {
        // Đảm bảo không có id trong data (nếu được truyền dưới dạng array)
        $data = ['category_name' => $name, 'description' => $description];
        $data = $this->removePrimaryKeyFromData($data, $this->table);
        
        $sql = "INSERT INTO {$this->table} (category_name, description)
                VALUES (:name, :description)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $data['category_name'], PDO::PARAM_STR);
        $stmt->bindValue(
            ':description',
            $data['description'],
            $data['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật danh mục
     */
    public function updateCategory(int $categoryId, string $name, ?string $description = null): bool
    {
        $sql = "UPDATE {$this->table}
                SET category_name = :name, description = :description
                WHERE category_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(
            ':description',
            $description,
            $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(':id', $categoryId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Xóa danh mục (chỉ thành công khi không có sản phẩm ràng buộc hoặc DB cho phép)
     */
    public function deleteCategory(int $categoryId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE category_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $categoryId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Lấy tất cả danh mục
     */
    public function getAllCategories(): array
    {
        $sql = "SELECT category_id, category_name, description 
                FROM {$this->table} 
                ORDER BY category_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh mục theo ID
     */
    public function getCategoryById(int $categoryId): ?array
    {
        $sql = "SELECT category_id, category_name, description 
                FROM {$this->table} 
                WHERE category_id = :category_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Tìm kiếm danh mục theo tên
     */
    public function searchCategories(string $keyword): array
    {
        $sql = "SELECT category_id, category_name, description 
                FROM {$this->table} 
                WHERE category_name LIKE :keyword
                ORDER BY category_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':keyword', "%{$keyword}%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

