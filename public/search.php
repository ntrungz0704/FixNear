<?php
require_once __DIR__ . '/../includes/pricing_engine.php';
$pageTitle = "Khám Phá Cửa Hàng & Bản Đồ Số — FixNear";
$pageStyles = ['assets/css/search.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Nhận tham số tìm kiếm
$device = $_GET['device'] ?? '';
$device = ['laptop' => 'win_laptop', 'mac' => 'macbook', 'pc' => 'pc_desktop'][$device] ?? $device;
$brand = $_GET['brand'] ?? '';
$model = $_GET['model'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$issue_name = $_GET['issue_name'] ?? '';
$district = $_GET['district'] ?? '';
$ward = $_GET['ward'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$sort = $_GET['sort'] ?? 'name';
// Tương thích liên kết cũ: tên model không phải từ khóa tìm tên cửa hàng.
if ($model === '' && $keyword !== '') {
    foreach (RepairAtlasPricing::searchModels($keyword, $device ?: null, 10) as $candidate) {
        if (mb_strtolower((string) $candidate['name'], 'UTF-8') === mb_strtolower(trim((string) $keyword), 'UTF-8')
            || $candidate['id'] === trim((string) $keyword)) {
            $model = $candidate['id'];
            $keyword = '';
            break;
        }
    }
}
$selectedModel = $model !== '' ? RepairAtlasPricing::getModelById($model) : null;
if ($selectedModel && $device === '') $device = $selectedModel['deviceType'];
$userLoc = getUserLocation();
$user_lat = $userLoc['lat'] ?? null;
$user_lng = $userLoc['lng'] ?? null;
$loc_name = $userLoc['name'] ?? null;
$radius_km = $_GET['radius_km'] ?? '';

$currentService = null;
if (!empty($service_id)) {
    $currentService = db()->getServiceById($service_id);
}

$filters = [
    'device' => $device,
    'brand' => $brand,
    'service_id' => $service_id,
    'district' => $district,
    'ward' => $ward,
    'keyword' => $keyword,
    'user_lat' => $user_lat,
    'user_lng' => $user_lng,
    'radius_km' => $radius_km
];

$shops = db()->getShops($filters);
$initialVisibleShops = array_slice($shops, 0, 24);
$allServices = db()->getServices($device ? $device : null);
$allServicesFull = db()->getServices(null);
?>

<div class="fn-split-layout">
    <h1 class="fn-sr-only">Tìm kiếm cửa hàng sửa chữa theo thiết bị, dịch vụ và khu vực</h1>
    <!-- Cột bên trái: Bộ lọc và Danh sách cửa hàng -->
    <div class="fn-split-sidebar">
        <!-- Nút Trở Lại Trang Chủ -->
        <div style="margin-bottom: 14px;">
            <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #1e293b; font-size: 13.5px; font-weight: 800; text-decoration: none; padding: 8px 14px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; transition: color 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.2s, opacity 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                <span style="font-size: 16px; color: #ea580c; font-weight: 900;">←</span>
                <span>Trở lại Trang Chủ</span>
            </a>
        </div>

        <!-- Form Bộ lọc nâng cao -->
        <div style="background: var(--fn-bg); padding: 16px; border-radius: var(--fn-radius); border: 1px solid var(--fn-border);">
            <form action="search.php" method="GET" id="search-filter-form">
                <?php if ($user_lat && $user_lng): ?>
                    <input type="hidden" name="user_lat" value="<?= htmlspecialchars($user_lat) ?>">
                    <input type="hidden" name="user_lng" value="<?= htmlspecialchars($user_lng) ?>">
                <?php endif; ?>
                <?php if ($loc_name): ?>
                    <input type="hidden" name="loc_name" value="<?= htmlspecialchars($loc_name) ?>">
                <?php endif; ?>
                <?php if ($brand): ?>
                    <input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>">
                <?php endif; ?>
                <?php if ($model): ?>
                    <input type="hidden" name="model" value="<?= htmlspecialchars($model) ?>">
                <?php endif; ?>
                <?php if ($issue_name): ?>
                    <input type="hidden" name="issue_name" value="<?= htmlspecialchars($issue_name) ?>">
                <?php endif; ?>

                <div class="fn-search-filter-row" style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label for="fn-device-select" class="fn-label" style="font-size: 12px;">Thiết bị:</label>
                        <select name="device" id="fn-device-select" class="fn-select" style="padding: 7px 10px; font-size: 13px;">
                            <option value="">Tất cả thiết bị</option>
                            <option value="phone" <?= $device === 'phone' ? 'selected' : '' ?>>📱 Điện thoại</option>
                            <option value="win_laptop" <?= $device === 'win_laptop' ? 'selected' : '' ?>>💻 Laptop Windows</option>
                            <option value="macbook" <?= $device === 'macbook' ? 'selected' : '' ?>>🍎 MacBook / Mac</option>
                            <option value="tablet" <?= $device === 'tablet' ? 'selected' : '' ?>>📟 iPad / Tablet</option>
                            <option value="smartwatch" <?= $device === 'smartwatch' ? 'selected' : '' ?>>⌚ Đồng hồ thông minh</option>
                            <option value="pc_desktop" <?= $device === 'pc_desktop' ? 'selected' : '' ?>>🖥️ Máy tính PC</option>
                        </select>
                    </div>
                    <div style="flex: 1.5;">
                        <label for="fn-service-select" class="fn-label" style="font-size: 12px;">Lỗi / Dịch vụ:</label>
                        <select name="service_id" id="fn-service-select" class="fn-select" style="padding: 7px 10px; font-size: 13px;">
                            <option value="">Tất cả các lỗi (<?= count($allServices) ?>)</option>
                            <?php foreach ($allServices as $srv): ?>
                                <option value="<?= $srv['id'] ?>" <?= $service_id == $srv['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($srv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fn-search-filter-row" style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <div style="flex: 1.2;">
                        <label for="fn-district-select" class="fn-label" style="font-size: 12px;">Quận / Đô thị:</label>
                        <select name="district" id="fn-district-select" class="fn-select" style="padding: 7px 10px; font-size: 13px;">
                            <option value="">Tất cả khu vực</option>
                            <option value="Quận 1" <?= $district === 'Quận 1' ? 'selected' : '' ?>>Quận 1</option>
                            <option value="Quận 3" <?= $district === 'Quận 3' ? 'selected' : '' ?>>Quận 3</option>
                            <option value="Quận 4" <?= $district === 'Quận 4' ? 'selected' : '' ?>>Quận 4</option>
                            <option value="Quận 5" <?= $district === 'Quận 5' ? 'selected' : '' ?>>Quận 5</option>
                            <option value="Quận 6" <?= $district === 'Quận 6' ? 'selected' : '' ?>>Quận 6</option>
                            <option value="Quận 7" <?= $district === 'Quận 7' ? 'selected' : '' ?>>Quận 7</option>
                            <option value="Quận 8" <?= $district === 'Quận 8' ? 'selected' : '' ?>>Quận 8</option>
                            <option value="Quận 10" <?= $district === 'Quận 10' ? 'selected' : '' ?>>Quận 10</option>
                            <option value="Quận 11" <?= $district === 'Quận 11' ? 'selected' : '' ?>>Quận 11</option>
                            <option value="Quận 12" <?= $district === 'Quận 12' ? 'selected' : '' ?>>Quận 12</option>
                            <option value="Quận Bình Tân" <?= $district === 'Quận Bình Tân' ? 'selected' : '' ?>>Quận Bình Tân</option>
                            <option value="Quận Bình Thạnh" <?= $district === 'Quận Bình Thạnh' ? 'selected' : '' ?>>Quận Bình Thạnh</option>
                            <option value="Quận Gò Vấp" <?= $district === 'Quận Gò Vấp' ? 'selected' : '' ?>>Quận Gò Vấp</option>
                            <option value="Quận Phú Nhuận" <?= $district === 'Quận Phú Nhuận' ? 'selected' : '' ?>>Quận Phú Nhuận</option>
                            <option value="Quận Tân Bình" <?= $district === 'Quận Tân Bình' ? 'selected' : '' ?>>Quận Tân Bình</option>
                            <option value="Quận Tân Phú" <?= $district === 'Quận Tân Phú' ? 'selected' : '' ?>>Quận Tân Phú</option>
                            <option value="TP. Thủ Đức" <?= $district === 'TP. Thủ Đức' ? 'selected' : '' ?>>TP. Thủ Đức</option>
                        </select>
                    </div>
                    <div style="flex: 0.8;">
                        <label for="fn-radius-select" class="fn-label" style="font-size: 12px;">Bán kính:</label>
                        <select name="radius_km" id="fn-radius-select" class="fn-select" style="padding: 7px 10px; font-size: 13px;">
                            <option value="">Toàn khoảng cách</option>
                            <option value="3" <?= $radius_km == '3' ? 'selected' : '' ?>>Trong 3 km</option>
                            <option value="5" <?= $radius_km == '5' ? 'selected' : '' ?>>Trong 5 km</option>
                            <option value="10" <?= $radius_km == '10' ? 'selected' : '' ?>>Trong 10 km</option>
                            <option value="15" <?= $radius_km == '15' ? 'selected' : '' ?>>Trong 15 km</option>
                            <option value="20" <?= $radius_km == '20' ? 'selected' : '' ?>>Trong 20 km</option>
                            <option value="35" <?= $radius_km == '35' ? 'selected' : '' ?>>Trong 35 km</option>
                        </select>
                    </div>
                </div>

                <div class="fn-search-filter-row fn-search-filter-row-full" style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label for="fn-sort-select" class="fn-label" style="font-size: 12px;">Sắp xếp theo:</label>
                        <select name="sort" id="fn-sort-select" class="fn-select" style="padding: 7px 10px; font-size: 13px;">
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A–Z</option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Điểm Google đã đối soát</option>
                            <option value="distance" <?= $sort === 'distance' ? 'selected' : '' ?>>Gần bạn nhất</option>
                        </select>
                    </div>
                </div>

                <div class="fn-search-filter-actions" style="display: flex; gap: 8px;">
                    <label for="fn-search-input" class="fn-sr-only">Tên cửa hàng hoặc địa chỉ</label>
                    <input type="search" name="keyword" id="fn-search-input" value="<?= htmlspecialchars($keyword) ?>" class="fn-input" placeholder="Tên tiệm hoặc địa chỉ…" autocomplete="off" style="padding: 7px 12px; font-size: 13px;">
                    <button type="submit" class="fn-btn fn-btn-primary fn-btn-sm" style="padding: 7px 14px;">Lọc</button>
                    <a href="search.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="padding: 7px 10px;" title="Xóa bộ lọc">↺</a>
                </div>
            </form>

            <?php if ($loc_name): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 7px 12px; border-radius: 6px; font-size: 12px; margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span>📍 Đang đo từ: <strong><?= htmlspecialchars($loc_name) ?></strong></span>
                    <a href="search.php" style="color: #6b7280; font-weight: bold; text-decoration: none; font-size: 14px;" title="Về mặc định">&times; Đặt lại</a>
                </div>
            <?php endif; ?>

            <div style="margin-top: 10px; text-align: center;">
                <button type="button" class="fn-gps-btn" style="margin: 0 auto; font-size: 12px;" onclick="openLocationModal()">
                    📍 Đổi mốc vị trí / Đo từ nơi bạn ở
                </button>
            </div>
        </div>

        <?php if (db()->radius_auto_expanded): ?>
            <div style="background: #eff6ff; border-left: 4px solid #2563eb; padding: 10px 14px; border-radius: 6px; font-size: 12.5px; color: #1e40af; line-height: 1.5;">
                💡 <strong>Tự động định vị:</strong> Vị trí hiện tại của bạn cách khu vực trung tâm khoảng <?= round($shops[0]['distance_km'] ?? 8, 1) ?> km. Hệ thống đã <strong>tự động mở rộng bán kính</strong> để hiển thị toàn bộ cửa hàng gần bạn nhất!
            </div>
        <?php endif; ?>

        <!-- Banner bộ lọc đang hoạt động -->
        <?php if (!empty($brand) || !empty($model) || !empty($issue_name) || !empty($currentService) || !empty($district)): ?>
            <div class="fn-search-context-banner">
                <div style="font-size: 13px; font-weight: 800; color: #9a3412; display: flex; align-items: center; justify-content: space-between;">
                    <span>🎯 Đang lọc theo yêu cầu sửa chữa:</span>
                    <a href="search.php" style="font-size: 12px; color: #ea580c; text-decoration: underline; font-weight: normal;">
                        Đặt lại bộ lọc ✕
                    </a>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; align-items: center;">
                    <?php if (!empty($district)): ?>
                        <span class="fn-pill" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd; font-weight:700; font-size:12.5px;">
                            📍 Khu vực: <?= htmlspecialchars($district) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($brand)): ?>
                        <span class="fn-pill" style="background:#fef3c7; color:#92400e; border-color:#fde68a; font-weight:700; font-size:12.5px;">
                            🏷️ Hãng: <?= htmlspecialchars($brand) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($model)): ?>
                        <span class="fn-pill" style="background:#ffedd5; color:#c2410c; border-color:#fed7aa; font-weight:700; font-size:12.5px;">
                            📱 Model: <?= htmlspecialchars($selectedModel['name'] ?? $model) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($issue_name)): ?>
                        <span class="fn-pill" style="background:#dcfce7; color:#166534; border-color:#bbf7d0; font-weight:700; font-size:12.5px;">
                            🔧 Lỗi: <?= htmlspecialchars($issue_name) ?>
                        </span>
                    <?php elseif (!empty($currentService)): ?>
                        <span class="fn-pill" style="background:#dcfce7; color:#166534; border-color:#bbf7d0; font-weight:700; font-size:12.5px;">
                            🔧 Lỗi: <?= htmlspecialchars($currentService['name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($selectedModel): ?>
                    <p style="margin:8px 0 0;font-size:12px;color:#7c2d12;">Các cửa hàng dưới đây có dịch vụ cho nhóm thiết bị này; hãy xác nhận họ nhận đúng <?= htmlspecialchars($selectedModel['name']) ?> trước khi đến.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tiêu đề kết quả -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 id="fn-shop-count" style="font-family: var(--fn-font-heading); font-size: 18px; font-weight: 800; color: var(--fn-dark);">
                <?= count($shops) ?> Cửa Hàng Phù Hợp
            </h2>
            <div style="font-size: 12px; color: var(--fn-text-light); display: flex; align-items: center; gap: 4px;">
                <span><?= $loc_name ? '📍 ' . htmlspecialchars($loc_name) : ($user_lat ? '📍 GPS gần bạn nhất' : '📍 Tất cả khu vực') ?></span>
                <button type="button" onclick="openLocationModal()" style="background: none; border: none; color: #ea580c; text-decoration: underline; cursor: pointer; font-weight: 700; font-size: 11.5px;">[Đổi Vị Trí ⚙️]</button>
            </div>
        </div>

        <!-- Danh sách thẻ cửa hàng -->
        <div id="fn-shop-results">
        <?php if (empty($shops)): ?>
            <div style="text-align: center; padding: 40px 20px; background: var(--fn-bg); border-radius: var(--fn-radius);">
                <div style="font-size: 32px; margin-bottom: 10px;">🔍</div>
                <h3 style="font-size: 16px; font-weight: 700; color: var(--fn-dark);">Không tìm thấy cửa hàng phù hợp</h3>
                <p style="font-size: 13px; color: var(--fn-dark-muted); margin-top: 4px;">
                    Chưa có bản ghi phù hợp bộ lọc này. Hãy thử nhóm thiết bị hoặc dịch vụ khác.
                </p>
                <a href="search.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="margin-top: 14px;">Xóa bộ lọc</a>
                <a href="request_repair.php" class="fn-btn fn-btn-primary fn-btn-sm" style="margin-top: 14px;">Gửi yêu cầu hỗ trợ</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($initialVisibleShops as $shop): ?>
                    <div class="fn-shop-card fn-split-shop-card" data-shop-id="<?= $shop['id'] ?>" style="margin-bottom: 0;">
                        <div class="fn-shop-body" style="padding: 18px;">
                            <!-- Tên + Rating -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                <h3 style="font-family: var(--fn-font-heading); font-size: 15.5px; font-weight: 800; color: #0f172a; line-height: 1.35; margin: 0; flex: 1;">
                                    <a href="shop_detail.php?id=<?= $shop['id'] ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                        <?= htmlspecialchars($shop['name']) ?>
                                    </a>
                                </h3>
                                <?php if (!empty($shop['google_rating_verified'])): ?>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0;">
                                        <span style="background:#fffbeb;border:1px solid #fde68a;padding:2px 8px;border-radius:6px;font-size:12.5px;font-weight:800;color:#b45309;">⭐ <?= htmlspecialchars($shop['google_rating']) ?></span>
                                        <span style="font-size:10.5px;color:#94a3b8;margin-top:2px;"><?= number_format($shop['google_reviews_count']) ?> Google</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Địa chỉ -->
                            <div style="font-size: 13px; color: #334155; display: flex; align-items: flex-start; gap: 5px; margin-bottom: 6px; line-height: 1.5;">
                                <span style="flex-shrink: 0;">🏢</span>
                                <span><?= htmlspecialchars($shop['address']) ?></span>
                            </div>

                            <!-- Khoảng cách + Giờ -->
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 12px; margin-bottom: 8px;">
                                <?php if (isset($shop['distance_km'])): ?>
                                    <span style="color: #ea580c; font-weight: 700;">📍 <?= htmlspecialchars((string)$shop['distance_km']) ?> km từ vị trí đã chọn</span>
                                <?php else: ?>
                                    <span style="color: #64748b; font-weight: 700;">📍 Chưa có vị trí để tính khoảng cách</span>
                                <?php endif; ?>
                                <span style="color: #64748b;">⏰ <?= htmlspecialchars($shop['opening_hours']) ?></span>
                            </div>

                            <?php if (!empty($shop['website']) && strpos($shop['website'], 'google.com/maps') === false): ?>
                                <div style="font-size: 12.5px; margin-bottom: 10px;">
                                    <a href="<?= htmlspecialchars($shop['website']) ?>" target="_blank" style="color: #2563eb; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                                        🌐 Website ↗
                                    </a>
                                </div>
                            <?php endif; ?>

                            <!-- Pills -->
                            <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px;">
                                <?php if (!empty($shop['service_policy_verified']) && !empty($shop['allows_onsite_watch'])): ?>
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 5px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">✓ Xem sửa trực tiếp</span>
                                <?php endif; ?>
                                <?php if (!empty($shop['service_policy_verified']) && !empty($shop['requires_component_signing'])): ?>
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 5px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">✓ Ký tên linh kiện</span>
                                <?php endif; ?>
                                <?php if (!empty($shop['student_discount_verified'])): ?>
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 5px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">🎓 Ưu đãi SV</span>
                                <?php endif; ?>
                            </div>

                            <!-- Nhóm nút hành động: Hàng trên 50/50, Hàng dưới 100% full width -->
                            <div style="margin-top: 14px;">
                                <!-- Hàng trên: Gọi ngay & Chỉ đường chia đều 50% / 50% -->
                                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                    <a href="tel:<?= preg_replace('/\s+/', '', $shop['phone']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; font-size: 13px; font-weight: 700; color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; border-radius: 8px; text-decoration: none; text-align: center; box-sizing: border-box;">
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
            <?php if (count($shops) > count($initialVisibleShops)): ?>
                <button type="button" id="fn-load-more-results" class="fn-btn fn-btn-secondary" style="width:100%;margin-top:16px;">
                    Hiển thị thêm (đang xem <?= count($initialVisibleShops) ?>/<?= count($shops) ?>)
                </button>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>

    <!-- Cột bên phải: Bản đồ số Leaflet.js tương tác -->
    <div class="fn-split-map-container">
        <div id="leaflet-map"></div>
    </div>
</div>

<!-- Nạp script khởi tạo map -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const shopsData = <?= json_encode($shops, JSON_UNESCAPED_UNICODE) ?>;
    const userLocation = <?= ($user_lat && $user_lng) ? json_encode(['lat' => (float)$user_lat, 'lng' => (float)$user_lng, 'name' => $loc_name ?: 'Vị trí của bạn'], JSON_UNESCAPED_UNICODE) : 'null' ?>;
    initFixnearMap(shopsData, [10.7769, 106.7009], userLocation);
});
</script>

<script>
// Real-time search & Dynamic Filter Synchronization
(function() {
    let debounceTimer;
    const searchInput = document.getElementById('fn-search-input');
    const districtSelect = document.getElementById('fn-district-select');
    const deviceSelect = document.getElementById('fn-device-select');
    const serviceSelect = document.getElementById('fn-service-select');
    const radiusSelect = document.getElementById('fn-radius-select');
    const shopListContainer = document.getElementById('fn-shop-results');
    const shopCountEl = document.getElementById('fn-shop-count');
    const sortSelect = document.getElementById('fn-sort-select');
    const PAGE_SIZE = 24;
    let currentResults = <?= json_encode($shops, JSON_UNESCAPED_UNICODE) ?>;
    let visibleResultCount = PAGE_SIZE;

    // Toàn bộ từ điển dịch vụ để lọc động khi đổi thiết bị (Không bành trướng lĩnh vực)
    const ALL_SERVICES_CATALOG = <?= json_encode($allServicesFull, JSON_UNESCAPED_UNICODE) ?>;

    function updateServiceOptions(selectedDevice) {
        if (!serviceSelect) return;
        const currentVal = serviceSelect.value;
        let filtered = ALL_SERVICES_CATALOG;
        const deviceAlias = {win_laptop: 'laptop', macbook: 'mac', pc_desktop: 'pc'};
        selectedDevice = deviceAlias[selectedDevice] || selectedDevice;
        if (selectedDevice) {
            filtered = ALL_SERVICES_CATALOG.filter(s => {
                if (s.devices && Array.isArray(s.devices)) {
                    return s.devices.includes(selectedDevice);
                }
                return true;
            });
        }
        
        let html = `<option value="">Tất cả các lỗi (${filtered.length})</option>`;
        filtered.forEach(s => {
            const isSel = (String(s.id) === String(currentVal)) ? 'selected' : '';
            html += `<option value="${s.id}" ${isSel}>${s.name}</option>`;
        });
        serviceSelect.innerHTML = html;
    }

    if (deviceSelect) {
        deviceSelect.addEventListener('change', () => {
            updateServiceOptions(deviceSelect.value);
        });
    }
    
    function getFilters() {
        const lat = localStorage.getItem('fixnear_user_lat') || '';
        const lng = localStorage.getItem('fixnear_user_lng') || '';
        const brandInput = document.querySelector('input[name="brand"]');
        const modelInput = document.querySelector('input[name="model"]');
        return {
            q: searchInput ? searchInput.value : '',
            district: districtSelect ? districtSelect.value : '',
            device: deviceSelect ? deviceSelect.value : '',
            service_id: serviceSelect ? serviceSelect.value : '',
            brand: brandInput ? brandInput.value : '',
            model: modelInput ? modelInput.value : '',
            radius_km: radiusSelect ? radiusSelect.value : '',
            user_lat: lat,
            user_lng: lng,
            sort: sortSelect ? sortSelect.value : 'name'
        };
    }
    
    function doSearch() {
        const filters = getFilters();
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });
        
        fetch('api/search_shops.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                if (shopCountEl) shopCountEl.textContent = data.total + ' Cửa Hàng Phù Hợp';
                if (shopListContainer) {
                    currentResults = data.shops;
                    visibleResultCount = PAGE_SIZE;
                    if (data.shops.length === 0) {
                        shopListContainer.innerHTML = '<div class="fn-search-empty"><strong>Chưa có cửa hàng phù hợp</strong><p>Thử đổi thiết bị, dịch vụ hoặc khu vực. Bạn cũng có thể gửi yêu cầu để quản trị viên rà soát.</p><a href="request_repair.php">Gửi yêu cầu hỗ trợ →</a></div>';
                    } else {
                        renderVisibleResults(filters);
                    }
                }
                // Update map if available
                if (typeof updateMapMarkers === 'function') {
                    updateMapMarkers(data.shops);
                }
            })
            .catch(err => console.error('Search error:', err));
    }

    function renderVisibleResults(filters) {
        if (!shopListContainer) return;
        const visible = currentResults.slice(0, visibleResultCount);
        const cards = visible.map(shop => renderShopCard(shop, filters.user_lat, filters.user_lng)).join('');
        const remaining = currentResults.length - visible.length;
        const loadMore = remaining > 0
            ? `<button type="button" id="fn-load-more-results" class="fn-btn fn-btn-secondary" style="width:100%;margin-top:2px;">Hiển thị thêm (${visible.length}/${currentResults.length})</button>`
            : '';
        shopListContainer.innerHTML = cards + loadMore;
        const loadButton = document.getElementById('fn-load-more-results');
        if (loadButton) {
            loadButton.addEventListener('click', () => {
                visibleResultCount += PAGE_SIZE;
                renderVisibleResults(filters);
            });
        }
    }

    const initialLoadButton = document.getElementById('fn-load-more-results');
    if (initialLoadButton) {
        initialLoadButton.addEventListener('click', () => {
            visibleResultCount += PAGE_SIZE;
            renderVisibleResults(getFilters());
        });
    }
    
    function renderShopCard(shop, userLat, userLng) {
        const shopId = Number.parseInt(shop.id, 10);
        if (!Number.isInteger(shopId) || shopId <= 0) return '';
        const isNearby = shop.distance_km && shop.distance_km < 3;
        const nearbyClass = isNearby ? 'fn-nearby' : '';
        const distanceHtml = shop.distance_km ? '<span style="color:#ea580c; font-weight:700;">📍 ' + shop.distance_km.toFixed(1) + ' km</span>' : '';
        const studentHtml = shop.student_discount_verified ? '<span style="background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; padding:2px 7px; border-radius:5px; font-size:11px; font-weight:600;">🎓 SV đã đối soát</span>' : '';
        const ratingHtml = shop.google_rating_verified
            ? `<span style="background:#fffbeb;border:1px solid #fde68a;padding:2px 8px;border-radius:6px;font-size:12.5px;font-weight:800;color:#b45309;">⭐ ${escapeResultText(shop.google_rating)}</span><span style="font-size:10.5px;color:#94a3b8;margin-top:2px;">${Number(shop.google_reviews_count || 0).toLocaleString('vi-VN')} Google</span>`
            : '';
        const ratingBlock = ratingHtml ? `<div style="display:flex; flex-direction:column; align-items:flex-end; flex-shrink:0; margin-left:8px;">${ratingHtml}</div>` : '';
        const watchHtml = shop.service_policy_verified && shop.allows_onsite_watch ? '<span style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 7px; border-radius:5px; font-size:11px; font-weight:600;">✓ Xem trực tiếp</span>' : '';
        const signHtml = shop.service_policy_verified && shop.requires_component_signing ? '<span style="background:#f0fdf4; color:#15803c; border:1px solid #bbf7d0; padding:2px 7px; border-radius:5px; font-size:11px; font-weight:600;">✓ Ký linh kiện</span>' : '';
        
        const safeWebsite = safeResultUrl(shop.website, '');
        const websiteHtml = (safeWebsite && safeWebsite.indexOf('google.com/maps') === -1) ?
            `<a href="${safeWebsite}" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#2563eb; font-weight:700; text-decoration:none;">🌐 Website ↗</a>` : '';
        const cleanPhone = (shop.phone || '').replace(/[^0-9]/g, '');
        
        return `
        <div class="fn-shop-card ${nearbyClass}" data-shop-id="${shopId}" style="padding:18px; border:1px solid var(--fn-border); border-radius:14px; margin-bottom:14px; background:#fff; transition: color 0.3s, background-color 0.3s, border-color 0.3s, box-shadow 0.3s, transform 0.3s, opacity 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                <h3 style="font-weight:800; font-size:16px; color:var(--fn-dark); line-height:1.35; margin:0; flex:1;">
                    <a href="shop_detail.php?id=${shopId}" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">${escapeResultText(shop.name)}</a>
                </h3>
                ${ratingBlock}
            </div>
            
            <div style="font-size:12.5px; color:#64748b; margin-bottom:6px; display:flex; align-items:flex-start; gap:5px; line-height:1.45;">
                <span>🏢</span>
                <span>${escapeResultText(shop.address)}</span>
            </div>
            
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; font-size:12px; margin-bottom:6px;">
                ${distanceHtml}
                <span style="color:#64748b;">⏰ ${escapeResultText(shop.opening_hours || '')}</span>
            </div>

            <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:center; margin-bottom:10px;">
                ${websiteHtml}
            </div>

            <div style="display:flex; gap:5px; flex-wrap:wrap; margin-bottom:14px;">
                ${watchHtml} ${signHtml} ${studentHtml}
            </div>

            <!-- Nhóm nút hành động: Hàng trên 50/50, Hàng dưới 100% full width -->
            <div style="margin-top:auto;">
                <!-- Hàng trên: Gọi ngay & Chỉ đường chia đều 50% / 50% -->
                <div style="display:flex; gap:8px; margin-bottom:8px;">
                    ${cleanPhone ? `
                    <a href="tel:${cleanPhone}" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 8px; font-size:13px; font-weight:700; color:#16a34a; border-color:#bbf7d0; background:#f0fdf4; border-radius:8px; text-decoration:none; text-align:center; box-sizing:border-box;">
                        📞 Gọi ngay
                    </a>` : `
                    <button disabled class="fn-btn fn-btn-secondary fn-btn-sm" style="flex:1; padding:10px 8px; font-size:13px; opacity:0.5; border-radius:8px;">📞 Gọi ngay</button>
                    `}
                    <a href="${safeResultUrl(shop.map_url, 'https://maps.google.com')}" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 8px; font-size:13px; font-weight:700; color:#2563eb; border-color:#bfdbfe; background:#eff6ff; border-radius:8px; text-decoration:none; text-align:center; box-sizing:border-box;" title="Chỉ đường Google Maps">
                        🗺️ Chỉ đường
                    </a>
                </div>
                <!-- Hàng dưới: Xem Bảng Giá & Review full-width 100% -->
                <div>
                    <a href="shop_detail.php?id=${shopId}" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-primary" style="width:100%; display:flex; align-items:center; justify-content:center; gap:6px; padding:11px 16px; font-size:13.5px; font-weight:800; border-radius:8px; text-decoration:none; box-sizing:border-box; text-align:center; box-shadow:0 2px 8px rgba(234,88,12,0.25);">
                        Xem Dịch Vụ & Phản Hồi ➔
                    </a>
                </div>
            </div>
        </div>`;
    }

    function escapeResultText(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[char]));
    }

    function safeResultUrl(value, fallback) {
        try {
            const url = new URL(String(value || ''), window.location.href);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : fallback;
        } catch (_) {
            return fallback;
        }
    }
    
    // Attach listeners
    [searchInput, districtSelect, deviceSelect, serviceSelect, radiusSelect, sortSelect].forEach(el => {
        if (!el) return;
        el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(doSearch, 300);
        });
    });
    
    // Initial search on page load
    if (shopListContainer) {
        doSearch();
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
