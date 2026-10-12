/**
 * FixNear - Leaflet.js Interactive Map Integration
 * Hỗ trợ hiển thị vị trí người dùng chính xác, định vị GPS tức thời,
 * tính khoảng cách thực tế (Haversine km/m) và vẽ lộ trình tới tiệm sửa chữa.
 */

let fixnearMap = null;
let shopMarkers = {};
let userMarker = null;
let userAccuracyCircle = null;
let defaultCenterMarker = null;
let focusPolyline = null;
let fixnearShopIcon = null;
let currentShopsData = [];
let currentUserLocation = null;
let currentFocusShopId = null;

function escapeMapText(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[char]));
}

function safeMapUrl(value) {
    try {
        const url = new URL(String(value || ''), window.location.href);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : 'https://maps.google.com';
    } catch (_) {
        return 'https://maps.google.com';
    }
}

/**
 * Tính khoảng cách đường chim bay giữa hai tọa độ địa lý (Haversine Formula)
 * @param {number} lat1
 * @param {number} lon1
 * @param {number} lat2
 * @param {number} lon2
 * @returns {number|null} Khoảng cách tính theo km
 */
function calculateHaversineKm(lat1, lon1, lat2, lon2) {
    const latA = Number(lat1);
    const lonA = Number(lon1);
    const latB = Number(lat2);
    const lonB = Number(lon2);
    if (!Number.isFinite(latA) || !Number.isFinite(lonA) || !Number.isFinite(latB) || !Number.isFinite(lonB)) {
        return null;
    }
    const R = 6371; // Bán kính trái đất ~6371 km
    const dLat = (latB - latA) * Math.PI / 180;
    const dLon = (lonB - lonA) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(latA * Math.PI / 180) * Math.cos(latB * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * Định dạng khoảng cách hiển thị thân thiện (m nếu < 1km, km nếu >= 1km)
 * @param {number|null} distanceKm
 * @returns {string}
 */
function formatDistanceKm(distanceKm) {
    if (!Number.isFinite(distanceKm) || distanceKm === null) return '';
    if (distanceKm < 1) {
        return `~${Math.round(distanceKm * 1000)}m`;
    }
    return `~${distanceKm.toFixed(1)} km`;
}

/**
 * Tạo icon marker riêng biệt cho vị trí người dùng / GPS
 */
function createUserIcon() {
    return L.divIcon({
        className: 'fn-user-map-pin',
        html: `
            <div style="width:22px;height:22px;background:#2563eb;border:3px solid #ffffff;border-radius:50%;box-shadow:0 0 14px rgba(37,99,235,0.7);position:relative;">
                <div style="position:absolute;inset:-7px;border-radius:50%;background:rgba(37,99,235,0.35);animation:pulse 2s infinite;"></div>
            </div>
        `,
        iconSize: [22, 22],
        iconAnchor: [11, 11]
    });
}

/**
 * Xây dựng nội dung popup cho cửa hàng
 */
function buildShopPopupContent(shop, userLoc) {
    const shopId = Number.parseInt(shop.id, 10);
    const latitude = Number(shop.latitude);
    const longitude = Number(shop.longitude);

    const savedDistance = shop.distance_km === null || shop.distance_km === undefined || shop.distance_km === ''
        ? NaN : Number(shop.distance_km);
    const distance = userLoc && typeof userLoc.lat === 'number' && typeof userLoc.lng === 'number'
        ? calculateHaversineKm(userLoc.lat, userLoc.lng, latitude, longitude)
        : savedDistance;
    const distanceText = Number.isFinite(distance) ? `Khoảng cách ước tính: <b>${formatDistanceKm(distance)}</b><br>` : '';
    const ratingText = shop.google_rating_verified
        ? `⭐ <b>${escapeMapText(shop.google_rating)}</b> (${escapeMapText(shop.google_reviews_count)} Google)<br>`
        : '';

    return `
        <div style="min-width:220px;font-family:inherit;">
            <div style="font-weight:800;font-size:14px;color:#0f172a;margin-bottom:4px;">${escapeMapText(shop.name)}</div>
            <div style="font-size:12px;color:#64748b;margin-bottom:6px;">${escapeMapText(shop.address)}</div>
            <div style="font-size:12px;color:#ea580c;margin-bottom:8px;">${ratingText}${distanceText}</div>
            <div style="font-size:11px;color:#92400e;margin-bottom:8px;">${shop.address_verified ? 'Pin tham khảo; mở chỉ đường để xác nhận vị trí.' : 'Địa chỉ chưa có nguồn chính thức; kiểm tra trước khi đến.'}</div>
            <div style="display:flex;gap:6px;">
                <a href="shop_detail.php?id=${shopId}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;border-radius:6px;font-size:11.5px;font-weight:bold;text-decoration:none;box-shadow:0 2px 6px rgba(234,88,12,0.3);">Xem chi tiết ↗</a>
                <a href="${safeMapUrl(shop.map_url)}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:6px 10px;background:#f1f5f9;color:#334155;border-radius:6px;font-size:11.5px;font-weight:bold;text-decoration:none;">Chỉ đường</a>
            </div>
        </div>`;
}

/**
 * Khởi tạo bản đồ tương tác Leaflet
 */
function initFixnearMap(shopsData, defaultCenter = [10.7769, 106.7009], userLocation = null, defaultZoom = 12, focusShopId = null) {
    const mapContainer = document.getElementById('leaflet-map');
    if (!mapContainer) return;

    currentShopsData = Array.isArray(shopsData) ? shopsData : [];
    currentFocusShopId = (focusShopId && Number.isInteger(Number(focusShopId))) ? Number(focusShopId) : null;

    // Tự động fallback lấy vị trí từ FixNearLocation nếu userLocation chưa được truyền từ server
    if (!userLocation && typeof window.FixNearLocation !== 'undefined' && window.FixNearLocation.hasLocation()) {
        userLocation = window.FixNearLocation.get();
    }
    currentUserLocation = userLocation;

    // Xác định tâm bản đồ và mức phóng to
    let center = [10.7769, 106.7009];
    let zoom = defaultZoom || 12;

    if (currentFocusShopId && Array.isArray(defaultCenter) && defaultCenter.length === 2) {
        center = defaultCenter;
        zoom = 16;
    } else if (currentUserLocation && typeof currentUserLocation.lat === 'number') {
        center = [currentUserLocation.lat, currentUserLocation.lng];
        zoom = 14;
    } else if (Array.isArray(defaultCenter) && defaultCenter.length === 2) {
        center = defaultCenter;
        zoom = defaultZoom || 12;
    }

    // Dọn dẹp bản đồ cũ nếu đã từng khởi tạo
    if (fixnearMap) {
        fixnearMap.remove();
        fixnearMap = null;
        shopMarkers = {};
        userMarker = null;
        userAccuracyCircle = null;
        defaultCenterMarker = null;
        focusPolyline = null;
    }

    fixnearMap = L.map('leaflet-map').setView(center, zoom);

    // Sử dụng tile layer OpenStreetMap miễn phí chất lượng cao
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
    }).addTo(fixnearMap);

    // Custom icon cho cửa hàng
    fixnearShopIcon = L.divIcon({
        className: 'fn-shop-map-pin',
        html: `
            <div style="width:34px;height:34px;background:linear-gradient(135deg, #f97316, #ea580c);border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 4px 12px rgba(234,88,12,0.4);font-size:14px;">
                🔧
            </div>
        `,
        iconSize: [34, 34],
        iconAnchor: [17, 17],
        popupAnchor: [0, -18]
    });

    // Thêm các marker cửa hàng
    currentShopsData.forEach(addFixnearShopMarker);

    // Đặt marker vị trí người dùng hoặc marker mặc định trung tâm TP.HCM
    if (currentUserLocation && typeof currentUserLocation.lat === 'number') {
        renderUserLocationOnMap(currentUserLocation);
    } else {
        renderDefaultCenterMarker();
    }

    // Nếu đang xem 1 shop cụ thể: mở popup và vẽ đường nối
    if (currentFocusShopId && shopMarkers[currentFocusShopId]) {
        const focusMarker = shopMarkers[currentFocusShopId];
        setTimeout(() => {
            focusMarker.openPopup();
        }, 300);

        if (currentUserLocation && typeof currentUserLocation.lat === 'number') {
            drawFocusPolyline(currentUserLocation, focusMarker.getLatLng());
        }
    }

    // Tương tác hover trên danh sách thẻ split (nếu có trên giao diện)
    const shopCards = document.querySelectorAll('.fn-split-shop-card');
    shopCards.forEach(card => {
        const id = card.dataset.shopId;
        card.addEventListener('mouseenter', () => {
            if (shopMarkers[id]) {
                const m = shopMarkers[id];
                fixnearMap.panTo(m.getLatLng());
                m.openPopup();
            }
        });
    });

    // Invalidate size để chống lệch hoặc méo tile do render flex/container
    setTimeout(() => { if (fixnearMap) fixnearMap.invalidateSize(); }, 200);
    setTimeout(() => { if (fixnearMap) fixnearMap.invalidateSize(); }, 600);
}

function renderDefaultCenterMarker() {
    if (!fixnearMap || defaultCenterMarker) return;
    const defaultPinIcon = L.divIcon({
        className: 'fn-school-map-pin',
        html: `
            <div style="background:#ea580c;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:bold;box-shadow:0 2px 6px rgba(0,0,0,0.3);white-space:nowrap;">
                📍 Mốc Trung tâm TP.HCM
            </div>
        `,
        iconSize: [140, 24],
        iconAnchor: [70, 12]
    });
    defaultCenterMarker = L.marker([10.7769, 106.7009], { icon: defaultPinIcon })
        .addTo(fixnearMap)
        .bindPopup("<b>Khu vực Trung tâm TP.HCM</b><br>Mốc định vị tính khoảng cách mặc định khi chưa bật GPS.");
}

function renderUserLocationOnMap(loc) {
    if (!fixnearMap || !loc || typeof loc.lat !== 'number' || typeof loc.lng !== 'number') return;

    if (defaultCenterMarker) {
        fixnearMap.removeLayer(defaultCenterMarker);
        defaultCenterMarker = null;
    }

    const locLatLng = [loc.lat, loc.lng];
    const locTitle = escapeMapText(loc.name || 'Vị trí của bạn');
    const accuracyText = (typeof loc.accuracy === 'number' && loc.accuracy > 0) ? ` (Độ chính xác: ~${Math.round(loc.accuracy)}m)` : '';

    if (userMarker) {
        userMarker.setLatLng(locLatLng);
        userMarker.setPopupContent(`<b>📍 ${locTitle}</b>${accuracyText}<br>Đang tính khoảng cách từ vị trí này.`);
    } else {
        userMarker = L.marker(locLatLng, { icon: createUserIcon(), zIndexOffset: 1000 })
            .addTo(fixnearMap)
            .bindPopup(`<b>📍 ${locTitle}</b>${accuracyText}<br>Đang tính khoảng cách từ vị trí này.`);
    }

    // Vẽ vòng bán kính accuracy nếu có
    if (typeof loc.accuracy === 'number' && loc.accuracy > 0 && loc.accuracy <= 2500) {
        if (userAccuracyCircle) {
            userAccuracyCircle.setLatLng(locLatLng);
            userAccuracyCircle.setRadius(loc.accuracy);
        } else {
            userAccuracyCircle = L.circle(locLatLng, {
                radius: loc.accuracy,
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.12,
                weight: 1.5,
                dashArray: '4, 4'
            }).addTo(fixnearMap);
        }
    } else if (userAccuracyCircle) {
        fixnearMap.removeLayer(userAccuracyCircle);
        userAccuracyCircle = null;
    }
}

function drawFocusPolyline(userLoc, shopLatLng) {
    if (!fixnearMap || !userLoc || !shopLatLng) return;
    const userLatLng = [userLoc.lat, userLoc.lng];

    if (focusPolyline) {
        focusPolyline.setLatLngs([userLatLng, shopLatLng]);
    } else {
        focusPolyline = L.polyline([userLatLng, shopLatLng], {
            color: '#2563eb',
            weight: 3.5,
            opacity: 0.8,
            dashArray: '8, 8'
        }).addTo(fixnearMap);
    }

    const dist = calculateHaversineKm(userLoc.lat, userLoc.lng, shopLatLng.lat, shopLatLng.lng);
    if (dist !== null && dist <= 35) {
        fixnearMap.fitBounds([userLatLng, shopLatLng], {
            padding: [60, 60],
            maxZoom: 16
        });
    }
}

function addFixnearShopMarker(shop) {
    const latitude = Number(shop.latitude);
    const longitude = Number(shop.longitude);
    if (!fixnearMap || !Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
    const shopId = Number.parseInt(shop.id, 10);
    if (!Number.isInteger(shopId) || shopId <= 0) return;
    const popupContent = buildShopPopupContent(shop, currentUserLocation);
    const marker = L.marker([latitude, longitude], { icon: fixnearShopIcon })
        .addTo(fixnearMap)
        .bindPopup(popupContent);
    shopMarkers[shopId] = marker;
}

function updateMapMarkers(shopsData) {
    if (!fixnearMap || !Array.isArray(shopsData)) return;
    Object.values(shopMarkers).forEach(marker => fixnearMap.removeLayer(marker));
    shopMarkers = {};
    currentShopsData = shopsData;
    shopsData.forEach(addFixnearShopMarker);
}

/**
 * Cập nhật động vị trí người dùng trên bản đồ mà không cần reload trang
 * @param {Object} loc { lat, lng, name, accuracy }
 * @param {Object} [options] { panToUser: boolean }
 * @returns {Object} { distanceToFocus: number|null }
 */
function updateUserLocationOnMap(loc, options = {}) {
    if (!fixnearMap || !loc || typeof loc.lat !== 'number' || typeof loc.lng !== 'number') return null;
    currentUserLocation = loc;

    renderUserLocationOnMap(loc);

    // Cập nhật lại popup khoảng cách cho tất cả các cửa hàng
    currentShopsData.forEach(shop => {
        const id = Number.parseInt(shop.id, 10);
        if (shopMarkers[id]) {
            shopMarkers[id].setPopupContent(buildShopPopupContent(shop, currentUserLocation));
        }
    });

    let distToFocus = null;

    // Nếu đang xem 1 shop cụ thể
    if (currentFocusShopId && shopMarkers[currentFocusShopId]) {
        const focusMarker = shopMarkers[currentFocusShopId];
        const focusLatLng = focusMarker.getLatLng();
        drawFocusPolyline(loc, focusLatLng);
        distToFocus = calculateHaversineKm(loc.lat, loc.lng, focusLatLng.lat, focusLatLng.lng);
    } else if (options.panToUser !== false) {
        fixnearMap.flyTo([loc.lat, loc.lng], 14, { duration: 1.0 });
    }

    return {
        distanceToFocus: distToFocus
    };
}
