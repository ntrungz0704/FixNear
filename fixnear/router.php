<?php
// Router an toàn cho PHP built-in server. Apache production dùng .htaccess.
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$normalized = str_replace('\\', '/', $requestPath);
$isLocalClient = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'], true)
    || (PHP_SAPI === 'cli-server' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', ''], true))
    || (getenv('FIXNEAR_ENABLE_INSTALLER') === '1');

$blockedFiles = ['/start_server.bat', '/pasted text.txt'];
if (!$isLocalClient) {
    $blockedFiles[] = '/install.php';
}

$blocked = preg_match('#^/(?:config|data|scripts|tools|outputs)(?:/|$)#i', $normalized)
    || preg_match('#/(?:\.|[^/]+\.(?:sql|log|ini|env|bak|dist|md))$#i', $normalized)
    || in_array(strtolower($normalized), $blockedFiles, true);

if ($blocked) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Not found');
}

$candidate = __DIR__ . $normalized;
if ($normalized === '/' || is_file($candidate)) {
    return false;
}

if (is_file($candidate . '.php')) {
    require $candidate . '.php';
    exit;
}

if (is_dir($candidate) && is_file(rtrim($candidate, '/') . '/index.php')) {
    if (!str_ends_with($requestPath, '/')) {
        header('Location: ' . $requestPath . '/', true, 301);
        exit;
    }
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not found';
