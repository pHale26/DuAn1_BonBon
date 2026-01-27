<?php

class AddressController
{
    /**
     * Lấy danh sách tỉnh/thành phố
     */
    public function getProvinces(): void
    {
        header('Content-Type: application/json');
        
        $dataFile = PATH_ROOT . 'assets/data/vietnam-addresses.json';
        if (!file_exists($dataFile)) {
            echo json_encode(['success' => false, 'message' => 'File dữ liệu không tồn tại']);
            exit;
        }
        
        $data = json_decode(file_get_contents($dataFile), true);
        echo json_encode(['success' => true, 'provinces' => $data['provinces'] ?? []]);
        exit;
    }

    /**
     * Lấy danh sách quận/huyện theo tỉnh
     */
    public function getDistricts(): void
    {
        header('Content-Type: application/json');
        
        $provinceCode = $_GET['province_code'] ?? '';
        if (empty($provinceCode)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã tỉnh']);
            exit;
        }
        
        $dataFile = PATH_ROOT . 'assets/data/vietnam-addresses.json';
        if (!file_exists($dataFile)) {
            echo json_encode(['success' => false, 'message' => 'File dữ liệu không tồn tại']);
            exit;
        }
        
        $data = json_decode(file_get_contents($dataFile), true);
        $districts = $data['districts'][$provinceCode] ?? [];
        echo json_encode(['success' => true, 'districts' => $districts]);
        exit;
    }

    /**
     * Lấy danh sách phường/xã theo quận/huyện
     */
    public function getWards(): void
    {
        header('Content-Type: application/json');
        
        $districtCode = $_GET['district_code'] ?? '';
        if (empty($districtCode)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã quận/huyện']);
            exit;
        }
        
        $dataFile = PATH_ROOT . 'assets/data/vietnam-addresses.json';
        if (!file_exists($dataFile)) {
            echo json_encode(['success' => false, 'message' => 'File dữ liệu không tồn tại']);
            exit;
        }
        
        $data = json_decode(file_get_contents($dataFile), true);
        $wards = $data['wards'][$districtCode] ?? [];
        echo json_encode(['success' => true, 'wards' => $wards]);
        exit;
    }

    /**
     * Loại bỏ dấu tiếng Việt
     */
    private function removeVietnameseAccents($str): string
    {
        $accents = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ',
            'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ',
            'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ',
            'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ',
            'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ',
            'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ',
            'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ',
            'Đ'
        ];
        
        $noAccents = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd',
            'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A',
            'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E',
            'I', 'I', 'I', 'I', 'I',
            'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O',
            'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U',
            'Y', 'Y', 'Y', 'Y', 'Y',
            'D'
        ];
        
        return str_replace($accents, $noAccents, $str);
    }
    
    /**
     * Tìm kiếm địa chỉ (hỗ trợ tìm kiếm không dấu và tìm kiếm theo ngữ cảnh)
     */
    public function search(): void
    {
        header('Content-Type: application/json');
        
        $keyword = trim($_GET['keyword'] ?? '');
        if (empty($keyword)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu từ khóa']);
            exit;
        }
        
        // Lấy thông tin ngữ cảnh (đã chọn tỉnh/quận chưa)
        $provinceCode = $_GET['province_code'] ?? null;
        $districtCode = $_GET['district_code'] ?? null;
        
        $dataFile = PATH_ROOT . 'assets/data/vietnam-addresses.json';
        if (!file_exists($dataFile)) {
            echo json_encode(['success' => false, 'message' => 'File dữ liệu không tồn tại']);
            exit;
        }
        
        $data = json_decode(file_get_contents($dataFile), true);
        $results = [];
        
        // Chuẩn hóa từ khóa (chuyển sang chữ thường, loại bỏ dấu)
        $keywordLower = mb_strtolower($keyword, 'UTF-8');
        $keywordNoAccent = mb_strtolower($this->removeVietnameseAccents($keyword), 'UTF-8');
        
        // Tìm kiếm theo ngữ cảnh
        // Nếu đã chọn quận/huyện: chỉ tìm trong phường/xã của quận đó
        if ($districtCode) {
            $wards = $data['wards'][$districtCode] ?? [];
            $districtName = '';
            $provinceName = '';
            $provinceCode = '';
            
            // Lấy thông tin quận và tỉnh
            foreach ($data['districts'] ?? [] as $pc => $districts) {
                foreach ($districts as $d) {
                    if ($d['code'] == $districtCode) {
                        $districtName = $d['name'];
                        $provinceCode = $pc;
                        foreach ($data['provinces'] ?? [] as $p) {
                            if ($p['code'] == $provinceCode) {
                                $provinceName = $p['name'];
                                break;
                            }
                        }
                        break 2;
                    }
                }
            }
            
            // Tìm trong phường/xã
            foreach ($wards as $ward) {
                $wardName = $ward['name'];
                $wardNameLower = mb_strtolower($wardName, 'UTF-8');
                $wardNameNoAccent = mb_strtolower($this->removeVietnameseAccents($wardName), 'UTF-8');
                
                if (stripos($wardName, $keyword) !== false || 
                    stripos($wardNameNoAccent, $keywordNoAccent) !== false ||
                    stripos($wardNameLower, $keywordLower) !== false) {
                    $results[] = [
                        'type' => 'ward',
                        'code' => $ward['code'],
                        'name' => $wardName,
                        'district_code' => $districtCode,
                        'district_name' => $districtName,
                        'province_code' => $provinceCode,
                        'province_name' => $provinceName,
                        'full_name' => $wardName . ', ' . $districtName . ', ' . $provinceName,
                        'relevance' => $this->calculateRelevance($wardName, $keyword, $keywordNoAccent)
                    ];
                }
            }
        }
        // Nếu đã chọn tỉnh nhưng chưa chọn quận: chỉ tìm trong quận/huyện của tỉnh đó
        elseif ($provinceCode) {
            $districts = $data['districts'][$provinceCode] ?? [];
            $provinceName = '';
            
            // Lấy tên tỉnh
            foreach ($data['provinces'] ?? [] as $p) {
                if ($p['code'] == $provinceCode) {
                    $provinceName = $p['name'];
                    break;
                }
            }
            
            // Tìm trong quận/huyện
            foreach ($districts as $district) {
                $districtName = $district['name'];
                $districtNameLower = mb_strtolower($districtName, 'UTF-8');
                $districtNameNoAccent = mb_strtolower($this->removeVietnameseAccents($districtName), 'UTF-8');
                
                if (stripos($districtName, $keyword) !== false || 
                    stripos($districtNameNoAccent, $keywordNoAccent) !== false ||
                    stripos($districtNameLower, $keywordLower) !== false) {
                    $results[] = [
                        'type' => 'district',
                        'code' => $district['code'],
                        'name' => $districtName,
                        'province_code' => $provinceCode,
                        'province_name' => $provinceName,
                        'full_name' => $districtName . ', ' . $provinceName,
                        'relevance' => $this->calculateRelevance($districtName, $keyword, $keywordNoAccent)
                    ];
                }
            }
        }
        // Nếu chưa chọn tỉnh: tìm trong tỉnh
        else {
            // Tìm trong tỉnh
            foreach ($data['provinces'] ?? [] as $province) {
                $provinceName = $province['name'];
                $provinceNameLower = mb_strtolower($provinceName, 'UTF-8');
                $provinceNameNoAccent = mb_strtolower($this->removeVietnameseAccents($provinceName), 'UTF-8');
                
                // Tìm kiếm với dấu và không dấu
                if (stripos($provinceName, $keyword) !== false || 
                    stripos($provinceNameNoAccent, $keywordNoAccent) !== false ||
                    stripos($provinceNameLower, $keywordLower) !== false) {
                    $results[] = [
                        'type' => 'province',
                        'code' => $province['code'],
                        'name' => $provinceName,
                        'full_name' => $provinceName,
                        'relevance' => $this->calculateRelevance($provinceName, $keyword, $keywordNoAccent)
                    ];
                }
            }
        }
        
        // Sắp xếp theo độ liên quan
        usort($results, function($a, $b) {
            // Ưu tiên theo relevance
            if ($a['relevance'] != $b['relevance']) {
                return $b['relevance'] - $a['relevance'];
            }
            // Sau đó ưu tiên tỉnh > quận > xã
            $typeOrder = ['province' => 1, 'district' => 2, 'ward' => 3];
            return ($typeOrder[$a['type']] ?? 99) - ($typeOrder[$b['type']] ?? 99);
        });
        
        echo json_encode(['success' => true, 'results' => array_slice($results, 0, 30)]);
        exit;
    }
    
    /**
     * Tính độ liên quan của kết quả
     */
    private function calculateRelevance($text, $keyword, $keywordNoAccent): int
    {
        $textLower = mb_strtolower($text, 'UTF-8');
        $textNoAccent = mb_strtolower($this->removeVietnameseAccents($text), 'UTF-8');
        $keywordLower = mb_strtolower($keyword, 'UTF-8');
        
        $relevance = 0;
        
        // Khớp chính xác = 100 điểm
        if ($textLower === $keywordLower || $textNoAccent === $keywordNoAccent) {
            $relevance += 100;
        }
        // Bắt đầu bằng từ khóa = 50 điểm
        elseif (mb_substr($textLower, 0, mb_strlen($keywordLower)) === $keywordLower ||
                mb_substr($textNoAccent, 0, mb_strlen($keywordNoAccent)) === $keywordNoAccent) {
            $relevance += 50;
        }
        // Chứa từ khóa = 10 điểm
        else {
            $relevance += 10;
        }
        
        // Ưu tiên kết quả ngắn hơn
        $relevance -= mb_strlen($text) / 10;
        
        return (int)$relevance;
    }
}

