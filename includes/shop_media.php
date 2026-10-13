<?php
declare(strict_types=1);

/** Chỉ hiển thị ảnh đã đối chiếu đúng chi nhánh; lỗi tải ảnh dùng thẻ thông tin. */
function fixnearShopMedia(array $shop): string
{
    $image = trim((string)($shop['image'] ?? ''));

    $shopName = htmlspecialchars((string)($shop['name'] ?? 'Cửa hàng sửa chữa'), ENT_QUOTES, 'UTF-8');
    $district = htmlspecialchars((string)($shop['district'] ?? 'TP.HCM'), ENT_QUOTES, 'UTF-8');
    $ward = htmlspecialchars((string)($shop['ward'] ?? ''), ENT_QUOTES, 'UTF-8');
    $locationText = $ward ? $ward . ', ' . $district : $district;

    $src = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    $hasImage = $image !== '';

    return '<div class="fn-shop-media-container" style="position:relative;width:100%;height:100%;overflow:hidden;background:#fff7ed;">'
        . ($hasImage ? '<img src="' . $src . '" alt="Ảnh chi nhánh ' . $shopName . '" loading="lazy" referrerpolicy="no-referrer" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.display=\'none\';if(this.nextElementSibling)this.nextElementSibling.style.display=\'flex\';">' : '')
        . '<div class="fn-shop-card-fallback" style="display:' . ($hasImage ? 'none' : 'flex') . ';position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 60%,#fed7aa 100%);color:#c2410c;padding:16px;text-align:center;box-sizing:border-box;">'
        . '<div style="width:48px;height:48px;border-radius:50%;background:#ea580c;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 4px 12px rgba(234,88,12,0.3);margin-bottom:8px;">🏪</div>'
        . '<div style="font-size:13px;font-weight:800;color:#9a3412;line-height:1.3;max-width:200px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;">' . $shopName . '</div>'
        . '<div style="font-size:11.5px;color:#c2410c;font-weight:700;display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,0.85);padding:2px 8px;border-radius:12px;border:1px solid #fdba74;">Chưa có ảnh đã xác minh · ' . $locationText . '</div>'
        . '</div>'
        . '</div>';
}
