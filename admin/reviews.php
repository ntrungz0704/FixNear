<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Xử lý admin phản hồi review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_review') {
    requireValidCsrf();
    $reviewId = (int)$_POST['review_id'];
    $replyText = trim($_POST['reply_text'] ?? '');
    if ($reviewId > 0 && !empty($replyText)) {
        db()->replyReview($reviewId, $replyText);
        header("Location: reviews.php?msg=replied");
        exit;
    }
}

// Ẩn hoặc xóa đánh giá bằng POST có CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['toggle_review', 'delete_review'], true)) {
    requireValidCsrf();
    $reviewId = (int)($_POST['review_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'toggle_review') {
        db()->toggleReviewVisibility($reviewId);
        header("Location: reviews.php?msg=toggled");
        exit;
    } else {
        db()->deleteReview($reviewId);
        header("Location: reviews.php?msg=deleted");
        exit;
    }
}

$reviews = db()->getAllReviews();
$filterStatus = $_GET['filter'] ?? 'all';
if ($filterStatus === 'pending') {
    $reviews = array_filter($reviews, fn($r) => !empty($r['is_hidden']));
} elseif ($filterStatus === 'approved') {
    $reviews = array_filter($reviews, fn($r) => empty($r['is_hidden']));
}

$pageTitle = "Quản Lý Đánh Giá — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>


    <div class="fn-admin-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                    Kiểm Duyệt & Quản Lý Đánh Giá Người Dùng
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                    Đánh giá mới gửi cần được Ban Quản Trị duyệt trước khi hiển thị công khai trên trang chi tiết cửa hàng.
                </p>
            </div>

            <!-- Bộ lọc trạng thái đánh giá -->
            <div style="display: flex; gap: 6px;">
                <a href="reviews.php?filter=all" class="fn-btn fn-btn-sm <?= $filterStatus === 'all' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>" style="font-size: 12px; padding: 6px 12px;">Tất cả</a>
                <a href="reviews.php?filter=pending" class="fn-btn fn-btn-sm <?= $filterStatus === 'pending' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>" style="font-size: 12px; padding: 6px 12px;">⏳ Chờ duyệt (Ẩn)</a>
                <a href="reviews.php?filter=approved" class="fn-btn fn-btn-sm <?= $filterStatus === 'approved' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>" style="font-size: 12px; padding: 6px 12px;">✓ Đang hiển thị</a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'toggled'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ✓ Đã cập nhật trạng thái hiển thị của đánh giá!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ✓ Đã xóa vĩnh viễn đánh giá khỏi hệ thống!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'replied'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                🛡️ Đã cập nhật phản hồi chính thức từ Ban Quản Trị thành công!
            </div>
        <?php endif; ?>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto; box-shadow: var(--fn-shadow-sm);">
            <table class="fn-price-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Người Đánh Giá</th>
                        <th>Cửa Hàng</th>
                        <th>Sao</th>
                        <th>Nội Dung Đánh Giá</th>
                        <th>Thời Gian</th>
                        <th>Trạng Thái</th>
                        <th style="text-align: right;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color: var(--fn-text-light);">Chưa có đánh giá nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td>#<?= $r['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['user_name']) ?></strong>
                                    <?php if (!empty($r['device_name'])): ?>
                                        <div style="font-size: 11px; color: var(--fn-text-light);">
                                            <?= htmlspecialchars($r['device_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['shop_id'])): ?>
                                        <a href="../shop_detail.php?id=<?= (int)$r['shop_id'] ?>" target="_blank" style="font-weight: 700; color: var(--fn-primary); text-decoration: none;">
                                            <?= htmlspecialchars($r['shop_name']) ?> ↗
                                        </a>
                                    <?php else: ?>
                                        <span style="font-weight: 700; color: var(--fn-primary);"><?= htmlspecialchars($r['shop_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: var(--fn-gold); font-weight: 800; white-space: nowrap;">
                                        <?= str_repeat('⭐', $r['rating']) ?>
                                    </span>
                                </td>
                                <td style="max-width: 320px; font-size: 13px; text-align: left;">
                                    <div style="color: #0f172a; line-height: 1.5;">
                                        <?= htmlspecialchars($r['comment']) ?>
                                    </div>
                                    <?php if (!empty($r['admin_reply'])): ?>
                                        <div style="margin-top: 8px; padding: 8px 10px; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 6px; font-size: 12px; color: #166534; text-align: left;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                                                <strong>🛡️ Phản hồi từ BQT FixNear:</strong>
                                                <span style="font-size: 10.5px; color: #64748b;"><?= !empty($r['admin_reply_at']) ? date('d/m/Y H:i', strtotime($r['admin_reply_at'])) : '' ?></span>
                                            </div>
                                            <?= htmlspecialchars($r['admin_reply']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Form trả lời nhanh của Admin -->
                                    <details style="margin-top: 8px;">
                                        <summary style="font-size: 11.5px; color: #2563eb; cursor: pointer; font-weight: 700;">
                                            💬 <?= !empty($r['admin_reply']) ? 'Sửa phản hồi BQT' : '+ Viết phản hồi BQT' ?>
                                        </summary>
                                        <form action="reviews.php" method="POST" style="margin-top: 6px;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="reply_review">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <textarea name="reply_text" rows="2" class="fn-textarea" style="font-size: 12px; padding: 6px 8px;" placeholder="Nhập câu trả lời chính thức của Ban Quản Trị..." required><?= htmlspecialchars($r['admin_reply'] ?? '') ?></textarea>
                                            <button type="submit" class="fn-btn fn-btn-primary fn-btn-sm" style="margin-top: 4px; font-size: 11px; padding: 4px 10px;">
                                                Lưu phản hồi công khai
                                            </button>
                                        </form>
                                    </details>
                                </td>
                                <td style="font-size: 12px; color: var(--fn-text-light); white-space: nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['is_hidden'])): ?>
                                        <span style="font-size: 11px; padding: 3px 8px; background: #fef3c7; color: #92400e; border-radius: 4px; font-weight: bold; white-space: nowrap;">
                                            ⏳ Chờ duyệt
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 11px; padding: 3px 8px; background: #dcfce7; color: #166534; border-radius: 4px; font-weight: bold; white-space: nowrap;">
                                            ✓ Hiển thị
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <form method="post" action="reviews.php" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_review">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <?php if (!empty($r['is_hidden'])): ?>
                                                <button type="submit" class="fn-btn fn-btn-sm" style="padding: 4px 10px; font-size: 11.5px; background: #16a34a; color: #ffffff; border-radius: 6px; font-weight: 700;">
                                                    ✓ Duyệt
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 4px 10px; font-size: 11.5px;">
                                                    Ẩn
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="post" action="reviews.php" onsubmit="return confirm('Xóa vĩnh viễn đánh giá này?')" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_review">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 4px 10px; font-size: 11.5px; color: #ef4444;">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
