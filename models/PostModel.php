<?php

class PostModel extends BaseModel
{
    protected $table = 'posts';

    public function getAll(?string $keyword = null, ?string $status = null, int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT p.*, u.full_name as author_name 
                FROM {$this->table} p 
                LEFT JOIN users u ON p.user_id = u.user_id 
                WHERE 1=1";
        $params = [];

        if ($keyword) {
            $sql .= " AND (p.title LIKE :keyword OR p.excerpt LIKE :keyword)";
            $params['keyword'] = "%$keyword%";
        }

        if ($status) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        } else {
            $sql .= " AND p.status != 'trash'";
        }

        $sql .= " ORDER BY p.created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function countAll(?string $keyword = null, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($keyword) {
            $sql .= " AND (title LIKE :keyword OR excerpt LIKE :keyword)";
            $params['keyword'] = "%$keyword%";
        }

        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        } else {
            $sql .= " AND status != 'trash'";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, u.full_name as author_name 
                FROM {$this->table} p 
                LEFT JOIN users u ON p.user_id = u.user_id 
                WHERE p.post_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT p.*, u.full_name as author_name 
                FROM {$this->table} p 
                LEFT JOIN users u ON p.user_id = u.user_id 
                WHERE p.slug = :slug AND p.status = 'published'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);
        
        $sql = "INSERT INTO {$this->table} (title, slug, excerpt, content, thumbnail, user_id, status, is_featured) 
                VALUES (:title, :slug, :excerpt, :content, :thumbnail, :user_id, :status, :is_featured)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title'     => $data['title'],
            'slug'      => $data['slug'],
            'excerpt'   => $data['excerpt'] ?? null,
            'content'   => $data['content'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'user_id'   => $data['user_id'] ?? null,
            'status'    => $data['status'] ?? 'published',
            'is_featured' => $data['is_featured'] ?? 0
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['title']) && empty($data['slug'])) {
            $currentData = $this->getById($id);
            if (empty($currentData['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }
        }
        
        if (!empty($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);
        }

        $fields = [];
        $params = [':id' => $id];

        // Map các trường cập nhật
        foreach (['title', 'slug', 'excerpt', 'content', 'thumbnail', 'user_id', 'status', 'views', 'is_featured'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE post_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE post_id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function softDelete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'trash' WHERE post_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    public function restore(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'draft' WHERE post_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function increaseViewCount(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET views = views + 1 WHERE post_id = :id");
        $stmt->execute(['id' => $id]);
    }
    
    // Tiện ích Slug
    private function generateSlug(string $string): string
    {
        $slug = strtolower(trim($string));
        
        // Chuyển ký tự tiếng Việt có dấu sang không dấu
        $slug = preg_replace("/(á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ)/", 'a', $slug);
        $slug = preg_replace("/(é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ)/", 'e', $slug);
        $slug = preg_replace("/(i|í|ì|ỉ|ĩ|ị)/", 'i', $slug);
        $slug = preg_replace("/(ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ)/", 'o', $slug);
        $slug = preg_replace("/(ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự)/", 'u', $slug);
        $slug = preg_replace("/(ý|ỳ|ỷ|ỹ|ỵ)/", 'y', $slug);
        $slug = preg_replace("/(đ)/", 'd', $slug);
        
        // Remove special chars
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        // Remove multiple dashes
        $slug = preg_replace('/-+/', '-', $slug);
        // Trim dashes
        return trim($slug, '-');
    }
    
    private function ensureUniqueSlug(string $slug, ?int $excludeUrlId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug";
            if ($excludeUrlId) {
                $sql .= " AND post_id != :id";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $params = ['slug' => $slug];
            if ($excludeUrlId) {
                $params['id'] = $excludeUrlId;
            }
            
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    public function getFeaturedPosts(int $limit = 3): array
    {
        $sql = "SELECT p.*, u.full_name as author_name 
                FROM {$this->table} p 
                LEFT JOIN users u ON p.user_id = u.user_id 
                WHERE p.status = 'published'
                ORDER BY p.is_featured DESC, p.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteMany(array $ids): bool
    {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE post_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($ids);
    }

    public function restoreMany(array $ids): bool
    {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET status = 'draft' WHERE post_id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($ids);
    }

    public function emptyTrash(): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE status = 'trash'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    public function restoreAll(): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'draft' WHERE status = 'trash'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
}
