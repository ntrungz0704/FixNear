<?php
$file = 'd:/Kỹ năng làm việc/fixnear/request_repair.php';
$content = file_get_contents($file);

$oldStr = "\$estimatedPrice = 'Chưa có báo giá đã xác minh — cần cửa hàng kiểm tra và xác nhận';";
$newStr = "\$estimatedPrice = 'Ước tính từ 250.000đ – 1.850.000đ (Bảo hành 6 – 12 tháng, thợ kiểm tra và xác nhận trực tiếp)';";

if (str_contains($content, $oldStr)) {
    $content = str_replace($oldStr, $newStr, $content);
    file_put_contents($file, $content);
    echo "SUCCESS: Da cap nhat estimatedPrice trong request_repair.php\n";
} else {
    echo "KHONG TIM THAY oldStr\n";
}
