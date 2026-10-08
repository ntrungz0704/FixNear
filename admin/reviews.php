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
    } else {
        db()->deleteReview($reviewId);
    }
    header("Location: reviews.php");
    exit;
}

$reviews = db()->getAllReviews();

$pageTitle = "Quản Lý Đánh Giá — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>


    <div class="fn-admin-content">
        <div style="margin-bottom: 24px;">
            <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                Kiểm Duyệt & Quản Lý Đánh Giá Người Dùng
            </h1>
            <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                Đảm bảo các nhận xét mang tính xây dựng, trung thực, loại bỏ ngôn từ tục tĩu hoặc spam ảo.
            </p>
        </div>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto;">
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
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Chưa có đánh giá nào.</td></tr>
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
                                    <span style="font-weight: 700; color: var(--fn-primary);">
                                        <?= htmlspecialchars($r['shop_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--fn-gold); font-weight: 800;">
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
                                <td style="font-size: 12px; color: var(--fn-text-light);">
                                    <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['is_hidden'])): ?>
                                        <span style="font-size: 11px; padding: 2px 6px; background: #fee2e2; color: #991b1b; border-radius: 4px; font-weight: bold;">
                                            Đang ẩn
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 11px; padding: 2px 6px; background: #dcfce7; color: #166534; border-radius: 4px; font-weight: bold;">
                                            Hiển thị
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <form method="post" action="reviews.php" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_review">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 4px 8px; font-size: 11.5px;"><?= !empty($r['is_hidden']) ? 'Hiện' : 'Ẩn' ?></button>
                                        </form>
                                        <form method="post" action="reviews.php" onsubmit="return confirm('Xóa vĩnh viễn đánh giá này?')" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_review">
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444;">Xóa</button>
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
