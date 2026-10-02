<?php
$pageTitle = "Bản Đồ Cửa Hàng & Chỉ Đường — FixNear";
require_once __DIR__ . '/config/db.php';

$userLoc = getUserLocation();
$user_lat = $userLoc['lat'] ?? null;
$user_lng = $userLoc['lng'] ?? null;
$loc_name = $userLoc['name'] ?? null;
$isLocated = !empty($user_lat) && !empty($user_lng);

$shopId = (int)($_GET['shop_id'] ?? 0);
$focusShop = null;

if ($shopId > 0) {
    $focusShop = db()->getShopById($shopId, $user_lat, $user_lng);
    if ($focusShop) {
        $pageTitle = "Vị Trí " . $focusShop['name'] . " — Bản Đồ FixNear";
    }
}

// Lấy danh sách cửa hàng phục vụ hiển thị
$allShops = db()->getShops([
    'user_lat' => $user_lat,
    'user_lng' => $user_lng
]);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="display: flex; flex-direction: column; height: calc(100vh - 68px); position: relative; overflow: hidden; background: #0f172a;">
    
    <!-- Top Action Bar -->
    <div style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.06);">
        <!-- Nút Trở Lại Danh Sách Cửa Hàng -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="shops.php" style="display: inline-flex; align-items: center; gap: 8px; color: #0f172a; font-size: 13.5px; font-weight: 800; text-decoration: none; padding: 8px 16px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                <span style="color: #ea580c; font-size: 16px; font-weight: 900;">←</span>
                <span>Quay lại danh sách cửa hàng</span>
            </a>

            <?php if ($focusShop): ?>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #334155;">
                <span style="color: #94a3b8;">|</span>
                <span>Đang xem:</span>
                <strong style="color: #ea580c; font-weight: 800; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: bottom;">
                    <?= htmlspecialchars($focusShop['name']) ?>
                </strong>
                <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; color: #475569;">
                    📍 <?= htmlspecialchars($focusShop['district']) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Trạng thái vị trí người dùng -->
        <div style="display: flex; align-items: center; gap: 10px;">
            <?php if ($isLocated): ?>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;">
                    <span class="fn-pulse-dot" style="background:#10b981;"></span>
                    <span>📍 <?= htmlspecialchars($loc_name ?: 'Vị trí của bạn') ?></span>
                    <?php if ($focusShop && isset($focusShop['distance_km'])): ?>
                        <span style="color: #047857; font-weight: 800; margin-left: 4px;">(Cách tiệm ~<?= $focusShop['distance_km'] < 1 ? round($focusShop['distance_km'] * 1000) . 'm' : $focusShop['distance_km'] . ' km' ?>)</span>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    ⚙️ Đổi vị trí
                </button>
            <?php else: ?>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;">
                    <span>📍 Chưa bật GPS định vị</span>
                </div>
                <button type="button" onclick="triggerDeviceGPS(this)" style="display: inline-flex; align-items: center; gap: 6px; background: #ea580c; border: 1px solid #ea580c; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 2px 8px rgba(234,88,12,0.3);">
                    🛰️ Bật GPS
                </button>
                <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    🏙️ Chọn quận
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map Container -->
    <div style="flex: 1; width: 100%; height: 100%; position: relative;">
        <div id="leaflet-map" style="width: 100%; height: 100%;"></div>

        <!-- Floating Info Card (Nếu đang xem 1 shop cụ thể) -->
        <?php if ($focusShop): ?>
        <div class="fn-map-floating-card" style="position: absolute; bottom: 24px; left: 24px; max-width: 420px; width: calc(100% - 48px); background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.18); padding: 18px; z-index: 1000; text-align: left;">
            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <img src="<?= htmlspecialchars($focusShop['image']) ?>" alt="<?= htmlspecialchars($focusShop['name']) ?>" style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; flex-shrink: 0; border: 1px solid #e2e8f0;">
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <span style="color: #ea580c; font-weight: 800; font-size: 13px;">
                            <?= !empty($focusShop['google_rating_verified']) ? '★ ' . htmlspecialchars($focusShop['google_rating']) : 'Chưa đối soát Google' ?>
                        </span>
                        <?php if (isset($focusShop['distance_km'])): ?>
                        <span style="font-size: 11px; background: #eff6ff; color: #1d4ed8; padding: 2px 7px; border-radius: 6px; font-weight: 700;">
                            📍 ~<?= $focusShop['distance_km'] < 1 ? round($focusShop['distance_km'] * 1000) . 'm' : $focusShop['distance_km'] . ' km' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($focusShop['name']) ?>
                    </h3>
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 6px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        📌 <?= htmlspecialchars($focusShop['address']) ?>
                    </p>
                    <div style="font-size: 12px; color: #475569;">
                        ⏰ <?= htmlspecialchars($focusShop['opening_hours']) ?> • 📞 <a href="tel:<?= preg_replace('/\s+/', '', $focusShop['phone']) ?>" style="color: #16a34a; font-weight: 700; text-decoration: none;"><?= htmlspecialchars($focusShop['phone']) ?></a>
                    </div>
                </div>
            </div>

            <!-- Action Buttons bên trong thẻ nổi -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 14px;">
                <a href="<?= htmlspecialchars($focusShop['map_url'] ?: ('https://www.google.com/maps/dir/?api=1&destination=' . urlencode($focusShop['latitude'] . ',' . $focusShop['longitude']))) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 12px; font-size: 12.5px; font-weight: 800; color: #2563eb; background: #eff6ff; border-color: #bfdbfe; border-radius: 8px; text-decoration: none;">
                    🗺️ Google Maps ↗
                </a>
                <a href="shop_detail.php?id=<?= $focusShop['id'] ?>" class="fn-btn fn-btn-primary fn-btn-sm" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 12px; font-size: 12.5px; font-weight: 800; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 8px rgba(234,88,12,0.25);">
                    👁️ Xem cửa hàng ➔
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Dữ liệu JSON cho bản đồ -->
<script>
    const FIXNEAR_SHOPS_DATA = <?= json_encode($allShops, JSON_UNESCAPED_UNICODE) ?>;
    const FIXNEAR_FOCUS_SHOP_ID = <?= $focusShop ? (int)$focusShop['id'] : 'null' ?>;
    const FIXNEAR_USER_LOCATION = <?= $isLocated ? json_encode([
        'lat' => (float)$user_lat,
        'lng' => (float)$user_lng,
        'name' => $loc_name ?: 'Vị trí của bạn'
    ], JSON_UNESCAPED_UNICODE) : 'null' ?>;

    document.addEventListener('DOMContentLoaded', function () {
        // Tọa độ trung tâm mặc định (Trung tâm TP.HCM hoặc vị trí cửa hàng focus)
        let defaultCenter = [10.7769, 106.7009];
        let defaultZoom = 12;

        <?php if ($focusShop && !empty($focusShop['latitude']) && !empty($focusShop['longitude'])): ?>
            defaultCenter = [<?= (float)$focusShop['latitude'] ?>, <?= (float)$focusShop['longitude'] ?>];
            defaultZoom = 16;
        <?php elseif ($isLocated): ?>
            defaultCenter = [<?= (float)$user_lat ?>, <?= (float)$user_lng ?>];
            defaultZoom = 13;
        <?php endif; ?>

        // Khởi tạo bản đồ thông qua initFixnearMap từ map.js
        if (typeof initFixnearMap === 'function') {
            initFixnearMap(FIXNEAR_SHOPS_DATA, defaultCenter, FIXNEAR_USER_LOCATION);

            // Nếu đang xem 1 shop cụ thể, pan tới vị trí và mở popup
            if (FIXNEAR_FOCUS_SHOP_ID && fixnearMap && shopMarkers[FIXNEAR_FOCUS_SHOP_ID]) {
                const marker = shopMarkers[FIXNEAR_FOCUS_SHOP_ID];
                fixnearMap.setView(marker.getLatLng(), 16);
                setTimeout(() => {
                    marker.openPopup();
                }, 300);

                // Vẽ đường nối giữa người dùng và cửa hàng (nếu có vị trí người dùng)
                if (FIXNEAR_USER_LOCATION && typeof FIXNEAR_USER_LOCATION.lat === 'number') {
                    const userLatLng = [FIXNEAR_USER_LOCATION.lat, FIXNEAR_USER_LOCATION.lng];
                    const shopLatLng = marker.getLatLng();

                    // Vẽ đường nét đứt biểu thị lộ trình hướng tới tiệm
                    const pathLine = L.polyline([userLatLng, shopLatLng], {
                        color: '#2563eb',
                        weight: 3,
                        opacity: 0.75,
                        dashArray: '8, 8'
                    }).addTo(fixnearMap);

                    // Tự động căn chỉnh màn hình hiển thị cả người dùng và cửa hàng nếu khoảng cách < 30km
                    <?php if ($focusShop && isset($focusShop['distance_km']) && $focusShop['distance_km'] <= 30): ?>
                        fixnearMap.fitBounds([userLatLng, shopLatLng], {
                            padding: [60, 60],
                            maxZoom: 16
                        });
                    <?php endif; ?>
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
