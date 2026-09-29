<?php
$file = 'd:/Kỹ năng làm việc/fixnear/model_detail.php';
$content = file_get_contents($file);

// Thay thế cả 3 khối card giá bằng regex
$pattern = '/<!-- 3 Cột khoảng giá linh kiện -->.*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s';

$replacement = '<!-- 3 Cột khoảng giá linh kiện -->
                    <div class="fn-model-price-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                        <!-- Standard -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 700; color: #64748b;">Tiêu Chuẩn (Loại 1)</span>
                                <span style="font-size: 11px; padding: 2px 6px; background: #e2e8f0; color: #475569; border-radius: 4px; font-weight: 700;">BH <?= $std[\'warrantyMonths\'] ?? 6 ?> tháng</span>
                            </div>
                            <div style="font-size: 18px; font-weight: 900; color: var(--fn-dark);">
                                <?= !empty($std[\'min\']) ? formatPrice($std[\'min\']) . \' – \' . formatPrice($std[\'max\']) : \'Liên hệ báo giá\' ?>
                            </div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Linh kiện phổ biến • Thay lấy liền</div>
                        </div>

                        <!-- OEM -->
                        <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 14px; border-radius: 8px; position: relative;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 800; color: #ea580c;">OEM Cao Cấp</span>
                                <span style="font-size: 11px; padding: 2px 6px; background: #ffedd5; color: #c2410c; border-radius: 4px; font-weight: 700;">BH <?= $oem[\'warrantyMonths\'] ?? 9 ?> tháng</span>
                            </div>
                            <div style="font-size: 18px; font-weight: 900; color: #c2410c;">
                                <?= !empty($oem[\'min\']) ? formatPrice($oem[\'min\']) . \' – \' . formatPrice($oem[\'max\']) : \'Liên hệ báo giá\' ?>
                            </div>
                            <div style="font-size: 11.5px; color: #ea580c; margin-top: 4px;">Foxconn / Pisen OEM • Khuyên dùng</div>
                        </div>

                        <!-- Genuine -->
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 800; color: #2563eb;">Chính Hãng / Zin</span>
                                <span style="font-size: 11px; padding: 2px 6px; background: #dbeafe; color: #1d4ed8; border-radius: 4px; font-weight: 700;">BH <?= $gen[\'warrantyMonths\'] ?? 12 ?> tháng</span>
                            </div>
                            <div style="font-size: 18px; font-weight: 900; color: #1e40af;">
                                <?= !empty($gen[\'min\']) ? formatPrice($gen[\'min\']) . \' – \' . formatPrice($gen[\'max\']) : \'Liên hệ báo giá\' ?>
                            </div>
                            <div style="font-size: 11.5px; color: #2563eb; margin-top: 4px;">Bóc máy zin / Chuẩn hãng • BH 12 tháng</div>
                        </div>
                    </div>';

$newContent = preg_replace($pattern, $replacement, $content);
if ($newContent !== null && $newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "THANH CONG: Da cap nhat gia 3 cap vao model_detail.php\n";
} else {
    echo "KHONG TIM THAY MATCH\n";
}
