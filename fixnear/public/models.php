<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';

$allDevices = RepairAtlasPricing::getAllDevices();
$activeDevice = $_GET['device'] ?? 'phone';
if (!isset($allDevices[$activeDevice])) {
    $activeDevice = 'phone';
}

$activeBrand = $_GET['brand'] ?? '';
$searchQuery = trim($_GET['q'] ?? '');

$pageTitle = 'Danh Mục Dòng Máy & Lỗi Thường Gặp | FixNear';
$pageStyles = ['assets/css/models.css'];

// Nạp danh sách hãng cho thiết bị đang chọn
$availableBrands = RepairAtlasPricing::getBrandsByDevice($activeDevice);
$allBrandsMap = [];
foreach ($availableBrands as $b) {
    $allBrandsMap[$b['id']] = $b['name'];
}
if ($activeBrand !== '' && !isset($allBrandsMap[$activeBrand])) {
    $activeBrand = '';
}

// Nạp từ cùng một nguồn rồi mới áp dụng kết hợp loại thiết bị + hãng + từ khóa.
$allMatchingModels = [];
if ($activeBrand !== '') {
    $allMatchingModels = RepairAtlasPricing::getModelsByBrand($activeDevice, $activeBrand);
} else {
    foreach ($availableBrands as $b) {
        $allMatchingModels = array_merge($allMatchingModels, RepairAtlasPricing::getModelsByBrand($activeDevice, $b['id']));
    }
}

if ($searchQuery !== '') {
    $queryTokens = preg_split('/\s+/u', mb_strtolower($searchQuery, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
    $allMatchingModels = array_values(array_filter($allMatchingModels, function ($model) use ($queryTokens) {
        $haystack = mb_strtolower(implode(' ', [
            $model['name'] ?? '', $model['brand'] ?? '',
            $model['specs']['chip'] ?? '', $model['specs']['cpu'] ?? ''
        ]), 'UTF-8');
        foreach ($queryTokens as $token) {
            if (mb_stripos($haystack, $token, 0, 'UTF-8') === false) return false;
        }
        return true;
    }));
}

// Sắp xếp thông minh: Đời máy mới nhất (2025 -> 2024 -> 2023...), cùng năm thì Flagship / Tier cao lên đầu (P4 -> P1)
usort($allMatchingModels, function($a, $b) {
    $yearA = intval($a['releaseYear'] ?? 0);
    $yearB = intval($b['releaseYear'] ?? 0);
    if ($yearA !== $yearB) {
        return $yearB <=> $yearA;
    }
    $tierOrder = [
        'P4' => 4, 'P3' => 3, 'P2' => 2, 'P1' => 1,
        'L4' => 4, 'L3' => 3, 'L2' => 2, 'L1' => 1,
        'M4' => 4, 'M3' => 3, 'M2' => 2, 'M1' => 1,
        'T4' => 4, 'T3' => 3, 'T2' => 2, 'T1' => 1,
        'W4' => 4, 'W3' => 3, 'W2' => 2, 'W1' => 1,
        'D4' => 4, 'D3' => 3, 'D2' => 2, 'D1' => 1
    ];
    $tA = $tierOrder[$a['tier'] ?? ''] ?? 0;
    $tB = $tierOrder[$b['tier'] ?? ''] ?? 0;
    if ($tA !== $tB) {
        return $tB <=> $tA;
    }
    return strcmp($a['name'] ?? '', $b['name'] ?? '');
});

$perPage = 24;
$totalMatches = count($allMatchingModels);
$totalPages = max(1, (int)ceil($totalMatches / $perPage));
$currentPageNumber = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$modelsList = array_slice($allMatchingModels, ($currentPageNumber - 1) * $perPage, $perPage);
$paginationParams = ['device' => $activeDevice];
if ($activeBrand !== '') $paginationParams['brand'] = $activeBrand;
if ($searchQuery !== '') $paginationParams['q'] = $searchQuery;

// Tính tổng số lượng model cho từng device tab
$deviceCounts = [];
foreach ($allDevices as $devKey => $devInfo) {
    $bList = RepairAtlasPricing::getBrandsByDevice($devKey);
    $total = 0;
    foreach ($bList as $b) {
        $total += $b['modelCount'];
    }
    $deviceCounts[$devKey] = $total;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-container" style="padding-top: 24px; padding-bottom: 60px;">
    <!-- Nút trở lại -->
    <div style="margin-bottom: 20px;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; font-weight: 800; font-size: 13.5px; color: #1e293b; text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: color 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.2s, opacity 0.2s;">
            <span style="color: #ea580c; font-size: 16px; font-weight: 900;">←</span>
            <span>Trở lại Trang Chủ</span>
        </a>
    </div>

    <!-- HERO HEADER HIỆN ĐẠI -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 20px; padding: 36px 32px; margin-bottom: 26px; box-shadow: 0 10px 30px -10px rgba(15,23,42,0.3); position: relative; overflow: hidden; color: #ffffff;">
        <div style="position: absolute; right: -30px; bottom: -30px; font-size: 160px; opacity: 0.04; pointer-events: none; user-select: none;">
            🔍
        </div>
        <div style="max-width: 820px; position: relative; z-index: 1;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(234, 88, 12, 0.16); border: 1px solid rgba(234, 88, 12, 0.35); border-radius: 30px; margin-bottom: 14px;">
                <span style="color: #fb923c; font-weight: 800; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">FixNear RepairAtlas</span>
                <span style="color: #64748b; font-size: 12px;">•</span>
                <span style="color: #e2e8f0; font-size: 12px; font-weight: 600;"><?= array_sum($deviceCounts) ?> model & dữ liệu lỗi tham khảo</span>
            </div>

            <h1 style="font-family: var(--fn-font-heading); font-size: 28px; font-weight: 900; margin: 0 0 10px 0; line-height: 1.25; color: #ffffff;">
                Tra Cứu Dòng Máy & Lỗi Thường Gặp
            </h1>
            <p style="font-size: 14.5px; color: #94a3b8; margin: 0 0 22px 0; line-height: 1.5; max-width: 700px;">
                Tra cứu model, lỗi phần cứng và ba mức giá dự đoán của FixNear. Giá niêm yết chỉ được hiển thị riêng khi có nguồn đối soát cho đúng model và hạng mục.
            </p>

            <!-- Ô TÌM KIẾM NHANH MODEL -->
            <form action="models.php" method="GET" class="fn-model-search-form" style="display: flex; gap: 10px; max-width: 650px;">
                <input type="hidden" name="device" value="<?= htmlspecialchars($activeDevice) ?>">
                <?php if ($activeBrand !== ''): ?><input type="hidden" name="brand" value="<?= htmlspecialchars($activeBrand) ?>"><?php endif; ?>
                <div style="position: relative; flex: 1;">
                    <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #64748b;">🔍</span>
                    <label class="fn-sr-only" for="fn-model-search-input">Tìm model theo tên, hãng hoặc chip</label>
                    <input type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Ví dụ: iPhone 13 Pro Max, ROG Strix, Z Fold, M3…" autocomplete="off" spellcheck="false"
                           style="width: 100%; padding: 13px 16px 13px 44px; border-radius: 12px; border: 1.5px solid #334155; background: #0b1329; color: #ffffff; font-size: 14.5px; outline: none; box-sizing: border-box;"
                           id="fn-model-search-input">
                </div>
                <button type="submit" class="fn-btn fn-btn-primary" style="padding: 0 24px; font-size: 14.5px; font-weight: 700; white-space: nowrap; border-radius: 12px;">
                    Tìm Model
                </button>
            </form>
        </div>
    </div>

    <!-- 6 TABS NHÓM THIẾT BỊ (GRID ĐA TẦNG ĐỐI XỨNG CHỐNG TRÀN CHỮ) -->
    <style>
    .fn-models-tabs-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
        padding: 6px;
        background: #f1f5f9;
        border-radius: 16px;
        margin-bottom: 22px;
    }
    .fn-models-tab-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 10px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13.5px;
        white-space: nowrap;
        transition: color 0.2s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        box-sizing: border-box;
    }
    @media (max-width: 1150px) {
        .fn-models-tabs-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
    }
    @media (max-width: 640px) {
        .fn-models-tabs-grid {
            grid-template-columns: 1fr;
        }
        .fn-models-tab-btn { justify-content: space-between; min-width: 0; white-space: normal; }
    }
    </style>
    <div class="fn-models-tabs-grid">
        <?php foreach ($allDevices as $devKey => $devInfo): 
            $isActive = ($activeDevice === $devKey);
            $count = $deviceCounts[$devKey] ?? 0;
        ?>
            <a href="models.php?device=<?= urlencode($devKey) ?>" 
               class="fn-models-tab-btn"
               style="background: <?= $isActive ? '#ffffff' : 'transparent' ?>; 
                      color: <?= $isActive ? '#ea580c' : '#475569' ?>; 
                      box-shadow: <?= $isActive ? '0 2px 10px rgba(0,0,0,0.08)' : 'none' ?>;">
                <span style="font-size: 17px;"><?= $devInfo['icon'] ?></span>
                <span><?= htmlspecialchars($devInfo['name']) ?></span>
                <span style="font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; 
                             background: <?= $isActive ? '#fff7ed' : '#e2e8f0' ?>; 
                             color: <?= $isActive ? '#ea580c' : '#64748b' ?>;">
                    <?= $count ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- BỘ LỌC THƯƠNG HIỆU (BRAND FILTER CHIPS ĐẲNG CẤP CHỐNG RỚT DÒNG & KHÔNG BỊ CẮT XÉN) -->
    <?php if (count($availableBrands) > 1): ?>
        <div style="margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 4px 0;">
            <span style="font-size: 13px; font-weight: 800; color: #475569; margin-right: 4px; white-space: nowrap;">Hãng:</span>
            <a href="models.php?device=<?= urlencode($activeDevice) ?>" 
               style="padding: 7px 16px; border-radius: 20px; font-size: 12.5px; font-weight: 700; text-decoration: none; transition: color 0.15s, background-color 0.15s, border-color 0.15s, box-shadow 0.15s, transform 0.15s, opacity 0.15s; white-space: nowrap; display: inline-flex; align-items: center;
                      background: <?= empty($activeBrand) ? 'linear-gradient(135deg, #ea580c, #f97316)' : '#ffffff' ?>;
                      color: <?= empty($activeBrand) ? '#ffffff' : '#475569' ?>;
                      border: 1.5px solid <?= empty($activeBrand) ? '#ea580c' : '#e2e8f0' ?>;
                      box-shadow: <?= empty($activeBrand) ? '0 2px 8px rgba(234, 88, 12, 0.3)' : 'none' ?>;">
                Tất cả (<?= $deviceCounts[$activeDevice] ?? 0 ?>)
            </a>
            <?php foreach ($availableBrands as $b): 
                $isBrandActive = ($activeBrand === $b['id']);
            ?>
                <a href="models.php?device=<?= urlencode($activeDevice) ?>&brand=<?= urlencode($b['id']) ?>" 
                   style="padding: 7px 16px; border-radius: 20px; font-size: 12.5px; font-weight: 700; text-decoration: none; transition: color 0.15s, background-color 0.15s, border-color 0.15s, box-shadow 0.15s, transform 0.15s, opacity 0.15s; white-space: nowrap; display: inline-flex; align-items: center;
                          background: <?= $isBrandActive ? 'linear-gradient(135deg, #ea580c, #f97316)' : '#ffffff' ?>;
                          color: <?= $isBrandActive ? '#ffffff' : '#475569' ?>;
                          border: 1.5px solid <?= $isBrandActive ? '#ea580c' : '#e2e8f0' ?>;
                          box-shadow: <?= $isBrandActive ? '0 2px 8px rgba(234, 88, 12, 0.3)' : 'none' ?>;">
                    <?= htmlspecialchars($b['name']) ?> (<?= $b['modelCount'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- TIÊU ĐỀ KẾT QUẢ -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <div style="font-size: 15px; font-weight: 800; color: #1e293b;">
            <?php if (!empty($searchQuery)): ?>
                Kết quả tìm kiếm cho: “<em><?= htmlspecialchars($searchQuery) ?></em>” (<?= $totalMatches ?> dòng máy)
                <a href="models.php?device=<?= urlencode($activeDevice) ?><?= $activeBrand !== '' ? '&brand=' . urlencode($activeBrand) : '' ?>" style="margin-left: 10px; font-size: 13px; color: #ea580c; text-decoration: none; font-weight: 700;">[✕ Xóa tìm kiếm]</a>
            <?php else: ?>
                Danh sách <?= $totalMatches ?> Dòng Máy <?= htmlspecialchars($allDevices[$activeDevice]['name'] ?? '') ?>
                <?= !empty($activeBrand) ? ' — Hãng ' . htmlspecialchars($allBrandsMap[$activeBrand] ?? ucfirst($activeBrand)) : '' ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- DANH SÁCH LƯỚI CARD MODEL TINH TẾ -->
    <?php if (empty($modelsList)): ?>
        <div style="background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 16px; text-align: center; padding: 50px 20px;">
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">
                Không tìm thấy dòng máy phù hợp
            </h3>
            <p style="font-size: 14px; color: #64748b; margin: 0 0 20px 0;">
                Hãy thử tìm kiếm với từ khóa khác hoặc chuyển sang nhóm thiết bị khác.
            </p>
            <a href="models.php?device=<?= urlencode($activeDevice) ?>" class="fn-btn fn-btn-primary" style="border-radius: 10px;">
                Xem lại toàn bộ danh mục
            </a>
        </div>
    <?php else: ?>
        <div class="fn-model-catalog-grid">
            <?php foreach ($modelsList as $m): 
                $specScreen = $m['specs']['screen'] ?? '';
                $specChip = $m['specs']['chip'] ?? ($m['specs']['cpu'] ?? '');
                $issueCount = count($m['knownIssues'] ?? []);
                $faultCount = count($m['supportedFaults'] ?? []);
                $brandSlug = strtolower((string) ($m['brand'] ?? ''));
                $brandLogo = preg_match('/^[a-z0-9_-]+$/', $brandSlug) && is_file(__DIR__ . '/assets/images/brands/' . $brandSlug . '.png')
                    ? 'assets/images/brands/' . $brandSlug . '.png' : null;
            ?>
                <article class="fn-model-catalog-card">
                    <div class="fn-model-catalog-meta">
                        <span class="fn-model-catalog-brand"><?php if ($brandLogo): ?><img src="<?= htmlspecialchars($brandLogo) ?>" width="18" height="18" alt=""><?php endif; ?><?= htmlspecialchars($allBrandsMap[$m['brand']] ?? ucfirst(str_replace('_', ' ', $m['brand']))) ?></span>
                        <span class="fn-model-catalog-tier"><?= htmlspecialchars($m['tier']) ?></span>
                    </div>
                    <h3><a href="model_detail.php?id=<?= urlencode($m['id']) ?>"><?= htmlspecialchars($m['name']) ?></a></h3>
                    <div class="fn-model-catalog-spec">
                        <?php if ($specChip): ?><span><b>Chip</b> <?= htmlspecialchars(mb_strimwidth($specChip, 0, 42, '…')) ?></span><?php endif; ?>
                        <?php if ($specScreen): ?><span><b>Màn hình</b> <?= htmlspecialchars(mb_strimwidth($specScreen, 0, 42, '…')) ?></span><?php endif; ?>
                    </div>
                    <div class="fn-model-catalog-bottom">
                        <span><?= $faultCount ?> hạng mục sửa<?= $issueCount > 0 ? ' · ' . $issueCount . ' lỗi tham khảo' : '' ?></span>
                        <a href="model_detail.php?id=<?= urlencode($m['id']) ?>">Xem chi tiết & giá <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Phân trang danh sách model" style="display:flex;justify-content:center;align-items:center;gap:8px;flex-wrap:wrap;margin-top:28px;">
            <?php if ($currentPageNumber > 1): $paginationParams['page'] = $currentPageNumber - 1; ?>
                <a class="fn-btn fn-btn-secondary fn-btn-sm" href="models.php?<?= htmlspecialchars(http_build_query($paginationParams)) ?>">← Trang trước</a>
            <?php endif; ?>
            <span style="font-weight:800;color:#475569;font-variant-numeric:tabular-nums;">Trang <?= $currentPageNumber ?> / <?= $totalPages ?></span>
            <?php if ($currentPageNumber < $totalPages): $paginationParams['page'] = $currentPageNumber + 1; ?>
                <a class="fn-btn fn-btn-secondary fn-btn-sm" href="models.php?<?= htmlspecialchars(http_build_query($paginationParams)) ?>">Trang sau →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
