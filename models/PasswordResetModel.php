<?php

class PasswordResetModel extends BaseModel
{
    protected $table = 'password_resets';

    public function create(int $userId, string $token, string $otp, string $expiresAt): int
    {
        // Đảm bảo không có id trong data
        $data = ['user_id' => $userId, 'token' => $token, 'otp_code' => $otp, 'expires_at' => $expiresAt];
        $data = $this->removePrimaryKeyFromData($data, $this->table);
        
        $sql = "INSERT INTO {$this->table} (user_id, token, otp_code, expires_at, is_used, created_at)
                VALUES (:user_id, :token, :otp_code, :expires_at, 0, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id'    => $data['user_id'],
            'token'      => $data['token'],
            'otp_code'   => $data['otp_code'],
            'expires_at' => $data['expires_at'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findValidToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = :token AND (is_used = 0 OR is_used IS NULL) LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    public function markUsed(int $resetId): void
    {
        $sql = "UPDATE {$this->table} SET is_used = 1 WHERE reset_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $resetId]);
    }
}

