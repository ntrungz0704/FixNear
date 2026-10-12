<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    if (!enforceRateLimit('report', 6, 3600)) {
        http_response_code(429);
        exit('Bạn đã gửi quá nhiều báo cáo. Vui lòng thử lại sau.');
    }
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'Khác');
    $details = trim($_POST['details'] ?? '');
    $reporter_phone = trim($_POST['reporter_phone'] ?? '');
    $redirect_url = safeLocalRedirect($_POST['redirect_url'] ?? '../index.php', '../index.php');

    if ($shop_id > 0 && !empty($details) && mb_strlen($details) <= 2000 && mb_strlen($reason) <= 120) {
        $user = currentUser();
        $shop = db()->getShopById($shop_id);

        $priority = 'medium';
        if (stripos($reason, 'giá') !== false) {
            $priority = 'high';
        } elseif (stripos($reason, 'ngừng hoạt động') !== false || stripos($reason, 'địa chỉ') !== false) {
            $priority = 'high';
        } elseif (stripos($reason, 'khác') !== false) {
            $priority = 'low';
        }

        db()->addReport([
            'shop_id' => $shop_id,
            'shop_name' => $shop ? $shop['name'] : "Tiệm #$shop_id",
            'user_id' => $user ? $user['id'] : null,
            'user_name' => $user ? $user['name'] : 'Khách vãng lai',
            'reporter_phone' => !empty($reporter_phone) ? $reporter_phone : ($user['phone'] ?? null),
            'reason' => $reason,
            'priority' => $priority,
            'details' => $details
        ]);

        $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
        header("Location: {$redirect_url}{$sep}msg=report_added");
        exit;
    }
}

header("Location: ../index.php");
exit;
