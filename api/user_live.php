<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
$userId = (int)$_SESSION['user_id'];
$requests = array_values(array_filter(db()->getRepairRequests(), static fn($request) => (int)($request['user_id'] ?? 0) === $userId));
$items = array_map(static fn($request) => [
    'id' => (string)($request['id'] ?? ''),
    'status' => (string)($request['status'] ?? 'pending'),
    'updated_at' => (string)($request['updated_at'] ?? $request['created_at'] ?? ''),
], $requests);
echo json_encode(['revision' => hash('sha256', json_encode($items)), 'requests' => $items], JSON_UNESCAPED_UNICODE);
