<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$requests = db()->getRepairRequests();
$reviews = db()->getAllReviews();
$pendingRequests = count(array_filter($requests, static fn($item) => in_array($item['status'] ?? 'pending', ['pending', 'reviewing'], true)));
$pendingReviews = count(array_filter($reviews, static fn($item) => !empty($item['is_hidden']) && ($item['origin'] ?? '') === 'user_submission'));
$revision = hash('sha256', json_encode([
    array_map(static fn($item) => [$item['id'] ?? '', $item['status'] ?? ''], $requests),
    array_map(static fn($item) => [$item['id'] ?? '', $item['is_hidden'] ?? '', $item['admin_reply_at'] ?? ''], $reviews),
]));
echo json_encode([
    'revision' => $revision,
    'pending_requests' => $pendingRequests,
    'pending_reviews' => $pendingReviews,
    'checked_at' => date('c'),
], JSON_UNESCAPED_UNICODE);
