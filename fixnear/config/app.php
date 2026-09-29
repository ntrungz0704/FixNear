<?php
declare(strict_types=1);

/**
 * Cấu hình nền tảng dùng chung.
 * Biến môi trường luôn được ưu tiên hơn config/local.php (không commit file này).
 */
$fixnearLocalConfig = [];
$fixnearLocalFile = __DIR__ . '/local.php';
if (is_file($fixnearLocalFile)) {
    $loaded = require $fixnearLocalFile;
    if (is_array($loaded)) {
        $fixnearLocalConfig = $loaded;
    }
}

function fixnearConfig(string $envName, string $localKey, mixed $default = null): mixed
{
    global $fixnearLocalConfig;
    $envValue = getenv($envName);
    if ($envValue !== false && $envValue !== '') {
        return $envValue;
    }
    return $fixnearLocalConfig[$localKey] ?? $default;
}

define('FIXNEAR_ENV', strtolower((string) fixnearConfig('FIXNEAR_ENV', 'environment', 'development')));
define('FIXNEAR_IS_PRODUCTION', FIXNEAR_ENV === 'production');
define('FIXNEAR_DATA_DIR', rtrim((string) fixnearConfig('FIXNEAR_DATA_DIR', 'data_dir', dirname(__DIR__) . '/data'), "/\\") . DIRECTORY_SEPARATOR);

// Cấu hình kết nối Cơ sở dữ liệu MySQL (Chuẩn XAMPP / Laragon)
define('DB_HOST', (string) fixnearConfig('DB_HOST', 'db_host', '127.0.0.1'));
define('DB_PORT', (int) fixnearConfig('DB_PORT', 'db_port', 3306));
define('DB_NAME', (string) fixnearConfig('DB_NAME', 'db_name', 'fixnear_db'));
define('DB_USER', (string) fixnearConfig('DB_USER', 'db_user', 'root'));
define('DB_PASS', (string) fixnearConfig('DB_PASS', 'db_pass', ''));

date_default_timezone_set((string) fixnearConfig('FIXNEAR_TIMEZONE', 'timezone', 'Asia/Ho_Chi_Minh'));
error_reporting(E_ALL);
ini_set('display_errors', FIXNEAR_IS_PRODUCTION ? '0' : '1');
ini_set('log_errors', '1');

$httpsEnabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_name('fixnear_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $httpsEnabled,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    if ($httpsEnabled && FIXNEAR_IS_PRODUCTION) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function safeLocalRedirect(mixed $candidate, string $fallback = 'index.php'): string
{
    if (!is_string($candidate) || $candidate === '' || strlen($candidate) > 500) {
        return $fallback;
    }
    if (str_contains($candidate, "\r") || str_contains($candidate, "\n") || str_contains($candidate, "\\")) {
        return $fallback;
    }
    $parts = parse_url($candidate);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || str_starts_with($candidate, '//')) {
        return $fallback;
    }
    $path = $parts['path'] ?? '';
    if ($path === '' || str_contains(rawurldecode($path), '..')) {
        return $fallback;
    }
    return $candidate;
}

function enforceRateLimit(string $key, int $limit, int $windowSeconds): bool
{
    $now = time();
    $bucketKey = 'rate_limit_' . preg_replace('/[^a-z0-9_-]/i', '_', $key);
    $attempts = array_values(array_filter(
        $_SESSION[$bucketKey] ?? [],
        static fn($timestamp) => is_int($timestamp) && $timestamp > $now - $windowSeconds
    ));
    if (count($attempts) >= $limit) {
        $_SESSION[$bucketKey] = $attempts;
        return false;
    }
    $attempts[] = $now;
    $_SESSION[$bucketKey] = $attempts;
    return true;
}
