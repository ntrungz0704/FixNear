<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/contacts.php");
    exit;
}

$stats = db()->getStats();

// Xử lý cập nhật trạng thái hoặc xóa
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireValidCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && $id > 0) {
        $st = in_array($_POST['status'] ?? '', ['pending', 'read', 'replied'], true) ? $_POST['status'] : 'pending';
        db()->updateContactMessageStatus($id, $st);
        header("Location: contacts.php?msg=updated");
        exit;
    } elseif ($action === 'delete' && $id > 0) {
        db()->deleteContactMessage($id);
        header("Location: contacts.php?msg=deleted");
        exit;
    }
}

$allContacts = db()->getContactMessages();

// Bộ lọc trạng thái
$filterStatus = $_GET['status'] ?? 'all';
$filteredContacts = array_filter($allContacts, function($c) use ($filterStatus) {
    if ($filterStatus === 'all') return true;
    $st = $c['status'] ?? 'pending';
    return $st === $filterStatus;
});

$pageTitle = "Quản Lý Tin Nhắn Liên Hệ — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị -->
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark); margin: 0;">
                    Hộp Thư Liên Hệ & Hợp Tác
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); margin-top: 4px;">
                    Tổng hợp các góp ý, khiếu nại chất lượng tiệm sửa, hoặc đề xuất tham gia mạng lưới của các cửa hàng.
                </p>
            </div>
            <div>
                <a href="../contact.php" target="_blank" class="fn-btn fn-btn-secondary fn-btn-sm">
                    ↗️ Mở trang form liên hệ
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div style="background: #dcfce7; border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 6px; color: #166534; font-size: 13.5px; margin-bottom: 20px;">
                ✅ Đã cập nhật trạng thái tin nhắn thành công!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 6px; color: #991b1b; font-size: 13.5px; margin-bottom: 20px;">
                🗑️ Đã xóa tin nhắn liên hệ khỏi hệ thống!
            </div>
        <?php endif; ?>

        <!-- Thanh Tab Bộ Lọc Trạng Thái -->
        <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="contacts.php?status=all" class="fn-btn fn-btn-sm <?= $filterStatus === 'all' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>">
                Tất cả (<?= count($allContacts) ?>)
            </a>
            <a href="contacts.php?status=pending" class="fn-btn fn-btn-sm <?= $filterStatus === 'pending' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>">
                ⏳ Chờ xử lý (<?= count(array_filter($allContacts, fn($c) => ($c['status'] ?? 'pending') === 'pending')) ?>)
            </a>
            <a href="contacts.php?status=read" class="fn-btn fn-btn-sm <?= $filterStatus === 'read' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>">
                👁️ Đã xem (<?= count(array_filter($allContacts, fn($c) => ($c['status'] ?? '') === 'read')) ?>)
            </a>
            <a href="contacts.php?status=replied" class="fn-btn fn-btn-sm <?= $filterStatus === 'replied' ? 'fn-btn-primary' : 'fn-btn-secondary' ?>">
                ✅ Đã phản hồi (<?= count(array_filter($allContacts, fn($c) => ($c['status'] ?? '') === 'replied')) ?>)
            </a>
        </div>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow: hidden; box-shadow: var(--fn-shadow-sm);">
            <div style="overflow-x: auto;">
                <table class="fn-price-table" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1.5px solid var(--fn-border); text-align: left; font-size: 12px; color: var(--fn-dark-muted); text-transform: uppercase;">
                            <th style="padding: 12px 16px; width: 60px;">ID</th>
                            <th style="padding: 12px 16px; width: 220px;">Người gửi</th>
                            <th style="padding: 12px 16px; width: 200px;">Chủ đề / Phân loại</th>
                            <th style="padding: 12px 16px;">Nội dung</th>
                            <th style="padding: 12px 16px; width: 140px;">Thời gian</th>
                            <th style="padding: 12px 16px; width: 130px;">Trạng thái</th>
                            <th style="padding: 12px 16px; width: 140px; text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 13.5px;">
                        <?php if (empty($filteredContacts)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 48px 20px; color: var(--fn-dark-muted);">
                                    <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                                    <strong>Không có tin nhắn nào trong danh mục này.</strong>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredContacts as $c): ?>
                                <?php
                                $cStatus = $c['status'] ?? 'pending';
                                $subject = $c['subject'] ?? ($c['type'] ?? 'Chưa phân loại');
                                $badgeColor = '#64748b';
                                $badgeBg = '#f1f5f9';
                                if (str_contains($subject, 'khiếu nại')) {
                                    $badgeColor = '#dc2626'; $badgeBg = '#fee2e2';
                                } elseif (str_contains($subject, 'Cửa hàng') || str_contains($subject, 'tham gia')) {
                                    $badgeColor = '#0284c7'; $badgeBg = '#e0f2fe';
                                } elseif (str_contains($subject, 'cải tiến')) {
                                    $badgeColor = '#16a34a'; $badgeBg = '#dcfce7';
                                } elseif (str_contains($subject, 'Hợp tác')) {
                                    $badgeColor = '#9333ea'; $badgeBg = '#f3e8ff';
                                }
                                ?>
                                <tr style="border-bottom: 1px solid var(--fn-border); vertical-align: top;">
                                    <td style="padding: 14px 16px; font-weight: 700; color: var(--fn-dark-muted);">
                                        #<?= (int)($c['id'] ?? 0) ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <strong style="color: var(--fn-dark); display: block;">
                                            <?= htmlspecialchars($c['name'] ?? 'Khách ẩn danh') ?>
                                        </strong>
                                        <?php if (!empty($c['phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars($c['phone']) ?>" style="color: #ea580c; font-size: 12.5px; font-weight: 600; text-decoration: none; display: block; margin-top: 2px;">
                                                📞 <?= htmlspecialchars($c['phone']) ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($c['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="color: var(--fn-dark-muted); font-size: 12px; text-decoration: none; display: block; margin-top: 2px;">
                                                ✉️ <?= htmlspecialchars($c['email']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; color: <?= $badgeColor ?>; background: <?= $badgeBg ?>; line-height: 1.3;">
                                            <?= htmlspecialchars($subject) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; color: var(--fn-dark); line-height: 1.5;">
                                        <div style="white-space: pre-wrap; word-break: break-word; max-height: 140px; overflow-y: auto; background: #f8fafc; padding: 10px 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 13px;">
                                            <?= htmlspecialchars($c['message'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px; color: var(--fn-dark-muted); font-size: 12.5px; white-space: nowrap;">
                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['created_at'] ?? 'now'))) ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <?php if ($cStatus === 'pending'): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 800; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                ⏳ Chờ xử lý
                                            </span>
                                        <?php elseif ($cStatus === 'read'): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 800; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                                👁️ Đã xem
                                            </span>
                                        <?php elseif ($cStatus === 'replied'): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 800; background: #dcfce7; color: #166534; border: 1px solid #86efac;">
                                                ✅ Đã phản hồi
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                        <!-- Form đổi trạng thái -->
                                        <form method="POST" style="display: inline-block; margin-bottom: 4px;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>">
                                            <select name="status" onchange="this.form.submit()" style="font-size: 11.5px; padding: 4px 6px; border-radius: 4px; border: 1px solid var(--fn-border); background: #fff; cursor: pointer;">
                                                <option value="pending" <?= $cStatus === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                                <option value="read" <?= $cStatus === 'read' ? 'selected' : '' ?>>Đã xem</option>
                                                <option value="replied" <?= $cStatus === 'replied' ? 'selected' : '' ?>>Đã phản hồi</option>
                                            </select>
                                        </form>

                                        <!-- Form xóa -->
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>">
                                            <button type="submit" class="fn-btn fn-btn-sm" style="padding: 4px 8px; color: #ef4444; background: #fee2e2; border: 1px solid #fca5a5; font-size: 11.5px; border-radius: 4px;" title="Xóa tin nhắn">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
