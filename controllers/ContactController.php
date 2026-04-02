<?php

class ContactController
{
    public function index()
    {
        $view = 'contact';
        $title = 'Liên Hệ - BonBonWear';
        require_once PATH_VIEW . 'main.php';
    }

    public function submit()
    {
        header('Content-Type: application/json');
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($name) || empty($email) || empty($message)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc (Họ tên, Email, Nội dung).'
            ]);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email không hợp lệ.'
            ]);
            exit;
        }
        
        // Lưu vào database
        try {
            require_once PATH_MODEL . 'ContactModel.php';
            $contactModel = new ContactModel();
            
            $contactId = $contactModel->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message,
            ]);
            
            if ($contactId > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.'
                ]);
            } else {
                throw new Exception('Không thể lưu liên hệ');
            }
        } catch (Exception $e) {
            error_log('ContactController::submit error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi liên hệ. Vui lòng thử lại sau.'
            ]);
        }
        exit;
    }

    /**
     * Xem lịch sử liên hệ của user
     */
    public function history()
    {
        // Yêu cầu đăng nhập
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
            set_flash('warning', 'Vui lòng đăng nhập để xem lịch sử liên hệ.');
            header('Location: ' . BASE_URL . '?action=show-login');
            exit;
        }

        require_once PATH_MODEL . 'ContactModel.php';
        $contactModel = new ContactModel();
        
        $userEmail = $_SESSION['user']['email'];
        $contacts = $contactModel->getByEmail($userEmail);
        
        $title = 'Lịch Sử Liên Hệ - BonBonWear';
        $view = 'contact-history';
        
        require_once PATH_VIEW . 'main.php';
    }
}

