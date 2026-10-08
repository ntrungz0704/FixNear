<?php
declare(strict_types=1);

/** Never present a website logo or product screenshot as a branch photo. */
function fixnearShopMedia(array $shop): string
{
    return '<div class="fn-shop-no-photo" role="img" aria-label="Chưa có ảnh được xác thực cho chi nhánh này">'
        . '<span class="fn-shop-no-photo-icon" aria-hidden="true">⌖</span>'
        . '<span>Chưa có ảnh đúng chi nhánh</span>'
        . '</div>';
}
