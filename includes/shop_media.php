<?php
declare(strict_types=1);

/**
 * Hiển thị ảnh cửa hàng. Nếu có link ảnh thì hiển thị thẻ <img>, 
 * nếu chưa có hoặc ảnh lỗi thì hiển thị placeholder mặc định.
 */
function fixnearShopMedia(array $shop): string
{
    $image = trim((string)($shop['image'] ?? ''));
    if ($image !== '' && ($shop['image_kind'] ?? '') === 'verified_branch') {
        $alt = htmlspecialchars((string)($shop['name'] ?? 'Cửa hàng'), ENT_QUOTES, 'UTF-8');
        $src = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $src . '" alt="' . $alt . '" loading="lazy" style="width:100%;height:100%;object-fit:cover;" '
            . 'onerror="this.style.display=\'none\';if(this.nextElementSibling)this.nextElementSibling.style.display=\'flex\';">'
            . '<div class="fn-shop-no-photo" role="img" aria-label="Ảnh không khả dụng" style="display:none;">'
            . '<span class="fn-shop-no-photo-icon" aria-hidden="true">⌖</span>'
            . '<span>Ảnh không khả dụng</span>'
            . '</div>';
    }

    return '<div class="fn-shop-no-photo" role="img" aria-label="Chưa có ảnh được xác thực cho chi nhánh này">'
        . '<span class="fn-shop-no-photo-icon" aria-hidden="true">⌖</span>'
        . '<span>Chưa có ảnh đúng chi nhánh</span>'
        . '</div>';
}
