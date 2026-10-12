<?php
$pageTitle = 'Theo Dõi Yêu Cầu Sửa Chữa — FixNear';
require_once __DIR__ . '/../config/db.php';

$request = null;
$lookupError = '';
$accountRequests = [];
$code = strtoupper(trim($_POST['code'] ?? ''));
$phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');

if (isLoggedIn()) {
    $viewer = currentUser();
    foreach (db()->getRepairRequests() as $candidate) {
        $ownedById = !empty($candidate['user_id']) && (int)$candidate['user_id'] === (int)$viewer['id'];
        $ownedByLegacyEmail = empty($candidate['user_id']) && !empty($candidate['customer_email']) && strcasecmp($candidate['customer_email'], $viewer['email']) === 0;
        if ($ownedById || $ownedByLegacyEmail) $accountRequests[] = $candidate;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    if (!enforceRateLimit('track_request', 12, 900)) {
        http_response_code(429);
        $lookupError = 'Bạn đã tra cứu quá nhiều lần. Vui lòng đợi 15 phút rồi thử lại.';
    } elseif (!preg_match('/^FN-[A-Z0-9-]{4,30}$/', $code) || strlen($phone) < 9 || strlen($phone) > 12) {
        $lookupError = 'Mã hồ sơ hoặc số điện thoại chưa đúng định dạng.';
    } else {
        foreach (db()->getRepairRequests() as $candidate) {
            $candidatePhone = preg_replace('/\D+/', '', (string)($candidate['customer_phone'] ?? ''));
            if (hash_equals((string)($candidate['id'] ?? ''), $code) && hash_equals($candidatePhone, $phone)) {
                $request = $candidate;
                break;
            }
        }
        if (!$request) $lookupError = 'Không tìm thấy hồ sơ khớp với mã và số điện thoại này.';
    }
}

$statusLabels = [
    'pending' => 'Chờ tiếp nhận',
    'reviewing' => 'Đang rà soát',
    'matched' => 'Đã ghép nơi sửa',
    'contacted' => 'Đã liên hệ',
    'completed' => 'Đã hoàn tất',
    'cancelled' => 'Đã hủy'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="fn-container" style="max-width:760px;padding:48px 20px 72px;">
    <h1 style="font-family:var(--fn-font-heading);font-size:30px;font-weight:900;color:var(--fn-dark);margin-bottom:8px;">Theo Dõi Yêu Cầu</h1>
    <p style="color:var(--fn-dark-muted);margin-bottom:24px;">Nhập đồng thời mã hồ sơ và số điện thoại đã gửi để bảo vệ thông tin cá nhân.</p>

    <?php if (isLoggedIn()): ?>
        <section style="margin-bottom:24px;background:#fff;border:1px solid var(--fn-border);border-radius:16px;padding:20px;box-shadow:var(--fn-shadow-sm);">
            <h2 style="font-size:18px;color:var(--fn-dark);margin-bottom:12px;">Yêu cầu của tài khoản</h2>
            <?php if (empty($accountRequests)): ?>
                <p style="color:var(--fn-dark-muted);font-size:13px;">Chưa có yêu cầu nào gắn với tài khoản này.</p>
            <?php else: ?>
                <div style="display:grid;gap:10px;">
                    <?php foreach ($accountRequests as $ownedRequest): $ownedStatus = $ownedRequest['status'] ?? 'pending'; ?>
                        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px;border:1px solid var(--fn-border);border-radius:10px;">
                            <div><strong><?= htmlspecialchars($ownedRequest['id']) ?></strong><div style="font-size:12px;color:#64748b;"><?= htmlspecialchars(($ownedRequest['device_type'] ?? '') . ' — ' . ($ownedRequest['brand_model'] ?? '')) ?></div></div>
                            <span style="font-size:13px;font-weight:700;color:#c2410c;"><?= htmlspecialchars($statusLabels[$ownedStatus] ?? $ownedStatus) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <form method="post" action="track_request.php" style="background:#fff;border:1px solid var(--fn-border);border-radius:16px;padding:24px;box-shadow:var(--fn-shadow-sm);">
        <?= csrfField() ?>
        <div class="fn-form-row">
            <div class="fn-form-group">
                <label class="fn-label" for="track-code">Mã hồ sơ</label>
                <input class="fn-input" id="track-code" name="code" value="<?= htmlspecialchars($code) ?>" placeholder="Ví dụ: FN-260927120000-A1B2" autocomplete="off" spellcheck="false" maxlength="33" required>
            </div>
            <div class="fn-form-group">
                <label class="fn-label" for="track-phone">Số điện thoại</label>
                <input class="fn-input" id="track-phone" name="phone" type="tel" inputmode="tel" placeholder="Ví dụ: 0908 123 456" autocomplete="tel" maxlength="20" required>
            </div>
        </div>
        <button class="fn-btn fn-btn-primary" type="submit">Kiểm tra trạng thái</button>
    </form>

    <?php if ($lookupError): ?>
        <div role="alert" aria-live="polite" style="margin-top:20px;background:#fee2e2;color:#991b1b;padding:14px 16px;border-radius:10px;border-left:4px solid #ef4444;"><?= htmlspecialchars($lookupError) ?></div>
    <?php elseif ($request): ?>
        <?php $status = $request['status'] ?? 'pending'; ?>
        <section aria-live="polite" style="margin-top:24px;background:#fff;border:1px solid var(--fn-border);border-radius:16px;padding:24px;box-shadow:var(--fn-shadow-sm);">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
                <h2 style="font-size:20px;color:var(--fn-dark);">Hồ sơ <?= htmlspecialchars($request['id']) ?></h2>
                <strong style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;padding:7px 12px;border-radius:999px;"><?= htmlspecialchars($statusLabels[$status] ?? $status) ?></strong>
            </div>
            <dl style="display:grid;grid-template-columns:minmax(120px,180px) 1fr;gap:10px;margin-top:20px;">
                <dt>Thiết bị</dt><dd><?= htmlspecialchars(($request['device_type'] ?? '') . ' — ' . (($request['brand_model'] ?? '') ?: 'chưa xác định model')) ?></dd>
                <dt>Lỗi mô tả</dt><dd><?= htmlspecialchars($request['issue_type'] ?? '') ?></dd>
                <dt>Khu vực</dt><dd><?= htmlspecialchars($request['district'] ?? '') ?></dd>
                <dt>Giá tham khảo</dt><dd><?= htmlspecialchars($request['estimated_price'] ?? 'Chưa có') ?></dd>
                <dt>Ngày gửi</dt><dd><?= htmlspecialchars($request['created_at'] ?? '') ?></dd>
            </dl>
            <p style="margin-top:18px;color:#64748b;font-size:13px;">Trạng thái chỉ phản ánh cập nhật trong hệ thống FixNear; không thay thế xác nhận trực tiếp từ cửa hàng.</p>
        </section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
