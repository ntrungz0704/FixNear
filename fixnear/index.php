<?php
$pageTitle = "FixNear — Tra Cứu Cửa Hàng Và Dịch Vụ Sửa Chữa Tại TP.HCM";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$stats = db()->getStats();

// Nhận diện tọa độ vị trí thực tế của người dùng từ Session, URL hoặc Cookie
$userLoc = getUserLocation();
$user_lat = $userLoc['lat'] ?? null;
$user_lng = $userLoc['lng'] ?? null;
$loc_name = $userLoc['name'] ?? null;

if (!empty($user_lat) && !empty($user_lng)) {
    $featuredShops = db()->getShops(['user_lat' => (float)$user_lat, 'user_lng' => (float)$user_lng]);
    $locDisplay = !empty($loc_name) ? htmlspecialchars($loc_name) : 'Vị trí GPS của bạn';
    $isLocated = true;
} else {
    // Chưa có quyền GPS: không suy diễn khoảng cách từ tâm thành phố.
    $featuredShops = db()->getShops();
    $locDisplay = 'Danh mục TP.HCM (chưa có GPS)';
    $isLocated = false;
}
$featuredShops = array_slice($featuredShops, 0, 6);
?>

<!-- Rotating data-quality notice -->
<div class="fn-ticker-bar">
    <div class="fn-ticker-inner">
        <span class="fn-pulse-dot"></span>
        <span style="font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; background:#dcfce7; padding: 2px 6px; border-radius: 4px;">Lưu ý dữ liệu</span>
        <span id="fn-live-ticker-text">Dữ liệu cửa hàng, giá và ưu đãi có thể thay đổi — hãy xác nhận trực tiếp trước khi sửa</span>
    </div>
</div>

<!-- Hero Section -->
<section class="fn-hero">
    <div class="fn-hero-badge" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: center;">
        <span>⚡ Nền Tảng Tra Cứu Thông Tin Sửa Chữa Tại TP.HCM</span>
        <?php if ($isLocated): ?>
        <button type="button" onclick="toggleGPS()" id="fn-gps-toggle-btn" style="display: inline-flex; align-items: center; gap: 6px; background: #dcfce7; border: 1px solid #16a34a; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;" title="Nhấn để tắt GPS">
            <span id="fn-gps-status-text">📍 <?= htmlspecialchars($locDisplay) ?></span>
            <span style="font-size: 11px; text-decoration: underline;">[Tắt ✕]</span>
        </button>
        <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; cursor: pointer;" title="Đổi khu vực hoặc cập nhật tọa độ">
            ⚙️ Đổi vị trí
        </button>
        <?php else: ?>
        <button type="button" onclick="toggleGPS()" id="fn-gps-toggle-btn" style="display: inline-flex; align-items: center; gap: 6px; background: #f3f4f6; border: 1px solid #d1d5db; color: #6b7280; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;" title="Nhấn để bật GPS">
            <span id="fn-gps-status-text">📍 Chưa bật GPS</span>
            <span style="font-size: 11px; text-decoration: underline;">[Bật ⚙️]</span>
        </button>
        <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; cursor: pointer;" title="Chọn nhanh quận bạn đang ở">
            🏙️ Chọn quận
        </button>
        <?php endif; ?>
    </div>
    
    <h1 class="fn-hero-title">
        Hỏng thiết bị? <span>Tìm đúng chỗ sửa.</span>
    </h1>
    
    <p class="fn-hero-subtitle">
        Tra cứu nhóm lỗi theo từng model và lọc bản ghi cửa hàng theo khu vực tại TP.HCM. Giá chỉ hiển thị khi có nguồn đối soát.
    </p>

    <!-- WIZARD 2 BƯỚC CHUẨN REPAIRBOOKINGS -->
    <div class="fn-wizard-box">
        <!-- Stepper -->
        <div class="fn-wizard-stepper">
            <div class="fn-step-item active" id="fn-step-indicator-1" onclick="goToWizardStep(1)">
                <span class="fn-step-badge">1</span>
                <span>Chọn thiết bị & Dòng máy</span>
            </div>
            <div class="fn-step-divider"></div>
            <div class="fn-step-item" id="fn-step-indicator-2" onclick="goToWizardStep(2)">
                <span class="fn-step-badge">2</span>
                <span>Chọn lỗi cần sửa</span>
            </div>
        </div>

        <!-- ================= BƯỚC 1: THIẾT BỊ, HÃNG & DÒNG MÁY ================= -->
        <div id="fn-wizard-view-1">
            
            <!-- Tầng 1.1: Chọn Loại Thiết Bị -->
            <div id="fn-subview-devices">
                <h3 class="fn-wizard-title">Bạn cần sửa loại thiết bị nào?</h3>
                <p class="fn-wizard-subtitle">Chọn loại thiết bị để khám phá các thương hiệu và dòng máy được hỗ trợ</p>

                <div class="fn-device-category-grid">
                    <div class="fn-device-cat-card" onclick="selectDeviceType('phone')">
                        <div class="fn-device-cat-icon">
                            <svg class="fn-device-cat-svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="11" y="4" width="26" height="40" rx="5" ry="5" fill="#f8fafc" stroke="currentColor"/>
                                <line x1="20" y1="9" x2="28" y2="9" stroke="currentColor" stroke-width="2"/>
                                <circle cx="24" cy="38" r="2" fill="currentColor"/>
                                <path d="M16 16h16v16H16z" fill="#ea580c" fill-opacity="0.12" stroke="none"/>
                            </svg>
                        </div>
                        <div class="fn-device-cat-title">Điện thoại</div>
                        <div class="fn-device-cat-sub">iPhone, Samsung, Xiaomi, Oppo...</div>
                    </div>
                    <div class="fn-device-cat-card" onclick="selectDeviceType('laptop')">
                        <div class="fn-device-cat-icon">
                            <svg class="fn-device-cat-svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="8" y="9" width="32" height="22" rx="3" fill="#f8fafc" stroke="currentColor"/>
                                <path d="M4 35h40a2 2 0 0 1 2 2v1H2v-1a2 2 0 0 1 2-2z" fill="#e2e8f0" stroke="currentColor"/>
                                <line x1="20" y1="35" x2="28" y2="35" stroke="currentColor" stroke-width="2.5"/>
                                <path d="M14 15h20v11H14z" fill="#0284c7" fill-opacity="0.12" stroke="none"/>
                            </svg>
                        </div>
                        <div class="fn-device-cat-title">Laptop Windows</div>
                        <div class="fn-device-cat-sub">Dell, Asus, HP, Acer, Lenovo, MSI...</div>
                    </div>
                    <div class="fn-device-cat-card" onclick="selectDeviceType('mac')">
                        <div class="fn-device-cat-icon">
                            <svg class="fn-device-cat-svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="8" width="30" height="23" rx="2.5" fill="#f8fafc" stroke="currentColor"/>
                                <path d="M5 35h38c1.1 0 2 .9 2 2v1H3v-1c0-1.1.9-2 2-2z" fill="#cbd5e1" stroke="currentColor"/>
                                <path d="M22 35h4" stroke="currentColor" stroke-width="2"/>
                                <path d="M24 16.5c-.7 0-1.5.4-2 .4-.6 0-1.3-.4-1.9-.4-1.5 0-2.6 1.4-2.6 3.2 0 2.2 1.9 4.3 2.8 4.3.5 0 .9-.3 1.5-.3.6 0 1 .3 1.6.3 1.1 0 2.4-1.7 2.6-2.5-1.5-.6-1.8-2.5-.3-3.4-.6-.9-1.3-1.6-1.7-1.6zm-.2-1.5c.3-.5.5-1.1.4-1.8-.6.1-1.2.4-1.5.9-.3.4-.5 1-.4 1.7.7 0 1.2-.3 1.5-.8z" fill="#ea580c"/>
                            </svg>
                        </div>
                        <div class="fn-device-cat-title">MacBook / Mac</div>
                        <div class="fn-device-cat-sub">MacBook Pro, Air, iMac, Mac Mini...</div>
                    </div>
                    <div class="fn-device-cat-card" onclick="selectDeviceType('tablet')">
                        <div class="fn-device-cat-icon">
                            <svg class="fn-device-cat-svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="7" y="6" width="34" height="36" rx="4" fill="#f8fafc" stroke="currentColor"/>
                                <circle cx="43" cy="24" r="1.5" fill="currentColor"/>
                                <path d="M13 12h22v24H13z" fill="#8b5cf6" fill-opacity="0.12" stroke="none"/>
                                <path d="M38 7l4-4 2 2-4 4-2-2z" fill="#ea580c" stroke="#ea580c" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <div class="fn-device-cat-title">Máy tính bảng (Tablet)</div>
                        <div class="fn-device-cat-sub">iPad Pro, iPad Air, Galaxy Tab...</div>
                    </div>
                    <div class="fn-device-cat-card" onclick="selectDeviceType('smartwatch')">
                        <div class="fn-device-cat-icon">
                            <svg class="fn-device-cat-svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 11V4h14v7M17 37v7h14v-7" stroke="currentColor" stroke-width="2" fill="#cbd5e1"/>
                                <rect x="12" y="11" width="24" height="26" rx="7" fill="#f8fafc" stroke="currentColor"/>
                                <rect x="36" y="20" width="2" height="6" rx="1" fill="currentColor"/>
                                <circle cx="24" cy="24" r="7" fill="#10b981" fill-opacity="0.15" stroke="#10b981" stroke-width="2"/>
                                <path d="M24 20v4l3 2" stroke="#ea580c" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="fn-device-cat-title">Đồng hồ thông minh</div>
                        <div class="fn-device-cat-sub">Apple Watch, Galaxy Watch, Garmin...</div>
                    </div>
                    <div class="fn-device-cat-card" onclick="selectDeviceType('pc')">
                        <div class="fn-device-cat-icon">
                            <svg class="fn-device-cat-svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="6" width="26" height="20" rx="3" fill="#f8fafc" stroke="currentColor"/>
                                <path d="M17 26v6m-6 0h12" stroke="currentColor" stroke-width="2.5"/>
                                <rect x="33" y="10" width="12" height="28" rx="2.5" fill="#f1f5f9" stroke="currentColor"/>
                                <line x1="36" y1="15" x2="42" y2="15" stroke="#ea580c" stroke-width="2"/>
                                <circle cx="39" cy="23" r="3" stroke="#0ea5e9" stroke-width="2" fill="none"/>
                                <circle cx="39" cy="31" r="3" stroke="#0ea5e9" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                        <div class="fn-device-cat-title">Máy tính bàn (PC)</div>
                        <div class="fn-device-cat-sub">PC Gaming, Đồ họa, Máy đồng bộ...</div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 18px;">
                    <button type="button" class="fn-btn-skip-link" onclick="skipToStep2()">
                        Bỏ qua — Tôi muốn chọn lỗi luôn ➔
                    </button>
                </div>
            </div>

            <!-- Tầng 1.2: Chọn Hãng (Select your brand) -->
            <div id="fn-subview-brands" style="display: none;">
                <div class="fn-wizard-nav-header">
                    <button type="button" class="fn-btn-back-link" onclick="wizardBackToDevices()">
                        ← Quay lại
                    </button>
                    <span class="fn-nav-path" id="fn-nav-device-label">Điện thoại</span>
                </div>

                <h3 class="fn-wizard-title">Chọn thương hiệu <span>(Select your brand)</span></h3>
                <p class="fn-wizard-subtitle">Bấm vào hãng thiết bị của bạn hoặc tự gõ tên nếu không thấy</p>

                <!-- Lưới thương hiệu có logo chuẩn RepairBookings -->
                <div class="fn-brands-grid" id="fn-brands-grid-container"></div>
            </div>

            <!-- Tầng 1.3: Chọn Dòng máy (Select your model) -->
            <div id="fn-subview-models" style="display: none;">
                <div class="fn-wizard-nav-header">
                    <button type="button" class="fn-btn-back-link" onclick="wizardBackToBrands()">
                        ← Quay lại
                    </button>
                    <span class="fn-nav-path" id="fn-nav-brand-path">Điện thoại &rsaquo; Apple</span>
                </div>

                <h3 class="fn-wizard-title">Chọn dòng máy của bạn <span>(Select your model)</span></h3>
                <p class="fn-wizard-subtitle">Tìm kiếm model hoặc bấm chọn trong danh sách phổ biến bên dưới</p>

                <!-- Ô tìm kiếm model của hãng -->
                <div class="fn-model-search-wrap" style="margin-bottom: 14px;">
                    <span class="fn-model-search-icon">🔍</span>
                    <input type="text" id="fn-search-brand-models" class="fn-model-input" placeholder="Tìm kiếm dòng máy..." autocomplete="off">
                </div>

                <!-- Bộ lọc theo Dòng máy (Series Tabs) -->
                <div class="fn-series-filter-bar" id="fn-series-filter-container" style="display: none;"></div>

                <!-- Lưới nút chọn model -->
                <div class="fn-models-grid" id="fn-models-grid-container"></div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                    <button type="button" class="fn-btn-back-link" onclick="wizardBackToBrands()">
                        ← Đổi hãng khác
                    </button>
                    <button type="button" class="fn-btn-skip-link" onclick="skipToStep2()">
                        Bỏ qua — Tôi không rõ model máy ➔
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= BƯỚC 2: CHỌN LỖI CẦN SỬA ================= -->
        <div id="fn-wizard-view-2" style="display: none;">
            <div class="fn-wizard-breadcrumb-bar">
                <button type="button" class="fn-btn-back-step" onclick="goToWizardStep(1)">
                    ← Quay lại chọn máy
                </button>
                <div style="font-size: 13.5px; font-weight: 800; color: var(--fn-primary);" id="fn-current-selected-model-text">
                    Đang chọn: 📱 iPhone 15 Pro Max
                </div>
            </div>

            <h3 class="fn-wizard-title">Thiết bị đang gặp vấn đề gì? <span>(Select repair)</span></h3>
            <p class="fn-wizard-subtitle">Bấm chọn lỗi để lọc bản ghi cửa hàng có dịch vụ liên quan</p>

            <div class="fn-repairs-grid" id="fn-repairs-grid-container">
                <!-- Sẽ render động 18 thẻ lỗi chuẩn RepairBookings -->
            </div>
        </div>
    </div>

    <!-- Thống kê dự án -->
    <div class="fn-stats-bar">
        <div class="fn-stat-item">
            <div class="fn-stat-number"><?= (int)$stats['total_shops'] ?></div>
            <div class="fn-stat-label">Bản ghi cửa hàng tham khảo</div>
        </div>
        <div class="fn-stat-item">
            <div class="fn-stat-number"><?= (int)$stats['total_districts'] ?></div>
            <div class="fn-stat-label">Khu vực có dữ liệu</div>
        </div>
        <div class="fn-stat-item">
            <div class="fn-stat-number"><?= (int)$stats['total_services'] ?></div>
            <div class="fn-stat-label">Dịch vụ trong dữ liệu mẫu</div>
        </div>
        <div class="fn-stat-item">
            <div class="fn-stat-number"><?= htmlspecialchars((string)($stats['average_shop_rating'] ?? '—')) ?><span>★</span></div>
            <div class="fn-stat-label">Điểm Google lưu trong bộ dữ liệu</div>
        </div>
    </div>
</section>

<!-- Danh sách cửa hàng tiêu biểu theo định vị -->
<section class="fn-container" id="fn-featured-shops-section" style="scroll-margin-top: 80px;">
    <!-- Header Căn Giữa Chuẩn Đẹp Toàn Diện -->
    <div style="text-align: center; max-width: 760px; margin: 0 auto 36px; display: flex; flex-direction: column; align-items: center;">
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; font-size: 12.5px; font-weight: 800; padding: 6px 16px; border-radius: 20px; margin-bottom: 12px; cursor: pointer; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.1);" onclick="openLocationModal()" title="Nhấn để đổi vị trí hoặc chọn quận khác">
            <span class="fn-pulse-dot" style="width: 8px; height: 8px; background: #ea580c;"></span>
            <?= $isLocated ? '📍 Đang định vị:' : '📍 Vị trí hiển thị:' ?> <?= $locDisplay ?>
            <span style="color: #ea580c; text-decoration: underline; font-weight: 800; margin-left: 6px;">[Đổi Vị Trí ⚙️]</span>
        </div>
        <h2 class="fn-section-title" style="margin: 4px 0 8px; text-align: center; font-size: 28px;">📍 <?= $isLocated ? 'Cửa Hàng Theo Khoảng Cách' : 'Cửa Hàng Trong Danh Mục' ?></h2>
        <p class="fn-section-desc" style="text-align: center; margin: 0 0 16px 0; max-width: 620px; font-size: 14.5px;"><?= $isLocated ? 'Khoảng cách được tính từ vị trí bạn đã cung cấp.' : 'Bật GPS hoặc chọn khu vực thủ công để xem khoảng cách phù hợp; danh sách hiện tại chưa được xếp theo vị trí của bạn.' ?></p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
            <a href="shops.php" class="fn-btn fn-btn-primary fn-btn-sm" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; font-weight: 800;">
                <span>📋 Xem danh sách <?= (int)$stats['total_shops'] ?> cửa hàng</span> <span>➔</span>
            </a>
            <a href="map.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 700;">
                <span>🗺️ Xem trên bản đồ số</span>
            </a>
        </div>
    </div>

    <div class="fn-shops-grid">
        <?php foreach ($featuredShops as $idx => $shop): ?>
            <div class="fn-shop-card" style="text-align: left; <?= $idx === 0 ? 'border: 2px solid #ea580c; box-shadow: 0 8px 24px rgba(234, 88, 12, 0.18); position: relative;' : '' ?>">
                <div class="fn-shop-thumb">
                    <img src="<?= htmlspecialchars($shop['image']) ?>" alt="<?= htmlspecialchars($shop['name']) ?>" width="600" height="400" loading="lazy">
                    <?php if ($isLocated && isset($shop['distance_km'])): ?>
                    <span class="fn-distance-badge" style="background: #0f172a; color: #38bdf8; font-weight: 800; border: 1px solid #0284c7;">
                        📍 <?= $shop['distance_km'] < 1 ? round($shop['distance_km'] * 1000) . 'm' : $shop['distance_km'] . ' km' ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($isLocated && $idx === 0): ?>
                        <span class="fn-nearest-badge">🏆 GẦN BẠN NHẤT</span>
                    <?php elseif (!empty($shop['is_verified'])): ?>
                        <span class="fn-verified-badge" style="background: #059669; color: #fff; font-weight: 800;">✓ ĐÃ SÀNG LỌC</span>
                    <?php endif; ?>
                </div>

                <div class="fn-shop-body" style="text-align: left; padding: 20px;">
                    <div class="fn-shop-title-row" style="text-align: left; justify-content: space-between; align-items: flex-start;">
                        <h3 class="fn-shop-name" style="text-align: left; margin: 0; line-height: 1.35;">
                            <a href="shop_detail.php?id=<?= $shop['id'] ?>" target="_blank" style="text-align: left; color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($shop['name']) ?>
                            </a>
                        </h3>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0;">
                            <?php if (!empty($shop['google_rating_verified'])): ?>
                                <div class="fn-shop-rating" style="flex-shrink: 0;">⭐ <?= htmlspecialchars($shop['google_rating']) ?></div>
                                <span style="font-size: 11px; color: #6b7280; font-weight: 600;"><?= number_format($shop['google_reviews_count']) ?> đánh giá Google</span>
                            <?php else: ?>
                                <span style="font-size:11px;color:#64748b;background:#f1f5f9;padding:4px 7px;border-radius:6px;">Google: chưa đối soát</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="fn-shop-address" style="text-align: left; justify-content: flex-start; margin-top: 8px;">
                        <span>🏢</span>
                        <span style="text-align: left;"><?= htmlspecialchars($shop['address']) ?></span>
                    </div>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 6px;">
                        <div class="fn-shop-hours" style="text-align: left; justify-content: flex-start; margin: 0;">
                            <span>⏰</span>
                            <span style="text-align: left;"><?= htmlspecialchars($shop['opening_hours']) ?></span>
                        </div>
                    </div>

                    <!-- Hotline nhanh -->
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px; padding: 8px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                        <span style="font-size: 14px;">📞</span>
                        <a href="tel:<?= preg_replace('/\s+/', '', $shop['phone']) ?>" style="color: #16a34a; font-weight: 800; font-size: 14px; text-decoration: none; letter-spacing: 0.3px;">
                            <?= htmlspecialchars($shop['phone']) ?>
                        </a>
                        <span style="font-size: 11px; color: #6b7280; margin-left: auto;">Gọi ngay</span>
                    </div>

                    <div class="fn-features-pills" style="justify-content: flex-start; text-align: left; margin-top: 10px; margin-bottom: 12px;">
                        <?php if (!empty($shop['service_policy_verified']) && !empty($shop['allows_onsite_watch'])): ?>
                            <span class="fn-pill fn-pill-highlight">✓ Xem trực tiếp</span>
                        <?php endif; ?>
                        <?php if (!empty($shop['service_policy_verified']) && !empty($shop['requires_component_signing'])): ?>
                            <span class="fn-pill">✓ Ký linh kiện</span>
                        <?php endif; ?>
                        <?php if (!empty($shop['student_discount_verified'])): ?>
                            <span class="fn-pill" style="background:#fff7ed; color:#c2410c; border-color:#fed7aa;">
                                🎓 SV
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Nhóm nút hành động: Hàng trên 50/50, Hàng dưới 100% full width -->
                    <div style="margin-top: auto; padding-top: 10px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <a href="tel:<?= preg_replace('/\s+/', '', $shop['phone']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; font-size: 13px; font-weight: 700; color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; border-radius: 8px; text-decoration: none; text-align: center; box-sizing: border-box;">
                                📞 Gọi ngay
                            </a>
                            <a href="<?= htmlspecialchars($shop['map_url'] ?: ('https://www.google.com/maps/dir/?api=1&destination=' . urlencode($shop['latitude'] . ',' . $shop['longitude']))) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; font-size: 13px; font-weight: 700; color: #2563eb; border-color: #bfdbfe; background: #eff6ff; border-radius: 8px; text-decoration: none; text-align: center; box-sizing: border-box;" title="Chỉ đường Google Maps">
                                🗺️ Chỉ đường
                            </a>
                        </div>
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
</section>

<!-- Quy trình 4 bước tìm chỗ sửa uy tín nâng cấp hiện đại -->
<section style="background: #ffffff; border-top: 1px solid var(--fn-border); border-bottom: 1px solid var(--fn-border); padding: 70px 20px;">
    <div class="fn-container" style="padding: 0;">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 46px;">
            <div class="fn-section-badge">QUY TRÌNH HOẠT ĐỘNG</div>
            <h2 class="fn-section-title" style="margin-top: 8px;">4 Bước Giải Quyết Vấn Đề <span style="color:#ea580c;">Hỏng Thiết Bị</span></h2>
            <p class="fn-section-subtitle">Quy trình tham khảo giúp bạn mô tả lỗi và chủ động kiểm tra thông tin trước khi chọn nơi sửa.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; position: relative;">
            <!-- Đường nối gradient giữa các bước (hiển thị desktop) -->
            <style>
                @media (min-width: 1100px) {
                    .fn-steps-connector { display: block !important; position: absolute; top: 58px; left: 15%; right: 15%; height: 3px; background: linear-gradient(90deg, #ea580c, #2563eb, #16a34a, #e11d48); border-radius: 4px; z-index: 0; opacity: 0.3; }
                }
            </style>
            <div class="fn-steps-connector" style="display: none;"></div>

            <!-- Bước 1 -->
            <div class="fn-process-card fn-animate-on-scroll" style="position: relative; z-index: 1;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #ea580c, #f97316); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; box-shadow: 0 4px 12px rgba(234,88,12,0.3);">1</div>
                    <div class="fn-process-step-num" style="margin: 0;">BƯỚC 01</div>
                </div>
                <div class="fn-process-icon-box" style="background: #fff7ed; color: #ea580c; border: 1.5px solid #fed7aa;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 900; color: var(--fn-dark); margin: 16px 0 8px; text-align: left;">Chọn Loại Thiết Bị</h3>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); line-height: 1.6; margin: 0 0 16px 0; text-align: left;">
                    Chọn loại thiết bị như Laptop, MacBook, iPhone, iPad hoặc PC để lọc nhóm dịch vụ phù hợp.
                </p>
                <a href="request_repair.php" class="fn-process-btn fn-process-btn-1" title="Bắt đầu mô tả thiết bị">
                    <span>Hỗ trợ 4 nhóm máy</span> <span>&rarr;</span>
                </a>
            </div>

            <!-- Bước 2 -->
            <div class="fn-process-card fn-animate-on-scroll" style="position: relative; z-index: 1;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; box-shadow: 0 4px 12px rgba(37,99,235,0.3);">2</div>
                    <div class="fn-process-step-num" style="margin: 0;">BƯỚC 02</div>
                </div>
                <div class="fn-process-icon-box" style="background: #eff6ff; color: #2563eb; border: 1.5px solid #bfdbfe;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 900; color: var(--fn-dark); margin: 16px 0 8px; text-align: left;">Chọn Biểu Hiện Lỗi</h3>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); line-height: 1.6; margin: 0 0 16px 0; text-align: left;">
                    Chọn biểu hiện như pin chai/phù, sọc màn hình, liệt phím, máy sập nguồn hoặc vào nước.
                </p>
                <a href="request_repair.php#fn-issues-select" class="fn-process-btn fn-process-btn-2" title="Xem 18 nhóm lỗi thường gặp">
                    <span>18 nhóm lỗi tham khảo</span> <span>&rarr;</span>
                </a>
            </div>

            <!-- Bước 3 -->
            <div class="fn-process-card fn-animate-on-scroll" style="position: relative; z-index: 1;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; box-shadow: 0 4px 12px rgba(22,163,106,0.3);">3</div>
                    <div class="fn-process-step-num" style="margin: 0;">BƯỚC 03</div>
                </div>
                <div class="fn-process-icon-box" style="background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <polyline points="9 12 11 14 15 10"></polyline>
                    </svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 900; color: var(--fn-dark); margin: 16px 0 8px; text-align: left;">Kiểm Tra Nguồn Thông Tin</h3>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); line-height: 1.6; margin: 0 0 16px 0; text-align: left;">
                    Kiểm tra nguồn, ngày đối soát, linh kiện, giá trọn gói và chính sách bảo hành trực tiếp với cửa hàng.
                </p>
                <button type="button" onclick="fnOpenVerificationModal()" class="fn-process-btn fn-process-btn-3" title="Xem quy chuẩn minh bạch 3 Không">
                    <span>Minh bạch 3 Không</span> <span>&rarr;</span>
                </button>
            </div>

            <!-- Bước 4 -->
            <div class="fn-process-card fn-animate-on-scroll" style="position: relative; z-index: 1;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #e11d48, #f43f5e); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; box-shadow: 0 4px 12px rgba(225,29,72,0.3);">4</div>
                    <div class="fn-process-step-num" style="margin: 0;">BƯỚC 04</div>
                </div>
                <div class="fn-process-icon-box" style="background: #fff1f2; color: #e11d48; border: 1.5px solid #fecdd3;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 900; color: var(--fn-dark); margin: 16px 0 8px; text-align: left;">Đến Tiệm Gần Nhất</h3>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); line-height: 1.6; margin: 0 0 16px 0; text-align: left;">
                    Xem khoảng cách ước tính theo vị trí của bạn và mở Google Maps để dẫn đường. Hãy hỏi cửa hàng trước nếu bạn muốn quan sát quá trình sửa.
                </p>
                <a href="shops.php" class="fn-process-btn fn-process-btn-4" title="Tìm tiệm gần nhất và chỉ đường">
                    <span>Chỉ đường 1-chạm</span> <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- B1. Customer Reviews Section -->
<?php if (false): // Dữ liệu testimonial cũ chỉ là fixture thiết kế, không có nguồn nên không công khai. ?>
<section class="fn-section fn-animate-on-scroll" style="background:#f8fafc;">
    <div class="fn-container">
        <!-- Header Căn Giữa Hoàn Hảo -->
        <div style="text-align: center; max-width: 720px; margin: 0 auto 36px; display: flex; flex-direction: column; align-items: center;">
            <div class="fn-section-badge" style="margin: 0 auto 10px;">PHẢN HỒI MINH HỌA TRONG DỮ LIỆU MẪU</div>
            <h2 class="fn-section-title" style="text-align: center; margin: 4px 0 8px; font-size: 28px;">Khách Hàng Nói Gì Về <span style="color:#ea580c;">FixNear</span>?</h2>
            <p class="fn-section-subtitle" style="text-align: center; margin: 0 auto 16px; max-width: 600px;">Các nội dung dưới đây minh họa giao diện đánh giá, chưa phải bằng chứng xác minh độc lập.</p>

            <!-- Thống kê nhanh -->
            <div style="display: flex; gap: 24px; flex-wrap: wrap; justify-content: center; margin-top: 8px;">
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <span style="font-size: 20px;">⭐</span>
                    <div>
                        <div style="font-size: 18px; font-weight: 900; color: #ea580c;"><?= htmlspecialchars((string)($stats['average_shop_rating'] ?? '—')) ?>/5</div>
                        <div style="font-size: 11px; color: #6b7280;">Điểm lưu trong dữ liệu</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <span style="font-size: 20px;">👥</span>
                    <div>
                        <div style="font-size: 18px; font-weight: 900; color: #2563eb;"><?= (int)$stats['total_shops'] ?></div>
                        <div style="font-size: 11px; color: #6b7280;">Bản ghi cửa hàng</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <span style="font-size: 20px;">✅</span>
                    <div>
                        <div style="font-size: 18px; font-weight: 900; color: #16a34a;"><?= (int)$stats['total_districts'] ?></div>
                        <div style="font-size: 11px; color: #6b7280;">Khu vực có dữ liệu</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 36px;">
            <!-- Review 1 -->
            <div class="fn-review-card fn-animate-on-scroll">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg,#ea580c,#f97316); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:19px; box-shadow: 0 4px 12px rgba(234,88,12,0.3);">T</div>
                    <div style="text-align: left;">
                        <div style="font-weight:800; color:var(--fn-dark); font-size: 15px;">Trần Minh Tuấn</div>
                        <div style="font-size:12.5px; color:var(--fn-text-light);">Sinh viên FPT Polytechnic • Q.12</div>
                    </div>
                    <div style="margin-left:auto; color:#f59e0b; font-size: 15px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                </div>
                <p style="font-size:14px; color:var(--fn-dark-muted); line-height:1.65; text-align: left;">
                    "Laptop hỏng phím trước tuần thi tốt nghiệp, mình tra trên FixNear tìm được tiệm cách trường đúng 800m. Thợ cho ngồi xem bóc phím thay tại chỗ trong 35 phút, còn xuất trình thẻ SV được hỗ trợ nhiệt tình!"
                </p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                    <span>🛠️ Thay bàn phím Asus TUF</span>
                    <span>2 tuần trước</span>
                </div>
            </div>
            <!-- Review 2 -->
            <div class="fn-review-card fn-animate-on-scroll">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#6366f1); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:19px; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">H</div>
                    <div style="text-align: left;">
                        <div style="font-weight:800; color:var(--fn-dark); font-size: 15px;">Hoàng Thị Ngọc</div>
                        <div style="font-size:12.5px; color:var(--fn-text-light);">Nhân viên văn phòng • Quận 1</div>
                    </div>
                    <div style="margin-left:auto; color:#f59e0b; font-size: 15px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                </div>
                <p style="font-size:14px; color:var(--fn-dark-muted); line-height:1.65; text-align: left;">
                    "Màn hình iPhone 13 Pro bị rơi sọc xanh, mình rất sợ bị luộc đồ. Nhờ FixNear mình chọn tiệm có cam kết ký tên lên toàn bộ linh kiện. Báo giá đúng như khung tham khảo trên web, không bị vẽ thêm bệnh!"
                </p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                    <span>🛠️ Thay màn hình iPhone 13 Pro</span>
                    <span>1 tháng trước</span>
                </div>
            </div>
            <!-- Review 3 -->
            <div class="fn-review-card fn-animate-on-scroll">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg,#10b981,#059669); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:19px; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">P</div>
                    <div style="text-align: left;">
                        <div style="font-weight:800; color:var(--fn-dark); font-size: 15px;">Phạm Văn Đạt</div>
                        <div style="font-size:12.5px; color:var(--fn-text-light);">Lập trình viên Freelance • TP. Thủ Đức</div>
                    </div>
                    <div style="margin-left:auto; color:#f59e0b; font-size: 15px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                </div>
                <p style="font-size:14px; color:var(--fn-dark-muted); line-height:1.65; text-align: left;">
                    "MacBook Pro bị chai pin phồng nắp đít máy. Tra cứu trên web thấy tiệm Viện Di Động gần nhà có sẵn pin chuẩn zin. Đem qua thay lấy ngay sau 45 phút, bảo hành 12 tháng rất an tâm."
                </p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                    <span>🛠️ Thay pin MacBook Pro 2020</span>
                    <span>3 ngày trước</span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- B2. Brands represented in the seed dataset (not a partnership claim). -->
<section class="fn-section fn-animate-on-scroll" style="background: #ffffff;">
    <div class="fn-container">
        <!-- Header Căn Giữa Hoàn Hảo -->
        <div style="text-align: center; max-width: 720px; margin: 0 auto 36px; display: flex; flex-direction: column; align-items: center;">
            <div class="fn-section-badge" style="margin: 0 auto 10px;">THƯƠNG HIỆU TRONG DỮ LIỆU</div>
            <h2 class="fn-section-title" style="text-align: center; margin: 4px 0 8px; font-size: 28px;">Khám Phá Theo <span style="color:#ea580c;">Tên Hệ Thống</span></h2>
            <p class="fn-section-subtitle" style="text-align: center; margin: 0 auto; max-width: 680px;">Đây là nhóm bản ghi để tra cứu, không đồng nghĩa với quan hệ đối tác hay chứng nhận chất lượng. Hãy mở Google Maps và website chính thức để kiểm tra thông tin hiện tại.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-top: 36px;">
            <!-- Partner 1: Điện Thoại Vui -->
            <a href="shops.php?q=Điện+Thoại+Vui" class="fn-brand-card fn-animate-on-scroll" style="text-decoration:none;">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                    <div style="width:50px; height:50px; border-radius:12px; background:#fee2e2; border:1px solid #fca5a5; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; color:#dc2626;">
                        📱
                    </div>
                    <div style="text-align:left;">
                        <h4 style="font-size:16px; font-weight:800; color:var(--fn-dark); margin:0;">Điện Thoại Vui</h4>
                        <div style="font-size:12px; color:#64748b; font-weight:700;">Điểm Google: xem tại nguồn</div>
                    </div>
                </div>
                <div style="font-size:12.5px; color:var(--fn-dark-muted); text-align:left; line-height:1.5;">
                    Nhóm bản ghi Điện Thoại Vui trong dữ liệu FixNear. Ưu đãi và điều kiện áp dụng cần xác nhận tại website chính thức hoặc từng cửa hàng.
                </div>
                <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; font-size:12px; font-weight:700; color:#ea580c; border-top:1px dashed #fed7aa; padding-top:10px;">
                    <span>Mở danh sách để đối chiếu</span> &rarr;
                </div>
            </a>

            <!-- Partner 2: Fastcare -->
            <a href="shops.php?q=Fastcare" class="fn-brand-card fn-animate-on-scroll" style="text-decoration:none;">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                    <div style="width:50px; height:50px; border-radius:12px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; color:#2563eb;">
                        ⚡
                    </div>
                    <div style="text-align:left;">
                        <h4 style="font-size:16px; font-weight:800; color:var(--fn-dark); margin:0;">Fastcare</h4>
                        <div style="font-size:12px; color:#64748b; font-weight:700;">Điểm Google: xem tại nguồn</div>
                    </div>
                </div>
                <div style="font-size:12.5px; color:var(--fn-dark-muted); text-align:left; line-height:1.5;">
                    Nhóm bản ghi FASTCARE trong dữ liệu FixNear. Giá, bảo hành và quy trình sửa cần được cửa hàng xác nhận trước khi giao máy.
                </div>
                <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; font-size:12px; font-weight:700; color:#ea580c; border-top:1px dashed #fed7aa; padding-top:10px;">
                    <span>Mở danh sách để đối chiếu</span> &rarr;
                </div>
            </a>

            <!-- Partner 3: Viện Di Động -->
            <a href="shops.php?q=Viện+Di+Động" class="fn-brand-card fn-animate-on-scroll" style="text-decoration:none;">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                    <div style="width:50px; height:50px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; color:#16a34a;">
                        🛠️
                    </div>
                    <div style="text-align:left;">
                        <h4 style="font-size:16px; font-weight:800; color:var(--fn-dark); margin:0;">Viện Di Động</h4>
                        <div style="font-size:12px; color:#64748b; font-weight:700;">Điểm Google: xem tại nguồn</div>
                    </div>
                </div>
                <div style="font-size:12.5px; color:var(--fn-dark-muted); text-align:left; line-height:1.5;">
                    Nhóm bản ghi Viện Di Động trong dữ liệu FixNear. Danh mục dịch vụ và giá hiện tại cần kiểm tra lại tại nguồn chính thức.
                </div>
                <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; font-size:12px; font-weight:700; color:#ea580c; border-top:1px dashed #fed7aa; padding-top:10px;">
                    <span>Mở danh sách để đối chiếu</span> &rarr;
                </div>
            </a>

            <!-- Partner 4: Bệnh Viện Điện Thoại 24h -->
            <a href="shops.php?q=Bệnh+Viện+Điện+Thoại+24h" class="fn-brand-card fn-animate-on-scroll" style="text-decoration:none;">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                    <div style="width:50px; height:50px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; color:#ea580c;">
                        🏥
                    </div>
                    <div style="text-align:left;">
                        <h4 style="font-size:16px; font-weight:800; color:var(--fn-dark); margin:0;">Bệnh Viện Điện Thoại 24h</h4>
                        <div style="font-size:12px; color:#64748b; font-weight:700;">Điểm Google: xem tại nguồn</div>
                    </div>
                </div>
                <div style="font-size:12.5px; color:var(--fn-dark-muted); text-align:left; line-height:1.5;">
                    Bản ghi cửa hàng trong dữ liệu dự án. Vui lòng mở nguồn và liên hệ trực tiếp để kiểm tra kinh nghiệm, giá và bảo hành.
                </div>
                <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; font-size:12px; font-weight:700; color:#ea580c; border-top:1px dashed #fed7aa; padding-top:10px;">
                    <span>Mở danh sách để đối chiếu</span> &rarr;
                </div>
            </a>
        </div>
    </div>
</section>

<!-- C. FAQ Accordion Section -->
<section class="fn-section fn-animate-on-scroll" style="background:#ffffff; border-top:1px solid var(--fn-border);">
    <div class="fn-container" style="max-width: 800px;">
        <div style="text-align: center; margin-bottom: 36px;">
            <div class="fn-section-badge">CÂU HỎI THƯỜNG GẶP</div>
            <h2 class="fn-section-title" style="margin-top: 8px;">Bạn Thắc Mắc? <span style="color:#ea580c;">Chúng Tôi Giải Đáp</span></h2>
        </div>

        <div class="fn-faq-list" style="text-align: left;">
            <div class="fn-faq-item">
                <button class="fn-faq-question" onclick="this.parentElement.classList.toggle('open')">
                    FixNear có phải là cửa hàng sửa chữa không?
                    <span class="fn-faq-icon">+</span>
                </button>
                <div class="fn-faq-answer">
                    <p>Không. FixNear là <strong>nền tảng tra cứu độc lập</strong>, cung cấp bản ghi cửa hàng để bạn tự so sánh. Giá chỉ được hiển thị khi có nguồn và ngày đối soát; các mục còn lại được ghi rõ là chưa xác minh.</p>
                </div>
            </div>
            <div class="fn-faq-item">
                <button class="fn-faq-question" onclick="this.parentElement.classList.toggle('open')">
                    Giá trên FixNear có phải báo giá chính thức không?
                    <span class="fn-faq-icon">+</span>
                </button>
                <div class="fn-faq-answer">
                    <p>Không. FixNear chỉ hiển thị giá khi bản ghi có nguồn và ngày đối soát. Giá cuối cùng vẫn phụ thuộc tình trạng thiết bị và phải được cửa hàng xác nhận sau khi kiểm tra.</p>
                </div>
            </div>
            <div class="fn-faq-item">
                <button class="fn-faq-question" onclick="this.parentElement.classList.toggle('open')">
                    Tiêu chuẩn minh bạch "3 Không" của FixNear là gì?
                    <span class="fn-faq-icon">+</span>
                </button>
                <div class="fn-faq-answer">
                    <p><strong>3 Không</strong> là bộ câu hỏi người dùng nên xác nhận với cửa hàng: có cho ký tên/quan sát linh kiện, có báo giá trước và có điều khoản bảo hành bằng văn bản hay không. FixNear chưa chứng nhận các cam kết này nếu bản ghi không có nguồn và ngày đối soát.</p>
                </div>
            </div>
            <div class="fn-faq-item">
                <button class="fn-faq-question" onclick="this.parentElement.classList.toggle('open')">
                    Tôi có thể gửi yêu cầu báo giá mà không cần đăng nhập không?
                    <span class="fn-faq-icon">+</span>
                </button>
                <div class="fn-faq-answer">
                    <p>Có! Bạn chỉ cần nhập số điện thoại và email để gửi yêu cầu báo giá. Đội ngũ FixNear sẽ chủ động liên hệ lại để tư vấn và gợi ý cửa hàng phù hợp.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
