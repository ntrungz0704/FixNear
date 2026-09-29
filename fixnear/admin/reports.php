<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    requireValidCsrf();
    $id = (int)$_POST['id'];
    $st = in_array($_POST['status'], ['pending', 'resolved'], true) ? $_POST['status'] : 'pending';
    db()->updateReportStatus($id, $st);
    header("Location: reports.php");
    exit;
}

$reports = db()->getReports();

$pageTitle = "Quản Lý Báo Cáo Thông Tin Sai — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <div class="fn-admin-sidebar">
        <a href="index.php" class="fn-admin-menu-item">
            📊 Bảng thống kê
        </a>
        <a href="requests.php" class="fn-admin-menu-item">
            📋 Yêu cầu báo giá
        </a>
        <a href="shops.php" class="fn-admin-menu-item">
            🏪 Quản lý cửa hàng
        </a>
        <a href="services.php" class="fn-admin-menu-item">
            🏷️ Dịch vụ & Bảng giá
        </a>
        <a href="reviews.php" class="fn-admin-menu-item">
            ⭐ Quản lý đánh giá
        </a>
        <a href="reports.php" class="fn-admin-menu-item active">
            🚩 Báo cáo sai sót (<?= count($reports) ?>)
        </a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--fn-border);">
            <a href="../index.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="width: 100%;">
                &larr; Xem giao diện web
            </a>
        </div>
    </div>

    <div class="fn-admin-content">
        <div style="margin-bottom: 24px;">
            <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                Xử Lý Báo Cáo Thông Tin Sai Từ Người Dùng
            </h1>
            <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                Người dùng phản ánh giá không khớp thực tế, tiệm chuyển chỗ hoặc đổi giờ mở cửa.
            </p>
        </div>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow: hidden;">
            <table class="fn-price-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Cửa Hàng Bị Phản Ánh</th>
                        <th>Người Gửi</th>
                        <th>Lý Do Báo Cáo</th>
                        <th>Chi Tiết Phản Ánh</th>
                        <th>Thời Gian</th>
                        <th>Trạng Thái</th>
                        <th style="text-align: right;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Hiện tại không có phản ánh sai sót nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $rp): ?>
                            <tr>
                                <td>#<?= $rp['id'] ?></td>
                                <td>
                                    <strong style="color: var(--fn-dark);">
                                        <?= htmlspecialchars($rp['shop_name'] ?? 'Cửa hàng') ?>
                                    </strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($rp['user_name'] ?? 'Khách vãng lai') ?>
                                </td>
                                <td>
                                    <span style="color: #dc2626; font-weight: 700; font-size: 12.5px;">
                                        <?= htmlspecialchars($rp['reason']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 13px; max-width: 280px;">
                                    <?= htmlspecialchars($rp['details']) ?>
                                </td>
                                <td style="font-size: 12px; color: var(--fn-text-light);">
                                    <?= date('d/m/Y H:i', strtotime($rp['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if (($rp['status'] ?? '') === 'resolved'): ?>
                                        <span style="font-size: 11px; padding: 2px 6px; background: #dcfce7; color: #166534; border-radius: 4px; font-weight: bold;">
                                            ✓ Đã xử lý
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 11px; padding: 2px 6px; background: #fef3c7; color: #92400e; border-radius: 4px; font-weight: bold;">
                                            ⏳ Chờ kiểm tra
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <?php if (($rp['status'] ?? '') !== 'resolved'): ?>
                                            <form method="post" action="reports.php" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= $rp['id'] ?>">
                                                <input type="hidden" name="status" value="resolved">
                                                <button type="submit" class="fn-btn fn-btn-sm fn-btn-primary" style="padding: 4px 8px; font-size: 11.5px;">✓ Đã sửa</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="reports.php" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= $rp['id'] ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 4px 8px; font-size: 11.5px;">Mở lại</button>
                                            </form>
                                        <?php endif; ?>
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
