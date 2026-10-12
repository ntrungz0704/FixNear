<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $shopId = (int)($_POST['shop_id'] ?? 0);
    $replyText = trim($_POST['reply_text'] ?? '');

    if ($reviewId > 0 && !empty($replyText)) {
        db()->replyReview($reviewId, $replyText);
        $redirect = !empty($shopId) ? "../shop_detail.php?id={$shopId}&msg=reply_added" : "../admin/reviews.php?msg=replied";
        header("Location: {$redirect}");
        exit;
    }
}

header("Location: ../index.php");
exit;
