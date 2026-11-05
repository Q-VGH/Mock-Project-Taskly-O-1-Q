<?php
// =============================================
// FILE: api/read.php
// MÔ TẢ: API đọc tất cả task từ database
// PHƯƠNG THỨC: GET
// TÁC GIẢ: Dev 4
// =============================================

// ===== BƯỚC 1: Thiết lập HTTP Headers =====
// Content-Type: Báo cho trình duyệt biết "response trả về là JSON"
header('Content-Type: application/json; charset=utf-8');

// CORS Headers: Cho phép Frontend từ domain khác gọi API này
// (Trong giai đoạn dev, cho phép tất cả bằng *)
header('Access-Control-Allow-Origin: *');

// ===== BƯỚC 2: Kết nối Database =====
// require_once: Load file config.php (chứa biến $pdo)
// __DIR__: Đường dẫn thư mục hiện tại (thư mục api/)
// Nếu file config.php không tồn tại → dừng chương trình với lỗi
require_once __DIR__ . '/config.php';

// ===== BƯỚC 3: Đọc dữ liệu từ Database =====
try {
    // Viết câu lệnh SQL: Lấy tất cả task, sắp xếp mới nhất trước
    $sql = "SELECT id, title, status, created_at FROM tasks ORDER BY created_at DESC";
    
    // Chuẩn bị (prepare) câu lệnh SQL
    // prepare() tạo ra một "khuôn" câu SQL (prepared statement)
    $stmt = $pdo->prepare($sql);
    
    // Thực thi (execute) câu lệnh
    // execute() chạy câu SQL trên database
    $stmt->execute();
    
    // Lấy tất cả kết quả thành mảng PHP
    // fetchAll() trả về mảng chứa tất cả các dòng kết quả
    // Mỗi dòng là 1 mảng: ['id' => 1, 'title' => 'Học bài', ...]
    $tasks = $stmt->fetchAll();
    
    // ===== BƯỚC 4: Trả kết quả về cho Frontend =====
    // json_encode(): Chuyển mảng PHP → chuỗi JSON (để JS đọc được)
    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
    // Kết quả trả về sẽ giống:
    // {
    //   "success": true,
    //   "tasks": [
    //     {"id": 1, "title": "Học bài", "status": "pending", "created_at": "..."},
    //     {"id": 2, "title": "Đi chợ", "status": "completed", "created_at": "..."}
    //   ]
    // }
    
} catch (PDOException $e) {
    // Nếu có lỗi SQL → trả về thông báo lỗi
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi đọc dữ liệu: ' . $e->getMessage()
    ]);
}
?>