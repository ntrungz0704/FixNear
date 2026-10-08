<?php
require_once __DIR__ . '/../config/db.php';

$publicDir = dirname(__DIR__) . '/public';
$files = glob($publicDir . '/*.php');
$passed = 0;
$failed = 0;

$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

foreach ($files as $fullPath) {
    $fname = basename($fullPath);
    $_SERVER['REQUEST_URI'] = '/' . $fname;
    $_SERVER['PHP_SELF'] = '/public/' . $fname;

    ob_start();
    try {
        include $fullPath;
        $out = ob_get_clean();
        if (preg_match('/(Fatal error|Parse error)/i', $out, $m)) {
            echo "PUBLIC FAILED: public/{$fname} -> " . substr(strip_tags($out), 0, 150) . "\n";
            $failed++;
        } else {
            echo "PUBLIC OK: public/{$fname}\n";
            $passed++;
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "PUBLIC EXCEPTION: public/{$fname} -> " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\nTổng kết: {$passed} trang ĐẠT, {$failed} trang LỖI.\n";
if ($failed > 0) exit(1);
