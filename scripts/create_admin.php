<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/../config/db.php';

$email = mb_strtolower(trim((string)getenv('FIXNEAR_ADMIN_EMAIL')), 'UTF-8');
$name = trim((string)getenv('FIXNEAR_ADMIN_NAME')) ?: 'Quản trị viên FixNear';
$password = (string)getenv('FIXNEAR_ADMIN_PASSWORD');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    fwrite(STDERR, "FIXNEAR_ADMIN_EMAIL không hợp lệ.\n");
    exit(1);
}
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    fwrite(STDERR, "FIXNEAR_ADMIN_NAME cần từ 2 đến 100 ký tự.\n");
    exit(1);
}
if (strlen($password) < 12 || strlen($password) > 200) {
    fwrite(STDERR, "FIXNEAR_ADMIN_PASSWORD cần từ 12 đến 200 ký tự.\n");
    exit(1);
}

$ok = db()->createOrUpdateAdmin($email, $name, password_hash($password, PASSWORD_DEFAULT));
fwrite($ok ? STDOUT : STDERR, $ok ? "Đã tạo/cập nhật quản trị viên.\n" : "Không thể ghi dữ liệu quản trị viên.\n");
exit($ok ? 0 : 1);
