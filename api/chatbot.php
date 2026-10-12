<?php
/**
 * FixNear AI Technical Assistant API
 * Động cơ tư vấn, chẩn đoán pan bệnh & báo giá thời gian thực
 * Kết nối dữ liệu 68 cửa hàng, 319 dòng máy catalog, 28 pan bệnh & ma trận giá 3 cấp
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';

// Tiếp nhận query từ POST hoặc GET
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? ($_GET['query'] ?? ($_POST['query'] ?? '')));
$userLat = !empty($input['lat']) ? (float)$input['lat'] : (!empty($_GET['lat']) ? (float)$_GET['lat'] : null);
$userLng = !empty($input['lng']) ? (float)$input['lng'] : (!empty($_GET['lng']) ? (float)$_GET['lng'] : null);

if ($query === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập câu hỏi hoặc mô tả triệu chứng thiết bị.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Khởi tạo Pricing Engine
RepairAtlasPricing::init();

// Chuẩn hóa chuỗi tìm kiếm không dấu
function removeAccents($str) {
    if (!$str) return '';
    $accents = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    ];
    $str = mb_strtolower($str, 'UTF-8');
    foreach ($accents as $nonAccent => $accent) {
        $str = preg_replace("/($accent)/i", $nonAccent, $str);
    }
    return $str;
}

$norm = removeAccents($query);

// ==========================================
// 1. NHẬN DIỆN KHU VỰC / QUẬN HUYỆN TP.HCM (Kiểm tra quận 2 chữ số trước)
// ==========================================
$detectedDistrict = null;
$districts = [
    'Quận 10' => ['quan 10', 'q10', 'q.10', 'ly thuong kiet', '3 thang 2', 'su van hanh', 'bk'],
    'Quận 11' => ['quan 11', 'q11', 'q.11', 'dam sen', 'lanh binh thang', 'lac long quan'],
    'Quận 12' => ['quan 12', 'q12', 'q.12', 'to ky', 'quang trung', 'fpt', 'poly', 'chieu bang', 'an phu dong'],
    'Quận 1'  => ['quan 1', 'q1', 'q.1', 'nguyen thai hoc', 'ben nghe', 'ben thanh', 'pham ngu lao', 'tran quang khai'],
    'Quận 3'  => ['quan 3', 'q3', 'q.3', 'cach mang thang 8', 'nam ky khoi nghia', 'vo van tan', 'nguyen dinh chieu'],
    'Quận 4'  => ['quan 4', 'q4', 'q.4', 'hoang dieu', 'doan van bo'],
    'Quận 5'  => ['quan 5', 'q5', 'q.5', 'an duong vuong', 'hung vuong', 'cho lon', 'tran hung dao'],
    'Quận 6'  => ['quan 6', 'q6', 'q.6', 'hau giang', 'binh phu'],
    'Quận 7'  => ['quan 7', 'q7', 'q.7', 'nguyen thi thap', 'huynh tan phat', 'phu my hung'],
    'Quận 8'  => ['quan 8', 'q8', 'q.8', 'pham the hien', 'ta quang buu'],
    'Gò Vấp'  => ['go vap', 'gv', 'quang trung go vap', 'phan van tri', 'nguyen oanh', 'le duc tho'],
    'Bình Thạnh' => ['binh thanh', 'bach dang', 'dien bien phu', 'hang xanh', 'd2', 'd5', 'xo viet nghe tinh'],
    'Tân Bình' => ['tan binh', 'cong hoa', 'hoang hoa tham', 'truong chinh', 'au co'],
    'Tân Phú'  => ['tan phu', 'luy ban bich', 'thoai ngoc hau'],
    'Thủ Đức'  => ['thu duc', 'lang dai hoc', 'kha van can', 'vo van ngan', 'spkt', 'hcmute', 'linh trung', 'linh chieu'],
    'Bình Tân' => ['binh tan', 'ten lua', 'kinh duong vuong'],
    'Phú Nhuận' => ['phu nhuan', 'phan xich long', 'huynh van banh', 'phan dang luu']
];

foreach ($districts as $dName => $dKws) {
    foreach ($dKws as $dKw) {
        if (preg_match('/\b' . preg_quote($dKw, '/') . '\b/u', $norm) || str_contains($norm, $dKw)) {
            $detectedDistrict = $dName;
            break 2;
        }
    }
}

// ==========================================
// 2. NHẬN DIỆN THIẾT BỊ & DÒNG MODEL CATALOG (319 MODELS)
// ==========================================
$detectedModel = null;
$detectedBrand = null;
$detectedDeviceType = null;

// Nạp toàn bộ 319 models vào bộ nhớ để so khớp thông minh
static $cachedModels = null;
if ($cachedModels === null) {
    $cachedModels = [];
    $catalogDir = RepairAtlasPricing::getDataDir() . '/catalog';
    if (is_dir($catalogDir)) {
        $catDirs = glob($catalogDir . '/*', GLOB_ONLYDIR);
        foreach ($catDirs as $cDir) {
            $jsonFiles = glob($cDir . '/*.json');
            foreach ($jsonFiles as $jf) {
                $raw = json_decode(file_get_contents($jf), true);
                if (is_array($raw)) {
                    foreach ($raw as $m) {
                        if (!empty($m['id']) && !empty($m['name'])) {
                            $cachedModels[] = $m;
                        }
                    }
                }
            }
        }
    }
}

// Thuật toán so khớp Model tối ưu:
$cleanQueryWords = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
$queryWordSet = array_flip($cleanQueryWords);

$bestScore = 0;
foreach ($cachedModels as $m) {
    $mNameLower = mb_strtolower($m['name'], 'UTF-8');

    // Ưu tiên 1: Khớp chuỗi con chính xác
    if (mb_stripos($query, $m['name'], 0, 'UTF-8') !== false) {
        $score = 1000 + mb_strlen($m['name']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $detectedModel = $m;
        }
        continue;
    }

    // Ưu tiên 2: Khớp tập từ khóa (ví dụ "Dell Inspiron 15" trong "Dell Inspiron 15 3520")
    $cleanMWords = preg_split('/[^\p{L}\p{N}]+/u', $mNameLower, -1, PREG_SPLIT_NO_EMPTY);
    $filteredMWords = array_filter($cleanMWords, fn($w) => !in_array($w, ['inch', '2020', '2021', '2022', '2023', '2024', 'all', 'in', 'one']));

    $matchCount = 0;
    foreach ($filteredMWords as $mw) {
        if (isset($queryWordSet[$mw])) {
            $matchCount++;
        }
    }

    if ($matchCount >= 2) {
        $score = ($matchCount * 10) - count($filteredMWords);
        if (isset($queryWordSet[mb_strtolower($m['brand'] ?? '', 'UTF-8')])) {
            $score += 8;
        }
        if ($score > $bestScore && $score > 10) {
            $bestScore = $score;
            $detectedModel = $m;
        }
    }
}

if ($detectedModel) {
    $detectedBrand = $detectedModel['brand'] ?? null;
    $detectedDeviceType = $detectedModel['deviceType'] ?? null;
}

// Nếu chưa phát hiện Brand, nhận diện qua từ khóa Hãng
if (!$detectedBrand) {
    $brandKeywords = [
        'apple' => ['apple', 'iphone', 'ipad', 'macbook', 'mac', 'airpods', 'apple watch'],
        'samsung' => ['samsung', 'galaxy', 'z flip', 'z fold'],
        'xiaomi' => ['xiaomi', 'redmi', 'poco', 'mi '],
        'oppo' => ['oppo', 'reno', 'find'],
        'vivo' => ['vivo', 'iqoo'],
        'realme' => ['realme'],
        'dell' => ['dell', 'xps', 'inspiron', 'latitude', 'vostro', 'alienware'],
        'asus' => ['asus', 'rog', 'tuf', 'zenbook', 'vivobook'],
        'hp' => ['hp', 'pavilion', 'envy', 'spectre', 'omen', 'elitebook'],
        'lenovo' => ['lenovo', 'thinkpad', 'legion', 'ideapad', 'yoga'],
        'acer' => ['acer', 'nitro', 'predator', 'aspire', 'swift'],
        'sony' => ['sony', 'xperia', 'playstation', 'ps5'],
        'google' => ['google', 'pixel'],
        'msi' => ['msi', 'katana', 'bravo', 'stealth'],
        'garmin' => ['garmin', 'fenix', 'forerunner']
    ];

    foreach ($brandKeywords as $bKey => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($norm, $kw)) {
                $detectedBrand = $bKey;
                break 2;
            }
        }
    }
}

if (!$detectedDeviceType) {
    if (str_contains($norm, 'laptop') || str_contains($norm, 'may tinh xach tay') || in_array($detectedBrand, ['dell', 'asus', 'hp', 'lenovo', 'acer', 'msi'])) {
        $detectedDeviceType = 'win_laptop';
    } elseif (str_contains($norm, 'macbook') || str_contains($norm, 'mac book') || str_contains($norm, 'imac')) {
        $detectedDeviceType = 'macbook';
    } elseif (str_contains($norm, 'ipad') || str_contains($norm, 'tablet') || str_contains($norm, 'may tinh bang')) {
        $detectedDeviceType = 'tablet';
    } elseif (str_contains($norm, 'apple watch') || str_contains($norm, 'dong ho') || str_contains($norm, 'smartwatch')) {
        $detectedDeviceType = 'smartwatch';
    } elseif (str_contains($norm, 'pc') || str_contains($norm, 'cay may tinh') || str_contains($norm, 'case')) {
        $detectedDeviceType = 'pc_desktop';
    } else {
        $detectedDeviceType = 'phone';
    }
}

// ==========================================
// 3. NHẬN DIỆN PAN BỆNH & LINH KIỆN
// ==========================================
$detectedFaultId = null;
$faultKeywords = [
    'screen' => ['man hinh', 'be man', 'nut man', 'soc man', 'soc chi', 'chay muc', 'dom man', 'toi den', 'den man', 'khong len man', 'am man', 'xanh man', 'thay man'],
    'glass-press' => ['ep kinh', 'mat kinh', 'thay kinh', 'nut kinh', 'vo kinh', 'kinh cam ung'],
    'battery' => ['pin', 'chai pin', 'phong pin', 'pin phong', 'tut pin', 'nhanh het pin', 'nong may', 'phu pin', 'battery', 'thay pin'],
    'charging-port' => ['chan sac', 'cong sac', 'khong vao pin', 'khong nhan sac', 'long sac', 'type c', 'lightning', 'cam sac chap chon', 'sac cham'],
    'water-damage' => ['nuoc', 'rot nuoc', 'vao nuoc', 'vo nuoc', 'ngam nuoc', 'di mua', 'am uot', 'roi xuong nuoc', 'say nuoc'],
    'mainboard' => ['sap nguon', 'mat nguon', 'khong len', 'mo khong len', 'chet main', 'ic nguon', 'treo logo', 'khoi dong lai', 'chap main', 'hu main'],
    'camera' => ['camera', 'cam truoc', 'cam sau', 'mo cam', 'rung cam', 'dom cam', 'chup hinh bi mo'],
    'speaker' => ['loa', 'loa re', 'loa nho', 'mat loa', 'loa trong', 'loa ngoai', 'khong nghe'],
    'mic' => ['mic', 'micro', 'noi khong nghe', 'thu am khong duoc', 'goi dien khong nghe'],
    'back-glass' => ['kinh lung', 'nap lung', 'vo lung', 'kinh sau'],
    'thermal' => ['ve sinh', 'nong may', 'tra keo', 'keo tan nhiet', 'quat keu to', 'quat laptop', 'hong quat'],
    'software' => ['cai win', 'chay phan mem', 'khoa icloud', 'quen mat khau', 'treo may', 'cham', 'virus'],
    'data-recovery' => ['cuu du lieu', 'mat du lieu', 'khoi phuc du lieu', 'o cung chet']
];

foreach ($faultKeywords as $fId => $fKws) {
    foreach ($fKws as $fKw) {
        if (str_contains($norm, $fKw)) {
            $detectedFaultId = $fId;
            break 2;
        }
    }
}

// ==========================================
// 4. TRUY VẤN CSDL 68 CỬA HÀNG UY TÍN
// ==========================================
$shopFilters = [];
if ($detectedDistrict) {
    $shopFilters['district'] = $detectedDistrict;
}
if ($detectedDeviceType) {
    $shopFilters['device'] = $detectedDeviceType;
}
if ($userLat && $userLng) {
    $shopFilters['user_lat'] = $userLat;
    $shopFilters['user_lng'] = $userLng;
}

$matchingShops = db()->getShops($shopFilters);
// Lấy top 3-4 cửa hàng uy tín nhất
$topShops = array_slice($matchingShops, 0, 4);

// ==========================================
// 5. TÍNH TOÁN BẢNG GIÁ 3 CẤP LINH KIỆN
// ==========================================
$pricingInfo = null;
if ($detectedFaultId) {
    $tier = $detectedModel['tier'] ?? 'P3';
    $brand = $detectedBrand ?: ($detectedModel['brand'] ?? 'apple');
    $deviceType = $detectedDeviceType ?: 'phone';

    $standardPrice = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $detectedFaultId, 'standard', $detectedModel['id'] ?? null);
    $oemPrice = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $detectedFaultId, 'oem', $detectedModel['id'] ?? null);
    $genuinePrice = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $detectedFaultId, 'genuine', $detectedModel['id'] ?? null);

    if ($standardPrice || $oemPrice || $genuinePrice) {
        $faultMeta = RepairAtlasPricing::getFault($detectedFaultId);
        $pricingInfo = [
            'faultId' => $detectedFaultId,
            'faultName' => $faultMeta['name'] ?? $detectedFaultId,
            'icon' => $faultMeta['icon'] ?? '🔧',
            'standard' => $standardPrice,
            'oem' => $oemPrice,
            'genuine' => $genuinePrice
        ];
    }
}

// ==========================================
// 6. XÂY DỰNG NỘI DUNG PHẢN HỒI THÔNG MINH
// ==========================================
$responseHtml = '';

// Kịch bản A: Hỏi về Tránh luộc đồ / Minh bạch
if (str_contains($norm, 'luoc') || str_contains($norm, 'trao') || str_contains($norm, 'an toan') || str_contains($norm, 'uy tin')) {
    $responseHtml .= '
        <div style="margin-bottom:8px;">
            <span style="display:inline-block;background:#dcfce7;color:#166534;border:1px solid #86efac;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                🛡️ CẨM NANG MINH BẠCH FIXNEAR
            </span>
        </div>
        <strong>🛡️ Câu hỏi nên xác nhận trước khi giao máy:</strong>
        <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
            1. <strong>Ký tên linh kiện:</strong> Ký trực tiếp lên pin, màn hình, mainboard, camera trước khi bàn giao.<br>
            2. <strong>Quan sát sửa chữa:</strong> Hỏi cửa hàng có cho theo dõi trực tiếp và ghi nhận tình trạng trước sửa hay không.<br>
            3. <strong>Bảo hành linh kiện:</strong> Yêu cầu nêu thời hạn, điều kiện và chứng từ bảo hành bằng văn bản.<br>
            4. <strong>Lấy lại đồ cũ:</strong> Luôn yêu cầu trả lại linh kiện hỏng sau khi thay thế.
        </div>
    ';
}
// Kịch bản B: Có Pan bệnh & Model cụ thể
elseif ($detectedFaultId && $pricingInfo) {
    $mName = $detectedModel['name'] ?? ($detectedBrand ? ucfirst($detectedBrand) : 'Thiết bị');
    $badgeColor = '#fef3c7';
    $badgeText = '#92400e';
    $severityTitle = '🟡 CHẨN ĐOÁN KỸ THUẬT & DỰ TOÁN CHI PHÍ';

    if (in_array($detectedFaultId, ['water-damage', 'mainboard'])) {
        $badgeColor = '#fee2e2';
        $badgeText = '#991b1b';
        $severityTitle = '🔴 MỨC ĐỘ: KHẨN CẤP / CẦN XỬ LÝ SỚM';
    }

    $responseHtml .= '
        <div style="margin-bottom:8px;">
            <span style="display:inline-block;background:'.$badgeColor.';color:'.$badgeText.';padding:3px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                '.$severityTitle.'
            </span>
        </div>
        <strong>'.$pricingInfo['icon'].' '.$pricingInfo['faultName'].' — '.htmlspecialchars($mName).'</strong>
        <div style="margin:8px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;font-size:12px;">
            <div style="font-weight:700;color:#0f172a;margin-bottom:6px;">📊 Ba mức giá dự đoán của FixNear (chưa đối soát cửa hàng):</div>
            <div style="display:grid;gap:6px;">';

    if ($pricingInfo['standard']) {
        $responseHtml .= '
            <div style="display:flex;justify-content:space-between;border-bottom:1px dashed #e2e8f0;padding-bottom:4px;">
                <span>🥉 <strong>Mức thấp:</strong></span>
                <span style="font-weight:700;color:#2563eb;">'.RepairAtlasPricing::formatVND($pricingInfo['standard']['min']).' – '.RepairAtlasPricing::formatVND($pricingInfo['standard']['max']).'</span>
            </div>';
    }
    if ($pricingInfo['oem']) {
        $responseHtml .= '
            <div style="display:flex;justify-content:space-between;border-bottom:1px dashed #e2e8f0;padding-bottom:4px;">
                <span>🥈 <strong>Mức trung bình:</strong></span>
                <span style="font-weight:700;color:#ea580c;">'.RepairAtlasPricing::formatVND($pricingInfo['oem']['min']).' – '.RepairAtlasPricing::formatVND($pricingInfo['oem']['max']).'</span>
            </div>';
    }
    if ($pricingInfo['genuine']) {
        $responseHtml .= '
            <div style="display:flex;justify-content:space-between;">
                <span>🥇 <strong>Mức cao:</strong></span>
                <span style="font-weight:700;color:#16a34a;">'.RepairAtlasPricing::formatVND($pricingInfo['genuine']['min']).' – '.RepairAtlasPricing::formatVND($pricingInfo['genuine']['max']).'</span>
            </div>';
    }

    $responseHtml .= '
            </div>
            <div style="margin-top:6px;font-size:11px;color:#64748b;">
                Linh kiện, thời gian và bảo hành cần cửa hàng xác nhận trước khi sửa.
            </div>
        </div>';

    // Thêm cảnh báo kỹ thuật đặc thù
    if ($detectedFaultId === 'water-damage') {
        $responseHtml .= '
            <div style="background:#fef2f2;border-left:3px solid #ef4444;padding:8px;font-size:11.5px;color:#991b1b;margin-bottom:8px;">
                ⚠️ <strong>Máy vô nước:</strong> Tắt nguồn, không cắm sạc hoặc sấy nóng; mang đi kiểm tra sớm.
            </div>';
    } elseif ($detectedFaultId === 'screen') {
        $responseHtml .= '
            <div style="background:#f0f9ff;border-left:3px solid #0284c7;padding:8px;font-size:11.5px;color:#0369a1;margin-bottom:8px;">
                💡 <strong>Hỏi cửa hàng:</strong> Nếu chỉ nứt kính và màn hiển thị, cảm ứng vẫn hoạt động, hãy hỏi khả năng ép kính và giá trước khi thay cả cụm.
            </div>';
    }
}
// Kịch bản C: Chỉ tìm tiệm / Khu vực hoặc hỏi chung
else {
    $locStr = $detectedDistrict ? "khu vực <strong>{$detectedDistrict}</strong>" : "toàn TP.HCM";
    $responseHtml .= '
        <div style="margin-bottom:8px;">
            <span style="display:inline-block;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                📍 DANH SÁCH CỬA HÀNG THAM KHẢO
            </span>
        </div>
        <strong>Tra cứu cửa hàng tại '.$locStr.':</strong>
        <div style="margin:4px 0 8px;font-size:12px;color:#475569;">
            Dữ liệu cửa hàng chưa được đối soát đầy đủ theo từng chi nhánh. Hãy hỏi trực tiếp về giá, linh kiện, bảo hành và chính sách trước khi sửa.
        </div>';
}

// ==========================================
// 7. GỢI Ý CỬA HÀNG THỰC TẾ TRONG 68 TIỆM
// ==========================================
if (!empty($topShops)) {
    $responseHtml .= '
        <div style="margin-top:10px;font-size:12px;font-weight:700;color:#0f172a;">
            🏪 Bản ghi cửa hàng phù hợp để tham khảo:
        </div>
        <div style="display:grid;gap:6px;margin-top:6px;">';

    foreach ($topShops as $sh) {
        $sName = htmlspecialchars($sh['name']);
        $sAddr = htmlspecialchars($sh['address']);
        $sDist = htmlspecialchars($sh['district'] ?? '');
        $sPhone = htmlspecialchars($sh['phone'] ?? 'Chưa có số điện thoại');
        $sRatingMarkup = !empty($sh['google_rating_verified'])
            ? '<span style="font-size:11px;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;font-weight:700;">★ ' . number_format((float) $sh['google_rating'], 1) . '</span>'
            : '';
        $sId = (int)$sh['id'];

        $distanceText = '';
        if (isset($sh['distance_km'])) {
            $distanceText = ' • 📍 ' . number_format($sh['distance_km'], 1) . 'km';
        }

        $responseHtml .= '
            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div style="font-weight:700;color:#0f172a;font-size:12.5px;">
                        <a href="shop_detail.php?id='.$sId.'" style="color:#0f172a;text-decoration:none;">'.$sName.'</a>
                    </div>
                    '.$sRatingMarkup.'
                </div>
                <div style="font-size:11.5px;color:#64748b;margin:3px 0;">
                    '.$sAddr.$distanceText.'
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:5px;padding-top:4px;border-top:1px solid #f1f5f9;">
                    <span style="font-size:11px;color:#ea580c;font-weight:700;">📞 '.$sPhone.'</span>
                    <div style="display:flex;gap:4px;">
                        <a href="shop_detail.php?id='.$sId.'" style="font-size:10.5px;padding:3px 8px;background:#f1f5f9;color:#334155;border-radius:4px;text-decoration:none;font-weight:700;">Xem tiệm</a>
                        <a href="request_repair.php?shop_id='.$sId.'" style="font-size:10.5px;padding:3px 8px;background:#ea580c;color:#fff;border-radius:4px;text-decoration:none;font-weight:700;">Gửi yêu cầu</a>
                    </div>
                </div>
            </div>';
    }

    $responseHtml .= '</div>';
}

// Các nút hành động nhanh
$responseHtml .= '
    <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">';
if ($detectedModel) {
    $responseHtml .= '
        <a href="model_detail.php?id='.urlencode($detectedModel['id']).'" style="font-size:11.5px;padding:5px 10px;background:#0f172a;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">
            📱 Chi Tiết Báo Giá '.htmlspecialchars($detectedModel['name']).'
        </a>';
}
if ($detectedDistrict) {
    $responseHtml .= '
        <a href="shops.php?district='.urlencode($detectedDistrict).'" style="font-size:11.5px;padding:5px 10px;background:#ea580c;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">
            📍 Tất Cả Tiệm Tại '.htmlspecialchars($detectedDistrict).'
        </a>';
} else {
    $responseHtml .= '
        <a href="shops.php" style="font-size:11.5px;padding:5px 10px;background:#ea580c;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">
            🏪 Khám Phá 68 Cửa Hàng
        </a>';
}
$responseHtml .= '
        <a href="request_repair.php" style="font-size:11.5px;padding:5px 10px;background:#fff;color:#ea580c;border:1px solid #ea580c;border-radius:6px;text-decoration:none;font-weight:700;">
            📝 Gửi Yêu Cầu Tìm Thợ
        </a>
    </div>';

echo json_encode([
    'success' => true,
    'html' => $responseHtml,
    'data' => [
        'detectedModel' => $detectedModel['name'] ?? null,
        'detectedBrand' => $detectedBrand,
        'detectedDistrict' => $detectedDistrict,
        'detectedFault' => $pricingInfo['faultName'] ?? null,
        'shopsFound' => count($topShops)
    ]
], JSON_UNESCAPED_UNICODE);
