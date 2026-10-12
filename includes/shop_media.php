<?php
declare(strict_types=1);

/**
 * Hiển thị ảnh cửa hàng chuyên nghiệp:
 * 1. Tải ảnh chính/raw_image của cửa hàng với referrerpolicy="no-referrer" để tránh bị chặn hotlinking.
 * 2. Nếu link ảnh gốc bị lỗi hoặc hết hạn, tự động chuyển sang ảnh đại diện tiệm sửa chữa chất lượng cao.
 * 3. Nếu không có internet, hiển thị card thương hiệu tiệm sửa chữa đẹp mắt kèm icon và vị trí quận/huyện.
 */
function fixnearShopMedia(array $shop): string
{
    $image = trim((string)($shop['image'] ?? ''));
    if ($image === '' && !empty($shop['raw_image']) && getenv('FIXNEAR_ENV') !== 'test') {
        $image = trim((string)$shop['raw_image']);
    }

    $shopName = htmlspecialchars((string)($shop['name'] ?? 'Cửa hàng sửa chữa'), ENT_QUOTES, 'UTF-8');
    $district = htmlspecialchars((string)($shop['district'] ?? 'TP.HCM'), ENT_QUOTES, 'UTF-8');
    $ward = htmlspecialchars((string)($shop['ward'] ?? ''), ENT_QUOTES, 'UTF-8');
    $locationText = $ward ? $ward . ', ' . $district : $district;

    // Ảnh đại diện công nghệ dự phòng theo nhóm thiết bị
    $devices = (array)($shop['devices'] ?? ['phone', 'laptop']);
    $isLaptop = in_array('laptop', $devices, true) || in_array('mac', $devices, true) || in_array('pc', $devices, true);
    $fallbackStockImage = $isLaptop 
        ? 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?w=600&auto=format&fit=crop&q=80'
        : 'https://images.unsplash.com/photo-1588508065123-287b28e013da?w=600&auto=format&fit=crop&q=80';

    $src = $image !== '' ? htmlspecialchars($image, ENT_QUOTES, 'UTF-8') : $fallbackStockImage;

    return '<div class="fn-shop-media-container" style="position:relative;width:100%;height:100%;overflow:hidden;background:#fff7ed;">'
        . '<img src="' . $src . '" alt="' . $shopName . '" loading="lazy" referrerpolicy="no-referrer" style="width:100%;height:100%;object-fit:cover;display:block;" '
        . 'onerror="if(this.src!=\'' . $fallbackStockImage . '\'){this.src=\'' . $fallbackStockImage . '\';}else{this.style.display=\'none\';if(this.nextElementSibling)this.nextElementSibling.style.display=\'flex\';}">'
        . '<div class="fn-shop-card-fallback" style="display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 60%,#fed7aa 100%);color:#c2410c;padding:16px;text-align:center;box-sizing:border-box;">'
        . '<div style="width:48px;height:48px;border-radius:50%;background:#ea580c;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 4px 12px rgba(234,88,12,0.3);margin-bottom:8px;">🏪</div>'
        . '<div style="font-size:13px;font-weight:800;color:#9a3412;line-height:1.3;max-width:200px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;">' . $shopName . '</div>'
        . '<div style="font-size:11.5px;color:#c2410c;font-weight:700;display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,0.85);padding:2px 8px;border-radius:12px;border:1px solid #fdba74;">📍 ' . $locationText . '</div>'
        . '</div>'
        . '</div>';
}
