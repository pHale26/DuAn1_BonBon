<?php

require_once PATH_MODEL . 'ContactModel.php';

class AdminContactController
{
    private ContactModel $contactModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->contactModel = new ContactModel();
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? null) !== 'admin') {
            set_flash('danger', 'Bạn cần quyền quản trị để truy cập trang này.');
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    /**
     * Danh sách liên hệ
     */
    public function index(): void
    {
        $status = $_GET['status'] ?? null;
        $keyword = $_GET['keyword'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;
        
        $contacts = $this->contactModel->getAll($status, $keyword, $fromDate, $toDate);
        $unreadCount = $this->contactModel->countUnread();
        
        $title = 'Quản Lý Liên Hệ';
        $view = 'admin/contacts/index';
        
        require_once PATH_VIEW . 'admin/layout.php';
    }

    /**
     * Xem chi tiết liên hệ
     */
    public function show(): void
    {
        $contactId = (int)($_GET['id'] ?? 0);
        
        if ($contactId <= 0) {
            set_flash('danger', 'Liên hệ không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-contacts');
            exit;
        }
        
        $contact = $this->contactModel->getById($contactId);
        
        if (!$contact) {
            set_flash('danger', 'Không tìm thấy liên hệ.');
            header('Location: ' . BASE_URL . '?action=admin-contacts');
            exit;
        }
        
        // Đánh dấu đã đọc nếu chưa đọc
        if ($contact['status'] === 'pending') {
            $this->contactModel->markAsRead($contactId);
            $contact['status'] = 'read';
        }
        
        $title = 'Chi Tiết Liên Hệ';
        $view = 'admin/contacts/show';
        
        require_once PATH_VIEW . 'admin/layout.php';
    }

    /**
     * Phản hồi liên hệ
     */
    public function reply(): void
    {
        header('Content-Type: application/json');
        
        $contactId = (int)($_POST['contact_id'] ?? 0);
        $reply = trim($_POST['reply'] ?? '');
        
        if ($contactId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Liên hệ không hợp lệ.'
            ]);
            exit;
        }
        
        if (empty($reply)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung phản hồi.'
            ]);
            exit;
        }
        
        $adminId = (int)($_SESSION['user']['id'] ?? 0);
        
        try {
            $success = $this->contactModel->reply($contactId, $reply, $adminId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã gửi phản hồi thành công.'
                ]);
            } else {
                throw new Exception('Không thể lưu phản hồi');
            }
        } catch (Exception $e) {
            error_log('AdminContactController::reply error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi phản hồi. Vui lòng thử lại.'
            ]);
        }
        exit;
    }

    /**
     * Xóa liên hệ
     */
    public function delete(): void
    {
        $contactId = (int)($_GET['id'] ?? 0);
        
        if ($contactId <= 0) {
            set_flash('danger', 'Liên hệ không hợp lệ.');
            header('Location: ' . BASE_URL . '?action=admin-contacts');
            exit;
        }
        
        try {
            $success = $this->contactModel->delete($contactId);
            
            if ($success) {
                set_flash('success', 'Đã xóa liên hệ thành công.');
            } else {
                set_flash('danger', 'Không thể xóa liên hệ.');
            }
        } catch (Exception $e) {
            error_log('AdminContactController::delete error: ' . $e->getMessage());
            set_flash('danger', 'Có lỗi xảy ra khi xóa liên hệ.');
        }
        
        header('Location: ' . BASE_URL . '?action=admin-contacts');
        exit;
    }
}


