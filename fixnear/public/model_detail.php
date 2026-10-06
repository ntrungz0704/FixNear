<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';
require_once __DIR__ . '/../includes/price_evidence.php';

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
$pageStyles = ['assets/css/model-detail-prices.css'];

// Nạp ma trận giá 3 cấp cho tất cả các pan bệnh được hỗ trợ
$allPrices = RepairAtlasPricing::getModelPrices(
    $model['deviceType'],
    $model['tier'],
    $model['brand'],
    $model['supportedFaults'],
    $model['id']
);

$grades = RepairAtlasPricing::getGradeFactors();
$sourceQuotes = FixNearPriceEvidence::forModel((string) $model['id']);
$brandSlug = strtolower((string) $model['brand']);
require_once __DIR__ . '/../includes/brand_assets.php';
$brandLogo = fixnearBrandLogoPath($brandSlug);
$allDevices = RepairAtlasPricing::getAllDevices();
$deviceInfo = $allDevices[$model['deviceType']] ?? ['name' => ucfirst($model['deviceType']), 'icon' => '📱'];
$specLabels = [
    'screen' => 'Màn hình', 'chip' => 'Chip', 'battery' => 'Pin', 'charging' => 'Sạc',
    'ram' => 'RAM', 'cpu' => 'CPU', 'chassis' => 'Khung máy', 'gpu' => 'GPU',
    'storage' => 'Bộ nhớ', 'case' => 'Vỏ máy', 'ports' => 'Cổng kết nối',
    'psu' => 'Nguồn', 'cooling' => 'Tản nhiệt', 'weight' => 'Khối lượng',
    'form' => 'Kiểu dáng', 'power' => 'Nguồn điện', 'water' => 'Chống nước',
    'special' => 'Tính năng', 'keyboard' => 'Bàn phím', 'adapter' => 'Bộ sạc',
    'sensor' => 'Cảm biến', 'pencil' => 'Bút', 'spen' => 'S Pen'
];

// Lấy danh sách cửa hàng hỗ trợ dòng máy này qua db() chuẩn hóa
$matchingShops = db()->getShops(['device' => $model['deviceType']]);
$topShops = array_slice($matchingShops, 0, 3);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
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
                    <span style="font-size: 13px; font-weight: 700; padding: 4px 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; display: inline-flex; align-items: center; gap: 7px;">
                        <?php if ($brandLogo): ?><img src="<?= htmlspecialchars($brandLogo) ?>" alt="" width="26" height="24" style="object-fit: contain; max-width: 26px;"><?php endif; ?>
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
                            <span style="color: var(--fn-dark-muted); text-transform: uppercase; font-size: 11px; font-weight: 700; display: block;"><?= htmlspecialchars($specLabels[$k] ?? ucfirst($k)) ?></span>
                            <strong style="color: var(--fn-dark); font-size: 13.5px;"><?= htmlspecialchars($v) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($model['specsSourceUrl']) && filter_var($model['specsSourceUrl'], FILTER_VALIDATE_URL)): ?>
                    <a href="<?= htmlspecialchars($model['specsSourceUrl']) ?>" target="_blank" rel="noopener noreferrer" style="display:inline-block;margin-top:8px;color:#c2410c;font-size:12px;font-weight:800;">Nguồn thông số ↗</a>
                <?php endif; ?>
            </div>

            <!-- Khối thống kê độ phủ -->
            <div style="background: #fafaf9; border: 1px solid #e7e5e4; padding: 20px; border-radius: 12px; min-width: 240px; text-align: center;">
                <div style="font-size: 12px; font-weight: 700; color: #ea580c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Cơ sở dữ liệu FixNear</div>
                <div style="font-size: 32px; font-weight: 900; color: var(--fn-dark); margin-bottom: 4px;">
                    <?= count($model['supportedFaults']) ?> <span style="font-size: 16px; font-weight: 600; color: #64748b;">hạng mục</span>
                </div>
                <?php if (!empty($model['knownIssues'])): ?>
                <div style="font-size: 13px; color: #16a34a; font-weight: 700; margin-bottom: 12px;">
                    <?= count($model['knownIssues']) ?> lỗi thường gặp trong dữ liệu tham khảo
                </div>
                <?php endif; ?>
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
                        Giả thuyết lỗi trong dữ liệu dự án; cần chẩn đoán trên thiết bị thực tế.
                    </p>
                </div>
                <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 20px;">
                    Chưa kiểm chứng theo model
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                <?php foreach ($model['knownIssues'] as $issue): 
                    $sevColor = '#dc2626'; $sevBg = '#fef2f2'; $sevBorder = '#fecaca'; $sevText = 'Nghiêm trọng';
                    if ($issue['severity'] === 'high') { $sevColor = '#ea580c'; $sevBg = '#fff7ed'; $sevBorder = '#fed7aa'; $sevText = 'Mức chú ý cao'; }
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
                                <strong style="color: #16a34a; display: block; margin-bottom: 3px; font-size: 12px; text-transform: uppercase;">Bước tiếp theo:</strong>
                                <span style="color: #0f172a;">Đề nghị kỹ thuật viên kiểm tra nguyên nhân, nêu rõ linh kiện, giá trọn gói và bảo hành bằng văn bản trước khi sửa.</span>
                            </div>
                        </div>

                        <div style="border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: #16a34a; font-weight: 600;">
                                ✓ Mức dữ liệu: <?= $issue['confidence'] === 'high' ? 'Ưu tiên tham khảo' : 'Cần kiểm tra máy' ?>
                            </span>
                            <a href="search.php?device=<?= urlencode($model['deviceType']) ?>&model=<?= urlencode($model['id']) ?>" class="fn-btn fn-btn-sm fn-btn-primary" style="font-size: 12px; padding: 6px 12px;">
                                📍 Tìm Tiệm Sửa ➔
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Chi phí ước tính và báo giá có nguồn theo từng hạng mục -->
    <section id="bang-gia" class="fn-model-prices" aria-labelledby="fn-model-prices-heading">
        <div class="fn-model-prices-heading">
            <div>
                <span class="fn-model-prices-kicker">CHI PHÍ SỬA CHỮA</span>
                <h2 id="fn-model-prices-heading"><?= htmlspecialchars($model['name']) ?></h2>
                <p>Khoảng ước tính để tham khảo. Cửa hàng xác nhận linh kiện, tổng tiền và bảo hành sau khi kiểm tra máy.</p>
            </div>
            <a href="prices.php?model=<?= urlencode($model['id']) ?>" class="fn-model-prices-all">Xem bảng giá →</a>
        </div>
        <div class="fn-model-prices-legend" aria-label="Ba khoảng dự đoán">
            <span><b>Thấp</b> <small>Khoảng tham khảo</small></span>
            <span><b>Trung bình</b> <small>Khoảng tham khảo</small></span>
            <span><b>Cao</b> <small>Khoảng tham khảo</small></span>
        </div>
        <div class="fn-model-prices-list">
            <?php foreach ($model['supportedFaults'] as $faultId):
                $fault = RepairAtlasPricing::getFault($faultId);
                $pMatrix = $allPrices[$faultId] ?? [];
                if (!$fault || !$pMatrix) continue;
                $linkedQuotes = $sourceQuotes[$faultId] ?? [];
            ?>
                <article class="fn-model-price-row">
                    <div class="fn-model-price-name">
                        <span class="fn-model-price-icon" aria-hidden="true"><?= $fault['icon'] ?? '🔧' ?></span>
                        <div>
                            <h3><?= htmlspecialchars($fault['name'] ?? $faultId) ?></h3>
                            <p><?= htmlspecialchars($fault['description'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="fn-model-price-levels" aria-label="Ba mức ước tính">
                        <?php foreach (['standard' => 'Thấp', 'oem' => 'TB', 'genuine' => 'Cao'] as $gradeId => $level):
                            $price = $pMatrix[$gradeId] ?? null;
                        ?>
                            <div><span><?= $level ?></span><strong><?= $price ? formatPrice($price['min']) . '–' . formatPrice($price['max']) : 'Cần báo giá' ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                    <a class="fn-model-price-shop" href="search.php?<?= htmlspecialchars(http_build_query(['device' => $model['deviceType'], 'model' => $model['id'], 'service_id' => $fault['serviceId'] ?? ''])) ?>">Tìm cửa hàng →</a>
                    <?php if ($linkedQuotes): ?>
                        <details class="fn-model-price-sources">
                            <summary><?= count($linkedQuotes) ?> giá niêm yết có nguồn cho hạng mục này</summary>
                            <div class="fn-model-price-source-list">
                                <?php foreach ($linkedQuotes as $quote): ?>
                                    <div>
                                        <span class="fn-model-price-brand"><?= htmlspecialchars($quote['componentBrand']) ?></span>
                                        <strong><?= htmlspecialchars($quote['componentName']) ?></strong>
                                        <span class="fn-model-price-source-amount"><?= formatPrice($quote['priceVnd']) ?></span>
                                        <small><?= htmlspecialchars($quote['provider']) ?> · BH <?= (int) $quote['warrantyMonths'] ?> tháng</small>
                                        <a href="<?= htmlspecialchars($quote['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer">Nguồn ↗</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="fn-model-prices-note">Giá ước tính không xác định hãng linh kiện hay giá tại cửa hàng. Xem nguồn niêm yết khi có và yêu cầu báo giá trọn gói trước khi sửa.</p>
    </section>

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
            <a href="search.php?device=<?= urlencode($model['deviceType']) ?>&brand=<?= urlencode($model['brand']) ?>" class="fn-btn fn-btn-secondary" style="font-size: 13px;">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
