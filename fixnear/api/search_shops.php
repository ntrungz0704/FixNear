<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
$district = mb_substr(trim((string)($_GET['district'] ?? '')), 0, 100);
$device = mb_substr(trim((string)($_GET['device'] ?? '')), 0, 40);
$service_id = filter_var($_GET['service_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: '';
$brand = mb_substr(trim((string)($_GET['brand'] ?? '')), 0, 80);
$model = mb_substr(trim((string)($_GET['model'] ?? '')), 0, 120);
$radius_km = floatval($_GET['radius_km'] ?? 0);
$user_lat = floatval($_GET['user_lat'] ?? 0);
$user_lng = floatval($_GET['user_lng'] ?? 0);
if ($user_lat < -90 || $user_lat > 90 || $user_lng < -180 || $user_lng > 180) {
    $user_lat = 0;
    $user_lng = 0;
}
$sort = trim($_GET['sort'] ?? 'name'); // rating (verified only), distance, name

$filters = [];
if (!empty($district)) $filters['district'] = $district;
if (!empty($device)) $filters['device'] = $device;
if (!empty($service_id)) $filters['service_id'] = $service_id;
if (!empty($radius_km)) $filters['radius_km'] = $radius_km;
if ($user_lat && $user_lng) {
    $filters['user_lat'] = $user_lat;
    $filters['user_lng'] = $user_lng;
}

$shops = db()->getShops($filters);

// Text search filter
if (!empty($q)) {
    $qLower = mb_strtolower($q, 'UTF-8');
    $shops = array_filter($shops, function($shop) use ($qLower) {
        return mb_strpos(mb_strtolower($shop['name'], 'UTF-8'), $qLower) !== false ||
               mb_strpos(mb_strtolower($shop['address'], 'UTF-8'), $qLower) !== false ||
               mb_strpos(mb_strtolower($shop['district'], 'UTF-8'), $qLower) !== false;
    });
    $shops = array_values($shops);
}

// Chỉ tính điểm khi rating có Place ID và thời điểm đối soát.
function getShopTrustScore($s) {
    if (empty($s['google_rating_verified'])) return 0;
    $r = floatval($s['google_rating'] ?? 4.5);
    $c = intval($s['google_reviews_count'] ?? 10);
    // Điểm sắp xếp nội bộ chỉ áp dụng cho rating đã có bằng chứng đối soát.
    return $r * log10($c + 10);
}

// Không dùng rating seed chưa có bằng chứng để xếp hạng cửa hàng.
if ($sort === 'rating') {
    usort($shops, function($a, $b) {
        $scoreA = getShopTrustScore($a);
        $scoreB = getShopTrustScore($b);
        if (abs($scoreA - $scoreB) > 0.05) {
            return $scoreB <=> $scoreA;
        }
        // Nếu điểm bằng nhau, ưu tiên cự ly gần hơn.
        $distA = floatval($a['distance_km'] ?? 999);
        $distB = floatval($b['distance_km'] ?? 999);
        return $distA <=> $distB;
    });
} elseif ($sort === 'distance') {
    usort($shops, function($a, $b) {
        $distA = floatval($a['distance_km'] ?? 999);
        $distB = floatval($b['distance_km'] ?? 999);
        if (abs($distA - $distB) > 0.1) {
            return $distA <=> $distB; // Gần nhất lên đầu
        }
        return getShopTrustScore($b) <=> getShopTrustScore($a);
    });
} elseif ($sort === 'name') {
    usort($shops, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
}

// Get unique districts sorted A-Z
$allShops = db()->getShops([]);
$districts = array_unique(array_column($allShops, 'district'));
sort($districts, SORT_LOCALE_STRING);

echo json_encode([
    'success' => true,
    'shops' => array_values($shops),
    'total' => count($shops),
    'districts' => array_values($districts)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
