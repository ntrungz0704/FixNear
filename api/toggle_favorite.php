<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

if (!enforceRateLimit('fav_toggle', 60, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau giây lát.']);
    exit;
}

// Lấy shop_id từ POST hoặc JSON input
$shopId = (int)($_POST['shop_id'] ?? 0);
if ($shopId <= 0) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded['shop_id'])) {
            $shopId = (int)$decoded['shop_id'];
        }
    }
}

if ($shopId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID cửa hàng hợp lệ']);
    exit;
}

// Kiểm tra cửa hàng có tồn tại không
$shop = db()->getShopById($shopId);
if (!$shop) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Cửa hàng không tồn tại']);
    exit;
}

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'message' => 'Đăng nhập để lưu cửa hàng yêu thích.'
    ]);
    exit;
}
if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Phiên đã hết hạn. Tải lại trang rồi thử lại.']);
    exit;
}

// Đã đăng nhập: Lưu vào CSDL / JSON
$result = db()->toggleFavorite((int)$user['id'], $shopId);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật danh sách yêu thích']);
    exit;
}

echo json_encode([
    'success' => true,
    'logged_in' => true,
    'shop_id' => $shopId,
    'favorited' => !empty($result['favorited']),
    'message' => !empty($result['favorited']) ? 'Đã thêm vào danh sách yêu thích' : 'Đã xóa khỏi danh sách yêu thích'
]);
exit;
