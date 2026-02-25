<?php

class UserModel extends BaseModel
{
    protected $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
    

    public function create(array $data): int
    {
        // Loại bỏ PRIMARY KEY khỏi data
        $data = $this->removePrimaryKeyFromData($data, $this->table);
        
        $sql = "INSERT INTO {$this->table} 
                (first_name, last_name, gender, birthday, phone, address, email, password, role, created_at, full_name) 
                VALUES (:first_name, :last_name, :gender, :birthday, :phone, :address, :email, :password, :role, NOW(), :full_name)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'gender'     => $data['gender'],
            'birthday'   => $data['birthday'],
            'phone'      => $data['phone'],
            'address'    => $data['address'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'role'       => $data['role'] ?? 'customer',
            'full_name'  => trim($data['first_name'] . ' ' . $data['last_name']),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function getAll(
        ?string $keyword = null,
        ?string $role = null,
        ?string $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $lockStatus = null
    ): array
    {
        $this->ensureIsLockedColumn();
        $this->ensureRankColumn();

        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($keyword) {
            $sql .= " AND (full_name LIKE :keyword OR email LIKE :keyword OR phone LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($role) {
            $sql .= " AND role = :role";
            $params['role'] = $role;
        }

        if ($fromDate) {
            $sql .= " AND created_at >= :from_date";
            $params['from_date'] = $fromDate . ' 00:00:00';
        }

        if ($toDate) {
            $sql .= " AND created_at <= :to_date";
            $params['to_date'] = $toDate . ' 23:59:59';
        }

        if ($lockStatus === 'locked') {
            $sql .= " AND COALESCE(is_locked, 0) = 1";
        } elseif ($lockStatus === 'active') {
            $sql .= " AND COALESCE(is_locked, 0) = 0";
        }

        if ($status === 'pending') {
            $sql .= " AND session_token IS NOT NULL";
        } elseif ($status === 'verified') {
            $sql .= " AND session_token IS NULL";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRole(int $userId, string $role): void
    {
        $sql = "UPDATE {$this->table} SET role = :role WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'role' => $role,
            'id'   => $userId,
        ]);
    }

    public function setVerificationToken(int $userId, string $token, string $expiresAt): void
    {
        $sql = "UPDATE {$this->table} SET session_token = :token, session_expires = :expires WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'token'   => $token,
            'expires' => $expiresAt,
            'id'      => $userId,
        ]);
    }

    public function findByVerificationToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE session_token = :token LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function markVerified(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET session_token = NULL, session_expires = NULL WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $sql = "UPDATE {$this->table} SET password = :password WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'password' => $passwordHash,
            'id'       => $userId,
        ]);
    }

    public function getRank(int $userId): ?string
    {
        $this->ensureRankColumn();
        $stmt = $this->pdo->prepare("SELECT rank FROM {$this->table} WHERE user_id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $rank = $stmt->fetchColumn();
        return $rank !== false ? $rank : null;
    }

    public function updateRank(int $userId, string $rank): void
    {
        $this->ensureRankColumn();
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET rank = :rank WHERE user_id = :id");
        $stmt->execute([
            'rank' => $rank,
            'id'   => $userId,
        ]);
    }

    private function ensureIsLockedColumn(): void
    {
        try {
            $checkColumn = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'is_locked'");
            $columnExists = $checkColumn->rowCount() > 0;
        } catch (PDOException $e) {
            $columnExists = false;
        }

        if (!$columnExists) {
            try {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN is_locked TINYINT(1) DEFAULT 0");
            } catch (PDOException $e) {
                // Cột có thể đã được thêm bởi tiến trình khác
            }
        }
    }

    private function ensureRankColumn(): void
    {
        try {
            $checkColumn = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'rank'");
            $columnExists = $checkColumn->rowCount() > 0;
        } catch (PDOException $e) {
            $columnExists = false;
        }

        if (!$columnExists) {
            try {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN rank VARCHAR(20) NOT NULL DEFAULT 'customer'");
            } catch (PDOException $e) {
                // Nếu đồng thời quá trình khác thêm cột, bỏ qua lỗi
            }
        }
    }

    public function delete(int $userId): void
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    // Khóa/mở khóa tài khoản
    public function toggleLock(int $userId): bool
    {
        // Lấy trạng thái hiện tại
        $user = $this->findById($userId);
        if (!$user) {
            throw new Exception('Không tìm thấy tài khoản.');
        }

        // Kiểm tra xem có cột is_locked không, nếu không thì tạo
        try {
            $currentLocked = isset($user['is_locked']) ? (bool)$user['is_locked'] : false;
            $newLocked = !$currentLocked;

            $sql = "UPDATE {$this->table} SET is_locked = :is_locked WHERE user_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'is_locked' => $newLocked ? 1 : 0,
                'id' => $userId,
            ]);

            return $newLocked;
        } catch (PDOException $e) {
            // Nếu cột is_locked chưa tồn tại, thêm cột và cập nhật
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                try {
                    $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN is_locked TINYINT(1) DEFAULT 0");
                    $sql = "UPDATE {$this->table} SET is_locked = :is_locked WHERE user_id = :id";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'is_locked' => 1,
                        'id' => $userId,
                    ]);
                    return true;
                } catch (PDOException $e2) {
                    throw new Exception('Không thể cập nhật trạng thái khóa: ' . $e2->getMessage());
                }
            }
            throw new Exception('Không thể cập nhật trạng thái khóa: ' . $e->getMessage());
        }
    }

    // Lấy tổng số người dùng
    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM {$this->table}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // Lấy số lượng admin
    public function getAdminCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM {$this->table} WHERE role = 'admin'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // Lấy số lượng khách hàng
    public function getCustomerCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM {$this->table} WHERE role = 'customer'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // Lấy thông tin user theo ID
    public function findById(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    // Cập nhật thông tin cá nhân
    public function updateProfile(int $userId, array $data): void
    {
        $sql = "UPDATE {$this->table} 
                SET first_name = :first_name, 
                    last_name = :last_name, 
                    full_name = :full_name,
                    gender = :gender, 
                    birthday = :birthday, 
                    phone = :phone, 
                    address = :address
                WHERE user_id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'full_name'  => trim($data['first_name'] . ' ' . $data['last_name']),
            'gender'     => $data['gender'],
            'birthday'   => $data['birthday'] ?: null,
            'phone'      => $data['phone'],
            'address'    => $data['address'],
            'id'         => $userId,
        ]);
    }

    // Lấy số khách hàng mới theo khoảng thời gian
    public function getNewCustomersCount(string $fromDate, string $toDate): int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS cnt
                FROM {$this->table}
                WHERE role = 'customer'
                AND DATE(created_at) BETWEEN :from_date AND :to_date
            ");
            $stmt->execute([':from_date' => $fromDate, ':to_date' => $toDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['cnt'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    // Phân loại khách hàng (mới / VIP / thân thiết)
    public function getCustomerSegmentation(): array
    {
        try {
            $this->ensureRankColumn();
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(rank, 'customer') AS segment,
                    COUNT(*) AS count
                FROM {$this->table}
                WHERE role = 'customer'
                GROUP BY COALESCE(rank, 'customer')
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $segmentation = [
                'customer' => 0,
                'vip' => 0,
                'loyal' => 0
            ];
            
            foreach ($results as $row) {
                $segment = $row['segment'];
                $count = (int)$row['count'];
                
                if (isset($segmentation[$segment])) {
                    $segmentation[$segment] = $count;
                }
            }
            
            return $segmentation;
        } catch (Exception $e) {
            return [
                'customer' => 0,
                'vip' => 0,
                'loyal' => 0
            ];
        }
    }
}

