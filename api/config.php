<?php
// =============================================
// FILE: api/config.php
// MÔ TẢ: Kết nối đến MySQL Database bằng PDO
// TÁC GIẢ: Dev 3
// =============================================

// ===== THÔNG TIN KẾT NỐI DATABASE =====
// Các biến này chứa thông tin để PHP tìm đến đúng database

$host = 'localhost';      // Địa chỉ server MySQL (localhost = máy mình)
$dbname = 'todo_db';      // Tên database (đã tạo ở bước 1.3)
$username = 'root';       // Tên đăng nhập MySQL (Laragon mặc định là root)
$password = '';            // Mật khẩu MySQL (Laragon mặc định là rỗng)
$charset = 'utf8mb4';     // Bộ ký tự (hỗ trợ tiếng Việt + emoji)

// ===== TẠO CHUỖI KẾT NỐI (DSN - Data Source Name) =====
// DSN = "công thức" để PDO biết kết nối đến đâu
// Giống địa chỉ nhà: "số nhà:tên đường:thành phố"
$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

// ===== CẤU HÌNH PDO =====
// Các tùy chọn để PDO hoạt động an toàn và đúng cách
$options = [
    // Khi có lỗi → NÉM EXCEPTION (để try/catch bắt được)
    // Nếu không set → lỗi sẽ im lặng, rất khó debug
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    
    // Kết quả trả về dưới dạng MẢNG KẾT HỢP (associative array)
    // VD: $row['title'] thay vì $row[1]
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Tắt emulate prepared statements → Dùng prepared statement THẬT
    // (An toàn hơn, chống SQL Injection tốt hơn)
    PDO::ATTR_EMULATE_PREPARES => false,
];

// ===== KẾT NỐI =====
// Dùng try/catch để bắt lỗi nếu kết nối thất bại
try {
    // Tạo đối tượng PDO (PHP Data Objects) để kết nối MySQL
    // Giống như: "Mở cửa kho lạnh bằng chìa khóa"
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Nếu code chạy đến đây → kết nối thành công!
    // Biến $pdo giờ đại diện cho kết nối đến database
    // Các file khác sẽ dùng $pdo để chạy câu lệnh SQL
    
} catch (PDOException $e) {
    // Nếu kết nối thất bại → hiện lỗi
    // PDOException: Loại lỗi đặc biệt của PDO
    
    // TRONG MÔI TRƯỜNG PRODUCTION (thực tế): KHÔNG hiện chi tiết lỗi cho user
    // Ở đây mình đang học nên hiện ra để debug
    
    // Trả về JSON để Frontend JS có thể đọc được
    http_response_code(500); // 500 = Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối Database: ' . $e->getMessage()
    ]);
    exit; // Dừng chương trình, không chạy tiếp
}
?>