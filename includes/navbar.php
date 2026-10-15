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
        <span class="fn-admin-navbar-title">Quản trị viên <small>FixNear</small></span>
        <div class="fn-admin-navbar-actions" style="display:flex;align-items:center;gap:12px;">
            <a href="../index.php" class="fn-btn-site" style="font-size:13px;font-weight:700;color:var(--fn-primary,#ea580c);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                Xem trang web ↗
            </a>
            <!-- Nút thông báo chuyển lên header -->
            <button type="button" class="fn-admin-notif-btn" id="fn-admin-notif-btn" aria-label="Thông báo quản trị" aria-expanded="false" aria-controls="fn-admin-notif-panel" style="position:relative;background:transparent;border:none;cursor:pointer;padding:6px;display:flex;align-items:center;justify-content:center;color:#64748b;" title="Thông báo hệ thống">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span id="fn-admin-notif-count" hidden style="position:absolute;top:2px;right:2px;min-width:16px;height:16px;background:#ea580c;color:#ffffff;font-size:10px;font-weight:800;border-radius:999px;align-items:center;justify-content:center;"></span>
            </button>
            <div id="fn-admin-notif-panel" class="fn-admin-notif-panel" hidden style="position:absolute;right:120px;top:64px;z-index:1300;background:white;border:1px solid #fed7aa;border-radius:12px;box-shadow:0 12px 35px #1e293b22;padding:16px;min-width:270px;color:#1e293b;font-size:13px"></div>
            <div class="fn-admin-nav-divider" style="height:20px;width:1px;background:#e2e8f0;"></div>
            <div class="fn-admin-nav-user-wrap" style="display:flex;align-items:center;gap:8px;">
                <div class="fn-admin-nav-avatar" style="width:30px;height:30px;border-radius:999px;background:#ea580c;color:#fff;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    A
                </div>
                <span class="fn-admin-navbar-user" style="font-weight:700;font-size:13px;color:#1e293b;"><?= htmlspecialchars($currentUser['name'] ?? 'Quản trị viên') ?></span>
            </div>
            <form method="post" action="../logout.php" style="margin:0;">
                <?= csrfField() ?>
                <button type="submit" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.15s;">Đăng xuất</button>
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
            <div class="fn-mobile-drawer-head">
                <a href="<?= $assetPrefix ?>index.php" class="fn-mobile-drawer-brand">
                    <img src="<?= $assetPrefix ?>assets/images/fixnear_logo_icon.svg" alt="" width="28" height="28">
                    <span><b>Fix</b>Near <small>Menu điều hướng</small></span>
                </a>
                <button type="button" class="fn-mobile-close-btn" onclick="fnToggleMobileMenu()" aria-label="Đóng menu">✕</button>
            </div>
            <a href="<?= $assetPrefix ?>index.php" class="fn-nav-link <?= ($currentPage === 'index.php' || $currentPage === 'search.php') ? 'active' : '' ?>">TRANG CHỦ</a>
            <a href="<?= $assetPrefix ?>models.php" class="fn-nav-link <?= ($currentPage === 'models.php' || $currentPage === 'model_detail.php') ? 'active' : '' ?>">DÒNG MÁY</a>
            <a href="<?= $assetPrefix ?>prices.php" class="fn-nav-link <?= $currentPage === 'prices.php' ? 'active' : '' ?>">BẢNG GIÁ</a>
            <a href="<?= $assetPrefix ?>request_repair.php" class="fn-nav-link <?= $currentPage === 'request_repair.php' ? 'active' : '' ?>">GỬI YÊU CẦU</a>
            <a href="<?= $assetPrefix ?>shops.php" class="fn-nav-link <?= ($currentPage === 'shops.php' || $currentPage === 'shop_detail.php') ? 'active' : '' ?>">CỬA HÀNG</a>
            <a href="<?= $assetPrefix ?>contact.php" class="fn-nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">LIÊN HỆ</a>
            <div class="fn-mobile-account-actions">
                <?php if (isLoggedIn()): ?>
                    <span class="fn-mobile-account-name"><?= htmlspecialchars($currentUser['name'] ?? 'Thành viên') ?></span>
                    <?php if (isAdmin()): ?><a href="<?= $assetPrefix ?>admin/index.php" class="fn-mobile-account-link fn-mobile-account-admin">🛡️ Trang quản trị</a><?php endif; ?>
                    <form method="post" action="<?= $assetPrefix ?>logout.php">
                        <?= csrfField() ?>
                        <button type="submit" class="fn-mobile-account-link">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a href="<?= $assetPrefix ?>login.php" class="fn-mobile-account-link">Đăng nhập</a>
                    <a href="<?= $assetPrefix ?>register.php" class="fn-mobile-account-link fn-mobile-account-primary">Đăng ký</a>
                <?php endif; ?>
            </div>
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
                    <?php if (!isAdmin()): ?>
                        <a id="fn-user-live-badge" href="<?= $assetPrefix ?>track_request.php" hidden data-user-id="<?= (int)($currentUser['id'] ?? 0) ?>" data-prefix="<?= htmlspecialchars($assetPrefix) ?>" style="font-size:12px;font-weight:700;color:#c2410c">Yêu cầu cập nhật</a>
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
<?php if (isLoggedIn() && !isAdmin()): ?><script defer src="<?= $assetPrefix ?>assets/js/user-live.js?v=<?= filemtime(__DIR__ . '/../assets/js/user-live.js') ?>"></script><?php endif; ?>
<!-- Backdrop tối khi mở menu mobile -->
<div class="fn-mobile-backdrop" id="fn-mobile-backdrop" onclick="fnToggleMobileMenu()"></div>
