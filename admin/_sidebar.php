<?php
$adminCurrentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$adminNavItems = [
    ['index.php', '📊', 'Tổng quan'],
    ['requests.php', '📋', 'Yêu cầu sửa chữa'],
    ['shops.php', '🏪', 'Cửa hàng'],
    ['services.php', '🏷️', 'Dịch vụ & giá'],
    ['reviews.php', '⭐', 'Đánh giá'],
    ['reports.php', '🚩', 'Báo cáo sai sót'],
    ['contacts.php', '💬', 'Liên hệ'],
];
if ($adminCurrentPage === 'shop_edit.php') $adminCurrentPage = 'shops.php';
?>
<aside class="fn-admin-sidebar" aria-label="Điều hướng quản trị">
    <p class="fn-admin-sidebar-label">QUẢN TRỊ FIXNEAR</p>
    <?php foreach ($adminNavItems as [$href, $icon, $label]): ?>
        <a href="<?= $href ?>" class="fn-admin-menu-item<?= $adminCurrentPage === $href ? ' active' : '' ?>"<?= $adminCurrentPage === $href ? ' aria-current="page"' : '' ?>>
            <span aria-hidden="true"><?= $icon ?></span><span><?= htmlspecialchars($label) ?></span>
        </a>
    <?php endforeach; ?>
</aside>
