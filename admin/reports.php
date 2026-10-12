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
    header("Location: reports.php?msg=" . $st);
    exit;
}

$reports = db()->getReports();
$filterStatus = $_GET['filter'] ?? 'all';
if ($filterStatus === 'pending') {
    $reports = array_filter($reports, fn($r) => ($r['status'] ?? 'pending') !== 'resolved');
} elseif ($filterStatus === 'resolved') {
    $reports = array_filter($reports, fn($r) => ($r['status'] ?? 'pending') === 'resolved');
}

$pageTitle = "Quản Lý Báo Cáo Thông Tin Sai — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>


    <div class="fn-admin-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                    Xử Lý Báo Cáo Thông Tin Sai Từ Người Dùng
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                    Người dùng phản ánh giá không khớp thực tế, tiệm chuyển chỗ hoặc đổi giờ mở cửa.
                </p>
            </div>

            <!-- Bộ lọc trạng thái -->
            <div style="display: flex; gap: 6px;">
                <a href="reports.php?filter=all" class="fn-btn fn-btn-sm <?= $filterStatus === 'all' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>" style="font-size: 12px; padding: 6px 12px;">Tất cả</a>
                <a href="reports.php?filter=pending" class="fn-btn fn-btn-sm <?= $filterStatus === 'pending' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>" style="font-size: 12px; padding: 6px 12px;">⏳ Chờ kiểm tra</a>
                <a href="reports.php?filter=resolved" class="fn-btn fn-btn-sm <?= $filterStatus === 'resolved' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>" style="font-size: 12px; padding: 6px 12px;">✓ Đã xử lý</a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'resolved'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ✓ Đã chuyển trạng thái báo cáo sang "Đã xử lý"!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'pending'): ?>
            <div style="background: #fef3c7; color: #92400e; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ✓ Đã mở lại báo cáo sang trạng thái "Chờ kiểm tra"!
            </div>
        <?php endif; ?>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto; box-shadow: var(--fn-shadow-sm);">
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
                        <tr><td colspan="8" style="text-align:center; padding:30px; color: var(--fn-text-light);">Hiện tại không có phản ánh sai sót nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $rp): ?>
                            <tr>
                                <td>#<?= $rp['id'] ?></td>
                                <td>
                                    <?php if (!empty($rp['shop_id'])): ?>
                                        <a href="../shop_detail.php?id=<?= (int)$rp['shop_id'] ?>" target="_blank" style="font-weight: 800; color: var(--fn-primary); text-decoration: none;">
                                            <?= htmlspecialchars($rp['shop_name'] ?? 'Cửa hàng #' . $rp['shop_id']) ?> ↗
                                        </a>
                                    <?php else: ?>
                                        <strong style="color: var(--fn-dark);"><?= htmlspecialchars($rp['shop_name'] ?? 'Cửa hàng') ?></strong>
                                    <?php endif; ?>
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
