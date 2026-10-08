<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<?php if ($assetPrefix === '../' && isAdmin()): ?>
<nav class="fn-navbar fn-admin-navbar" aria-label="Thanh điều hướng quản trị">
    <div class="fn-nav-container">
        <a href="../index.php" class="fn-logo" aria-label="FixNear, về trang web">
            <img src="../assets/images/fixnear_logo_icon.svg" alt="" width="32" height="32">
            <span class="fn-logo-text"><span>Fix</span><span>Near</span></span>
        </a>
        <span class="fn-admin-navbar-title">Quản trị <small>Điều hành dữ liệu FixNear</small></span>
        <div class="fn-admin-navbar-actions">
            <a href="../index.php">Xem trang web ↗</a>
            <span class="fn-admin-navbar-user"><?= htmlspecialchars($currentUser['name'] ?? 'Quản trị viên') ?></span>
            <form method="post" action="../logout.php">
                <?= csrfField() ?>
                <button type="submit">Đăng xuất</button>
            </form>
        </div>
    </div>
</nav>
<div id="fn-admin-live-status" class="fn-admin-live-status" role="status" aria-live="polite">Đang kiểm tra cập nhật quản trị…</div>
<script defer src="../assets/js/admin-live.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-live.js') ?>"></script>
<script defer src="../assets/js/admin-tables.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-tables.js') ?>"></script>
<?php return; endif; ?>
<nav class="fn-navbar">
    <div class="fn-nav-container">
        <!-- Logo -->
        <a href="<?= $assetPrefix ?>index.php" class="fn-logo">
            <img src="<?= $assetPrefix ?>assets/images/fixnear_logo_icon.svg" alt="FixNear" width="32" height="32" style="flex-shrink:0; display:block;">
            <div class="fn-logo-text">
                <span>Fix</span><span>Near</span>
            </div>
        </a>

        <!-- Nút Hamburger Menu (Mobile) -->
        <button type="button" class="fn-hamburger" id="fn-hamburger-btn" onclick="fnToggleMobileMenu()" aria-label="Mở menu điều hướng" aria-controls="fn-nav-links" aria-expanded="false">
            <span class="fn-hamburger-line"></span>
            <span class="fn-hamburger-line"></span>
            <span class="fn-hamburger-line"></span>
        </button>

        <!-- Menu links: Trọng Tâm Chuẩn Yêu Cầu -->
        <div class="fn-nav-links" id="fn-nav-links">
            <button type="button" class="fn-mobile-close-btn" onclick="fnToggleMobileMenu()" aria-label="Đóng menu">✕</button>
            <a href="<?= $assetPrefix ?>index.php" class="fn-nav-link <?= ($currentPage === 'index.php' || $currentPage === 'search.php') ? 'active' : '' ?>">TRANG CHỦ</a>
            <a href="<?= $assetPrefix ?>models.php" class="fn-nav-link <?= ($currentPage === 'models.php' || $currentPage === 'model_detail.php') ? 'active' : '' ?>">DÒNG MÁY</a>
            <a href="<?= $assetPrefix ?>prices.php" class="fn-nav-link <?= $currentPage === 'prices.php' ? 'active' : '' ?>">BẢNG GIÁ</a>
            <a href="<?= $assetPrefix ?>request_repair.php" class="fn-nav-link <?= $currentPage === 'request_repair.php' ? 'active' : '' ?>">GỬI YÊU CẦU</a>
            <a href="<?= $assetPrefix ?>shops.php" class="fn-nav-link <?= $currentPage === 'shops.php' ? 'active' : '' ?>">CỬA HÀNG</a>
            <a href="<?= $assetPrefix ?>contact.php" class="fn-nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">LIÊN HỆ</a>
        </div>

        <!-- Actions -->
        <div class="fn-nav-actions">
            <?php if (isLoggedIn()): ?>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if (isAdmin()): ?>
                        <a href="<?= $assetPrefix ?>admin/index.php" class="fn-btn fn-btn-sm fn-btn-primary" style="background:#0f172a;">
                            🛡️ Trang Quản Trị
                        </a>
                    <?php endif; ?>
                    <span style="font-size: 13.5px; font-weight: 700; color: var(--fn-dark);">
                        <?= htmlspecialchars($currentUser['name'] ?? 'Thành viên') ?>
                        <span style="font-size: 11px; padding: 2px 6px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; border-radius: 4px; margin-left: 4px;">
                            <?= ($currentUser['role'] ?? '') === 'admin' ? 'Admin' : 'Thành viên' ?>
                        </span>
                    </span>
                    <form method="post" action="<?= $assetPrefix ?>logout.php" style="margin:0;">
                        <?= csrfField() ?>
                        <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" title="Đăng xuất">Đăng xuất</button>
                    </form>
                </div>
            <?php else: ?>
                <a href="<?= $assetPrefix ?>login.php" class="fn-btn fn-btn-sm fn-btn-secondary">Đăng nhập</a>
                <a href="<?= $assetPrefix ?>register.php" class="fn-btn fn-btn-sm fn-btn-primary">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- Backdrop tối khi mở menu mobile -->
<div class="fn-mobile-backdrop" id="fn-mobile-backdrop" onclick="fnToggleMobileMenu()"></div>
