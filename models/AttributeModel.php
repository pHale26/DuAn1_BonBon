<?php

class AttributeModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'attributes';
    }

    public function getAttributesWithValues(): array
    {
        $sql = "SELECT 
                    a.attribute_id,
                    a.attribute_name,
                    av.value_id,
                    av.value_name
                FROM {$this->table} a
                LEFT JOIN attribute_values av ON a.attribute_id = av.attribute_id
                ORDER BY a.attribute_id ASC, av.value_name ASC";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attributes = [];
        foreach ($rows as $row) {
            $id = (int)$row['attribute_id'];
            if (!isset($attributes[$id])) {
                $attributes[$id] = [
                    'attribute_id' => $id,
                    'attribute_name' => $row['attribute_name'],
                    'values' => [],
                ];
            }

            if (!empty($row['value_id'])) {
                $attributes[$id]['values'][] = [
                    'value_id' => (int)$row['value_id'],
                    'value_name' => $row['value_name'],
                ];
            }
        }

        return array_values($attributes);
    }

    public function getAllAttributes(): array
    {
        $stmt = $this->pdo->query("SELECT attribute_id, attribute_name FROM {$this->table} ORDER BY attribute_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getValuesByAttribute(int $attributeId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT value_id, value_name
            FROM attribute_values
            WHERE attribute_id = :attribute_id
            ORDER BY value_name ASC
        ");
        $stmt->bindValue(':attribute_id', $attributeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function attributeExists(string $name): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE LOWER(TRIM(attribute_name)) = LOWER(TRIM(:name))");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    public function attributeExistsExcept(string $name, int $exceptId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE LOWER(TRIM(attribute_name)) = LOWER(TRIM(:name)) AND attribute_id <> :id");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':id', $exceptId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    public function createAttribute(string $name): int
    {
        // Kiểm tra trùng tên
        if ($this->attributeExists($name)) {
            throw new InvalidArgumentException('Thuộc tính này đã tồn tại.');
        }
        // Đảm bảo không có id trong data
        $data = ['attribute_name' => $name];
        $data = $this->removePrimaryKeyFromData($data, $this->table);
        
        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (attribute_name) VALUES (:name)");
        $stmt->bindValue(':name', $data['attribute_name'], PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updateAttribute(int $attributeId, string $name): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET attribute_name = :name WHERE attribute_id = :id");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':id', $attributeId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteAttribute(int $attributeId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $valueIds = $this->getValueIdsByAttribute($attributeId);

            if ($valueIds) {
                $placeholder = implode(',', array_fill(0, count($valueIds), '?'));
                $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE value_id IN ($placeholder)");
                $stmt->execute($valueIds);

                $stmt = $this->pdo->prepare("DELETE FROM attribute_values WHERE value_id IN ($placeholder)");
                $stmt->execute($valueIds);
            }

            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE attribute_id = :id");
            $stmt->bindValue(':id', $attributeId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function createValue(int $attributeId, string $valueName): int
    {
        // Kiểm tra trùng giá trị theo attribute
        $check = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM attribute_values WHERE attribute_id = :attribute_id AND LOWER(TRIM(value_name)) = LOWER(TRIM(:value_name))");
        $check->bindValue(':attribute_id', $attributeId, PDO::PARAM_INT);
        $check->bindValue(':value_name', $valueName, PDO::PARAM_STR);
        $check->execute();
        $exists = (int)($check->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0) > 0;
        if ($exists) {
            throw new InvalidArgumentException('Giá trị thuộc tính này đã tồn tại.');
        }
        // Đảm bảo không có id trong data
        $data = ['attribute_id' => $attributeId, 'value_name' => $valueName];
        $data = $this->removePrimaryKeyFromData($data, 'attribute_values');
        
        $stmt = $this->pdo->prepare("
            INSERT INTO attribute_values (attribute_id, value_name)
            VALUES (:attribute_id, :value_name)
        ");
        $stmt->bindValue(':attribute_id', $data['attribute_id'], PDO::PARAM_INT);
        $stmt->bindValue(':value_name', $data['value_name'], PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updateValue(int $valueId, string $valueName): bool
    {
        $stmt = $this->pdo->prepare("UPDATE attribute_values SET value_name = :value_name WHERE value_id = :value_id");
        $stmt->bindValue(':value_name', $valueName, PDO::PARAM_STR);
        $stmt->bindValue(':value_id', $valueId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteValue(int $valueId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("DELETE FROM product_attribute_values WHERE value_id = :value_id");
            $stmt->bindValue(':value_id', $valueId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM attribute_values WHERE value_id = :value_id");
            $stmt->bindValue(':value_id', $valueId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function getValueIdsByAttribute(int $attributeId): array
    {
        $stmt = $this->pdo->prepare("SELECT value_id FROM attribute_values WHERE attribute_id = :attribute_id");
        $stmt->bindValue(':attribute_id', $attributeId, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($row) => (int)$row['value_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

