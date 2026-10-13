<?php
declare(strict_types=1);

/** Single, allow-listed entry point for Vercel's PHP function. */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (str_contains($path, "\0") || str_contains($path, '\\') || str_contains($path, '..')) {
    http_response_code(404);
    exit('Not found');
}

$root = dirname(__DIR__);
if ($path === '/' || $path === '/index.php') {
    $file = $root . '/public/index.php';
} elseif (preg_match('#^/(admin|api)/([a-z][a-z0-9_]*)\.php$#i', $path, $match)) {
    $file = $root . '/' . strtolower($match[1]) . '/' . $match[2] . '.php';
} elseif (preg_match('#^/([a-z][a-z0-9_]*)\.php$#i', $path, $match)) {
    $file = $root . '/public/' . $match[1] . '.php';
} elseif ($path === '/admin' || $path === '/admin/') {
    $file = $root . '/admin/index.php';
} else {
    http_response_code(404);
    exit('Not found');
}
if (!is_file($file)) {
    http_response_code(404);
    exit('Not found');
}
require $file;
