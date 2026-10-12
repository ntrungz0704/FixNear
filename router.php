<?php
// Router an toàn cho PHP built-in server. Hỗ trợ chuẩn MVC (public/ thư mục web).
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$normalized = str_replace('\\', '/', $requestPath);
$isLocalClient = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'], true)
    || (PHP_SAPI === 'cli-server' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', ''], true))
    || (getenv('FIXNEAR_ENABLE_INSTALLER') === '1');

$blockedFiles = ['/start_server.bat', '/pasted text.txt'];
if (!$isLocalClient) {
    $blockedFiles[] = '/install.php';
}

$blocked = preg_match('#^/(?:config|data|scripts|tools|outputs|docs|audit)(?:/|$)#i', $normalized)
    || preg_match('#/(?:\.|[^/]+\.(?:sql|log|ini|env|bak|dist|md))$#i', $normalized)
    || in_array(strtolower($normalized), $blockedFiles, true);

if ($blocked) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Not found');
}

// 1. Trang chủ
if ($normalized === '/' || $normalized === '/index.php') {
    require __DIR__ . '/public/index.php';
    exit;
}

$candidate = __DIR__ . $normalized;
$publicCandidate = __DIR__ . '/public' . $normalized;

// 2. Tệp tĩnh trực tiếp tại root
if (is_file($candidate)) {
    return false;
}

// 3. Tệp PHP tại root (vd: install.php)
if (is_file($candidate . '.php')) {
    require $candidate . '.php';
    exit;
}

// 4. Thư mục tại root có index.php (vd: /admin/, /api/)
if (is_dir($candidate) && is_file(rtrim($candidate, '/') . '/index.php')) {
    if (!str_ends_with($requestPath, '/')) {
        header('Location: ' . $requestPath . '/', true, 301);
        exit;
    }
    return false;
}

// 5. Tệp tĩnh hoặc PHP trong public/
if (is_file($publicCandidate)) {
    if (str_ends_with($publicCandidate, '.php')) {
        require $publicCandidate;
        exit;
    }
    $mime = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    $ext = strtolower(pathinfo($publicCandidate, PATHINFO_EXTENSION));
    if (isset($mime[$ext])) {
        header('Content-Type: ' . $mime[$ext]);
    }
    readfile($publicCandidate);
    exit;
}

// 6. Tệp PHP không đuôi trong public/ (vd: /shops, /models, /map...)
if (is_file($publicCandidate . '.php')) {
    require $publicCandidate . '.php';
    exit;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not found';
