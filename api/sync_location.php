<?php
/**
 * FixNear Location Synchronization API
 * Đồng bộ trạng thái vị trí giữa Browser (localStorage) và PHP Session.
 */
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

if (!enforceRateLimit('sync_loc', 60, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Thao tác quá nhanh']);
    exit;
}

// Lấy dữ liệu từ POST form hoặc JSON input
$action = $_POST['action'] ?? '';
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$name = $_POST['name'] ?? null;
$accuracy = $_POST['accuracy'] ?? null;
$source = $_POST['source'] ?? 'gps';

if ($action === '' && ($lat === null || $lng === null)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $action = $decoded['action'] ?? $action;
            $lat = $decoded['lat'] ?? $lat;
            $lng = $decoded['lng'] ?? $lng;
            $name = $decoded['name'] ?? $name;
            $accuracy = $decoded['accuracy'] ?? $accuracy;
            $source = $decoded['source'] ?? $source;
        }
    }
}

// Xử lý Xóa vị trí
if ($action === 'clear') {
    unset($_SESSION['fixnear_location']);
    setcookie('fixnear_lat', '', time() - 3600, '/');
    setcookie('fixnear_lng', '', time() - 3600, '/');
    setcookie('fixnear_loc', '', time() - 3600, '/');

    echo json_encode([
        'success' => true,
        'action' => 'cleared',
        'message' => 'Đã xóa vị trí thành công'
    ]);
    exit;
}

// Kiểm tra tính hợp lệ của tọa độ
if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tọa độ không hợp lệ']);
    exit;
}

$lat = (float)$lat;
$lng = (float)$lng;

if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tọa độ nằm ngoài phạm vi trái đất']);
    exit;
}

$sanitizedName = trim((string)($name ?? 'Vị trí GPS của bạn'));
if ($sanitizedName === '') {
    $sanitizedName = 'Vị trí GPS của bạn';
}
if (mb_strlen($sanitizedName) > 100) {
    $sanitizedName = mb_substr($sanitizedName, 0, 100);
}

$locationState = [
    'lat' => $lat,
    'lng' => $lng,
    'name' => $sanitizedName,
    'accuracy' => is_numeric($accuracy) ? (float)$accuracy : null,
    'timestamp' => time(),
    'source' => in_array($source, ['gps', 'district', 'manual', 'url'], true) ? $source : 'gps',
    'status' => 'granted'
];

// Lưu vào PHP Session
$_SESSION['fixnear_location'] = $locationState;

// Lưu cookie hỗ trợ tương thích ngược (30 ngày)
$cookieExpire = time() + (30 * 86400);
setcookie('fixnear_lat', (string)$lat, [
    'expires' => $cookieExpire,
    'path' => '/',
    'samesite' => 'Lax'
]);
setcookie('fixnear_lng', (string)$lng, [
    'expires' => $cookieExpire,
    'path' => '/',
    'samesite' => 'Lax'
]);
setcookie('fixnear_loc', rawurlencode($sanitizedName), [
    'expires' => $cookieExpire,
    'path' => '/',
    'samesite' => 'Lax'
]);

echo json_encode([
    'success' => true,
    'location' => $locationState,
    'message' => 'Đã lưu và đồng bộ vị trí thành công'
]);
exit;
