<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Xóa cửa hàng bằng POST có CSRF; không thay đổi dữ liệu qua URL GET.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    requireValidCsrf();
    $delId = (int)$_POST['delete'];
    db()->deleteShop($delId);
    header("Location: shops.php?msg=deleted");
    exit;
}

$shops = db()->getShops(['include_unverified' => true]);
$publishedShopCount = count(array_filter($shops, static fn($shop) => !empty($shop['address_verified'])));
$stats = db()->getStats();

$pageTitle = "Quản Lý Cửa Hàng — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>


    <div class="fn-admin-content">
        <div class="fn-admin-page-head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                    Cửa hàng: <?= $publishedShopCount ?> công khai / <?= count($shops) ?> bản ghi
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                    Chỉ chi nhánh có địa chỉ và số liên hệ đối chiếu từ website chính thức mới xuất hiện trên trang công khai. <?= count($shops) - $publishedShopCount ?> bản ghi đang chờ rà soát.
                </p>
            </div>
            <a href="shop_edit.php" class="fn-btn fn-btn-primary">
                + Thêm Cửa Hàng Mới
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ✓ Đã lưu thông tin cửa hàng thành công!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ✓ Đã xóa cửa hàng khỏi hệ thống!
            </div>
        <?php endif; ?>

        <?php
        // Lấy danh sách quận/huyện duy nhất để hiện vào bộ lọc
        $allDistricts = array_unique(array_filter(array_column($shops, 'district')));
        sort($allDistricts);
        ?>
        <!-- ===== THANH TÌM KIẾM & LỌC ===== -->
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
            <input id="shop-search" type="search" placeholder="🔍 Tìm theo tên, địa chỉ, hotline…"
                   style="flex:1;min-width:220px;padding:8px 14px;border:1px solid var(--fn-border);border-radius:8px;font-size:14px;outline:none;"
                   oninput="filterShops()">
            <select id="district-filter"
                    style="padding:8px 14px;border:1px solid var(--fn-border);border-radius:8px;font-size:14px;background:var(--fn-surface);min-width:160px;"
                    onchange="filterShops()">
                <option value="">— Tất cả quận/huyện —</option>
                <?php foreach ($allDistricts as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>
            <span id="shop-count-badge" style="font-size:13px;color:var(--fn-dark-muted);white-space:nowrap;">
                <?= count($shops) ?> cửa hàng
            </span>
        </div>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto; box-shadow: var(--fn-shadow-sm);">
            <table class="fn-price-table" id="shops-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Tên Cửa Hàng</th>
                        <th>Địa Chỉ & Khu Vực</th>
                        <th>Hotline</th>
                        <th>Google Star</th>
                        <th>Tọa Độ (Map)</th>
                        <th style="text-align: right;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shops as $s): ?>
                        <tr data-search="<?= htmlspecialchars(strtolower($s['name'] . ' ' . $s['address'] . ' ' . $s['phone'] . ' ' . $s['ward']), ENT_QUOTES) ?>"
                            data-district="<?= htmlspecialchars($s['district'] ?? '', ENT_QUOTES) ?>">
                            <td><strong>#<?= $s['id'] ?></strong></td>
                            <td>
                                <div style="font-weight: 800; color: var(--fn-dark); font-size: 14.5px;">
                                    <?php if (!empty($s['address_verified'])): ?>
                                        <a href="../shop_detail.php?id=<?= (int) $s['id'] ?>" target="_blank" rel="noopener noreferrer" style="color: var(--fn-primary);">
                                            <?= htmlspecialchars($s['name']) ?> ↗
                                        </a>
                                    <?php else: ?>
                                        <a href="shop_edit.php?id=<?= (int) $s['id'] ?>" style="color: var(--fn-primary);">
                                            <?= htmlspecialchars($s['name']) ?> · Chỉnh sửa
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; color: var(--fn-text-light);">
                                    <?= implode(', ', array_map('ucfirst', $s['devices'] ?? [])) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 13px; color: var(--fn-dark);">
                                    <?= htmlspecialchars($s['address']) ?>
                                </div>
                                <span style="font-size: 11px; font-weight: 700; color: #4338ca; background: #e0e7ff; padding: 2px 6px; border-radius: 4px;">
                                    <?= htmlspecialchars($s['ward']) ?>, <?= htmlspecialchars($s['district']) ?>
                                </span>
                                <div style="margin-top:6px;font-size:11px;font-weight:800;color:<?= !empty($s['address_verified']) ? '#c2410c' : '#64748b' ?>;">
                                    <?php if (!empty($s['address_verified'])): ?><a href="<?= htmlspecialchars($s['address_source_url']) ?>" target="_blank" rel="noopener noreferrer" style="color:#c2410c;">✓ Công khai · nguồn địa chỉ ↗</a><?php else: ?>Chưa đối soát · ẩn khỏi trang công khai<?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px; font-family: monospace; font-weight: bold;">
                                    <?= htmlspecialchars($s['phone']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($s['google_rating_verified'])): ?><span style="color: var(--fn-gold); font-weight: 800; font-size: 13.5px;">⭐ <?= htmlspecialchars($s['google_rating']) ?></span><span style="font-size: 11.5px; color: var(--fn-text-light);">(<?= (int)$s['google_reviews_count'] ?>)</span><?php else: ?><span style="font-size:11px;color:#64748b;">Chưa đối soát</span><?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 11.5px; font-family: monospace; color: var(--fn-text-light);">
                                    <?= round($s['latitude'], 4) ?>, <?= round($s['longitude'], 4) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="shop_edit.php?id=<?= $s['id'] ?>" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                        ✏️ Sửa
                                    </a>
                                    <form method="post" action="shops.php" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tiệm này không?')" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="delete" value="<?= $s['id'] ?>">
                                        <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 5px 10px; font-size: 12px; color: #ef4444;">🗑️ Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
function filterShops() {
    var kw       = (document.getElementById('shop-search').value || '').toLowerCase().trim();
    var district = document.getElementById('district-filter').value;
    var rows     = document.querySelectorAll('#shops-table tbody tr');
    var visible  = 0;
    rows.forEach(function(row) {
        var matchKw  = !kw || (row.dataset.search || '').indexOf(kw) !== -1;
        var matchDis = !district || row.dataset.district === district;
        var show = matchKw && matchDis;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('shop-count-badge').textContent = visible + ' cửa hàng';
}
// focus search khi ấn Ctrl+F trên trang admin
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        var s = document.getElementById('shop-search');
        if (s) { e.preventDefault(); s.focus(); s.select(); }
    }
});
</script>

