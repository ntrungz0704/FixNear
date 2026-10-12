<?php
// FixNear Root Entry Point
// Tự động điều hướng / chuyển tiếp vào public/index.php theo chuẩn kiến trúc MVC
$publicEntry = __DIR__ . '/public/index.php';
if (file_exists($publicEntry)) {
    require_once $publicEntry;
} else {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'FixNear Error: Không tìm thấy tệp public/index.php.';
}
