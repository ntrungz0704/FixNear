<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$device = $_GET['device'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$ward = $_GET['ward'] ?? '';
$district = $_GET['district'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$user_lat = $_GET['user_lat'] ?? '';
$user_lng = $_GET['user_lng'] ?? '';
$radius_km = $_GET['radius_km'] ?? '';

$shops = db()->getShops([
    'device' => $device,
    'service_id' => $service_id,
    'ward' => $ward,
    'district' => $district,
    'keyword' => $keyword,
    'user_lat' => $user_lat,
    'user_lng' => $user_lng,
    'radius_km' => $radius_km
]);

$public = array_map(static function (array $shop): array {
    $safe = [
        'id'              => (int) $shop['id'],
        'name'            => $shop['name'],
        'address'         => $shop['address'],
        'district'        => $shop['district'],
        'phone'           => $shop['phone'],
        'opening_hours'   => $shop['opening_hours'],
        'map_url'         => $shop['map_url'],
        'latitude'        => $shop['latitude'],
        'longitude'       => $shop['longitude'],
        'website'         => $shop['website'] ?? '',
        'image'           => $shop['image'],
        'devices'         => $shop['devices'] ?? [],
        'source_verified' => !empty($shop['source_verified']),
        'google_rating_verified'      => !empty($shop['google_rating_verified']),
        'student_discount_verified'   => !empty($shop['student_discount_verified']),
        'service_policy_verified'     => !empty($shop['service_policy_verified']),
    ];
    // Chỉ trả dữ liệu Google khi đã đối soát
    if (!empty($shop['google_rating_verified'])) {
        $safe['google_rating']        = $shop['google_rating'];
        $safe['google_reviews_count'] = $shop['google_reviews_count'];
    }
    // Chỉ trả ưu đãi SV khi đã xác minh
    if (!empty($shop['student_discount_verified'])) {
        $safe['student_discount'] = $shop['student_discount'];
    }
    // Chỉ trả chính sách khi đã xác minh
    if (!empty($shop['service_policy_verified'])) {
        $safe['allows_onsite_watch']          = $shop['allows_onsite_watch'] ?? false;
        $safe['requires_component_signing']   = $shop['requires_component_signing'] ?? false;
    }
    if (isset($shop['distance_km'])) {
        $safe['distance_km'] = $shop['distance_km'];
    }
    return $safe;
}, $shops);

echo json_encode([
    'status' => 'success',
    'total' => count($public),
    'data' => $public
], JSON_UNESCAPED_UNICODE);
