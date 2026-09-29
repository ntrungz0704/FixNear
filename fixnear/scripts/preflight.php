<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/../config/app.php';

$errors = [];
$warnings = [];
$passes = [];

$check = static function (bool $condition, string $success, string $failure) use (&$errors, &$passes): void {
    if ($condition) {
        $passes[] = $success;
        return;
    }
    $errors[] = $failure;
};

$check(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP ' . PHP_VERSION, 'Cần PHP 8.1 trở lên; hiện tại: ' . PHP_VERSION);
foreach (['json', 'mbstring', 'openssl'] as $extension) {
    $check(extension_loaded($extension), "Extension {$extension}", "Thiếu PHP extension: {$extension}");
}

$dataDir = FIXNEAR_DATA_DIR;
$check(is_dir($dataDir), 'Thư mục dữ liệu tồn tại', "Không tìm thấy thư mục dữ liệu: {$dataDir}");
$check(is_dir($dataDir) && is_readable($dataDir), 'Thư mục dữ liệu đọc được', "PHP không đọc được thư mục dữ liệu: {$dataDir}");
$check(is_dir($dataDir) && is_writable($dataDir), 'Thư mục dữ liệu ghi được', "PHP không ghi được thư mục dữ liệu: {$dataDir}");

$requiredJson = ['shops.json', 'services.json', 'shop_services.json', 'faults.json'];
foreach ($requiredJson as $filename) {
    $path = $dataDir . $filename;
    if (!is_file($path)) {
        $errors[] = "Thiếu file dữ liệu bắt buộc: {$path}";
        continue;
    }
    try {
        json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $passes[] = "JSON hợp lệ: {$filename}";
    } catch (JsonException $exception) {
        $errors[] = "JSON lỗi {$filename}: {$exception->getMessage()}";
    }
}

if (!FIXNEAR_IS_PRODUCTION) {
    $warnings[] = 'FIXNEAR_ENV chưa đặt thành production.';
}

$projectRoot = realpath(__DIR__ . '/..');
$resolvedDataDir = realpath($dataDir);
if ($projectRoot !== false && $resolvedDataDir !== false) {
    $rootPrefix = rtrim(str_replace('\\', '/', $projectRoot), '/') . '/';
    $dataPath = rtrim(str_replace('\\', '/', $resolvedDataDir), '/') . '/';
    if (str_starts_with(strtolower($dataPath), strtolower($rootPrefix))) {
        $warnings[] = 'Thư mục dữ liệu đang nằm trong thư mục dự án; trên host hãy chuyển ra ngoài public_html.';
    }
}

$usersFile = $dataDir . 'users.json';
$adminCount = 0;
if (is_file($usersFile)) {
    try {
        $users = json_decode((string) file_get_contents($usersFile), true, 512, JSON_THROW_ON_ERROR);
        foreach (is_array($users) ? $users : [] as $user) {
            if (($user['role'] ?? '') === 'admin' && !empty($user['password'])) {
                $adminCount++;
            }
        }
    } catch (JsonException $exception) {
        $errors[] = "JSON lỗi users.json: {$exception->getMessage()}";
    }
}
if ($adminCount === 0) {
    $warnings[] = 'Chưa có quản trị viên; chạy scripts/create_admin.php sau khi cấu hình thư mục dữ liệu production.';
} else {
    $passes[] = "Có {$adminCount} tài khoản quản trị trong môi trường hiện tại.";
}

foreach ($passes as $message) {
    fwrite(STDOUT, "[OK] {$message}\n");
}
foreach ($warnings as $message) {
    fwrite(STDOUT, "[CANH BAO] {$message}\n");
}
foreach ($errors as $message) {
    fwrite(STDERR, "[LOI] {$message}\n");
}

fwrite(STDOUT, sprintf("\nKet qua: %d dat, %d canh bao, %d loi.\n", count($passes), count($warnings), count($errors)));
exit($errors === [] ? 0 : 1);
