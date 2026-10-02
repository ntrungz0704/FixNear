<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/index.php");
    exit;
}

$stats = db()->getStats();
$shops = db()->getShops();
$reviews = db()->getAllReviews();
$reports = db()->getReports();

// Xử lý nhanh trạng thái báo cáo nếu có POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireValidCsrf();
    if ($_POST['action'] === 'resolve_report') {
        db()->updateReportStatus((int)$_POST['report_id'], 'resolved');
        header("Location: index.php");
        exit;
    } elseif ($_POST['action'] === 'toggle_review') {
        db()->toggleReviewVisibility((int)$_POST['review_id']);
        header("Location: index.php");
        exit;
    }
}

$pageTitle = "Bảng Điều Khiển Quản Trị — FixNear";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị -->
    <div class="fn-admin-sidebar">
        <div style="font-size: 11px; font-weight: 800; color: var(--fn-text-light); text-transform: uppercase; padding: 0 12px 8px;">
            Quản Trị Hệ Thống
        </div>
        <a href="index.php" class="fn-admin-menu-item active">
            📊 Bảng thống kê
        </a>
        <a href="requests.php" class="fn-admin-menu-item">
            📋 Yêu cầu báo giá (<?= $stats['total_requests'] ?? 0 ?>)
        </a>
        <a href="shops.php" class="fn-admin-menu-item">
            🏪 Quản lý cửa hàng (<?= $stats['total_shops'] ?>)
        </a>
        <a href="services.php" class="fn-admin-menu-item">
            🏷️ Dịch vụ & Bảng giá (<?= $stats['total_services'] ?>)
        </a>
        <a href="reviews.php" class="fn-admin-menu-item">
            ⭐ Quản lý đánh giá (<?= $stats['total_reviews'] ?>)
        </a>
        <a href="reports.php" class="fn-admin-menu-item">
            🚩 Báo cáo sai sót (<?= $stats['pending_reports'] ?>)
        </a>
        <a href="contacts.php" class="fn-admin-menu-item">
            💬 Tin nhắn liên hệ (<?= $stats['total_contacts'] ?? 0 ?>)
        </a>
        
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--fn-border);">
            <a href="../index.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="width: 100%;">
                &larr; Xem giao diện web
            </a>
        </div>
    </div>

    <!-- Nội dung chính -->
    <div class="fn-admin-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark); margin: 0;">
                        Bảng Điều Khiển FixNear
                    </h1>
                    <?php if (db()->isUsingMySQL()): ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #dcfce7; color: #166534; border: 1px solid #86efac;">
                            🟢 MySQL PDO (fixnear_db)
                        </span>
                    <?php else: ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                            🟡 Dữ liệu Dự Phòng JSON (Bật MySQL XAMPP để nạp SQL)
                        </span>
                    <?php endif; ?>
                </div>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); margin-top: 4px;">
                    Hệ thống hiện lưu <?= (int)$stats['total_shops'] ?> bản ghi cửa hàng thuộc <?= (int)$stats['total_districts'] ?> khu vực TP.HCM.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="../install.php" class="fn-btn fn-btn-secondary fn-btn-sm" title="Khởi tạo hoặc kiểm tra CSDL MySQL">
                    ⚙️ Quản Lý CSDL MySQL
                </a>
                <a href="shop_edit.php" class="fn-btn fn-btn-primary fn-btn-sm">
                    + Thêm Cửa Hàng Mới
                </a>
            </div>
        </div>

        <!-- 4 Thẻ KPI thống kê -->
        <div class="fn-admin-stats-grid" style="margin-bottom: 30px;">
            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--fn-text-light); text-transform: uppercase;">Tổng Cửa Hàng</div>
                <div style="font-size: 28px; font-weight: 900; color: var(--fn-primary); margin-top: 6px;"><?= $stats['total_shops'] ?></div>
                <div style="font-size: 11.5px; color: #16a34a; margin-top: 4px;">68 tiệm / 17 quận TP.HCM</div>
            </div>

            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--fn-text-light); text-transform: uppercase;">Lỗi & Dịch Vụ</div>
                <div style="font-size: 28px; font-weight: 900; color: var(--fn-secondary); margin-top: 6px;"><?= $stats['total_services'] ?></div>
                <div style="font-size: 11.5px; color: var(--fn-text-light); margin-top: 4px;">10 Laptop • 9 Điện thoại</div>
            </div>

            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--fn-text-light); text-transform: uppercase;">Đánh Giá Thành Viên</div>
                <div style="font-size: 28px; font-weight: 900; color: var(--fn-gold); margin-top: 6px;"><?= $stats['total_reviews'] ?></div>
                <div style="font-size: 11.5px; color: var(--fn-text-light); margin-top: 4px;">Bản ghi đánh giá trong hệ thống</div>
            </div>

            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--fn-text-light); text-transform: uppercase;">Báo Cáo Cần Xử Lý</div>
                <div style="font-size: 28px; font-weight: 900; color: <?= $stats['pending_reports'] > 0 ? '#ef4444' : '#10b981' ?>; margin-top: 6px;"><?= $stats['pending_reports'] ?></div>
                <div style="font-size: 11.5px; color: var(--fn-text-light); margin-top: 4px;">Cập nhật thông tin sai lệch</div>
            </div>
        </div>

        <div class="fn-admin-two-col-grid">
            <!-- Báo cáo mới nhất từ người dùng -->
            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px;">
                <h3 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 800; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span>🚩 Báo Cáo Thông Tin Sai Mới Nhất</span>
                    <a href="reports.php" style="font-size: 12px; color: var(--fn-primary);">Xem tất cả &rarr;</a>
                </h3>

                <?php if (empty($reports)): ?>
                    <p style="font-size: 13px; color: var(--fn-text-light);">Chưa có báo cáo sai sót nào.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach (array_slice($reports, 0, 3) as $rp): ?>
                            <div style="padding: 12px; border: 1px solid var(--fn-border); border-radius: 8px; background: var(--fn-bg);">
                                <div style="display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 700;">
                                    <span style="color: var(--fn-dark);"><?= htmlspecialchars($rp['shop_name'] ?? 'Cửa hàng') ?></span>
                                    <span style="color: <?= ($rp['status'] ?? '') === 'resolved' ? '#10b981' : '#f59e0b' ?>;">
                                        <?= ($rp['status'] ?? '') === 'resolved' ? '✓ Đã xử lý' : '⏳ Chờ kiểm tra' ?>
                                    </span>
                                </div>
                                <div style="font-size: 12px; color: #ef4444; font-weight: 600; margin-top: 2px;">
                                    Lý do: <?= htmlspecialchars($rp['reason']) ?>
                                </div>
                                <p style="font-size: 12px; color: var(--fn-dark-muted); margin-top: 4px;">
                                    <?= htmlspecialchars($rp['details']) ?>
                                </p>
                                <?php if (($rp['status'] ?? '') === 'pending'): ?>
                                    <form action="index.php" method="POST" style="margin-top: 8px;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="resolve_report">
                                        <input type="hidden" name="report_id" value="<?= $rp['id'] ?>">
                                        <button type="submit" class="fn-btn fn-btn-sm fn-btn-primary" style="padding: 4px 10px; font-size: 11px;">
                                            ✓ Đánh dấu đã kiểm tra xong
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Đánh giá mới nhất từ người dùng -->
            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px;">
                <h3 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 800; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span>⭐ Đánh Giá Mới Gửi Lên</span>
                    <a href="reviews.php" style="font-size: 12px; color: var(--fn-primary);">Xem tất cả &rarr;</a>
                </h3>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach (array_slice($reviews, 0, 3) as $rev): ?>
                        <div style="padding: 12px; border: 1px solid var(--fn-border); border-radius: 8px; background: var(--fn-bg);">
                            <div style="display: flex; justify-content: space-between; font-size: 12px;">
                                <strong><?= htmlspecialchars($rev['user_name']) ?></strong>
                                <span style="color: var(--fn-gold); font-weight: bold;"><?= str_repeat('⭐', $rev['rating']) ?></span>
                            </div>
                            <div style="font-size: 11.5px; color: var(--fn-primary); margin-top: 2px;">
                                Cửa hàng: <?= htmlspecialchars($rev['shop_name']) ?>
                            </div>
                            <p style="font-size: 12px; color: var(--fn-dark-muted); margin-top: 4px; line-height: 1.4;">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </p>
                            <form action="index.php" method="POST" style="margin-top: 8px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_review">
                                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 3px 8px; font-size: 11px;">
                                    <?= !empty($rev['is_hidden']) ? '👁️ Bỏ ẩn đánh giá' : '🚫 Ẩn đánh giá này' ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
