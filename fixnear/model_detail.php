<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/pricing_engine.php';

$modelId = trim($_GET['id'] ?? '');

if (empty($modelId)) {
    header('Location: models.php');
    exit;
}

$model = RepairAtlasPricing::getModelById($modelId);
if (!$model) {
    header('Location: models.php?error=not_found');
    exit;
}

$pageTitle = $model['name'] . ' — Lỗi Thường Gặp & Tìm Nơi Sửa | FixNear';

// Nạp ma trận giá 3 cấp cho tất cả các pan bệnh được hỗ trợ
$allPrices = RepairAtlasPricing::getModelPrices(
    $model['deviceType'],
    $model['tier'],
    $model['brand'],
    $model['supportedFaults'],
    $model['id']
);

$grades = RepairAtlasPricing::getGradeFactors();
$allDevices = RepairAtlasPricing::getAllDevices();
$deviceInfo = $allDevices[$model['deviceType']] ?? ['name' => ucfirst($model['deviceType']), 'icon' => '📱'];

// Lấy danh sách cửa hàng hỗ trợ dòng máy này qua db() chuẩn hóa
$matchingShops = db()->getShops(['device' => $model['deviceType']]);
$topShops = array_slice($matchingShops, 0, 3);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="fn-container" style="padding-top: 24px; padding-bottom: 60px;">
    <!-- Nút trở lại -->
    <div style="margin-bottom: 20px;">
        <a href="models.php?device=<?= urlencode($model['deviceType']) ?>" class="fn-back-btn">
            <span class="fn-back-arrow">←</span>
            <span>Trở lại Danh Mục <?= htmlspecialchars($deviceInfo['name']) ?></span>
        </a>
    </div>

    <!-- HERO SECTION: THÔNG TIN DÒNG MÁY -->
    <div class="fn-card" style="padding: 28px; margin-bottom: 30px; border-top: 4px solid var(--fn-primary); background: #ffffff;">
        <div class="fn-model-detail-hero" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 20px;">
            <div style="flex: 1; min-width: 280px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                    <span style="font-size: 13px; font-weight: 700; padding: 4px 10px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; border-radius: 6px;">
                        <?= $deviceInfo['icon'] ?> <?= htmlspecialchars($deviceInfo['name']) ?>
                    </span>
                    <span style="font-size: 13px; font-weight: 700; padding: 4px 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px;">
                        Hãng: <?= htmlspecialchars(ucfirst($model['brand'])) ?>
                    </span>
                    <span style="font-size: 13px; font-weight: 700; padding: 4px 10px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px;">
                        Ra mắt: <?= htmlspecialchars($model['releaseYear']) ?>
                    </span>
                    <span style="font-size: 13px; font-weight: 800; padding: 4px 10px; background: #f8fafc; color: #0f172a; border: 1px solid #94a3b8; border-radius: 6px;">
                        Tier: <?= htmlspecialchars($model['tier']) ?>
                    </span>
                </div>

                <h1 style="font-family: var(--fn-font-heading); font-size: 28px; font-weight: 900; color: var(--fn-dark); margin: 0 0 14px 0; line-height: 1.3;">
                    <?= htmlspecialchars($model['name']) ?>
                </h1>

                <!-- Thông số kỹ thuật tóm tắt -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 16px; background: #f8fafc; padding: 14px 18px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <?php foreach ($model['specs'] as $k => $v): ?>
                        <div style="font-size: 13px;">
                            <span style="color: var(--fn-dark-muted); text-transform: uppercase; font-size: 11px; font-weight: 700; display: block;"><?= htmlspecialchars($k) ?></span>
                            <strong style="color: var(--fn-dark); font-size: 13.5px;"><?= htmlspecialchars($v) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Khối thống kê độ phủ -->
            <div style="background: #fafaf9; border: 1px solid #e7e5e4; padding: 20px; border-radius: 12px; min-width: 240px; text-align: center;">
                <div style="font-size: 12px; font-weight: 700; color: #ea580c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Cơ sở dữ liệu FixNear</div>
                <div style="font-size: 32px; font-weight: 900; color: var(--fn-dark); margin-bottom: 4px;">
                    <?= count($model['supportedFaults']) ?> <span style="font-size: 16px; font-weight: 600; color: #64748b;">Pan Bệnh</span>
                </div>
                <div style="font-size: 13px; color: #16a34a; font-weight: 700; margin-bottom: 12px;">
                    <?= count($model['knownIssues']) ?> lỗi thường gặp trong dữ liệu tham khảo
                </div>
                <a href="#bang-gia" class="fn-btn fn-btn-primary" style="width: 100%; display: block; text-align: center; font-size: 14px; padding: 10px 14px;">
                    Xem Hạng Mục Cần Kiểm Tra ↓
                </a>
            </div>
        </div>
    </div>

    <!-- KHỐI 1: LỖI ĐẶC TRƯNG CỦA DÒNG MÁY (KNOWN ISSUES) -->
    <?php if (!empty($model['knownIssues'])): ?>
        <div style="margin-bottom: 36px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="font-family: var(--fn-font-heading); font-size: 22px; font-weight: 900; color: var(--fn-dark); margin: 0 0 4px 0;">
                        ⚠️ Lỗi Đặc Trưng Của Dòng Máy <?= htmlspecialchars($model['name']) ?>
                    </h2>
                    <p style="font-size: 14px; color: var(--fn-dark-muted); margin: 0;">
                        Các sự cố phần cứng thường gặp được mô tả trong bộ dữ liệu tham khảo của dự án.
                    </p>
                </div>
                <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 20px;">
                    Cập nhật tháng 09/2026
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                <?php foreach ($model['knownIssues'] as $issue): 
                    $sevColor = '#dc2626'; $sevBg = '#fef2f2'; $sevBorder = '#fecaca'; $sevText = 'Nghiêm trọng';
                    if ($issue['severity'] === 'high') { $sevColor = '#ea580c'; $sevBg = '#fff7ed'; $sevBorder = '#fed7aa'; $sevText = 'Phổ biến cao'; }
                    if ($issue['severity'] === 'medium') { $sevColor = '#d97706'; $sevBg = '#fffbeb'; $sevBorder = '#fde68a'; $sevText = 'Trung bình'; }
                    if ($issue['severity'] === 'low') { $sevColor = '#0284c7'; $sevBg = '#f0f9ff'; $sevBorder = '#bae6fd'; $sevText = 'Nhẹ'; }
                ?>
                    <div class="fn-card" style="padding: 20px; border-left: 4px solid <?= $sevColor ?>; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 12px;">
                                <h3 style="font-size: 16px; font-weight: 800; color: var(--fn-dark); margin: 0; line-height: 1.4;">
                                    <?= htmlspecialchars($issue['title']) ?>
                                </h3>
                                <span style="font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 4px; background: <?= $sevBg ?>; color: <?= $sevColor ?>; border: 1px solid <?= $sevBorder ?>; white-space: nowrap;">
                                    <?= $sevText ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 12px; font-size: 13.5px; line-height: 1.5;">
                                <strong style="color: #475569; display: block; margin-bottom: 3px; font-size: 12px; text-transform: uppercase;">Triệu chứng nhận biết:</strong>
                                <span style="color: #334155;"><?= htmlspecialchars($issue['symptoms']) ?></span>
                            </div>

                            <div style="margin-bottom: 16px; font-size: 13.5px; line-height: 1.5; background: #f8fafc; padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <strong style="color: #16a34a; display: block; margin-bottom: 3px; font-size: 12px; text-transform: uppercase;">Cách xử lý kỹ thuật:</strong>
                                <span style="color: #0f172a;"><?= htmlspecialchars($issue['solution']) ?></span>
                            </div>
                        </div>

                        <div style="border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: #16a34a; font-weight: 600;">
                                ✓ Mức dữ liệu: <?= $issue['confidence'] === 'high' ? 'Ưu tiên tham khảo' : 'Cần kiểm tra máy' ?>
                            </span>
                            <a href="search.php?device=<?= urlencode($model['deviceType']) ?>&keyword=<?= urlencode($model['name']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-sm fn-btn-primary" style="font-size: 12px; padding: 6px 12px;">
                                📍 Tìm Tiệm Sửa ➔
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- KHỐI 2: BẢNG BÁO GIÁ ƯỚC TÍNH 3 CẤP LINH KIỆN -->
    <div id="bang-gia" style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="font-family: var(--fn-font-heading); font-size: 22px; font-weight: 900; color: var(--fn-dark); margin: 0 0 4px 0;">
                    📊 Hạng Mục Cần Cửa Hàng Kiểm Tra — <?= htmlspecialchars($model['name']) ?>
                </h2>
                <p style="font-size: 14px; color: var(--fn-dark-muted); margin: 0;">
                    Khoảng giá linh kiện ước tính 3 cấp độ (Tiêu chuẩn, OEM, Chính hãng) theo phân khúc dòng máy tại TP.HCM.
                </p>
            </div>
            
            <button type="button" class="fn-btn fn-btn-sm fn-btn-secondary" onclick="document.getElementById('fn-report-modal').classList.add('active');" style="color: #dc2626; border-color: #fecaca; background: #fff5f5;">
                🚩 Báo giá lệch thực tế
            </button>
        </div>

        <!-- Chú thích 3 cấp linh kiện -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div style="background: #ffffff; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; border-top: 3px solid #64748b;">
                <div style="font-weight: 800; font-size: 13px; color: #334155; margin-bottom: 2px;">🟢 Tiêu Chuẩn (Standard / Loại 1)</div>
                <div style="font-size: 12px; color: #64748b;">Yêu cầu cửa hàng ghi rõ nhà sản xuất, tình trạng và bảo hành.</div>
            </div>
            <div style="background: #ffffff; padding: 12px 16px; border-radius: 8px; border: 1px solid #fed7aa; border-top: 3px solid #ea580c;">
                <div style="font-weight: 800; font-size: 13px; color: #ea580c; margin-bottom: 2px;">🔵 OEM Cao Cấp (Khuyên dùng)</div>
                <div style="font-size: 12px; color: #64748b;">Phân loại ước tính OEM; nguồn gốc và bảo hành phải được cửa hàng xác nhận.</div>
            </div>
            <div style="background: #ffffff; padding: 12px 16px; border-radius: 8px; border: 1px solid #bfdbfe; border-top: 3px solid #2563eb;">
                <div style="font-weight: 800; font-size: 13px; color: #2563eb; margin-bottom: 2px;">🟣 Chính Hãng / Bóc Máy Zin</div>
                <div style="font-size: 12px; color: #64748b;">Phân loại dự kiến chính hãng/bóc máy; yêu cầu hóa đơn và điều khoản bảo hành cụ thể.</div>
            </div>
        </div>

        <!-- Danh sách bảng giá từng lỗi -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($model['supportedFaults'] as $faultId): 
                $fault = RepairAtlasPricing::getFault($faultId);
                $pMatrix = $allPrices[$faultId] ?? [];
                if (empty($pMatrix)) continue;
                $std = $pMatrix['standard'] ?? null;
                $oem = $pMatrix['oem'] ?? null;
                $gen = $pMatrix['genuine'] ?? null;
            ?>
                <div class="fn-card" style="padding: 20px; transition: transform 0.15s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 28px; width: 44px; height: 44px; background: #fff7ed; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <?= $fault['icon'] ?? '🔧' ?>
                            </span>
                            <div>
                                <h3 style="font-size: 17px; font-weight: 800; color: var(--fn-dark); margin: 0 0 4px 0;">
                                    <?= htmlspecialchars($fault['name'] ?? $faultId) ?>
                                </h3>
                                <p style="font-size: 13px; color: var(--fn-dark-muted); margin: 0;">
                                    <?= htmlspecialchars($fault['description'] ?? '') ?>
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 12px; padding: 4px 10px; background: #f1f5f9; color: #475569; border-radius: 6px; font-weight: 600;">
                                ⏱️ <?= htmlspecialchars($std['turnaround'] ?? '30 - 60 phút') ?>
                            </span>
                            <a href="search.php?device=<?= urlencode($model['deviceType']) ?>&keyword=<?= urlencode($model['name']) ?>&service_id=<?= urlencode($fault['serviceId'] ?? 1) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-sm fn-btn-primary" style="white-space: nowrap;">
                                📍 Tìm Tiệm Sửa Lỗi Này ➔
                            </a>
                        </div>
                    </div>

                    <!-- 3 Cột khoảng giá linh kiện -->
                    <div class="fn-model-price-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                        <!-- Standard -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 700; color: #64748b;">Tiêu Chuẩn (Loại 1)</span>
                                <span style="font-size: 11px; padding: 2px 6px; background: #e2e8f0; color: #475569; border-radius: 4px; font-weight: 700;">BH <?= $std['warrantyMonths'] ?? 6 ?> tháng</span>
                            </div>
                            <div style="font-size: 18px; font-weight: 900; color: var(--fn-dark);">
                                <?= !empty($std['min']) ? formatPrice($std['min']) . ' – ' . formatPrice($std['max']) : 'Liên hệ báo giá' ?>
                            </div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Linh kiện phổ biến • Thay lấy liền</div>
                        </div>

                        <!-- OEM -->
                        <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 14px; border-radius: 8px; position: relative;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 800; color: #ea580c;">OEM Cao Cấp</span>
                                <span style="font-size: 11px; padding: 2px 6px; background: #ffedd5; color: #c2410c; border-radius: 4px; font-weight: 700;">BH <?= $oem['warrantyMonths'] ?? 9 ?> tháng</span>
                            </div>
                            <div style="font-size: 18px; font-weight: 900; color: #c2410c;">
                                <?= !empty($oem['min']) ? formatPrice($oem['min']) . ' – ' . formatPrice($oem['max']) : 'Liên hệ báo giá' ?>
                            </div>
                            <div style="font-size: 11.5px; color: #ea580c; margin-top: 4px;">Foxconn / Pisen OEM • Khuyên dùng</div>
                        </div>

                        <!-- Genuine -->
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 800; color: #2563eb;">Chính Hãng / Zin</span>
                                <span style="font-size: 11px; padding: 2px 6px; background: #dbeafe; color: #1d4ed8; border-radius: 4px; font-weight: 700;">BH <?= $gen['warrantyMonths'] ?? 12 ?> tháng</span>
                            </div>
                            <div style="font-size: 18px; font-weight: 900; color: #1e40af;">
                                <?= !empty($gen['min']) ? formatPrice($gen['min']) . ' – ' . formatPrice($gen['max']) : 'Liên hệ báo giá' ?>
                            </div>
                            <div style="font-size: 11.5px; color: #2563eb; margin-top: 4px;">Bóc máy zin / Chuẩn hãng • BH 12 tháng</div>
                        </div>
                    </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KHỐI 3: GỢI Ý CỬA HÀNG UY TÍN CHUYÊN DÒNG MÁY NÀY -->
    <div style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="font-family: var(--fn-font-heading); font-size: 20px; font-weight: 900; color: var(--fn-dark); margin: 0 0 4px 0;">
                    📍 Bản Ghi Cửa Hàng Có Hỗ Trợ Nhóm Thiết Bị Này
                </h2>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); margin: 0;">
                    Các bản ghi phù hợp với thiết bị <?= htmlspecialchars($deviceInfo['name']) ?>; vui lòng xác nhận địa chỉ, giờ mở cửa và dịch vụ trực tiếp.
                </p>
            </div>
            <a href="search.php?device=<?= urlencode($model['deviceType']) ?>&keyword=<?= urlencode($model['brand']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary" style="font-size: 13px;">
                Xem tất cả cửa hàng ➔
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
            <?php foreach ($topShops as $shop): ?>
                <?php
                    $sRating = !empty($shop['google_rating_verified']) ? floatval($shop['google_rating']) : null;
                    $sReviews = !empty($shop['google_rating_verified']) ? intval($shop['google_reviews_count']) : null;
                    $sHours = $shop['opening_hours'] ?? $shop['hours'] ?? '08:00 - 21:00 (Cả tuần & Ngày lễ)';
                    $sLat = $shop['latitude'] ?? $shop['lat'] ?? 10.7769;
                    $sLng = $shop['longitude'] ?? $shop['lng'] ?? 106.7009;
                    $sVerified = !empty($shop['source_verified']);
                    $mapUrl = !empty($shop['map_url']) ? $shop['map_url'] : "https://www.google.com/maps/dir/?api=1&destination={$sLat},{$sLng}";
                ?>
                <div class="fn-shop-card" style="margin-bottom: 0;">
                    <div class="fn-shop-card-header">
                        <div>
                            <h3 class="fn-shop-title">
                                <a href="shop_detail.php?id=<?= $shop['id'] ?>" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">
                                    <?= htmlspecialchars($shop['name']) ?>
                                </a>
                                <?php if ($sVerified): ?>
                                    <span class="fn-badge fn-badge-verified" title="Bản ghi có cờ xác minh trong dữ liệu FixNear">✓ Có cờ xác minh</span>
                                <?php endif; ?>
                            </h3>
                            <div class="fn-shop-rating">
                                <?php if ($sRating !== null): ?>
                                    <span class="fn-stars">★ <?= number_format($sRating, 1) ?></span>
                                    <span class="fn-review-count">(<?= number_format($sReviews, 0, ',', '.') ?> đánh giá Google)</span>
                                <?php else: ?>
                                    <span class="fn-review-count">Google: chưa đối soát</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="fn-shop-info">
                        <div class="fn-shop-info-row">
                            <span class="fn-shop-info-icon">📍</span>
                            <span><?= htmlspecialchars($shop['address']) ?>, <strong><?= htmlspecialchars($shop['district']) ?></strong></span>
                        </div>
                        <div class="fn-shop-info-row">
                            <span class="fn-shop-info-icon">⏰</span>
                            <span><?= htmlspecialchars($sHours) ?></span>
                        </div>
                        <div class="fn-shop-info-row">
                            <span class="fn-shop-info-icon">📞</span>
                            <span style="font-weight:700; color:var(--fn-primary);"><?= htmlspecialchars($shop['phone']) ?></span>
                        </div>
                    </div>

                    <!-- Nút bấm hành động chuẩn FixNear: Hàng trên 50/50, Hàng dưới 100% full width, Target blank -->
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; gap: 8px;">
                            <a href="tel:<?= preg_replace('/[^0-9]/', '', $shop['phone']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-sm fn-btn-primary" style="flex: 1; text-align: center; justify-content: center;">
                                📞 Gọi ngay
                            </a>
                            <a href="<?= htmlspecialchars($mapUrl) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-sm fn-btn-secondary" style="flex: 1; text-align: center; justify-content: center;">
                                🗺️ Chỉ đường
                            </a>
                        </div>
                        <a href="shop_detail.php?id=<?= $shop['id'] ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-sm" style="width: 100%; text-align: center; justify-content: center; background: linear-gradient(135deg, #ea580c, #f97316); color: #ffffff; border: none; font-weight: 800; padding: 10px 14px; border-radius: 8px; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.25);">
                            Xem Dịch Vụ & Phản Hồi ➔
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
