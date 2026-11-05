<?php
// =============================================
// FILE: api/update.php
// MÔ TẢ: API cập nhật task (sửa title hoặc đổi status)
// PHƯƠNG THỨC: POST
// FRONTEND GỬI: { "id": 1, "title": "Nội dung mới" }
//           hoặc { "id": 1, "status": "completed" }
// TÁC GIẢ: Dev 4
// =============================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận phương thức POST']);
    exit;
}

require_once __DIR__ . '/config.php';

// ===== BƯỚC 1: Đọc dữ liệu từ Frontend =====
$data = json_decode(file_get_contents('php://input'), true);

// ===== BƯỚC 2: Validate dữ liệu =====
// Kiểm tra: bắt buộc phải có trường 'id'
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID task không hợp lệ.']);
    exit;
}

$id = (int)$data['id'];

// Kiểm tra: phải có ít nhất 1 trường cần cập nhật (title hoặc status)
if (!isset($data['title']) && !isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Vui lòng cung cấp title hoặc status để cập nhật.'
    ]);
    exit;
}

// ===== BƯỚC 3: Xây dựng câu SQL động =====
// "Động" nghĩa là: câu SQL thay đổi tùy theo dữ liệu Frontend gửi lên
// Nếu gửi title → UPDATE title
// Nếu gửi status → UPDATE status
// Nếu gửi cả hai → UPDATE cả hai

$fields = [];      // Mảng chứa các cặp "tên_cột = :placeholder"
$params = [];      // Mảng chứa giá trị tương ứng
$params['id'] = $id;

// Nếu Frontend gửi title → thêm vào danh sách cần update
if (isset($data['title'])) {
    $title = trim($data['title']);
    if ($title === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nội dung task không được rỗng.']);
        exit;
    }
    if (mb_strlen($title) > 255) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nội dung task không được quá 255 ký tự.']);
        exit;
    }
    $fields[] = "title = :title";
    $params['title'] = $title;
}

// Nếu Frontend gửi status → thêm vào danh sách cần update
if (isset($data['status'])) {
    // Chỉ chấp nhận 2 giá trị: 'pending' hoặc 'completed'
    if (!in_array($data['status'], ['pending', 'completed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Status chỉ được là pending hoặc completed.']);
        exit;
    }
    $fields[] = "status = :status";
    $params['status'] = $data['status'];
}

// ===== BƯỚC 4: Thực thi UPDATE =====
try {
    // Ghép câu SQL: UPDATE tasks SET title = :title, status = :status WHERE id = :id
    // implode(', ', $fields): Nối các phần tử mảng bằng dấu ", "
    $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // rowCount(): Đếm số dòng bị ảnh hưởng bởi câu SQL
    // Nếu = 0 → Không tìm thấy task với id đó
    if ($stmt->rowCount() === 0) {
        http_response_code(404); // 404 = Not Found
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy task với ID: ' . $id]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật task thành công!'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi cập nhật task: ' . $e->getMessage()
    ]);
}
?>