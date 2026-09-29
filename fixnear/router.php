<?php
// Router an toàn cho PHP built-in server. Apache production dùng .htaccess.
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$normalized = str_replace('\\', '/', $requestPath);
$blocked = preg_match('#^/(?:config|data|scripts|tools|outputs)(?:/|$)#i', $normalized)
    || preg_match('#/(?:\.|[^/]+\.(?:sql|log|ini|env|bak|dist|md))$#i', $normalized)
    || in_array(strtolower($normalized), ['/install.php', '/start_server.bat', '/pasted text.txt'], true);

if ($blocked) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Not found');
}

$candidate = __DIR__ . $normalized;
if ($normalized !== '/' && is_file($candidate)) {
    return false;
}

if ($normalized === '/' || is_file($candidate)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not found';
