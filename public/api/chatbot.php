<?php
/**
 * FixNear AI Customer Service & Technical Support API
 * Hệ thống AI Chatbot Chăm Sóc Khách Hàng Thông Minh FixNear
 *
 * Tích hợp Google Gemini AI kết hợp đối soát dữ liệu nội bộ:
 * - 3 Nguyên tắc bắt buộc:
 *   1. Ưu tiên giải quyết vấn đề của khách hơn việc giới thiệu/bán dịch vụ.
 *   2. Chỉ cung cấp thông tin trong phạm vi sản phẩm, dịch vụ & chính sách thực tế của FixNear.
 *   3. Không tự động biến mọi cuộc trò chuyện thành tư vấn bán hàng.
 * - Phân loại 7 nhóm ý định & trạng thái:
 *   ANGRY_COMPLAINT, URGENT_SUPPORT, PRODUCT_INQUIRY, SERVICE_INQUIRY,
 *   GENERAL_GREETING, UNCLEAR_INTENT, OUT_OF_SCOPE.
 * - Hỗ trợ hội thoại nhiều lượt (Multi-turn Conversation History).
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';

// Nhận dữ liệu đầu vào
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? ($_GET['query'] ?? ($_POST['query'] ?? '')));
$history = $input['history'] ?? [];
$userLat = !empty($input['lat']) ? (float)$input['lat'] : (!empty($_GET['lat']) ? (float)$_GET['lat'] : null);
$userLng = !empty($input['lng']) ? (float)$input['lng'] : (!empty($_GET['lng']) ? (float)$_GET['lng'] : null);

if ($query === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập câu hỏi hoặc vấn đề bạn cần hỗ trợ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Khởi tạo Pricing Engine
RepairAtlasPricing::init();

// ==========================================
// HELPER: Chuẩn hóa chuỗi không dấu
// ==========================================
if (!function_exists('removeAccents')) {
function removeAccents(string $str): string
{
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
}

$norm = removeAccents($query);

// Mở rộng từ viết tắt phổ biến
$expandedNorm = preg_replace('/\bip\s*([0-9]+)/i', 'iphone $1', $norm);
$expandedNorm = preg_replace('/\bip\s*x\b/i', 'iphone x', $expandedNorm);
$expandedNorm = preg_replace('/\bip\s*(pro|plus|promax|max)\b/i', 'iphone $1', $expandedNorm);
$expandedNorm = preg_replace('/\bmb\s*(air|pro|m1|m2|m3)\b/i', 'macbook $1', $expandedNorm);
$expandedNorm = preg_replace('/\bss\s*(s[0-9]+|a[0-9]+|note|fold|flip)\b/i', 'samsung $1', $expandedNorm);

// ==========================================
// 1. PHÂN LOẠI Ý ĐỊNH & TRẠNG THÁI KHÁCH HÀNG
// ==========================================
$detectedIntent = 'UNCLEAR_INTENT';

// Dấu hiệu tức giận, khiếu nại, thất vọng (ANGRY_COMPLAINT)
$angryPatterns = [
    'tuc gian', 'kho chiu', 'lua dao', 'lua gat', 'lua khach', 'te qua', 'dich vu te',
    'nhu hach', 'nhu cc', 'mat day', 'that vong', 'mat thoi gian', 'cho lau', 'mai khong xong',
    'sao lau the', 'qua kem', 'khieu nai', 'phan anh', 'tay chay', 'to cao', 'kien',
    'cong an', 'doi gap nguoi', 'gap nguoi that', 'gap quan ly', 'cho gap admin',
    'khong muon nghe giai thich', 'bi luoc', 'trao do', 'mat linh kien', 'thay do dom',
    'sua hu them', 'lam hong', 'chua nhan duoc', 'lam an kieu gi', 'dich vu kieu gi',
    'buc minh', 'buc boi', 'chan nan', 'mat uy tin', 'lua tien'
];

// Dấu hiệu khẩn cấp (URGENT_SUPPORT)
$urgentPatterns = [
    'khan cap', 'cap cuu', 'gap', 'ngay bay gio', 'chay', 'no', 'boc khoi',
    'vo nuoc', 'rot nuoc', 'ngam nuoc', 'roi xuong nuoc', 'di mua vao nuoc',
    'sap nguon', 'chet nguon', 'chap dien', 'mat du lieu gap'
];

// Dấu hiệu tra cứu yêu cầu sửa chữa
$trackingPatterns = ['tra cuu', 'kiem tra don', 'ma don', 'ma yeu cau', 'tien do', 'tinh trang don', 'don sua'];

// Dấu hiệu chào hỏi / xã giao (GENERAL_GREETING)
$greetingPatterns = ['chao', 'xin chao', 'hello', 'hi', 'alo', 'cam on', 'thank you', 'tam biet', 'bye', 'good morning', 'good afternoon'];

// Dấu hiệu ngoài phạm vi (OUT_OF_SCOPE)
$outOfScopePatterns = ['thoi tiet', 've may bay', 've xe', 'xem boi', 'bong da', 'nau an', 'bat dong san', 'gia vang', 'chung khoan', 'chinh tri', 'tin tuc'];

$isAngry = false;
foreach ($angryPatterns as $pat) {
    if (str_contains($norm, $pat)) {
        $isAngry = true;
        break;
    }
}

$isUrgent = false;
foreach ($urgentPatterns as $pat) {
    if (str_contains($norm, $pat)) {
        $isUrgent = true;
        break;
    }
}

$isTracking = false;
foreach ($trackingPatterns as $pat) {
    if (str_contains($norm, $pat)) {
        $isTracking = true;
        break;
    }
}

$isGreeting = false;
foreach ($greetingPatterns as $pat) {
    if (preg_match('/\b' . preg_quote($pat, '/') . '\b/u', $norm)) {
        $isGreeting = true;
        break;
    }
}

$isOutOfScope = false;
foreach ($outOfScopePatterns as $pat) {
    if (str_contains($norm, $pat)) {
        $isOutOfScope = true;
        break;
    }
}

// ==========================================
// 2. TRA CỨU YÊU CẦU SỬA CHỮA (NẾU CÓ MÃ HOẶC SĐT)
// ==========================================
$matchedRepairRequest = null;
if (preg_match('/(FN-\d{3,6})/i', $query, $mReq)) {
    $reqCode = strtoupper($mReq[1]);
    $allReqs = db()->getRepairRequests();
    foreach ($allReqs as $r) {
        if (strtoupper($r['id'] ?? '') === $reqCode) {
            $matchedRepairRequest = $r;
            break;
        }
    }
} elseif (preg_match('/\b(0\d{8,10})\b/', $query, $mPhone)) {
    $phone = $mPhone[1];
    $allReqs = db()->getRepairRequests();
    foreach ($allReqs as $r) {
        if (($r['customer_phone'] ?? '') === $phone || ($r['customer_zalo'] ?? '') === $phone) {
            $matchedRepairRequest = $r;
            break;
        }
    }
}

// ==========================================
// 3. NHẬN DIỆN KHU VỰC / QUẬN HUYỆN TP.HCM
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
    'Phú Nhuận' => ['phu nhuan', 'phan xich long', 'huynh van banh', 'phan dang luu'],
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
// 4. NHẬN DIỆN THIẾT BỊ & MODEL TRONG CATALOG (319 MODEL)
// ==========================================
$detectedModel = null;
$detectedBrand = null;
$detectedDeviceType = null;

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

$modelStopWords = array_flip([
    'ban', 'cho', 'co', 'the', 'la', 'va', 'cua', 'o', 'tai', 'gi', 'toi',
    'minh', 'nay', 'duoc', 'voi', 'mot', 'hai', 'ba', 'may', 'laptop',
    'dien', 'thoai', 'tiem', 'sua', 'hoi', 'chao', 'xin', 'giup', 'bi',
    'khong', 'lam', 'sao', 'bao', 'nhieu', 'da', 'dang', 've', 'nhiem',
    'van', 'phong', 'de', 'sieu', 'nho', 'gon', 'inch', '2020', '2021',
    '2022', '2023', '2024', 'all', 'in', 'one'
]);

$cleanQueryWords = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($expandedNorm, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
$queryWordSet = array_flip(array_filter($cleanQueryWords, fn($w) => !isset($modelStopWords[$w])));

$bestScore = 0;
foreach ($cachedModels as $m) {
    $normModelName = removeAccents($m['name']);
    if (mb_strlen($normModelName) >= 4 && !isset($modelStopWords[$normModelName]) && mb_stripos($expandedNorm, $normModelName, 0, 'UTF-8') !== false) {
        $score = 1000 + mb_strlen($m['name']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $detectedModel = $m;
        }
        continue;
    }

    $cleanMWords = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($normModelName, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
    $filteredMWords = array_filter($cleanMWords, fn($w) => !isset($modelStopWords[$w]));

    $matchCount = 0;
    foreach ($filteredMWords as $mw) {
        if (isset($queryWordSet[$mw])) {
            $matchCount++;
        }
    }

    if ($matchCount >= 2) {
        $score = ($matchCount * 14) - count($filteredMWords);
        if (isset($queryWordSet[mb_strtolower($m['brand'] ?? '', 'UTF-8')])) {
            $score += 10;
        }
        if ($score > $bestScore && $score > 15) {
            $bestScore = $score;
            $detectedModel = $m;
        }
    }
}

if ($detectedModel) {
    $detectedBrand = $detectedModel['brand'] ?? null;
    $detectedDeviceType = $detectedModel['deviceType'] ?? null;
}

if (!$detectedBrand) {
    $brandKeywords = [
        'apple'   => ['apple', 'iphone', 'ipad', 'macbook', 'mac', 'airpods', 'apple watch'],
        'samsung' => ['samsung', 'galaxy', 'z flip', 'z fold'],
        'xiaomi'  => ['xiaomi', 'redmi', 'poco', 'mi '],
        'oppo'    => ['oppo', 'reno', 'find'],
        'vivo'    => ['vivo', 'iqoo'],
        'realme'  => ['realme'],
        'dell'    => ['dell', 'xps', 'inspiron', 'latitude', 'vostro', 'alienware'],
        'asus'    => ['asus', 'rog', 'tuf', 'zenbook', 'vivobook'],
        'hp'      => ['hp', 'pavilion', 'envy', 'spectre', 'omen', 'elitebook'],
        'lenovo'  => ['lenovo', 'thinkpad', 'legion', 'ideapad', 'yoga'],
        'acer'    => ['acer', 'nitro', 'predator', 'aspire', 'swift'],
        'sony'    => ['sony', 'xperia'],
        'google'  => ['google', 'pixel'],
        'msi'     => ['msi', 'katana', 'bravo', 'stealth'],
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
// 5. NHẬN DIỆN PAN BỆNH & LINH KIỆN
// ==========================================
$detectedFaultId = null;
$faultKeywords = [
    'screen'        => ['man hinh', 'be man', 'nut man', 'soc man', 'soc chi', 'chay muc', 'dom man', 'toi den', 'den man', 'khong len man', 'am man', 'xanh man', 'thay man'],
    'glass-press'   => ['ep kinh', 'mat kinh', 'thay kinh', 'nut kinh', 'vo kinh', 'kinh cam ung'],
    'battery'       => ['pin', 'chai pin', 'phong pin', 'pin phong', 'tut pin', 'nhanh het pin', 'nong may', 'phu pin', 'battery', 'thay pin'],
    'charging-port' => ['chan sac', 'cong sac', 'khong vao pin', 'khong nhan sac', 'long sac', 'type c', 'lightning', 'cam sac chap chon', 'sac cham'],
    'water-damage'  => ['nuoc', 'rot nuoc', 'vao nuoc', 'vo nuoc', 'ngam nuoc', 'di mua', 'am uot', 'roi xuong nuoc', 'say nuoc'],
    'mainboard'     => ['sap nguon', 'mat nguon', 'khong len', 'mo khong len', 'chet main', 'ic nguon', 'treo logo', 'khoi dong lai', 'chap main', 'hu main'],
    'camera'        => ['camera', 'cam truoc', 'cam sau', 'mo cam', 'rung cam', 'dom cam', 'chup hinh bi mo'],
    'speaker'       => ['loa', 'loa re', 'loa nho', 'mat loa', 'loa trong', 'loa ngoai', 'khong nghe'],
    'mic'           => ['mic', 'micro', 'noi khong nghe', 'thu am khong duoc', 'goi dien khong nghe'],
    'back-glass'    => ['kinh lung', 'nap lung', 'vo lung', 'kinh sau'],
    'thermal'       => ['ve sinh', 'nong may', 'tra keo', 'keo tan nhiet', 'quat keu to', 'quat laptop', 'hong quat'],
    'software'      => ['cai win', 'chay phan mem', 'khoa icloud', 'quen mat khau', 'treo may', 'cham', 'virus'],
    'data-recovery' => ['cuu du lieu', 'mat du lieu', 'khoi phuc du lieu', 'o cung chet'],
];

foreach ($faultKeywords as $fId => $fKws) {
    foreach ($fKws as $fKw) {
        if (str_contains($norm, $fKw)) {
            $detectedFaultId = $fId;
            break 2;
        }
    }
}

// XÁC ĐỊNH Ý ĐỊNH CUỐI CÙNG THEO THỨ TỰ ƯU TIÊN:
if ($isAngry) {
    // Nguyên tắc 1: Ưu tiên cảm xúc và khiếu nại của khách hàng lên hàng đầu
    $detectedIntent = 'ANGRY_COMPLAINT';
} elseif ($isUrgent) {
    $detectedIntent = 'URGENT_SUPPORT';
} elseif ($matchedRepairRequest || $isTracking) {
    $detectedIntent = 'SERVICE_INQUIRY';
} elseif ($detectedModel || ($detectedFaultId && ($detectedBrand || $detectedDeviceType))) {
    $detectedIntent = 'PRODUCT_INQUIRY';
} elseif (str_contains($norm, 'bao hanh') || str_contains($norm, 'chinh sach') || str_contains($norm, 'ky ten') || str_contains($norm, 'luoc do') || str_contains($norm, 'trao do')) {
    $detectedIntent = 'SERVICE_INQUIRY';
} elseif ($isOutOfScope) {
    $detectedIntent = 'OUT_OF_SCOPE';
} elseif ($isGreeting && mb_strlen($norm) < 30) {
    $detectedIntent = 'GENERAL_GREETING';
} else {
    $detectedIntent = 'UNCLEAR_INTENT';
}

// ==========================================
// 6. TRUY VẤN CSDL BẢNG GIÁ & CỬA HÀNG
// ==========================================
$pricingInfo = null;
$modelServicesPricing = [];
$topShops = [];

// Chỉ hiển thị cửa hàng khi KHÔNG PHẢI đang khiếu nại/tức giận
if ($detectedIntent !== 'ANGRY_COMPLAINT') {
    $shopFilters = [];
    if ($detectedDistrict) $shopFilters['district'] = $detectedDistrict;
    if ($detectedDeviceType) $shopFilters['device'] = $detectedDeviceType;
    if ($userLat && $userLng) {
        $shopFilters['user_lat'] = $userLat;
        $shopFilters['user_lng'] = $userLng;
    }
    $matchingShops = db()->getShops($shopFilters);
    $topShops = array_slice($matchingShops, 0, 3);
}

// Tra cứu giá từ CSDL nếu khách hỏi sản phẩm/dịch vụ
if (in_array($detectedIntent, ['PRODUCT_INQUIRY', 'SERVICE_INQUIRY', 'URGENT_SUPPORT'])) {
    if ($detectedFaultId) {
        $tier       = $detectedModel['tier'] ?? 'P3';
        $brand      = $detectedBrand ?: ($detectedModel['brand'] ?? 'apple');
        $deviceType = $detectedDeviceType ?: 'phone';

        $standardPrice = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $detectedFaultId, 'standard', $detectedModel['id'] ?? null);
        $oemPrice      = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $detectedFaultId, 'oem',      $detectedModel['id'] ?? null);
        $genuinePrice  = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $detectedFaultId, 'genuine',  $detectedModel['id'] ?? null);

        if ($standardPrice || $oemPrice || $genuinePrice) {
            $faultMeta  = RepairAtlasPricing::getFault($detectedFaultId);
            $pricingInfo = [
                'faultId'   => $detectedFaultId,
                'faultName' => $faultMeta['name'] ?? $detectedFaultId,
                'icon'      => $faultMeta['icon'] ?? '🔧',
                'desc'      => $faultMeta['description'] ?? '',
                'standard'  => $standardPrice,
                'oem'       => $oemPrice,
                'genuine'   => $genuinePrice,
            ];
        }
    } elseif ($detectedModel) {
        $tier       = $detectedModel['tier'] ?? 'P3';
        $brand      = $detectedBrand ?: ($detectedModel['brand'] ?? 'apple');
        $deviceType = $detectedDeviceType ?: ($detectedModel['deviceType'] ?? 'phone');

        $sampleFaults = ($deviceType === 'phone' || $deviceType === 'tablet')
            ? ['screen' => 'Thay màn hình', 'battery' => 'Thay pin', 'glass-press' => 'Ép mặt kính', 'charging-port' => 'Sửa chân sạc']
            : ['thermal' => 'Vệ sinh & tra keo', 'battery' => 'Thay pin', 'screen' => 'Thay màn hình', 'mainboard' => 'Sửa nguồn/main'];

        foreach ($sampleFaults as $fKey => $fTitle) {
            $std = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $fKey, 'standard', $detectedModel['id']);
            $gen = RepairAtlasPricing::calculatePrice($deviceType, $tier, $brand, $fKey, 'genuine', $detectedModel['id']);
            if ($std && $gen) {
                $modelServicesPricing[] = [
                    'faultId' => $fKey,
                    'name'    => $fTitle,
                    'range'   => RepairAtlasPricing::formatVND($std['min']) . ' – ' . RepairAtlasPricing::formatVND($gen['max']),
                ];
            }
        }
    }
}

// ==========================================
// 7. XÂY DỰNG PROMPT CHĂM SÓC KHÁCH HÀNG CHUYÊN NGHIỆP
// ==========================================
if (!function_exists('buildCustomerServicePrompt')) {
function buildCustomerServicePrompt(
    string  $detectedIntent,
    ?array  $detectedModel,
    ?string $detectedBrand,
    ?string $detectedDistrict,
    ?string $detectedFaultId,
    ?array  $pricingInfo,
    array   $modelServicesPricing,
    ?array  $matchedRepairRequest,
    array   $topShops
): string {
    $priceContext = '';
    if ($pricingInfo) {
        $priceContext = "
- Pan bệnh phát hiện: {$pricingInfo['faultName']} ({$pricingInfo['desc']})";
        if ($pricingInfo['standard']) {
            $priceContext .= "\n  + Mức Tiết kiệm (Linh kiện thường): " . RepairAtlasPricing::formatVND($pricingInfo['standard']['min']) . ' – ' . RepairAtlasPricing::formatVND($pricingInfo['standard']['max']);
        }
        if ($pricingInfo['oem']) {
            $priceContext .= "\n  + Mức Tiêu chuẩn (Linh kiện OEM): " . RepairAtlasPricing::formatVND($pricingInfo['oem']['min']) . ' – ' . RepairAtlasPricing::formatVND($pricingInfo['oem']['max']);
        }
        if ($pricingInfo['genuine']) {
            $priceContext .= "\n  + Mức Cao cấp (Zin chính hãng): " . RepairAtlasPricing::formatVND($pricingInfo['genuine']['min']) . ' – ' . RepairAtlasPricing::formatVND($pricingInfo['genuine']['max']);
        }
    } elseif (!empty($modelServicesPricing)) {
        $priceContext = "\n- Dịch vụ phổ biến của model này:";
        foreach ($modelServicesPricing as $msp) {
            $priceContext .= "\n  + {$msp['name']}: {$msp['range']}";
        }
    }

    $repairContext = '';
    if ($matchedRepairRequest) {
        $repairContext = "\n- TÌM THẤY YÊU CẦU SỬA CHỮA TRONG HỆ THỐNG:"
            . "\n  + Mã yêu cầu: " . ($matchedRepairRequest['id'] ?? '')
            . "\n  + Khách hàng: " . ($matchedRepairRequest['customer_name'] ?? '')
            . "\n  + Thiết bị: " . ($matchedRepairRequest['brand_model'] ?? '')
            . "\n  + Vấn đề: " . ($matchedRepairRequest['issue_type'] ?? '') . ' - ' . ($matchedRepairRequest['symptom'] ?? '')
            . "\n  + Trạng thái hiện tại: " . ($matchedRepairRequest['status'] ?? 'Đang xử lý')
            . "\n  + Ngày tạo: " . ($matchedRepairRequest['created_at'] ?? '');
    }

    $shopContext = '';
    if (!empty($topShops)) {
        $shopContext = "\n- CỬA HÀNG ĐÃ ĐỐI SOÁT PHÙ HỢP:\n" . implode("\n", array_map(fn($s) => '  + ' . $s['name'] . ' (' . $s['address'] . ' - SĐT: ' . ($s['phone'] ?? 'chưa có') . ')', $topShops));
    }

    $modelName = $detectedModel['name'] ?? 'Chưa xác định cụ thể';
    $distName  = $detectedDistrict ?: 'Toàn TP.HCM';

    return <<<PROMPT
Bạn là Chuyên viên Chăm Sóc Khách Hàng (CSKH) & Hỗ Trợ Kỹ Thuật của nền tảng FixNear (Hệ thống tra cứu chi phí sửa chữa & tiệm sửa uy tín tại TP.HCM).

3 NGUYÊN TẮC BẮT BUỘC:
1. Ưu tiên giải quyết vấn đề và cảm xúc của khách hàng hơn việc giới thiệu hoặc bán dịch vụ.
2. Chỉ cung cấp thông tin thuộc phạm vi sản phẩm, dịch vụ và chính sách thực tế của FixNear.
3. KHÔNG tự động biến mọi cuộc trò chuyện thành tư vấn bán hàng.

Ý ĐỊNH KHÁCH HÀNG HIỆN TẠI: [{$detectedIntent}]

DỮ LIỆU NỘI BỘ TỪ HỆ THỐNG:
- Dòng máy: {$modelName}
- Khu vực: {$distName}{$priceContext}{$repairContext}{$shopContext}

QUY TẮC PHẢN HỒI THEO TỪNG CHẾ ĐỘ:

【NẾU LÀ ANGRY_COMPLAINT (Khách tức giận, khiếu nại, thất vọng)】:
- Giữ giọng điệu bình tĩnh, tôn trọng, chuyên nghiệp và đồng cảm.
- Công nhận cảm xúc của khách, KHÔNG tranh cãi, KHÔNG đổ lỗi.
- Xin lỗi chân thành vì trải nghiệm không tốt mà khách đang gặp phải.
- Xác định vấn đề cụ thể và hỏi TỐI ĐA MỘT CÂU cần thiết cho mỗi lượt.
- Đưa ra hướng xử lý thực tế:
  + Nếu khách khiếu nại chất lượng tiệm sửa / nghi bị tráo đồ / thợ sửa sai giá: Hướng dẫn khách gửi thông tin qua form Liên Hệ & Khiếu Nại (contact.php) hoặc Báo Sai Cửa Hàng (report.php) để Ban Quản Trị FixNear trực tiếp kiểm tra và làm việc với cửa hàng.
  + Nếu khách yêu cầu gặp người thật: Giải thích chân thành rằng bạn là trợ lý trực tuyến, hướng dẫn khách gửi số điện thoại qua trang Liên Hệ (contact.php) để Ban Quản Trị liên hệ trực tiếp.
- KHÔNG được giả vờ rằng khiếu nại đã được chuyển cho nhân viên nếu khách chưa gửi form.
- TUYỆT ĐỐI KHÔNG ép khách mua hàng, KHÔNG giới thiệu tiệm mới và KHÔNG gửi quảng cáo trong lúc xử lý khiếu nại.
- KHÔNG yêu cầu khách cung cấp mật khẩu, mã OTP hoặc thông tin thanh toán nhạy cảm.

【NẾU LÀ URGENT_SUPPORT (Cần hỗ trợ khẩn cấp)】:
- Nếu máy vô nước, cháy chập, sập nguồn, pin phồng: Cảnh báo an toàn ngay lập tức (TẮT NGUỒN NGAY, TUYỆT ĐỐI KHÔNG CẮM SẠC, KHÔNG sấy nóng), hướng dẫn sơ cứu đúng cách trước khi đem đến tiệm gần nhất.
- Trả lời thẳng vào hành động cần làm ngay, súc tích, rõ ràng.

【NẾU LÀ PRODUCT_INQUIRY / SERVICE_INQUIRY (Hỏi sản phẩm, dịch vụ, bảng giá)】:
- Trả lời DỰA HOÀN TOÀN TRÊN DỮ LIỆU CSDL FIXNEAR ở trên (tên máy, khoảng giá 3 cấp, chính sách ký tên linh kiện, ngồi xem trực tiếp, bảo hành).
- Không bịa đặt số liệu giá ngoài dữ liệu hệ thống.
- Giải thích khách quan, trung thực (mức tiết kiệm là linh kiện thường, tiêu chuẩn là OEM, cao cấp là zin chính hãng).

【NẾU LÀ GENERAL_GREETING (Chào hỏi, xã giao)】:
- Chào hỏi tự nhiên, thân thiện, xưng "mình" gọi "bạn".
- KHÔNG tự động spam toàn bộ danh sách sản phẩm hoặc quảng cáo.
- Gợi ý 3-4 chủ đề FixNear có thể hỗ trợ (tra cứu bảng giá, tìm tiệm gần bạn, cẩm nang tránh luộc đồ, tra cứu tiến độ yêu cầu sửa chữa).

【NẾU LÀ OUT_OF_SCOPE (Ngoài phạm vi)】:
- Lịch sự giải thích FixNear chuyên về tra cứu thông tin sửa chữa thiết bị điện tử tại TP.HCM, và mời khách đặt câu hỏi liên quan đến thiết bị/cửa hàng.

YÊU CẦU ĐỊNH DẠNG:
- Trả lời tự nhiên như tin nhắn chat thật giữa người với người.
- KHÔNG dùng tiêu đề markdown cứng nhắc như ### hay các gạch đầu dòng dài dòng.
- Không xưng là AI của Google/Gemini. Bạn là "Trợ lý CSKH FixNear".
PROMPT;
}
}

// ==========================================
// 8. GỌI GEMINI VỚI MULTI-TURN HISTORY
// ==========================================
if (!function_exists('callGeminiMultiTurn')) {
function callGeminiMultiTurn(string $systemPrompt, string $userMessage, array $history): ?string
{
    $apiKey = GEMINI_API_KEY;
    if (!$apiKey) return null;

    $models = ['gemini-3.8-flash', 'gemini-flash-lite-latest', 'gemini-flash-latest'];

    // Xây dựng chuỗi hội thoại contents
    $contents = [];
    if (!empty($history) && is_array($history)) {
        foreach ($history as $h) {
            if (empty($h['content'])) continue;
            $role = ($h['role'] === 'assistant' || $h['role'] === 'model') ? 'model' : 'user';
            // Không để 2 role cùng loại đứng liền kề
            if (!empty($contents) && end($contents)['role'] === $role) {
                continue;
            }
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => mb_substr((string)$h['content'], 0, 1000)]]
            ];
        }
    }

    // Đảm bảo lượt cuối cùng là user message hiện tại
    if (empty($contents) || end($contents)['role'] !== 'user') {
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];
    } else {
        $contents[count($contents) - 1] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];
    }

    $payload = [
        'system_instruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature'     => 0.65,
            'maxOutputTokens' => 500,
            'topP'            => 0.9,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
        ],
    ];

    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text) return trim($text);
        }
    }

    return null;
}
}

// Gọi Gemini AI
$geminiText = null;
if (GEMINI_ENABLED) {
    $systemPrompt = buildCustomerServicePrompt(
        $detectedIntent,
        $detectedModel,
        $detectedBrand,
        $detectedDistrict,
        $detectedFaultId,
        $pricingInfo,
        $modelServicesPricing,
        $matchedRepairRequest,
        $topShops
    );
    $geminiText = callGeminiMultiTurn($systemPrompt, $query, $history);
}

// ==========================================
// 9. XÂY DỰNG GIAO DIỆN PHẢN HỒI (HTML)
// ==========================================
$responseHtml = '';
$plainTextResponse = '';

if ($geminiText) {
    $plainTextResponse = $geminiText;
    $cleanText = htmlspecialchars($geminiText, ENT_QUOTES, 'UTF-8');
    $cleanText = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', $cleanText);
    $cleanText = nl2br($cleanText);

    $responseHtml .= '
        <div style="font-size:13px;line-height:1.6;color:#1e293b;margin-bottom:8px;">
            ' . $cleanText . '
        </div>';
} else {
    // Fallback CSKH tự nhiên nếu API gián đoạn
    if ($detectedIntent === 'ANGRY_COMPLAINT') {
        $plainTextResponse = "Mình rất xin lỗi vì bạn đang gặp phải trải nghiệm không hài lòng. Mình hiểu sự bức xúc này và sẵn sàng hỗ trợ bạn làm rõ vấn đề. Bạn có thể cho mình biết cụ thể sự việc xảy ra ở cửa hàng nào, hoặc mã đơn của bạn không?";
        $responseHtml .= '
            <div style="font-size:13px;line-height:1.6;color:#1e293b;margin-bottom:8px;">
                Mình rất xin lỗi vì bạn đang gặp phải trải nghiệm không hài lòng. Mình hoàn toàn hiểu sự bức xúc này và ở đây để hỗ trợ bạn.<br><br>
                Bạn có thể cho mình biết cụ thể sự việc xảy ra ở cửa hàng nào, hoặc mã yêu cầu sửa chữa để mình kiểm tra nhé?
            </div>';
    } elseif ($detectedIntent === 'URGENT_SUPPORT') {
        $plainTextResponse = "CẢNH BÁO AN TOÀN: Hãy tắt nguồn thiết bị ngay lập tức, tuyệt đối không cắm sạc pin và không dùng máy sấy tóc nóng. Hãy mang máy đến cửa hàng kỹ thuật gần nhất để được sấy khô bằng sóng siêu âm.";
        $responseHtml .= '
            <div style="font-size:13px;line-height:1.6;color:#991b1b;background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px;margin-bottom:8px;">
                ⚠️ <strong>CẢNH BÁO AN TOÀN KHẨN CẤP:</strong><br>
                1. <strong>Tắt nguồn thiết bị ngay lập tức.</strong><br>
                2. <strong>TUYỆT ĐỐI KHÔNG cắm sạc pin</strong> để tránh chập cháy bo mạch.<br>
                3. <strong>Không dùng máy sấy tóc</strong> (nhiệt độ cao làm chảy gioăng cao su và hỏng màn hình).<br>
                4. Mang máy đến tiệm sửa gần nhất để sấy hút ẩm chuyên dụng.
            </div>';
    } elseif ($matchedRepairRequest) {
        $statusStr = htmlspecialchars($matchedRepairRequest['status'] ?? 'Đang xử lý');
        $mName = htmlspecialchars($matchedRepairRequest['brand_model'] ?? 'Thiết bị');
        $rId = htmlspecialchars($matchedRepairRequest['id']);
        $plainTextResponse = "Hệ thống đã tìm thấy yêu cầu sửa chữa mã {$rId} cho dòng máy {$mName}. Trạng thái hiện tại: {$statusStr}.";
        $responseHtml .= '
            <div style="font-size:13px;line-height:1.6;color:#1e293b;margin-bottom:8px;">
                Dạ hệ thống FixNear đã tìm thấy thông tin yêu cầu của bạn:<br>
                • <strong>Mã yêu cầu:</strong> ' . $rId . '<br>
                • <strong>Thiết bị:</strong> ' . $mName . '<br>
                • <strong>Trạng thái:</strong> <span style="color:#ea580c;font-weight:700;">' . $statusStr . '</span><br>
                Kỹ thuật viên của cửa hàng liên kết sẽ chủ động liên hệ qua số điện thoại của bạn ngay khi có cập nhật mới nhé!
            </div>';
    } elseif ($detectedIntent === 'GENERAL_GREETING') {
        $plainTextResponse = "Xin chào bạn! Mình là Trợ lý CSKH FixNear. Mình có thể hỗ trợ bạn tìm hiểu về bảng giá tham khảo, tìm tiệm sửa chữa uy tín hoặc kiểm tra tiến độ yêu cầu sửa chữa. Bạn đang cần hỗ trợ điều gì nhỉ?";
        $responseHtml .= '
            <div style="font-size:13px;line-height:1.6;color:#1e293b;margin-bottom:8px;">
                Xin chào bạn! 👋 Mình là Trợ lý CSKH FixNear.<br>
                Mình có thể hỗ trợ bạn các vấn đề sau:<br>
                • 🔍 Tra cứu bảng giá sửa chữa tham khảo theo dòng máy.<br>
                • 📍 Tìm tiệm sửa chữa uy tín gần bạn theo quận/huyện TP.HCM.<br>
                • 🛡️ Cẩm nang an toàn đi sửa máy (tránh tráo linh kiện).<br>
                • 📋 Tra cứu tiến độ yêu cầu sửa chữa đã gửi.<br><br>
                Bạn đang quan tâm đến nội dung nào nhỉ?
            </div>';
    } else {
        $plainTextResponse = "Chào bạn, mình là Trợ lý CSKH FixNear. Bạn có thể cho mình biết cụ thể dòng thiết bị và triệu chứng máy đang gặp phải để mình hỗ trợ tốt nhất nhé.";
        $responseHtml .= '
            <div style="font-size:13px;line-height:1.6;color:#1e293b;margin-bottom:8px;">
                Chào bạn! Bạn hãy mô tả rõ hơn dòng máy và tình trạng thiết bị đang gặp phải để mình tra cứu bảng giá và hướng xử lý phù hợp nhất nhé!
            </div>';
    }
}

// --- THẺ HÀNH ĐỘNG KHI KHIẾU NẠI (ANGRY_COMPLAINT) ---
// TUYỆT ĐỐI KHÔNG QUẢNG CÁO TIỆM HAY BÁN HÀNG TRONG LÚC KHÁCH BỨC XÚC
if ($detectedIntent === 'ANGRY_COMPLAINT') {
    $responseHtml .= '
        <div style="margin-top:10px;padding:10px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;">
            <div style="font-size:11.5px;font-weight:700;color:#991b1b;margin-bottom:6px;">
                🛡️ Kênh tiếp nhận chính thức từ Ban Quản Trị FixNear:
            </div>
            <div style="font-size:12px;color:#475569;line-height:1.5;margin-bottom:8px;">
                Nếu bạn gặp phải tình trạng tráo đồ, thợ sửa sai giá hoặc dịch vụ kém chất lượng, hãy gửi phản ánh để BQT can thiệp trực tiếp với cửa hàng:
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="contact.php" style="font-size:11.5px;padding:6px 12px;background:#dc2626;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">
                    ✉️ Gửi Khiếu Nại Đến Ban Quản Trị
                </a>
                <a href="report.php" style="font-size:11.5px;padding:6px 12px;background:#fff;color:#dc2626;border:1px solid #dc2626;border-radius:6px;text-decoration:none;font-weight:700;">
                    🚩 Báo Sai / Phản Ánh Cửa Hàng
                </a>
            </div>
        </div>';
}

// --- THẺ BẢNG GIÁ NỘI BỘ (Chỉ hiển thị khi khách hỏi sản phẩm/dịch vụ) ---
if ($detectedIntent !== 'ANGRY_COMPLAINT' && $pricingInfo && ($pricingInfo['standard'] || $pricingInfo['oem'] || $pricingInfo['genuine'])) {
    $fName = htmlspecialchars($pricingInfo['faultName']);
    $responseHtml .= '
        <div style="margin:8px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:12px;">
            <div style="font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                <span>' . $pricingInfo['icon'] . '</span>
                <span>Bảng giá ước tính từ CSDL FixNear (' . $fName . '):</span>
            </div>
            <div style="display:grid;gap:5px;">';
    if ($pricingInfo['standard']) {
        $responseHtml .= '<div style="display:flex;justify-content:space-between;border-bottom:1px dashed #e2e8f0;padding-bottom:3px;"><span style="color:#475569;">🥉 Tiết kiệm (Linh kiện thường):</span><span style="font-weight:700;color:#2563eb;">' . RepairAtlasPricing::formatVND($pricingInfo['standard']['min']) . ' – ' . RepairAtlasPricing::formatVND($pricingInfo['standard']['max']) . '</span></div>';
    }
    if ($pricingInfo['oem']) {
        $responseHtml .= '<div style="display:flex;justify-content:space-between;border-bottom:1px dashed #e2e8f0;padding-bottom:3px;"><span style="color:#475569;">🥈 Tiêu chuẩn (Linh kiện OEM):</span><span style="font-weight:700;color:#ea580c;">' . RepairAtlasPricing::formatVND($pricingInfo['oem']['min']) . ' – ' . RepairAtlasPricing::formatVND($pricingInfo['oem']['max']) . '</span></div>';
    }
    if ($pricingInfo['genuine']) {
        $responseHtml .= '<div style="display:flex;justify-content:space-between;"><span style="color:#475569;">🥇 Cao cấp (Zin chính hãng):</span><span style="font-weight:700;color:#16a34a;">' . RepairAtlasPricing::formatVND($pricingInfo['genuine']['min']) . ' – ' . RepairAtlasPricing::formatVND($pricingInfo['genuine']['max']) . '</span></div>';
    }
    $responseHtml .= '</div></div>';
} elseif ($detectedIntent !== 'ANGRY_COMPLAINT' && !empty($modelServicesPricing)) {
    $mName = htmlspecialchars($detectedModel['name']);
    $responseHtml .= '
        <div style="margin:8px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:12px;">
            <div style="font-weight:700;color:#0f172a;margin-bottom:6px;">📊 Bảng giá dịch vụ thông dụng (' . $mName . '):</div>
            <div style="display:grid;gap:5px;">';
    foreach ($modelServicesPricing as $msp) {
        $responseHtml .= '<div style="display:flex;justify-content:space-between;border-bottom:1px dashed #e2e8f0;padding-bottom:3px;"><span style="color:#475569;">' . htmlspecialchars($msp['name']) . ':</span><span style="font-weight:700;color:#ea580c;">' . htmlspecialchars($msp['range']) . '</span></div>';
    }
    $responseHtml .= '</div></div>';
}

// --- THẺ CỬA HÀNG ĐỀ XUẤT (Chỉ hiển thị khi KHÔNG PHẢI ANGRY_COMPLAINT) ---
if ($detectedIntent !== 'ANGRY_COMPLAINT' && !empty($topShops) && in_array($detectedIntent, ['PRODUCT_INQUIRY', 'SERVICE_INQUIRY', 'URGENT_SUPPORT'])) {
    $responseHtml .= '
        <div style="margin-top:8px;font-size:12px;font-weight:700;color:#0f172a;">
            🏪 Cửa hàng tham khảo đã qua đối soát:
        </div>
        <div style="display:grid;gap:6px;margin-top:6px;">';

    foreach ($topShops as $sh) {
        $sName   = htmlspecialchars($sh['name']);
        $sAddr   = htmlspecialchars($sh['address']);
        $sPhone  = htmlspecialchars($sh['phone'] ?? 'Chưa có số');
        $sId     = (int)$sh['id'];
        $sRating = !empty($sh['google_rating_verified'])
            ? '<span style="font-size:11px;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;font-weight:700;">★ ' . number_format((float)$sh['google_rating'], 1) . '</span>'
            : '';
        $distText = isset($sh['distance_km']) ? ' • 📍 ' . number_format($sh['distance_km'], 1) . 'km' : '';

        $responseHtml .= '
            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div style="font-weight:700;color:#0f172a;font-size:12.5px;">
                        <a href="shop_detail.php?id=' . $sId . '" style="color:#0f172a;text-decoration:none;">' . $sName . '</a>
                    </div>
                    ' . $sRating . '
                </div>
                <div style="font-size:11.5px;color:#64748b;margin:3px 0;">' . $sAddr . $distText . '</div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:5px;padding-top:4px;border-top:1px solid #f1f5f9;">
                    <span style="font-size:11px;color:#ea580c;font-weight:700;">📞 ' . $sPhone . '</span>
                    <div style="display:flex;gap:4px;">
                        <a href="shop_detail.php?id=' . $sId . '" style="font-size:10.5px;padding:3px 8px;background:#f1f5f9;color:#334155;border-radius:4px;text-decoration:none;font-weight:700;">Xem tiệm</a>
                        <a href="request_repair.php?shop_id=' . $sId . '" style="font-size:10.5px;padding:3px 8px;background:#ea580c;color:#fff;border-radius:4px;text-decoration:none;font-weight:700;">Gửi yêu cầu</a>
                    </div>
                </div>
            </div>';
    }
    $responseHtml .= '</div>';
}

// --- NÚT HÀNH ĐỘNG ĐIỀU HƯỚNG ---
if ($detectedIntent !== 'ANGRY_COMPLAINT') {
    $responseHtml .= '<div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">';
    if ($detectedModel) {
        $responseHtml .= '<a href="model_detail.php?id=' . urlencode($detectedModel['id']) . '" style="font-size:11.5px;padding:5px 10px;background:#0f172a;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">📱 Báo Giá ' . htmlspecialchars($detectedModel['name']) . '</a>';
    }
    if ($detectedDistrict) {
        $responseHtml .= '<a href="shops.php?district=' . urlencode($detectedDistrict) . '" style="font-size:11.5px;padding:5px 10px;background:#ea580c;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">📍 Tiệm tại ' . htmlspecialchars($detectedDistrict) . '</a>';
    } elseif ($detectedIntent === 'PRODUCT_INQUIRY' || $detectedIntent === 'SERVICE_INQUIRY') {
        $responseHtml .= '<a href="shops.php" style="font-size:11.5px;padding:5px 10px;background:#ea580c;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">🏪 68 Cửa Hàng</a>';
    }
    $responseHtml .= '<a href="request_repair.php" style="font-size:11.5px;padding:5px 10px;background:#fff;color:#ea580c;border:1px solid #ea580c;border-radius:6px;text-decoration:none;font-weight:700;">📝 Gửi Yêu Cầu</a>';
    $responseHtml .= '<a href="contact.php" style="font-size:11.5px;padding:5px 10px;background:#f8fafc;color:#475569;border:1px solid #cbd5e1;border-radius:6px;text-decoration:none;font-weight:700;">📞 Liên Hệ</a>';
    $responseHtml .= '</div>';
}

echo json_encode([
    'success'      => true,
    'intent'       => $detectedIntent,
    'html'         => $responseHtml,
    'text'         => $plainTextResponse,
    'gemini_used'  => $geminiText !== null,
    'data'         => [
        'intent'           => $detectedIntent,
        'detectedModel'    => $detectedModel['name'] ?? null,
        'detectedBrand'    => $detectedBrand,
        'detectedDistrict' => $detectedDistrict,
        'detectedFault'    => $pricingInfo['faultName'] ?? null,
        'repairFound'      => !empty($matchedRepairRequest),
        'shopsFound'       => count($topShops),
    ],
], JSON_UNESCAPED_UNICODE);
