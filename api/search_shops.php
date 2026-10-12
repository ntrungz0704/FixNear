<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/shop_search.php';

$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
$district = mb_substr(trim((string)($_GET['district'] ?? '')), 0, 100);
$ward = mb_substr(trim((string)($_GET['ward'] ?? '')), 0, 100);
$device = mb_substr(trim((string)($_GET['device'] ?? '')), 0, 40);
$service_id = filter_var($_GET['service_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: '';
$brand = mb_substr(trim((string)($_GET['brand'] ?? '')), 0, 80);
$model = mb_substr(trim((string)($_GET['model'] ?? '')), 0, 120);
$radius_km = max(0, min(100, floatval($_GET['radius_km'] ?? 0)));
$user_lat = floatval($_GET['user_lat'] ?? 0);
$user_lng = floatval($_GET['user_lng'] ?? 0);
if ($user_lat < -90 || $user_lat > 90 || $user_lng < -180 || $user_lng > 180) {
    $user_lat = 0;
    $user_lng = 0;
}
$sort = in_array($_GET['sort'] ?? 'name', ['name', 'rating', 'distance'], true) ? ($_GET['sort'] ?? 'name') : 'name';

$filters = [];
if (!empty($district)) $filters['district'] = $district;
if (!empty($ward)) $filters['ward'] = $ward;
if (!empty($device)) $filters['device'] = $device;
if (!empty($service_id)) $filters['service_id'] = $service_id;
if (!empty($radius_km)) {
    $filters['radius_km'] = $radius_km;
    $filters['strict_radius'] = true;
}
if ($q !== '') $filters['keyword'] = $q;
if ($user_lat && $user_lng) {
    $filters['user_lat'] = $user_lat;
    $filters['user_lng'] = $user_lng;
}

$shops = db()->getShops($filters);

fixnearSortSearchShops($shops, $sort, $user_lat !== 0.0 && $user_lng !== 0.0);

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
