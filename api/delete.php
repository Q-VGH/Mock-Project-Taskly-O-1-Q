<?php
// =============================================
// FILE: api/delete.php
// MÔ TẢ: API xóa task khỏi database
// PHƯƠNG THỨC: POST
// FRONTEND GỬI: { "id": 1 }
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

// ===== BƯỚC 2: Validate =====
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID task không hợp lệ.']);
    exit;
}

$id = (int)$data['id'];

// ===== BƯỚC 3: Xóa task khỏi Database =====
try {
    // Câu SQL: Xóa dòng có id khớp
    // ⚠️ LUÔN CÓ WHERE! Nếu quên WHERE → XÓA HẾT BẢNG!
    $sql = "DELETE FROM tasks WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    // Kiểm tra: có dòng nào bị xóa không?
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy task với ID: ' . $id]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa task thành công!'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xóa task: ' . $e->getMessage()
    ]);
}
?>