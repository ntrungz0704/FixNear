<?php
$pageTitle = "Danh Sách Bản Ghi Cửa Hàng Sửa Chữa — FixNear";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Nhận tham số lọc
$deviceFilter = $_GET['device'] ?? '';
$districtFilter = $_GET['district'] ?? '';
$expertFilter = $_GET['expert'] ?? '';
$studentOnly = isset($_GET['student']) ? true : false;
$searchKw = $_GET['q'] ?? '';

$allShops = db()->getShops();

// Lọc dữ liệu
$filteredShops = array_filter($allShops, function($shop) use ($deviceFilter, $districtFilter, $expertFilter, $studentOnly, $searchKw) {
    if ($deviceFilter) {
        $devs = $shop['devices'] ?? [];
        if (is_array($devs)) {
            if (!in_array($deviceFilter, $devs)) return false;
        } else {
            if (mb_stripos((string)$devs, $deviceFilter) === false) return false;
        }
    }
    if ($districtFilter && mb_stripos($shop['district'], $districtFilter) === false) {
        return false;
    }
    if ($studentOnly && empty($shop['student_discount_verified'])) {
        return false;
    }
    if ($searchKw) {
        $kw = mb_strtolower($searchKw, 'UTF-8');
        $inName = mb_stripos($shop['name'], $kw) !== false;
        $inAddr = mb_stripos($shop['address'], $kw) !== false;
        $inDesc = mb_stripos($shop['description'], $kw) !== false;
        if (!$inName && !$inAddr && !$inDesc) return false;
    }
    return true;
});

// Chỉ dùng điểm Google khi có nguồn + thời điểm đối soát; nếu chưa thì xếp tên A-Z.
usort($filteredShops, function($a, $b) {
    if (!empty($a['google_rating_verified']) && !empty($b['google_rating_verified'])) {
        return ((float)$b['google_rating'] <=> (float)$a['google_rating'])
            ?: ((int)$b['google_reviews_count'] <=> (int)$a['google_reviews_count']);
    }
    return strnatcasecmp($a['name'], $b['name']);
});
?>

<div class="fn-container" style="padding: 24px 20px 80px;">
    <!-- Nút Trở Lại Trang Chủ -->
    <div style="margin-bottom: 24px;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #1e293b; font-size: 14px; font-weight: 800; text-decoration: none; padding: 9px 16px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; transition: color 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.2s, opacity 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <span style="font-size: 18px; color: #ea580c; font-weight: 900; line-height: 1;">←</span>
            <span>Trở lại Trang Chủ</span>
        </a>
    </div>

    <!-- Tiêu đề trang (Chỉ tiêu đề lớn mới căn giữa) -->
    <div style="text-align: center; max-width: 800px; margin: 0 auto 36px;">
        <h1 style="font-family: var(--fn-font-heading); font-size: 32px; font-weight: 900; color: var(--fn-dark); line-height: 1.25;">
            <?= count($allShops) ?> Bản Ghi Cửa Hàng Sửa Chữa <span style="color: #ea580c;">Để Tra Cứu & Đối Chiếu</span>
        </h1>
        <p style="font-size: 15px; color: var(--fn-dark-muted); margin-top: 10px; line-height: 1.6;">
            Thông tin, điểm số và chính sách đang lấy từ bộ dữ liệu dự án. Hãy xác nhận lại giá, giờ mở cửa, khả năng sửa và bảo hành trực tiếp với cửa hàng trước khi đến.
        </p>
    </div>

    <!-- Thanh 3 tiêu chuẩn minh bạch (Căn trái nội dung bên trong) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 32px; text-align: left;">
        <div style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 20px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div style="font-size: 26px;">👁️</div>
            <div>
                <h4 style="font-size: 15px; font-weight: 800; color: #1e40af; margin-bottom: 4px;">Xem Sửa Trực Tiếp</h4>
                <p style="font-size: 13px; color: #475569; margin: 0; line-height: 1.5;">Khách ngồi quan sát trực tiếp kỹ thuật viên bóc tách máy và thay linh kiện tại bàn.</p>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #bbf7d0; border-radius: 14px; padding: 20px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div style="font-size: 26px;">✍️</div>
            <div>
                <h4 style="font-size: 15px; font-weight: 800; color: #166534; margin-bottom: 4px;">Ký Tên Lên Linh Kiện</h4>
                <p style="font-size: 13px; color: #475569; margin: 0; line-height: 1.5;">Ký tên lên màn hình, pin, mainboard, ram, ổ cứng trước khi bàn giao lưu máy lại.</p>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 14px; padding: 20px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div style="font-size: 26px;">🎓</div>
            <div>
                <h4 style="font-size: 15px; font-weight: 800; color: #c2410c; margin-bottom: 4px;">Ưu Đãi Học Sinh - Sinh Viên</h4>
                <p style="font-size: 13px; color: #475569; margin: 0; line-height: 1.5;">Một số hệ thống sửa chữa có chương trình hỗ trợ HSSV. Hãy hỏi trực tiếp cửa hàng về điều kiện áp dụng và thời hạn.</p>
            </div>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm nhanh -->
    <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 20px; margin-bottom: 32px; box-shadow: var(--fn-shadow);">
        <form action="shops.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1.5; min-width: 220px;">
                <input type="text" name="q" value="<?= htmlspecialchars($searchKw) ?>" class="fn-input" placeholder="🔍 Nhập tên cửa hàng, đường hoặc quận..." style="padding: 10px 14px;">
            </div>

            <div style="flex: 1; min-width: 160px;">
                <select name="device" class="fn-select">
                    <option value="">Tất cả thiết bị</option>
                    <option value="laptop" <?= $deviceFilter === 'laptop' ? 'selected' : '' ?>>Chuyên Laptop</option>
                    <option value="mac" <?= $deviceFilter === 'mac' ? 'selected' : '' ?>>Chuyên MacBook / Apple</option>
                    <option value="phone" <?= $deviceFilter === 'phone' ? 'selected' : '' ?>>Chuyên Điện Thoại</option>
                    <option value="pc" <?= $deviceFilter === 'pc' ? 'selected' : '' ?>>Chuyên PC Gaming</option>
                    <option value="tablet" <?= $deviceFilter === 'tablet' ? 'selected' : '' ?>>Chuyên iPad / Tablet</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 160px;">
                <select name="district" class="fn-select">
                    <option value="">Tất cả khu vực</option>
                    <option value="Quận 1" <?= $districtFilter === 'Quận 1' ? 'selected' : '' ?>>Quận 1</option>
                    <option value="Quận 3" <?= $districtFilter === 'Quận 3' ? 'selected' : '' ?>>Quận 3</option>
                    <option value="Quận 4" <?= $districtFilter === 'Quận 4' ? 'selected' : '' ?>>Quận 4</option>
                    <option value="Quận 5" <?= $districtFilter === 'Quận 5' ? 'selected' : '' ?>>Quận 5</option>
                    <option value="Quận 6" <?= $districtFilter === 'Quận 6' ? 'selected' : '' ?>>Quận 6</option>
                    <option value="Quận 7" <?= $districtFilter === 'Quận 7' ? 'selected' : '' ?>>Quận 7</option>
                    <option value="Quận 8" <?= $districtFilter === 'Quận 8' ? 'selected' : '' ?>>Quận 8</option>
                    <option value="Quận 10" <?= $districtFilter === 'Quận 10' ? 'selected' : '' ?>>Quận 10</option>
                    <option value="Quận 11" <?= $districtFilter === 'Quận 11' ? 'selected' : '' ?>>Quận 11</option>
                    <option value="Quận 12" <?= $districtFilter === 'Quận 12' ? 'selected' : '' ?>>Quận 12</option>
                    <option value="Quận Bình Tân" <?= $districtFilter === 'Quận Bình Tân' ? 'selected' : '' ?>>Quận Bình Tân</option>
                    <option value="Quận Bình Thạnh" <?= $districtFilter === 'Quận Bình Thạnh' ? 'selected' : '' ?>>Quận Bình Thạnh</option>
                    <option value="Quận Gò Vấp" <?= $districtFilter === 'Quận Gò Vấp' ? 'selected' : '' ?>>Quận Gò Vấp</option>
                    <option value="Quận Phú Nhuận" <?= $districtFilter === 'Quận Phú Nhuận' ? 'selected' : '' ?>>Quận Phú Nhuận</option>
                    <option value="Quận Tân Bình" <?= $districtFilter === 'Quận Tân Bình' ? 'selected' : '' ?>>Quận Tân Bình</option>
                    <option value="Quận Tân Phú" <?= $districtFilter === 'Quận Tân Phú' ? 'selected' : '' ?>>Quận Tân Phú</option>
                    <option value="TP. Thủ Đức" <?= $districtFilter === 'TP. Thủ Đức' ? 'selected' : '' ?>>TP. Thủ Đức</option>
                </select>
            </div>

            <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; color: #ea580c; cursor: pointer; white-space: nowrap;">
                <input type="checkbox" name="student" value="1" <?= $studentOnly ? 'checked' : '' ?> style="accent-color: #ea580c; width: 16px; height: 16px;">
                Ưu đãi sinh viên
            </label>

            <button type="submit" class="fn-btn fn-btn-primary" style="padding: 10px 20px; font-size: 14px; font-weight: 800;">
                Lọc Tiệm
            </button>

            <?php if ($deviceFilter || $districtFilter || $studentOnly || $searchKw): ?>
                <a href="shops.php" class="fn-btn fn-btn-secondary" style="font-size: 13px; text-decoration: none;">
                    ✕ Xóa lọc
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Kết quả thống kê -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div style="font-size: 15px; font-weight: 800; color: var(--fn-dark);">
            Tìm thấy <span style="color: #ea580c;"><?= count($filteredShops) ?></span> bản ghi cửa hàng phù hợp bộ lọc
        </div>
        <div style="font-size: 12.5px; color: #64748b;">
            Xếp theo: <strong><?= count(array_filter($filteredShops, fn($s) => !empty($s['google_rating_verified']))) > 0 ? 'Điểm Google (đã đối soát)' : 'Tên A–Z (chưa có điểm Google đã đối soát)' ?></strong>
        </div>
    </div>

    <!-- Danh sách cửa hàng dạng Card sang trọng (Căn trái toàn bộ nội dung) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 22px; text-align: left;">
        <?php foreach ($filteredShops as $shop): ?>
            <div class="fn-shop-card" style="display: flex; flex-direction: column; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.04); text-align: left;">
                <!-- Thumbnail -->
                <div style="height: 170px; position: relative; overflow: hidden; border-radius: 16px 16px 0 0;">
                    <img src="<?= htmlspecialchars($shop['image']) ?>" alt="<?= htmlspecialchars($shop['name']) ?>" width="600" height="400" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                    <span style="position: absolute; top: 10px; left: 10px; background: rgba(15,23,42,0.85); backdrop-filter: blur(4px); color: #38bdf8; font-weight: 800; padding: 3px 10px; border-radius: 20px; font-size: 11px;">
                        📍 <?= htmlspecialchars($shop['district']) ?>
                    </span>
                    <?php if (!empty($shop['source_verified'])): ?>
                    <span style="position: absolute; top: 10px; right: 10px; background: #059669; color: #fff; font-weight: 800; padding: 3px 10px; border-radius: 20px; font-size: 10.5px;">
                        ✓ Đã xác minh nguồn
                    </span>
                    <?php else: ?>
                    <span style="position: absolute; top: 10px; right: 10px; background: rgba(15,23,42,0.7); backdrop-filter: blur(4px); color: #e2e8f0; font-weight: 700; padding: 3px 10px; border-radius: 20px; font-size: 10.5px;">
                        📋 Dữ liệu tham khảo
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div style="flex: 1; display: flex; flex-direction: column; padding: 16px; text-align: left;">
                    <!-- Rating row -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                        <span style="color: #ea580c; font-weight: 800; font-size: 13.5px;">
                            <?= !empty($shop['google_rating_verified']) ? '★ ' . htmlspecialchars($shop['google_rating']) . ' <span style="color:#94a3b8;font-weight:600;font-size:11.5px;">(' . number_format($shop['google_reviews_count'], 0, ',', '.') . ' Google)</span>' : '<span style="color:#64748b;font-size:11.5px;">Google: chưa đối soát</span>' ?>
                        </span>
                        <span style="font-size: 11px; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-weight: 600;">
                            🕒 <?= htmlspecialchars($shop['opening_hours']) ?>
                        </span>
                    </div>

                    <!-- Tên -->
                    <h3 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.35; margin: 0 0 5px 0;">
                        <a href="shop_detail.php?id=<?= $shop['id'] ?>" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: none;">
                            <?= htmlspecialchars($shop['name']) ?>
                        </a>
                    </h3>

                    <!-- Địa chỉ -->
                    <p style="font-size: 12.5px; color: #64748b; line-height: 1.5; margin: 0 0 8px 0;">
                        📌 <?= htmlspecialchars($shop['address']) ?>
                    </p>

                    <!-- Hotline + Website compact -->
                    <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; font-size: 12.5px;">
                        <a href="tel:<?= preg_replace('/[^0-9]/', '', $shop['phone']) ?>" target="_blank" rel="noopener noreferrer" style="color: #16a34a; font-weight: 700; text-decoration: none;">
                            📞 <?= htmlspecialchars($shop['phone']) ?>
                        </a>
                        <?php if (!empty($shop['website']) && strpos($shop['website'], 'google.com/maps') === false): ?>
                            <a href="<?= htmlspecialchars($shop['website']) ?>" target="_blank" rel="noopener noreferrer" style="color: #2563eb; font-weight: 600; text-decoration: none;">
                                🌐 Website ↗
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Pills -->
                    <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: auto; margin-bottom: 14px;">
                        <?php if (!empty($shop['service_policy_verified']) && !empty($shop['allows_onsite_watch'])): ?>
                            <span style="background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 5px; border: 1px solid #bfdbfe;">✓ Xem trực tiếp</span>
                        <?php endif; ?>
                        <?php if (!empty($shop['service_policy_verified']) && !empty($shop['requires_component_signing'])): ?>
                            <span style="background: #f0fdf4; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 5px; border: 1px solid #bbf7d0;">✓ Ký linh kiện</span>
                        <?php endif; ?>
                        <?php if (!empty($shop['student_discount_verified'])): ?>
                            <span style="background: #fff7ed; color: #c2410c; font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 5px; border: 1px solid #fed7aa;">🎓 SV</span>
                        <?php endif; ?>
                    </div>

                    <!-- Nhóm nút hành động: Hàng trên 50/50, Hàng dưới 100% full width -->
                    <div style="margin-top: auto;">
                        <!-- Hàng trên: Gọi ngay & Chỉ đường chia đều 50% / 50% -->
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <a href="tel:<?= preg_replace('/[^0-9]/', '', $shop['phone']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; font-size: 13px; font-weight: 700; color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; border-radius: 8px; text-decoration: none; text-align: center; box-sizing: border-box;">
                                📞 Gọi ngay
                            </a>
                            <a href="<?= htmlspecialchars($shop['map_url'] ?: 'https://maps.google.com') ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; font-size: 13px; font-weight: 700; color: #2563eb; border-color: #bfdbfe; background: #eff6ff; border-radius: 8px; text-decoration: none; text-align: center; box-sizing: border-box;" title="Chỉ đường Google Maps">
                                🗺️ Chỉ đường
                            </a>
                        </div>
                        <!-- Hàng dưới: Xem Bảng Giá & Review full-width 100% -->
                        <div>
                            <a href="shop_detail.php?id=<?= $shop['id'] ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 16px; font-size: 13.5px; font-weight: 800; border-radius: 8px; text-decoration: none; box-sizing: border-box; text-align: center; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.25);">
                                Xem Dịch Vụ & Phản Hồi ➔
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
