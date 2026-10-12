<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/pricing_engine.php';

echo "=======================================================\n";
echo "       FIXNEAR DEEP AUDIT & DATA VERIFICATION          \n";
echo "=======================================================\n\n";

// 1. DATA FILES CHECK
$dataFiles = [
    'shops.json',
    'services.json',
    'shop_services.json',
    'reviews.json',
    'repair_requests.json',
    'reports.json',
    'contact_messages.json',
    'users.json',
    'faults.json'
];

echo "--- 1. KIEM TRA CAC TEP DU LIEU DATA/ ---\n";
foreach ($dataFiles as $f) {
    $path = FIXNEAR_DATA_DIR . $f;
    if (!file_exists($path)) {
        echo "[THIEU] $f khong ton tai!\n";
        continue;
    }
    $raw = file_get_contents($path);
    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        echo "[LOI JSON] $f chua noi dung khong hop le!\n";
    } else {
        echo sprintf("[OK] %-22s: %d ban ghi (%s)\n", $f, count($arr), number_format(strlen($raw)) . " bytes");
    }
}

// 2. USERS AUDIT
echo "\n--- 2. KIEM TRA TAI KHOAN NGUOI DUNG (users.json) ---\n";
$users = json_decode(file_get_contents(FIXNEAR_DATA_DIR . 'users.json'), true) ?: [];
foreach ($users as $u) {
    $isHashed = password_get_info($u['password'] ?? '')['algo'] !== 0;
    $hashType = $isHashed ? 'Bcrypt/Argon2 (AN TOAN)' : 'PLAIN TEXT (NGUY HIEM!)';
    echo sprintf("- ID %s | Email: %-25s | Quyen: %-6s | Pass Hash: %s\n",
        $u['id'] ?? '?',
        $u['email'] ?? '?',
        $u['role'] ?? 'user',
        $hashType
    );
}

// 3. SHOPS & VERIFICATION AUDIT
echo "\n--- 3. KIEM TRA DỮ LIỆU CỬA HÀNG (shops.json) ---\n";
$shops = db()->getShops([]);
$totalShops = count($shops);
$withVerifiedUrl = 0;
$withGooglePlace = 0;
$withVerifiedDiscount = 0;
$withVerifiedPolicy = 0;
$districts = [];
$emptyCoords = 0;

foreach ($shops as $s) {
    if (!empty($s['source_verified'])) $withVerifiedUrl++;
    if (!empty($s['google_rating_verified'])) $withGooglePlace++;
    if (!empty($s['student_discount_verified'])) $withVerifiedDiscount++;
    if (!empty($s['service_policy_verified'])) $withVerifiedPolicy++;
    if (empty($s['latitude']) || empty($s['longitude'])) $emptyCoords++;
    $d = trim($s['district'] ?? '');
    if ($d !== '') $districts[$d] = true;
}

echo "Tong so cua hang: $totalShops\n";
echo "So quan/huyen/thanh pho: " . count($districts) . " (" . implode(', ', array_keys($districts)) . ")\n";
echo "Cua hang co toa do GPS: " . ($totalShops - $emptyCoords) . "/$totalShops\n";
echo "Cua hang da xac minh nguon goc (source_url + verified_at): $withVerifiedUrl/$totalShops\n";
echo "Cua hang da xac minh Google Place ID (google_place_id + google_verified_at): $withGooglePlace/$totalShops\n";
echo "Cua hang co xac minh uu dai sinh vien theo chi nhanh: $withVerifiedDiscount/$totalShops\n";
echo "Cua hang co xac minh chinh sach sua chua: $withVerifiedPolicy/$totalShops\n";

// 4. CATALOG & PRICING ENGINE
echo "\n--- 4. KIEM TRA REPAIR ATLAS CATALOG & PRICING ---\n";
RepairAtlasPricing::init();
$devices = RepairAtlasPricing::getAllDevices();
$totalModels = 0;
foreach ($devices as $devKey => $devInfo) {
    $brands = RepairAtlasPricing::getBrandsByDevice($devKey);
    $devModels = 0;
    foreach ($brands as $b) {
        $devModels += $b['modelCount'];
    }
    $totalModels += $devModels;
    echo sprintf("- %-15s: %d hang, %d models\n", $devInfo['name'], count($brands), $devModels);
}
echo "Tong so models toan he thong: $totalModels\n";

// Test calculation for a random model
$testModel = RepairAtlasPricing::getModelById('apple-iphone-13');
if ($testModel) {
    $prices = RepairAtlasPricing::getModelPrices(
        $testModel['deviceType'],
        $testModel['tier'],
        $testModel['brand'],
        $testModel['supportedFaults'],
        $testModel['id']
    );
    echo "Thu nghiem tinh gia cho '{$testModel['name']}': tinh duoc " . count($prices) . " pan benh.\n";
    $faultKey = array_key_first($prices);
    if ($faultKey && isset($prices[$faultKey])) {
        $sample = $prices[$faultKey];
        $faultInfo = RepairAtlasPricing::getFault($faultKey);
        $faultName = $faultInfo['name'] ?? $faultKey;
        echo "  VD pan '{$faultName}': Standard=" . number_format($sample['standard']['min'] ?? 0) . "d - " . number_format($sample['standard']['max'] ?? 0) . "d | OEM=" . number_format($sample['oem']['min'] ?? 0) . "d - " . number_format($sample['oem']['max'] ?? 0) . "d | Genuine=" . number_format($sample['genuine']['min'] ?? 0) . "d - " . number_format($sample['genuine']['max'] ?? 0) . "d\n";
    }
} else {
    echo "[CANH BAO] Khong tim thay model apple-iphone-13!\n";
}

// 5. REVIEWS AUDIT
echo "\n--- 5. KIEM TRA DANH GIA (reviews.json) ---\n";
$reviews = json_decode(file_get_contents(FIXNEAR_DATA_DIR . 'reviews.json'), true) ?: [];
$userReviews = 0;
$seedReviews = 0;
foreach ($reviews as $r) {
    if (($r['origin'] ?? '') === 'user_submission' || !empty($r['verified_at'])) {
        $userReviews++;
    } else {
        $seedReviews++;
    }
}
echo "Tong so danh gia trong database: " . count($reviews) . "\n";
echo "- Danh gia tu nguoi dung thuc / da xac minh: $userReviews\n";
echo "- Danh gia seed / minh hoa ban dau: $seedReviews (Co bi an tren giao dien neu chua xac minh: " . (count(db()->getReviewsByShop(1)) > 0 ? "HIEN THI" : "DA AN TOAN AN DI") . ")\n";

// 6. REPAIR REQUESTS AUDIT
echo "\n--- 6. KIEM TRA YEU CAU SUA CHUA (repair_requests.json) ---\n";
$reqs = db()->getRepairRequests();
echo "Tong so yeu cau sua chua: " . count($reqs) . "\n";
foreach ($reqs as $rq) {
    echo sprintf("- Ma: %-22s | Ten: %-15s | DT: %-12s | Tinh trang: %-10s | Ngay: %s\n",
        $rq['id'] ?? '?',
        $rq['customer_name'] ?? '?',
        $rq['customer_phone'] ?? '?',
        $rq['status'] ?? 'pending',
        $rq['created_at'] ?? '?'
    );
}

