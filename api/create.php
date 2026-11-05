<?php
// =============================================
// FILE: api/create.php
// MÔ TẢ: API thêm task mới vào database
// PHƯƠNG THỨC: POST
// FRONTEND GỬI: { "title": "Nội dung task" }
// TÁC GIẢ: Dev 4
// =============================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Xử lý preflight request (OPTIONS)
// Trình duyệt gửi OPTIONS trước khi gửi POST thật (để kiểm tra CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 = Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận phương thức POST'
    ]);
    exit;
}

require_once __DIR__ . '/config.php';

// ===== BƯỚC 1: Đọc dữ liệu JSON từ Frontend gửi lên =====
// php://input: Đọc dữ liệu "thô" (raw) từ body của HTTP request
// Khi Frontend gửi: fetch(url, { body: JSON.stringify({title: "Học bài"}) })
// PHP nhận được: '{"title":"Học bài"}' (chuỗi JSON)
$rawData = file_get_contents('php://input');

// json_decode(): Chuyển chuỗi JSON → object PHP
// true: Trả về dạng mảng (associative array) thay vì object
$data = json_decode($rawData, true);

// ===== BƯỚC 2: Validate (Kiểm tra) dữ liệu =====
// Kiểm tra: có trường 'title' không? Có rỗng không?
if (!isset($data['title']) || trim($data['title']) === '') {
    http_response_code(400); // 400 = Bad Request (dữ liệu không hợp lệ)
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập nội dung task.'
    ]);
    exit;
}

// Lấy giá trị title và bỏ khoảng trắng đầu/cuối
$title = trim($data['title']);

// Kiểm tra độ dài (tối đa 255 ký tự - khớp với VARCHAR(255) trong DB)
if (mb_strlen($title) > 255) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nội dung task không được quá 255 ký tự.'
    ]);
    exit;
}

// ===== BƯỚC 3: Thêm vào Database =====
try {
    // Câu SQL: Thêm 1 dòng vào bảng tasks
    // :title là placeholder (chỗ trống) - sẽ được điền giá trị ở execute()
    $sql = "INSERT INTO tasks (title) VALUES (:title)";
    
    // Chuẩn bị câu SQL (prepared statement)
    $stmt = $pdo->prepare($sql);
    
    // Thực thi: Điền giá trị thật vào placeholder
    // PDO sẽ tự động escape ký tự đặc biệt → CHỐNG SQL INJECTION
    $stmt->execute(['title' => $title]);
    
    // Lấy ID của dòng vừa thêm
    // lastInsertId(): Trả về id tự động tăng (AUTO_INCREMENT) của dòng mới
    $newId = $pdo->lastInsertId();
    
    // ===== BƯỚC 4: Trả kết quả thành công =====
    http_response_code(201); // 201 = Created (tạo mới thành công)
    echo json_encode([
        'success' => true,
        'message' => 'Thêm task thành công!',
        'task' => [
            'id' => (int)$newId,      // Ép kiểu thành số nguyên
            'title' => $title,
            'status' => 'pending'     // Mặc định là pending
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi thêm task: ' . $e->getMessage()
    ]);
}
?>