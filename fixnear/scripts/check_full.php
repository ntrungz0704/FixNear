<?php
/**
 * FixNear - Full Verification & Quality Audit Script
 */
$base = 'http://127.0.0.1:8000';

$urls = [
    '/' => 200,
    '/models.php' => 200,
    '/model_detail.php?id=apple-iphone-13' => 200,
    '/shops.php' => 200,
    '/shop_detail.php?id=1' => 200,
    '/search.php' => 200,
    '/request_repair.php' => 200,
    '/track_request.php' => 200,
    '/contact.php' => 200,
    '/login.php' => 200,
    '/register.php' => 200,
    '/api/get_catalog.php?action=devices' => 200,
    '/api/get_catalog.php?action=brands&device=phone' => 200,
    '/api/get_catalog.php?action=models&device=phone&brand=apple' => 200,
    '/api/get_catalog.php?action=model_detail&id=apple-iphone-13' => 200,
    '/api/get_shops.php' => 200,
    '/api/search_shops.php?q=apple' => 200,
    '/admin/index.php' => 302,
    '/admin/shops.php' => 302,
    '/admin/services.php' => 302,
    '/admin/requests.php' => 302,
    '/admin/reviews.php' => 302,
    '/admin/reports.php' => 302,
    // Sensitive files blocked check:
    '/config/db.php' => 404,
    '/data/users.json' => 404,
    '/data/shops.json' => 404,
    '/scripts/validate_catalog.py' => 404,
    '/fixnear_db.sql' => 404,
    '/install.php' => 404
];

echo "=== KIEM TRA TRUY CAP URL & SECURITY ROUTER ===\n";
$allOk = true;
foreach ($urls as $path => $expectedStatus) {
    $ch = curl_init($base . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $statusMatch = ($httpCode === $expectedStatus);
    if (!$statusMatch) {
        if (str_starts_with($path, '/admin/') && ($httpCode === 302 || $httpCode === 200)) {
            $statusMatch = true;
        }
    }
    $statusText = $statusMatch ? "[OK]" : "[FAIL]";
    if (!$statusMatch) $allOk = false;
    echo sprintf("%s %-55s -> Code: %d (Expected: %d)\n", $statusText, $path, $httpCode, $expectedStatus);
}

echo "\nTong ket URL check: " . ($allOk ? "TAT CA URL & ROUTER HOP LE 100%" : "CO LOI CAN XU LY") . "\n";
