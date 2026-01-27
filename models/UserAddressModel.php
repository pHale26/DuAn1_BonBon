<?php

require_once PATH_MODEL . 'BaseModel.php';

class UserAddressModel extends BaseModel
{
    protected $table = 'user_addresses';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Đảm bảo bảng tồn tại, nếu chưa thì tạo
     */
  private function ensureTableExists(): void
{
    try {
        $checkTable = $this->pdo->query("SHOW TABLES LIKE '{$this->table}'");
        if ($checkTable->rowCount() == 0) {
            $sql = "CREATE TABLE {$this->table} (
                address_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                fullname VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                address TEXT NOT NULL,
                city VARCHAR(100),
                district VARCHAR(100),
                ward VARCHAR(100),
                address_type VARCHAR(20) DEFAULT 'home',
                is_default TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_is_default (is_default)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->pdo->exec($sql);
        }
    } catch (PDOException $e) {
        error_log("UserAddressModel: Failed to create table - " . $e->getMessage());
    }
}

public function getByUserId(int $userId): array
{
    $sql = "SELECT * FROM {$this->table} 
            WHERE user_id = :user_id 
            ORDER BY is_default DESC, created_at DESC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

public function getDefaultByUserId(int $userId): ?array
{
    $sql = "SELECT * FROM {$this->table} 
            WHERE user_id = :user_id AND is_default = 1 
            LIMIT 1";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

public function create(array $data): int
{
    if (!empty($data['is_default'])) {
        $this->unsetDefaultForUser($data['user_id']);
    }

    $sql = "INSERT INTO {$this->table} 
            (user_id, fullname, phone, email, address, city, district, ward, address_type, is_default) 
            VALUES (:user_id, :fullname, :phone, :email, :address, :city, :district, :ward, :address_type, :is_default)";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'user_id'      => $data['user_id'],
        'fullname'     => $data['fullname'],
        'phone'        => $data['phone'],
        'email'        => $data['email'],
        'address'      => $data['address'],
        'city'         => $data['city'] ?? null,
        'district'     => $data['district'] ?? null,
        'ward'         => $data['ward'] ?? null,
        'address_type' => $data['address_type'] ?? 'home',
        'is_default'   => $data['is_default'] ?? 0,
    ]);

    return (int) $this->pdo->lastInsertId();
}

public function update(int $addressId, array $data): bool
{
    $address = $this->getById($addressId);
    if ($address && !empty($data['is_default'])) {
        $this->unsetDefaultForUser($address['user_id'], $addressId);
    }

    $sql = "UPDATE {$this->table} SET
                fullname     = :fullname,
                phone        = :phone,
                email        = :email,
                address      = :address,
                city         = :city,
                district     = :district,
                ward         = :ward,
                address_type = :address_type,
                is_default   = :is_default
            WHERE address_id = :address_id";

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
        'fullname'    => $data['fullname'],
        'phone'       => $data['phone'],
        'email'       => $data['email'],
        'address'     => $data['address'],
        'city'        => $data['city'] ?? null,
        'district'    => $data['district'] ?? null,
        'ward'        => $data['ward'] ?? null,
        'address_type'=> $data['address_type'] ?? 'home',
        'is_default'  => $data['is_default'] ?? 0,
        'address_id'  => $addressId,  // ✅ thêm tham số thiếu
    ]);
}

public function delete(int $addressId): bool
{
    $sql = "DELETE FROM {$this->table} WHERE address_id = :address_id";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute(['address_id' => $addressId]);
}

public function setDefault(int $addressId, int $userId): bool
{
    $this->unsetDefaultForUser($userId, $addressId);

    $sql = "UPDATE {$this->table} 
            SET is_default = 1 
            WHERE address_id = :address_id AND user_id = :user_id";
    $stmt = $this->pdo->prepare($sql);

    return $stmt->execute([
        'address_id' => $addressId,
        'user_id'    => $userId,
    ]);
}

private function unsetDefaultForUser(int $userId, ?int $excludeAddressId = null): void
{
    $sql = "UPDATE {$this->table} SET is_default = 0 WHERE user_id = :user_id";
    $params = ['user_id' => $userId];

    if ($excludeAddressId !== null) {
        $sql .= " AND address_id != :exclude";
        $params['exclude'] = $excludeAddressId;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
}

public function getById(int $addressId): ?array
{
    $sql = "SELECT * FROM {$this->table} WHERE address_id = :address_id LIMIT 1";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['address_id' => $addressId]);
    $result = $stmt->fetch();
    return $result ?: null;
}
}

