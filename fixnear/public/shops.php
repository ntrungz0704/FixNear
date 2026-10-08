<?php
$pageTitle = "Danh Sách Cửa Hàng Sửa Chữa Phù Hợp — FixNear";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';

// Nhận diện trạng thái vị trí người dùng (Session -> URL -> Cookie)
$userLoc = getUserLocation();
$user_lat = $userLoc['lat'] ?? null;
$user_lng = $userLoc['lng'] ?? null;
$loc_name = $userLoc['name'] ?? null;
$isLocated = !empty($user_lat) && !empty($user_lng);

// Nhận tham số ngữ cảnh từ Wizard hoặc URL
$device = trim($_GET['device'] ?? '');
$brand = trim($_GET['brand'] ?? '');
$modelId = trim($_GET['model'] ?? '');
$faultId = trim($_GET['fault_id'] ?? '');
$serviceId = (int)($_GET['service_id'] ?? 0);
$issueName = trim($_GET['issue_name'] ?? '');

// Tham số lọc bổ sung
$districtFilter = trim($_GET['district'] ?? '');
$studentOnly = !empty($_GET['student']);
$favoriteFilter = !empty($_GET['favorite']);
$searchKw = trim($_GET['q'] ?? '');

// Bảng ánh xạ serviceId <-> faultId chuẩn FixNear
$serviceToFaultMap = [
    1 => 'screen',
    2 => 'glass-press',
    3 => 'battery',
    4 => 'charging-port',
    5 => 'water-damage',
    6 => 'mainboard',
    7 => 'camera',
    8 => 'speaker',
    9 => 'mic',
    10 => 'keyboard',
    11 => 'trackpad',
    12 => 'hinge',
    13 => 'ssd-upgrade',
    14 => 'ram-upgrade',
    15 => 'thermal-cleaning',
    16 => 'software',
    17 => 'data-recovery',
    18 => 'general-check'
];

if (empty($faultId) && $serviceId > 0 && isset($serviceToFaultMap[$serviceId])) {
    $faultId = $serviceToFaultMap[$serviceId];
}

// Lấy thông tin pan bệnh từ faults.json
$faultData = null;
if (!empty($faultId)) {
    $faultData = RepairAtlasPricing::getFault($faultId);
    if ($faultData && empty($serviceId) && !empty($faultData['serviceId'])) {
        $serviceId = (int)$faultData['serviceId'];
    }
}

// Xác định chế độ hiển thị: Có ngữ cảnh chọn lỗi/model hay xem toàn bộ danh bạ
$isResultMode = (!empty($faultId) || !empty($serviceId) || !empty($modelId) || !empty($issueName));

// Lấy thông tin Model chi tiết nếu có
$modelData = null;
if (!empty($modelId)) {
    $modelData = RepairAtlasPricing::getModelById($modelId);
    if (!$modelData) {
        $searchRes = RepairAtlasPricing::searchModels($modelId);
        if (!empty($searchRes)) {
            $modelData = $searchRes[0];
        }
    }
}

$modelDisplayName = $modelData['name'] ?? ($modelId ? ucwords(str_replace('-', ' ', $modelId)) : '');
$brandDisplayName = $modelData['brand'] ?? ($brand ? ucfirst($brand) : '');
$tier = $modelData['tier'] ?? 'P3';

// Chuẩn hóa loại thiết bị cho pricing engine
$deviceNormalized = 'phone';
if ($device === 'laptop' || $device === 'win_laptop') $deviceNormalized = 'win_laptop';
elseif ($device === 'mac' || $device === 'macbook') $deviceNormalized = 'macbook';
elseif ($device === 'tablet') $deviceNormalized = 'tablet';
elseif ($device === 'pc') $deviceNormalized = 'pc_desktop';
elseif ($device === 'smartwatch') $deviceNormalized = 'smartwatch';
elseif (!empty($modelData['category'])) $deviceNormalized = $modelData['category'];

// Tính toán báo giá tham khảo 3 cấp linh kiện (Dynamic 100% từ Pricing Engine, KHÔNG hardcode)
$gradePrices = [];
if (!empty($faultId)) {
    $grades = ['standard', 'oem', 'genuine'];
    foreach ($grades as $g) {
        $calc = RepairAtlasPricing::calculatePrice(
            $deviceNormalized,
            $tier,
            $brandDisplayName ?: 'apple',
            $faultId,
            $g,
            $modelData['id'] ?? null
        );
        if ($calc) {
            $gradePrices[$g] = $calc;
        }
    }

    // Fallback nếu tier cụ thể chưa có giá: thử tier P2
    if (empty($gradePrices)) {
        foreach ($grades as $g) {
            $calc = RepairAtlasPricing::calculatePrice($deviceNormalized, 'P2', 'generic', $faultId, $g);
            if ($calc) {
                $gradePrices[$g] = $calc;
            }
        }
    }
}

// Lấy danh sách yêu thích của thành viên
$currentUserId = (int)(currentUser()['id'] ?? 0);
$userFavorites = $currentUserId > 0 ? db()->getFavorites($currentUserId) : [];

// Truy vấn danh sách cửa hàng
$filters = [
    'device' => $device,
    'service_id' => $serviceId > 0 ? $serviceId : null,
    'district' => $districtFilter,
    'keyword' => $searchKw,
    'user_lat' => $user_lat,
    'user_lng' => $user_lng
];

$allShops = db()->getShops($filters);

// Lọc bổ sung: yêu thích, ưu đãi sinh viên
$filteredShops = array_filter($allShops, function ($shop) use ($favoriteFilter, $userFavorites, $studentOnly) {
    if ($favoriteFilter && !in_array((int)$shop['id'], array_map('intval', $userFavorites), true)) {
        return false;
    }
    if ($studentOnly && empty($shop['student_discount_verified'])) {
        return false;
    }
    return true;
});

// Sắp xếp: Nếu có vị trí GPS -> sắp xếp theo khoảng cách gần nhất
// Nếu chưa có vị trí -> sắp xếp theo điểm Google đối soát -> tên A-Z
if ($isLocated) {
    usort($filteredShops, function ($a, $b) {
        $distA = $a['distance_km'] ?? 9999.0;
        $distB = $b['distance_km'] ?? 9999.0;
        return $distA <=> $distB;
    });
} else {
    usort($filteredShops, function ($a, $b) {
        if (!empty($a['google_rating_verified']) && !empty($b['google_rating_verified'])) {
            return ((float)$b['google_rating'] <=> (float)$a['google_rating'])
                ?: ((int)$b['google_reviews_count'] <=> (int)$a['google_reviews_count']);
        }
        return strnatcasecmp($a['name'], $b['name']);
    });
}

$shopCount = count($filteredShops);
$hasVerifiedRating = (bool)array_filter($filteredShops, static fn($shop) => !empty($shop['google_rating_verified']));
$shopsPerPage = 9;
$shopPageCount = max(1, (int)ceil($shopCount / $shopsPerPage));
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$shopPage = min($requestedPage, $shopPageCount);
$visibleShops = array_slice($filteredShops, ($shopPage - 1) * $shopsPerPage, $shopsPerPage);
$shopPageUrl = static function (int $page): string {
    $params = array_intersect_key($_GET, array_flip(['device', 'brand', 'model', 'fault_id', 'service_id', 'issue_name', 'district', 'student', 'favorite', 'q', 'user_lat', 'user_lng', 'loc_name']));
    $params['page'] = $page;
    return 'shops.php?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) . '#shop-results';
};

// Bảng tên hiển thị thiết bị
$deviceNames = [
    'phone' => 'Điện thoại',
    'laptop' => 'Laptop Windows',
    'win_laptop' => 'Laptop Windows',
    'mac' => 'MacBook / Mac',
    'macbook' => 'MacBook / Mac',
    'tablet' => 'Máy tính bảng',
    'smartwatch' => 'Đồng hồ thông minh',
    'pc' => 'Máy tính để bàn PC'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-container" style="padding: 24px 20px 80px;">

    <!-- Thanh Điều Hướng & Trở Lại -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #1e293b; font-size: 13.5px; font-weight: 800; text-decoration: none; padding: 8px 16px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <span style="font-size: 16px; color: #ea580c; font-weight: 900;">←</span>
            <span>Trở lại Trang Chủ</span>
        </a>

        <?php if ($isResultMode): ?>
            <a href="index.php#fn-wizard-box" style="display: inline-flex; align-items: center; gap: 6px; color: #2563eb; font-size: 13px; font-weight: 700; text-decoration: none; padding: 7px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                🔄 Chọn lại thiết bị hoặc lỗi khác
            </a>
        <?php endif; ?>
    </div>

    <!-- Header Tiêu Đề Kết Quả & Ngữ Cảnh -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px 28px; margin-bottom: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); text-align: left;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; font-size: 12px; font-weight: 800; padding: 3px 10px; border-radius: 20px; margin-bottom: 8px; text-transform: uppercase;">
                    <?= $isResultMode ? '⚡ Kết quả chẩn đoán & tìm kiếm' : '📋 Danh bạ cửa hàng sửa chữa' ?>
                </div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 26px; font-weight: 900; color: #0f172a; margin: 0 0 6px 0; line-height: 1.3;">
                    Tìm thấy <span style="color: #ea580c;"><?= count($filteredShops) ?></span> Cửa Hàng Phù Hợp
                </h1>
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.5;">
                    <?= $isResultMode ? 'Dưới đây là danh sách cửa hàng hỗ trợ sửa chữa theo đúng thiết bị và lỗi bạn đã chọn.' : 'Bộ dữ liệu các cơ sở sửa chữa công nghệ tại TP.HCM kèm chính sách minh bạch.' ?>
                </p>
            </div>

            <!-- GPS Status Bar trong Header -->
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                <?php if ($isLocated): ?>
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1.5px solid #a7f3d0; color: #059669; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                        <span class="fn-pulse-dot" style="background:#10b981; width:8px; height:8px;"></span>
                        <span>Đang tìm quanh: <strong><?= htmlspecialchars($loc_name ?: 'Vị trí của bạn') ?></strong></span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" onclick="openLocationModal()" style="font-size: 12px; padding: 4px 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; font-weight: 700; cursor: pointer;">
                            ⚙️ Đổi vị trí
                        </button>
                        <button type="button" onclick="toggleGPS()" style="font-size: 12px; padding: 4px 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; color: #dc2626; font-weight: 700; cursor: pointer;">
                            ✕ Tắt GPS
                        </button>
                    </div>
                <?php else: ?>
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: #fff7ed; border: 1.5px solid #fed7aa; color: #c2410c; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                        <span>📍 Chưa xác định vị trí của bạn</span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" onclick="triggerDeviceGPS(this)" style="font-size: 12px; padding: 5px 12px; background: #ea580c; border: 1px solid #ea580c; border-radius: 6px; color: #ffffff; font-weight: 800; cursor: pointer; box-shadow: 0 2px 6px rgba(234,88,12,0.3);">
                            🛰️ Bật vị trí
                        </button>
                        <button type="button" onclick="openLocationModal()" style="font-size: 12px; padding: 5px 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; font-weight: 700; cursor: pointer;">
                            🏙️ Nhập khu vực
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges Ngữ Cảnh Đã Chọn -->
        <?php if ($isResultMode): ?>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <span style="font-size: 12.5px; font-weight: 800; color: #475569; text-transform: uppercase;">Bộ tiêu chí:</span>
            
            <?php if (!empty($device)): ?>
                <span style="display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 12.5px; font-weight: 700; padding: 3px 10px; border-radius: 14px;">
                    📱 <?= htmlspecialchars($deviceNames[$device] ?? ucfirst($device)) ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($brandDisplayName)): ?>
                <span style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; font-size: 12.5px; font-weight: 700; padding: 3px 10px; border-radius: 14px;">
                    🏷️ <?= htmlspecialchars($brandDisplayName) ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($modelDisplayName)): ?>
                <span style="display: inline-flex; align-items: center; gap: 4px; background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; font-size: 12.5px; font-weight: 700; padding: 3px 10px; border-radius: 14px;">
                    💻 <?= htmlspecialchars($modelDisplayName) ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($faultData['name']) || !empty($issueName)): ?>
                <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 12.5px; font-weight: 800; padding: 3px 10px; border-radius: 14px;">
                    🔧 <?= htmlspecialchars($faultData['name'] ?? $issueName) ?>
                </span>
            <?php endif; ?>

            <a href="shops.php" style="margin-left: auto; font-size: 12.5px; color: #64748b; text-decoration: underline; font-weight: 600;">
                ✕ Bỏ lọc ngữ cảnh
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Summary Card: Thông Tin Lỗi & Báo Giá Tham Khảo (Chỉ hiển thị khi có chọn Lỗi/Pan bệnh) -->
    <?php if (!empty($faultId) && (!empty($faultData) || !empty($gradePrices))): ?>
    <div style="background: #ffffff; border: 2px solid #ea580c; border-radius: 18px; padding: 24px; margin-bottom: 32px; box-shadow: 0 8px 30px rgba(234, 88, 12, 0.08); text-align: left;">
        <div style="display: grid; grid-template-columns: 1.1fr 1.9fr; gap: 24px; align-items: stretch;">
            
            <!-- Cột trái: Thông tin pan bệnh -->
            <div style="display: flex; flex-direction: column; justify-content: space-between; border-right: 1px solid #f1f5f9; padding-right: 20px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 32px;"><?= htmlspecialchars($faultData['icon'] ?? '🔧') ?></span>
                        <div>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #ea580c; background: #fff7ed; padding: 2px 8px; border-radius: 10px;">
                                Pan bệnh đã chọn
                            </span>
                            <h2 style="font-family: var(--fn-font-heading); font-size: 20px; font-weight: 900; color: #0f172a; margin: 4px 0 0 0; line-height: 1.3;">
                                <?= htmlspecialchars($faultData['name'] ?? ($issueName ?: 'Sửa chữa linh kiện')) ?>
                            </h2>
                        </div>
                    </div>

                    <div style="display: inline-block; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 6px; margin: 8px 0 12px;">
                        ⚠️ Mức độ: Cần kiểm tra kỹ thuật trực tiếp trước khi thay thế
                    </div>

                    <p style="font-size: 13.5px; color: #475569; line-height: 1.6; margin: 0 0 14px 0;">
                        <?= htmlspecialchars($faultData['description'] ?? 'Khắc phục các lỗi phần cứng hoặc tiếp xúc chập chờn, khôi phục trạng thái hoạt động tiêu chuẩn.') ?>
                    </p>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 12.5px; color: #334155; line-height: 1.5;">
                    💡 <strong>Lưu ý cho sinh viên:</strong> Luôn yêu cầu cửa hàng báo giá trọn gói (bao gồm công thợ) và hỏi rõ điều kiện bảo hành trước khi giao máy.
                </div>
            </div>

            <!-- Cột phải: Bảng giá tham khảo 3 cấp linh kiện (Dynamic RepairAtlas) -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                        <h3 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 900; color: #0f172a; margin: 0;">
                            GIÁ DỰ ĐOÁN TỪ MÔ HÌNH FIXNEAR
                        </h3>
                        <span style="font-size: 12px; color: #64748b;">
                            Ước tính theo mô hình RepairAtlas <?= $modelDisplayName ? 'cho ' . htmlspecialchars($modelDisplayName) : '' ?>
                        </span>
                    </div>
                    <span style="font-size: 11px; background: #ecfdf5; color: #047857; font-weight: 800; padding: 3px 8px; border-radius: 6px; border: 1px solid #a7f3d0;">
                        Ước tính · hỏi giá tại cửa hàng
                    </span>
                </div>

                <!-- 3 Thẻ Linh Kiện -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 12px;">
                    
                    <!-- Cấp 1: Tiêu chuẩn -->
                    <?php $pStd = $gradePrices['standard'] ?? null; ?>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px 12px; text-align: left;">
                        <div style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                            Mức thấp
                        </div>
                        <div style="font-size: 15px; font-weight: 900; color: #0f172a; line-height: 1.3; margin-bottom: 6px;">
                            <?= $pStd ? RepairAtlasPricing::formatVND($pStd['min']) . ' - ' . RepairAtlasPricing::formatVND($pStd['max']) : 'Đang cập nhật' ?>
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; line-height: 1.4;">
                            Thời gian và bảo hành cần cửa hàng xác nhận
                        </div>
                    </div>

                    <!-- Cấp 2: OEM Cao cấp -->
                    <?php $pOem = $gradePrices['oem'] ?? null; ?>
                    <div style="background: #eff6ff; border: 1.5px solid #93c5fd; border-radius: 12px; padding: 14px 12px; text-align: left; position: relative;">
                        <span style="position: absolute; top: -8px; right: 8px; background: #2563eb; color: #fff; font-size: 9.5px; font-weight: 900; padding: 1px 6px; border-radius: 8px;">
                            MÔ HÌNH
                        </span>
                        <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase; margin-bottom: 4px;">
                            Mức trung bình
                        </div>
                        <div style="font-size: 15px; font-weight: 900; color: #1e3a8a; line-height: 1.3; margin-bottom: 6px;">
                            <?= $pOem ? RepairAtlasPricing::formatVND($pOem['min']) . ' - ' . RepairAtlasPricing::formatVND($pOem['max']) : 'Đang cập nhật' ?>
                        </div>
                        <div style="font-size: 11.5px; color: #3b82f6; line-height: 1.4;">
                            Chưa xác minh hãng linh kiện
                        </div>
                    </div>

                    <!-- Cấp 3: Chính hãng Zin -->
                    <?php $pGen = $gradePrices['genuine'] ?? null; ?>
                    <div style="background: #fff7ed; border: 1.5px solid #fdba74; border-radius: 12px; padding: 14px 12px; text-align: left;">
                        <div style="font-size: 11px; font-weight: 800; color: #c2410c; text-transform: uppercase; margin-bottom: 4px;">
                            Mức cao
                        </div>
                        <div style="font-size: 15px; font-weight: 900; color: #9a3412; line-height: 1.3; margin-bottom: 6px;">
                            <?= $pGen ? RepairAtlasPricing::formatVND($pGen['min']) . ' - ' . RepairAtlasPricing::formatVND($pGen['max']) : 'Đang cập nhật' ?>
                        </div>
                        <div style="font-size: 11.5px; color: #ea580c; line-height: 1.4;">
                            Không mặc định là linh kiện chính hãng
                        </div>
                    </div>
                </div>

                <div style="font-size: 11.5px; color: #94a3b8; line-height: 1.4;">
                    * Đây là dự đoán từ ma trận giá, không phải báo giá của cửa hàng. Yêu cầu báo giá trọn gói, tên linh kiện và bảo hành trước khi sửa.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Thanh Bộ Lọc & Tìm Kiếm Khu Vực Nhanh -->
    <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: 14px; padding: 18px 20px; margin-bottom: 28px; box-shadow: var(--fn-shadow-sm);">
        <form action="shops.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <!-- Giữ nguyên context ẩn nếu có -->
            <?php if ($device): ?><input type="hidden" name="device" value="<?= htmlspecialchars($device) ?>"><?php endif; ?>
            <?php if ($brand): ?><input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>"><?php endif; ?>
            <?php if ($modelId): ?><input type="hidden" name="model" value="<?= htmlspecialchars($modelId) ?>"><?php endif; ?>
            <?php if ($faultId): ?><input type="hidden" name="fault_id" value="<?= htmlspecialchars($faultId) ?>"><?php endif; ?>
            <?php if ($serviceId > 0): ?><input type="hidden" name="service_id" value="<?= (int)$serviceId ?>"><?php endif; ?>
            <?php if ($issueName): ?><input type="hidden" name="issue_name" value="<?= htmlspecialchars($issueName) ?>"><?php endif; ?>

            <div style="flex: 1.5; min-width: 220px;">
                <input type="text" name="q" value="<?= htmlspecialchars($searchKw) ?>" class="fn-input" placeholder="🔍 Tìm tên tiệm, đường hoặc khu vực..." style="padding: 10px 14px;">
            </div>

            <div style="flex: 1; min-width: 160px;">
                <select name="district" class="fn-select" style="padding: 10px 14px;">
                    <option value="">Tất cả khu vực TP.HCM</option>
                    <?php
                    $allDistricts = [
                        'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
                        'Quận 10', 'Quận 11', 'Quận 12', 'Quận Bình Tân', 'Quận Bình Thạnh',
                        'Quận Gò Vấp', 'Quận Phú Nhuận', 'Quận Tân Bình', 'Quận Tân Phú', 'TP. Thủ Đức'
                    ];
                    foreach ($allDistricts as $d): ?>
                        <option value="<?= $d ?>" <?= $districtFilter === $d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #ea580c; cursor: pointer; white-space: nowrap;">
                <input type="checkbox" name="student" value="1" <?= $studentOnly ? 'checked' : '' ?> style="accent-color: #ea580c; width: 16px; height: 16px;">
                Ưu đãi sinh viên
            </label>

            <?php if (isLoggedIn()): ?>
                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #dc2626; cursor: pointer; white-space: nowrap;">
                    <input type="checkbox" name="favorite" value="1" <?= $favoriteFilter ? 'checked' : '' ?> style="accent-color: #dc2626; width: 16px; height: 16px;">
                    ❤️ Đã lưu (<?= count($userFavorites) ?>)
                </label>
            <?php endif; ?>

            <button type="submit" class="fn-btn fn-btn-primary" style="padding: 10px 20px; font-size: 13.5px; font-weight: 800;">
                Lọc Tiệm
            </button>

            <?php if ($districtFilter || $studentOnly || $favoriteFilter || $searchKw): ?>
                <a href="shops.php<?= $isResultMode ? '?device=' . urlencode($device) . '&fault_id=' . urlencode($faultId) . '&model=' . urlencode($modelId) : '' ?>" class="fn-btn fn-btn-secondary" style="font-size: 13px; text-decoration: none;">
                    ✕ Xóa lọc phụ
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Thông báo thứ tự sắp xếp -->
    <div id="shop-results" class="fn-shop-results-heading" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 14.5px; font-weight: 800; color: #0f172a;">
            Danh sách cửa hàng phù hợp (<?= $shopCount ?> bản ghi)
            <?php if ($shopCount > 0): ?><span class="fn-shop-page-summary">· Hiển thị <?= ($shopPage - 1) * $shopsPerPage + 1 ?>–<?= min($shopPage * $shopsPerPage, $shopCount) ?></span><?php endif; ?>
        </div>
        <div style="font-size: 12.5px; color: #64748b;">
            Thứ tự: <strong><?= $isLocated ? '📍 Khoảng cách gần bạn nhất' : ($hasVerifiedRating ? 'Đánh giá có nguồn / tên A–Z' : 'Tên A–Z') ?></strong>
        </div>
    </div>

    <!-- Danh Sách Thẻ Cửa Hàng -->
    <?php if (empty($filteredShops)): ?>
        <div style="background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 48px 24px; text-align: center; color: #64748b;">
            <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
                Chưa tìm thấy cửa hàng phù hợp tiêu chí này
            </h3>
            <p style="font-size: 14px; max-width: 480px; margin: 0 auto 20px; line-height: 1.5;">
                Bạn có thể thử xóa bớt bộ lọc khu vực hoặc bấm nút bên dưới để xem toàn bộ cửa hàng sửa chữa trên hệ thống.
            </p>
            <a href="shops.php" class="fn-btn fn-btn-primary" style="padding: 10px 24px; font-weight: 800;">
                Xem tất cả cửa hàng
            </a>
        </div>
    <?php else: ?>
        <div class="fn-shop-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 18px; text-align: left;">
            <?php foreach ($visibleShops as $shop): ?>
                <div class="fn-shop-card" data-shop-id="<?= (int)$shop['id'] ?>" style="display: flex; flex-direction: column; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.04); overflow: hidden; text-align: left; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 8px 25px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.04)'">
                    
                    <!-- Thumbnail & Badges -->
                    <div class="fn-shop-media" style="height: 145px; position: relative; overflow: hidden;">
                        <?= fixnearShopMedia($shop) ?>
                        
                        <!-- Badge Khu Vực -->
                        <span style="position: absolute; top: 10px; left: 10px; background: rgba(15,23,42,0.85); backdrop-filter: blur(4px); color: #38bdf8; font-weight: 800; padding: 3px 10px; border-radius: 20px; font-size: 11px;">
                            📍 <?= htmlspecialchars($shop['district']) ?>
                        </span>

                        <!-- Nút Yêu Thích -->
                        <?php $isFav = in_array((int)$shop['id'], array_map('intval', $userFavorites), true); ?>
                        <?php if (isLoggedIn()): ?><button type="button" class="fn-fav-btn" data-shop-id="<?= $shop['id'] ?>" data-favorited="<?= $isFav ? '1' : '0' ?>" onclick="toggleFavorite(event, <?= $shop['id'] ?>)" title="<?= $isFav ? 'Xóa khỏi yêu thích' : 'Lưu cửa hàng' ?>" aria-label="Lưu cửa hàng" style="position: absolute; bottom: 10px; right: 10px; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,0.92); backdrop-filter: blur(4px); border: 1px solid rgba(0,0,0,0.08); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 2;">
                            <span class="fn-fav-icon" id="fav-icon-<?= $shop['id'] ?>"><?= $isFav ? '❤️' : '🤍' ?></span>
                        </button><?php endif; ?>
                    </div>

                    <!-- Body Thẻ Cửa Hàng -->
                    <div class="fn-shop-list-body" style="flex: 1; display: flex; flex-direction: column; padding: 16px; text-align: left;">
                        
                        <!-- Hàng Rating & Giờ Mở Cửa -->
                        <div class="fn-shop-list-meta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                            <?php if (!empty($shop['google_rating_verified'])): ?>
                                <span style="color: #ea580c; font-weight: 800; font-size: 13.5px;">★ <?= htmlspecialchars($shop['google_rating']) ?> <span style="color:#94a3b8;font-weight:600;font-size:11px;">(<?= number_format($shop['google_reviews_count'], 0, ',', '.') ?> Google)</span></span>
                            <?php endif; ?>
                            <span style="font-size: 11px; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-weight: 600;">
                                🕒 <?= htmlspecialchars($shop['opening_hours']) ?>
                            </span>
                        </div>

                        <!-- Tên Cửa Hàng -->
                        <h3 class="fn-shop-list-title" style="font-family: var(--fn-font-heading); font-size: 16.5px; font-weight: 800; color: #0f172a; line-height: 1.35; margin: 0 0 6px 0;">
                            <a href="shop_detail.php?id=<?= $shop['id'] ?>" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($shop['name']) ?>
                            </a>
                        </h3>

                        <!-- Địa Chỉ -->
                        <p class="fn-shop-list-address" style="font-size: 13px; color: #334155; line-height: 1.5; margin: 0 0 8px 0;">
                            📌 <?= htmlspecialchars($shop['address']) ?>
                        </p>
                        <?php if (isset($shop['distance_km'])): ?>
                            <div class="fn-shop-list-facts">
                                <span class="fn-shop-list-distance" style="display: inline-flex; align-items: center; gap: 4px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 800;">
                                    📍 Cách bạn: <?= $shop['distance_km'] < 1 ? round($shop['distance_km'] * 1000) . 'm' : $shop['distance_km'] . ' km' ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Pills Tiêu Chuẩn Minh Bạch -->
                        <div class="fn-shop-list-pills" style="display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 14px;">
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

                        <!-- 2 Hàng Nút Hành Động Rõ Ràng Chuẩn UX -->
                        <div style="margin-top: auto; padding-top: 6px;">
                            <!-- Hàng 1: Gọi ngay 50% & Xem địa chỉ trên Map 50% -->
                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <a href="tel:<?= preg_replace('/[^0-9]/', '', $shop['phone']) ?>" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 8px; font-size: 12.5px; font-weight: 700; color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; border-radius: 8px; text-decoration: none;">
                                    📞 Gọi ngay
                                </a>
                                <a href="map.php?shop_id=<?= $shop['id'] ?>" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 8px; font-size: 12.5px; font-weight: 700; color: #2563eb; border-color: #bfdbfe; background: #eff6ff; border-radius: 8px; text-decoration: none;" title="Xem vị trí cửa hàng trên bản đồ FixNear">
                                    🗺️ Xem địa chỉ
                                </a>
                            </div>

                            <!-- Hàng 2: Nút Xem Cửa Hàng Chi Tiết (Full Width) -->
                            <div>
                                <a href="shop_detail.php?id=<?= $shop['id'] ?>" class="fn-btn fn-btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 16px; font-size: 13.5px; font-weight: 800; border-radius: 8px; text-decoration: none; box-sizing: border-box; text-align: center; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.25);">
                                    👁️ Xem cửa hàng & Phản hồi ➔
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($shopPageCount > 1): ?>
            <nav class="fn-shop-pagination" aria-label="Phân trang cửa hàng">
                <span>Trang <?= $shopPage ?> / <?= $shopPageCount ?></span>
                <div class="fn-shop-pagination-links">
                    <?php if ($shopPage > 1): ?><a href="<?= htmlspecialchars($shopPageUrl($shopPage - 1)) ?>" rel="prev">← Trước</a><?php endif; ?>
                    <?php for ($pageNumber = 1; $pageNumber <= $shopPageCount; $pageNumber++): ?>
                        <a href="<?= htmlspecialchars($shopPageUrl($pageNumber)) ?>" <?= $pageNumber === $shopPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                    <?php endfor; ?>
                    <?php if ($shopPage < $shopPageCount): ?><a href="<?= htmlspecialchars($shopPageUrl($shopPage + 1)) ?>" rel="next">Sau →</a><?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
