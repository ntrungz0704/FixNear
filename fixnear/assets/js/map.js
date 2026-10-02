/**
 * FixNear - Leaflet.js Interactive Map Integration
 */

let fixnearMap = null;
let shopMarkers = {};
let userMarker = null;
let fixnearShopIcon = null;

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

function initFixnearMap(shopsData, defaultCenter = [10.7769, 106.7009], userLocation = null, defaultZoom = 12) {
    const mapContainer = document.getElementById('leaflet-map');
    if (!mapContainer) return;

    // Ưu tiên defaultCenter nếu truyền vào, sau đó tới userLocation, cuối cùng là tâm TP.HCM
    const center = defaultCenter || (userLocation ? [userLocation.lat, userLocation.lng] : [10.7769, 106.7009]);
    const zoom = defaultZoom || 12;
    fixnearMap = L.map('leaflet-map').setView(center, zoom);

    // Sử dụng lớp bản đồ OpenStreetMap miễn phí chất lượng cao
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
    }).addTo(fixnearMap);

    // Marker riêng biệt cho vị trí sinh viên / người dùng
    if (userLocation) {
        const userIcon = L.divIcon({
            className: 'fn-user-map-pin',
            html: `
                <div style="width:20px;height:20px;background:#2563eb;border:3px solid #fff;border-radius:50%;box-shadow:0 0 10px rgba(37,99,235,0.6);position:relative;">
                    <div style="position:absolute;inset:-6px;border-radius:50%;background:rgba(37,99,235,0.3);animation:pulse 2s infinite;"></div>
                </div>
            `,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });

        const locTitle = escapeMapText(userLocation.name || 'Vị trí của bạn');
        userMarker = L.marker([userLocation.lat, userLocation.lng], { icon: userIcon })
            .addTo(fixnearMap)
            .bindPopup(`<b>📍 ${locTitle}</b><br>Đang tính khoảng cách từ đây.`);
    } else {
        // Đánh dấu mốc trung tâm TP.HCM
        const defaultPinIcon = L.divIcon({
            className: 'fn-school-map-pin',
            html: `
                <div style="background:#ea580c;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:bold;box-shadow:0 2px 6px rgba(0,0,0,0.3);white-space:nowrap;">
                    📍 Trung tâm TP.HCM
                </div>
            `,
            iconSize: [110, 24],
            iconAnchor: [55, 12]
        });
        L.marker([10.7769, 106.7009], { icon: defaultPinIcon })
            .addTo(fixnearMap)
            .bindPopup("<b>Khu vực Trung tâm TP.HCM</b><br>Mốc định vị tính khoảng cách mặc định.");
    }

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
    shopsData.forEach(addFixnearShopMarker);

    // Tương tác khi di chuột vào thẻ cửa hàng ở cột bên trái
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
}

function addFixnearShopMarker(shop) {
    const latitude = Number(shop.latitude);
    const longitude = Number(shop.longitude);
    if (!fixnearMap || !Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
    const shopId = Number.parseInt(shop.id, 10);
    if (!Number.isInteger(shopId) || shopId <= 0) return;
    const distance = Number(shop.distance_km);
    const distanceText = Number.isFinite(distance) ? `Cách bạn: <b>${distance.toFixed(1)} km</b><br>` : '';
    const ratingText = shop.google_rating_verified
        ? `⭐ <b>${escapeMapText(shop.google_rating)}</b> (${escapeMapText(shop.google_reviews_count)} Google)<br>`
        : 'Google: chưa đối soát<br>';
    const popupContent = `
        <div style="min-width:210px;font-family:inherit;">
            <div style="font-weight:800;font-size:14px;color:#0f172a;margin-bottom:4px;">${escapeMapText(shop.name)}</div>
            <div style="font-size:12px;color:#64748b;margin-bottom:6px;">${escapeMapText(shop.address)}</div>
            <div style="font-size:12px;color:#ea580c;margin-bottom:8px;">${ratingText}${distanceText}</div>
            <div style="display:flex;gap:6px;">
                <a href="shop_detail.php?id=${shopId}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;border-radius:6px;font-size:11.5px;font-weight:bold;text-decoration:none;box-shadow:0 2px 6px rgba(234,88,12,0.3);">Xem chi tiết ↗</a>
                <a href="${safeMapUrl(shop.map_url)}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:6px 10px;background:#f1f5f9;color:#334155;border-radius:6px;font-size:11.5px;font-weight:bold;text-decoration:none;">Chỉ đường</a>
            </div>
        </div>`;
    const marker = L.marker([latitude, longitude], { icon: fixnearShopIcon })
        .addTo(fixnearMap)
        .bindPopup(popupContent);
    shopMarkers[shopId] = marker;
}

function updateMapMarkers(shopsData) {
    if (!fixnearMap || !Array.isArray(shopsData)) return;
    Object.values(shopMarkers).forEach(marker => fixnearMap.removeLayer(marker));
    shopMarkers = {};
    shopsData.forEach(addFixnearShopMarker);
}
