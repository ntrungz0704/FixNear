<?php
require_once __DIR__ . '/../config/db.php';

$_SESSION['user_id'] = 1;
$_SERVER['PHP_SELF'] = '/admin/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

$files = [
    'admin/index.php',
    'admin/shops.php',
    'admin/services.php',
    'admin/reviews.php',
    'admin/requests.php',
    'admin/reports.php',
    'admin/contacts.php',
    'admin/shop_edit.php'
];

foreach ($files as $f) {
    $fullPath = dirname(__DIR__) . '/' . $f;
    ob_start();
    try {
        include $fullPath;
        $out = ob_get_clean();
        if (preg_match('/(Fatal error|Warning|Notice)/i', $out, $m)) {
            echo "ISSUE in $f: " . substr(strip_tags($out), 0, 150) . "\n";
        } else {
            echo "ADMIN OK: $f\n";
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "EXCEPTION in $f: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
    }
}
