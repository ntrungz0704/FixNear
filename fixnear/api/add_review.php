<?php
require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    if (!enforceRateLimit('review', 5, 3600)) {
        http_response_code(429);
        exit('Bạn đã gửi quá nhiều đánh giá. Vui lòng thử lại sau.');
    }
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $device_name = trim($_POST['device_name'] ?? '');
    $service_repaired = trim($_POST['service_repaired'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($shop_id > 0 && !empty($comment) && mb_strlen($comment) <= 2000 && mb_strlen($device_name) <= 100 && mb_strlen($service_repaired) <= 150 && db()->getShopById($shop_id)) {
        $user = currentUser();
        db()->addReview([
            'shop_id' => $shop_id,
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'rating' => max(1, min(5, $rating)),
            'device_name' => $device_name,
            'service_repaired' => $service_repaired,
            'comment' => $comment
        ]);
        header("Location: ../shop_detail.php?id={$shop_id}&msg=review_added");
        exit;
    }
}

header("Location: ../index.php");
exit;
