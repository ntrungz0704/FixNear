<?php
$pageTitle = "Bản Đồ Cửa Hàng & Chỉ Đường — FixNear";
require_once __DIR__ . '/../config/db.php';

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

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
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
        <div id="fn-map-loc-container" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span style="font-size:11px;color:#9a3412;font-weight:700;">Ghim tham khảo · xác nhận địa chỉ trước khi đến</span>
            <div id="fn-map-loc-badge-wrap" style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <?php if ($isLocated): ?>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;">
                    <span class="fn-pulse-dot" style="background:#10b981;"></span>
                    <span>📍 <?= htmlspecialchars($loc_name ?: 'Vị trí của bạn') ?></span>
                    <?php if ($focusShop && isset($focusShop['distance_km'])): ?>
                        <span id="fn-map-top-distance-text" style="color: #047857; font-weight: 800; margin-left: 4px;">(Ước tính ~<?= $focusShop['distance_km'] < 1 ? round($focusShop['distance_km'] * 1000) . 'm' : $focusShop['distance_km'] . ' km' ?>)</span>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    ⚙️ Đổi vị trí
                </button>
            <?php else: ?>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;">
                    <span>📍 Chưa bật GPS định vị</span>
                </div>
                <button type="button" id="fn-map-top-gps-btn" onclick="triggerDeviceGPS(this)" style="display: inline-flex; align-items: center; gap: 6px; background: #ea580c; border: 1px solid #ea580c; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 2px 8px rgba(234,88,12,0.3);">
                    🛰️ Bật GPS
                </button>
                <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    🏙️ Chọn quận
                </button>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div style="flex: 1; width: 100%; height: 100%; position: relative;">
        <div id="leaflet-map" style="width: 100%; height: 100%;"></div>

        <!-- Floating Info Card (Nếu đang xem 1 shop cụ thể) -->
        <?php if ($focusShop): ?>
        <div class="fn-map-floating-card" style="position: absolute; bottom: 24px; left: 24px; max-width: 420px; width: calc(100% - 48px); background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.18); padding: 18px; z-index: 1000; text-align: left;">
            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <?php if (!empty($focusShop['google_rating_verified'])): ?><span style="color: #ea580c; font-weight: 800; font-size: 13px;">★ <?= htmlspecialchars($focusShop['google_rating']) ?></span><?php endif; ?>
                        <?php if (isset($focusShop['distance_km'])): ?>
                        <span id="fn-map-card-distance-badge" style="font-size: 11px; background: #eff6ff; color: #1d4ed8; padding: 2px 7px; border-radius: 6px; font-weight: 700;">
                            📍 Ước tính ~<?= $focusShop['distance_km'] < 1 ? round($focusShop['distance_km'] * 1000) . 'm' : $focusShop['distance_km'] . ' km' ?>
                        </span>
                        <?php else: ?>
                        <span id="fn-map-card-distance-badge" style="display: none; font-size: 11px; background: #eff6ff; color: #1d4ed8; padding: 2px 7px; border-radius: 6px; font-weight: 700;"></span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($focusShop['name']) ?>
                    </h3>
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 6px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        📌 <?= htmlspecialchars($focusShop['address']) ?>
                    </p>
                    <p style="font-size:11px;color:#92400e;margin:0 0 6px;">Pin tham khảo; bấm Google Maps để xác nhận vị trí theo địa chỉ.</p>
                    <div style="font-size: 12px; color: #475569;">
                        ⏰ <?= htmlspecialchars($focusShop['opening_hours']) ?><?php if (!empty($focusShop['phone_verified'])): ?> • 📞 <a href="tel:<?= preg_replace('/\s+/', '', $focusShop['phone']) ?>" style="color: #16a34a; font-weight: 700; text-decoration: none;"><?= htmlspecialchars($focusShop['phone']) ?></a><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons bên trong thẻ nổi -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 14px;">
                <a href="<?= htmlspecialchars($focusShop['map_url'] ?: ('https://www.google.com/maps/search/?api=1&query=' . urlencode($focusShop['name'] . ', ' . $focusShop['address']))) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary fn-btn-sm" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 12px; font-size: 12.5px; font-weight: 800; color: #2563eb; background: #eff6ff; border-color: #bfdbfe; border-radius: 8px; text-decoration: none;">
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

    function updateMapLocationBadge(loc, distText) {
        const wrap = document.getElementById('fn-map-loc-badge-wrap');
        if (!wrap || !loc) return;
        const name = String(loc.name || 'Vị trí GPS của bạn').trim() || 'Vị trí GPS của bạn';
        const distBadge = distText ? `<span id="fn-map-top-distance-text" style="color: #047857; font-weight: 800; margin-left: 4px;">(${distText})</span>` : '';
        wrap.innerHTML = `
            <div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;">
                <span class="fn-pulse-dot" style="background:#10b981;"></span>
                <span>📍 <span id="fn-map-location-name"></span></span>
                ${distBadge}
            </div>
            <button type="button" onclick="openLocationModal()" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer;">
                ⚙️ Đổi vị trí
            </button>
        `;
        wrap.querySelector('#fn-map-location-name').textContent = name;
    }

    function applyUserLocationToMap(loc, panToUser) {
        if (!loc || typeof loc.lat !== 'number' || typeof loc.lng !== 'number') return;
        if (typeof updateUserLocationOnMap === 'function') {
            const res = updateUserLocationOnMap(loc, { panToUser: panToUser });
            let distText = '';
            if (res && res.distanceToFocus !== null && typeof res.distanceToFocus === 'number') {
                const formatted = (typeof formatDistanceKm === 'function') ? formatDistanceKm(res.distanceToFocus) : `~${res.distanceToFocus.toFixed(1)} km`;
                distText = `Ước tính ${formatted}`;
                const cardBadge = document.getElementById('fn-map-card-distance-badge');
                if (cardBadge) {
                    cardBadge.textContent = `📍 ${distText}`;
                    cardBadge.style.display = 'inline-block';
                }
            }
            updateMapLocationBadge(loc, distText);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Phối hợp nguồn vị trí: ưu tiên Server, fallback LocalStorage client
        const clientLoc = (typeof FixNearLocation !== 'undefined') ? FixNearLocation.get() : null;
        const initialUserLoc = FIXNEAR_USER_LOCATION || clientLoc;

        // Tọa độ trung tâm mặc định (Trung tâm TP.HCM hoặc vị trí cửa hàng focus)
        let defaultCenter = [10.7769, 106.7009];
        let defaultZoom = 12;

        <?php if ($focusShop && !empty($focusShop['latitude']) && !empty($focusShop['longitude'])): ?>
            defaultCenter = [<?= (float)$focusShop['latitude'] ?>, <?= (float)$focusShop['longitude'] ?>];
            defaultZoom = 16;
        <?php elseif ($isLocated): ?>
            defaultCenter = [<?= (float)$user_lat ?>, <?= (float)$user_lng ?>];
            defaultZoom = 14;
        <?php endif; ?>

        if (!FIXNEAR_FOCUS_SHOP_ID && initialUserLoc && typeof initialUserLoc.lat === 'number') {
            defaultCenter = [initialUserLoc.lat, initialUserLoc.lng];
            defaultZoom = 14;
        }

        // Khởi tạo bản đồ thông qua initFixnearMap từ map.js
        if (typeof initFixnearMap === 'function') {
            initFixnearMap(FIXNEAR_SHOPS_DATA, defaultCenter, initialUserLoc, defaultZoom, FIXNEAR_FOCUS_SHOP_ID);
        }

        // Nếu server chưa có vị trí nhưng client đã có (từ localStorage / session trước)
        if (!FIXNEAR_USER_LOCATION && clientLoc) {
            applyUserLocationToMap(clientLoc, !FIXNEAR_FOCUS_SHOP_ID);
            if (typeof FixNearLocation !== 'undefined') {
                FixNearLocation.syncToServer(clientLoc);
            }
        }

        // 2. Tự động kiểm tra quyền Geolocation của trình duyệt (nếu người dùng đã "Cho phép")
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (result) {
                if (result.state === 'granted') {
                    if (typeof FixNearLocation !== 'undefined') {
                        FixNearLocation.requestGPS(function (loc) {
                            applyUserLocationToMap(loc, !FIXNEAR_FOCUS_SHOP_ID);
                        }, null, { timeout: 8000 });
                    }
                }
            }).catch(function () {});
        }

        // 3. Lắng nghe sự kiện cập nhật vị trí toàn cục (từ Modal hoặc GPS)
        window.addEventListener('fixnear:location-updated', function (e) {
            if (e && e.detail) {
                applyUserLocationToMap(e.detail, !FIXNEAR_FOCUS_SHOP_ID);
            }
        });

        // 4. Invalidate size để bảo đảm Leaflet hiển thị toàn vẹn không bị lệch map tile
        setTimeout(function () {
            if (typeof fixnearMap !== 'undefined' && fixnearMap) {
                fixnearMap.invalidateSize();
            }
        }, 200);
        setTimeout(function () {
            if (typeof fixnearMap !== 'undefined' && fixnearMap) {
                fixnearMap.invalidateSize();
            }
        }, 600);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
