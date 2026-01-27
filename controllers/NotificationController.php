<?php

require_once PATH_MODEL . 'NotificationModel.php';

class NotificationController
{
    private NotificationModel $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Lấy danh sách thông báo mới nhất của user.
     */
    public function index(): void
    {
        $userId = $this->requireUserId();
        header('Content-Type: application/json; charset=utf-8');

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $items = $this->notificationModel->getByUser($userId, $limit);
        $unread = $this->notificationModel->countUnread($userId);

        echo json_encode([
            'success' => true,
            'items' => $items,
            'unread' => $unread,
        ]);
        exit;
    }

    /**
     * Đánh dấu đã đọc (1 hoặc nhiều hoặc tất cả).
     */
    public function markRead(): void
    {
        $userId = $this->requireUserId();
        header('Content-Type: application/json; charset=utf-8');

        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $affected = 0;

        if (!empty($payload['all'])) {
            $affected = $this->notificationModel->markAllRead($userId);
        } else {
            $ids = $payload['ids'] ?? [];
            if (isset($payload['id'])) {
                $ids[] = $payload['id'];
            }
            $affected = $this->notificationModel->markReadMany($userId, (array)$ids);
        }

        echo json_encode([
            'success' => true,
            'updated' => $affected,
            'unread' => $this->notificationModel->countUnread($userId),
        ]);
        exit;
    }

    /**
     * Xóa thông báo: theo danh sách id, chỉ những cái đã đọc, hoặc toàn bộ.
     */
    public function delete(): void
    {
        $userId = $this->requireUserId();
        header('Content-Type: application/json; charset=utf-8');

        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $mode = $payload['mode'] ?? '';
        $deleted = 0;

        if ($mode === 'all') {
            $deleted = $this->notificationModel->deleteAll($userId);
        } elseif ($mode === 'read') {
            $deleted = $this->notificationModel->deleteRead($userId);
        } else {
            $ids = $payload['ids'] ?? [];
            if (isset($payload['id'])) {
                $ids[] = $payload['id'];
            }
            $deleted = $this->notificationModel->deleteMany($userId, (array)$ids);
        }

        echo json_encode([
            'success' => true,
            'deleted' => $deleted,
        ]);
        exit;
    }

    private function requireUserId(): int
    {
        $user = $_SESSION['user'] ?? null;
        $userId = (int)($user['id'] ?? $user['user_id'] ?? 0);

        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để xem thông báo.',
            ]);
            exit;
        }

        return $userId;
    }
}


