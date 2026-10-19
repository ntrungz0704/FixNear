<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');

date_default_timezone_set('Asia/Ho_Chi_Minh');

function fnLiveUserInitials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'ND';
    $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) >= 2) {
        return mb_strtoupper(
            mb_substr($words[count($words) - 2], 0, 1, 'UTF-8') .
            mb_substr($words[count($words) - 1], 0, 1, 'UTF-8'),
            'UTF-8'
        );
    }
    return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
}

function fnLiveDeviceBadgeClass(string $dev): string {
    $d = mb_strtolower($dev, 'UTF-8');
    if (str_contains($d, 'macbook') || str_contains($d, 'mac')) {
        return 'bg-purple-50 text-purple-700 border-purple-200';
    }
    if (str_contains($d, 'laptop')) {
        return 'bg-blue-50 text-blue-700 border-blue-200';
    }
    if (str_contains($d, 'tablet') || str_contains($d, 'bảng')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
    if (str_contains($d, 'đồng hồ') || str_contains($d, 'watch')) {
        return 'bg-amber-50 text-amber-700 border-amber-200';
    }
    if (str_contains($d, 'pc') || str_contains($d, 'bàn')) {
        return 'bg-slate-100 text-slate-700 border-slate-300';
    }
    return 'bg-orange-50 text-orange-700 border-orange-200';
}

function fnLiveShortDeviceLabel(string $dev): string {
    $dev = trim($dev);
    return match ($dev) {
        'Máy tính bảng (Tablet)' => 'Tablet',
        'Máy tính bàn (PC)' => 'PC',
        'Đồng hồ thông minh' => 'Smartwatch',
        default => $dev,
    };
}

$devLabelsMap = [
    'phone' => 'Điện thoại',
    'win_laptop' => 'Laptop Windows',
    'laptop' => 'Laptop Windows',
    'macbook' => 'MacBook / Mac',
    'mac' => 'MacBook / Mac',
    'tablet' => 'Máy tính bảng (Tablet)',
    'smartwatch' => 'Đồng hồ thông minh',
    'pc_desktop' => 'Máy tính bàn (PC)',
    'pc' => 'Máy tính bàn (PC)',
];

// ================= POST: GHI NHẬN SỰ KIỆN & TRA CỨU REAL-TIME =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $payload = json_decode((string)$rawBody, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $eventName = trim((string)($payload['event_name'] ?? ''));
    $devParam = trim((string)($payload['device'] ?? ''));
    $brandParam = trim((string)($payload['brand'] ?? ''));
    $modelParam = trim((string)($payload['model'] ?? ''));
    $faultParam = trim((string)($payload['fault'] ?? ''));
    $shopId = (int)($payload['shop_id'] ?? 0);
    $customQuery = trim((string)($payload['search_query'] ?? ''));
    $pageUrl = trim((string)($payload['page_url'] ?? 'index.php'));
    $pageUrl = ltrim($pageUrl, '/');

    $currentUser = currentUser();
    $distParam = trim((string)($payload['district'] ?? ''));
    if ($distParam === '') {
        $distParam = trim((string)($currentUser['district'] ?? ($_SESSION['user_location_name'] ?? 'TP.HCM')));
    }
    if ($distParam === '') {
        $distParam = 'TP.HCM';
    }

    // Giải mã tên Model & Lỗi từ RepairAtlasPricing nếu có
    $allModels = RepairAtlasPricing::getAllModels();
    $allFaults = RepairAtlasPricing::getAllFaults();
    $modelTitle = '';
    if ($modelParam !== '' && isset($allModels[$modelParam])) {
        $modelTitle = $allModels[$modelParam]['name'] ?? $modelParam;
        if ($devParam === '') {
            $devParam = $allModels[$modelParam]['deviceType'] ?? '';
        }
    } elseif ($modelParam !== '') {
        $modelTitle = $modelParam;
    }

    $faultTitle = '';
    if ($faultParam !== '' && isset($allFaults[$faultParam])) {
        $faultTitle = $allFaults[$faultParam]['name'] ?? $faultParam;
    } elseif ($faultParam !== '') {
        $faultTitle = $faultParam;
    }

    $devCategory = $devLabelsMap[$devParam] ?? '';
    if ($devCategory === '') {
        if (in_array($devParam, array_values($devLabelsMap), true)) {
            $devCategory = $devParam;
        } else {
            $firstDev = explode(',', (string)($currentUser['device_group'] ?? 'Điện thoại'))[0] ?? 'Điện thoại';
            $devCategory = trim($firstDev) ?: 'Điện thoại';
        }
    }

    // Ánh xạ event sang chuỗi features_used chuẩn GA4
    $featureLabel = match ($eventName) {
        'fn_select_device_flow' => 'Chọn loại thiết bị → hãng → model → lỗi',
        'fn_view_prices' => ($modelTitle !== '' || $faultTitle !== '' || $brandParam !== '')
            ? 'Chọn loại thiết bị → hãng → model → lỗi, Xem giá dự đoán / giá có nguồn theo model và dịch vụ'
            : 'Xem giá dự đoán / giá có nguồn theo model và dịch vụ',
        'fn_view_shops' => 'Lọc hoặc tra cứu danh sách cửa hàng',
        'fn_view_map', 'fn_click_directions' => 'Xem bản đồ / chỉ đường',
        'fn_view_shop_detail' => 'Xem chi tiết dịch vụ & phản hồi của cửa hàng',
        'fn_view_repair_request' => 'Xem giao diện gửi yêu cầu sửa chữa',
        'fn_track_request' => 'Tra cứu mã yêu cầu sửa chữa',
        'fn_open_chatbot' => 'Sử dụng trợ lý Chatbot AI',
        default => 'Tra cứu trên website',
    };

    // Xây dựng nội dung search_query tự nhiên, rõ ràng
    if ($customQuery !== '') {
        $queryText = $customQuery;
        if (!str_contains($queryText, '(')) {
            $queryText .= ' (' . $distParam . ')';
        }
    } elseif ($eventName === 'fn_view_shop_detail' && $shopId > 0) {
        $shop = db()->getShopById($shopId);
        $shopName = $shop['name'] ?? ('Cửa hàng #' . $shopId);
        $shopDist = $shop['district'] ?? $distParam;
        $distParam = $shopDist ?: $distParam;
        $queryText = $shopName . ' — Xem chi tiết dịch vụ & đánh giá tiệm (' . $distParam . ')';
    } elseif ($eventName === 'fn_open_chatbot') {
        $queryText = 'Mở Trợ lý Kỹ thuật Chatbot AI — Tư vấn chẩn đoán & báo giá (' . $distParam . ')';
    } elseif ($eventName === 'fn_view_repair_request') {
        $queryText = 'Mở biểu mẫu gửi yêu cầu sửa chữa & tìm kỹ thuật viên (' . $distParam . ')';
    } elseif ($eventName === 'fn_track_request') {
        $queryText = 'Tra cứu tiến độ & mã yêu cầu sửa chữa (' . $distParam . ')';
    } elseif ($eventName === 'fn_view_map' || $eventName === 'fn_click_directions') {
        $queryText = 'Xem bản đồ định vị & chỉ đường tới cửa hàng sửa chữa (' . $distParam . ')';
    } elseif ($eventName === 'fn_view_shops') {
        $parts = array_filter([$devCategory, $modelTitle ?: $brandParam]);
        $prefix = !empty($parts) ? implode(' · ', $parts) . ' — ' : '';
        $queryText = $prefix . 'Lọc & tra cứu danh sách cửa hàng sửa chữa (' . $distParam . ')';
    } else {
        $parts = array_filter([$modelTitle ?: ($brandParam !== '' ? ucfirst($brandParam) : $devCategory), $faultTitle]);
        if (!empty($parts)) {
            $queryText = implode(' — ', $parts) . ' (' . $distParam . ')';
        } else {
            $queryText = $featureLabel . ' (' . $distParam . ')';
        }
    }

    $userId = (int)($currentUser['id'] ?? 0);
    $userName = trim((string)($currentUser['name'] ?? ''));
    $userEmail = trim((string)($currentUser['email'] ?? ''));
    $userPhone = trim((string)($currentUser['phone'] ?? ''));

    if ($userName === '') {
        $userName = 'Khách truy cập';
    }
    if ($userPhone === '' && $userEmail === '') {
        $userPhone = 'Khách trực tuyến';
    }

    $logged = db()->logUserSearch([
        'user_id' => $userId,
        'user_name' => $userName,
        'user_email' => $userEmail,
        'user_phone' => $userPhone,
        'district' => $distParam,
        'device_category' => $devCategory,
        'search_query' => $queryText,
        'features_used' => $featureLabel,
        'page_url' => $pageUrl ?: 'index.php',
        'dedup_seconds' => (int)($payload['dedup_seconds'] ?? 2),
    ]);

    echo json_encode([
        'ok' => true,
        'logged' => $logged,
        'event_name' => $eventName,
        'search_query' => $queryText,
        'features_used' => $featureLabel,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================= GET: TRẢ VỀ DỮ LIỆU ĐỒNG BỘ REAL-TIME CHO ADMIN =================
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$allUsers = db()->getUsers();
$regularUsers = array_values(array_filter($allUsers, static fn($u) => ($u['role'] ?? 'user') !== 'admin'));

$filterUserQuery = trim((string)($_GET['user_q'] ?? ($_GET['email'] ?? '')));
$filterDistrict = trim((string)($_GET['district'] ?? ''));
$filterDevice = trim((string)($_GET['device'] ?? ''));
$filterQuery = trim((string)($_GET['q'] ?? ''));

$allSearchLogs = db()->getSearchLogs();
$filteredLogs = db()->getSearchLogs([
    'user_q' => $filterUserQuery,
    'district' => $filterDistrict,
    'device' => $filterDevice,
    'q' => $filterQuery,
]);

$userByEmail = [];
foreach ($regularUsers as $u) {
    $em = mb_strtolower(trim((string)($u['email'] ?? '')), 'UTF-8');
    if ($em !== '') {
        $userByEmail[$em] = $u;
    }
}

$logsByEmail = [];
$featuresByEmail = [];
$totalGaEvents = 0;
$gaEventBreakdown = [
    'fn_select_device_flow' => ['label' => 'Chọn thiết bị → hãng → model → lỗi', 'count' => 0],
    'fn_view_prices' => ['label' => 'Xem giá dự đoán / giá có nguồn', 'count' => 0],
    'fn_view_shops' => ['label' => 'Lọc & tra cứu danh sách cửa hàng', 'count' => 0],
    'fn_view_map' => ['label' => 'Xem bản đồ & chỉ đường', 'count' => 0],
    'fn_view_shop_detail' => ['label' => 'Xem chi tiết dịch vụ & đánh giá tiệm', 'count' => 0],
    'fn_view_repair_request' => ['label' => 'Xem giao diện gửi yêu cầu sửa chữa', 'count' => 0],
    'fn_track_request' => ['label' => 'Tra cứu mã yêu cầu sửa chữa', 'count' => 0],
    'fn_open_chatbot' => ['label' => 'Sử dụng trợ lý Chatbot AI', 'count' => 0],
];

foreach ($allSearchLogs as $log) {
    $em = mb_strtolower(trim((string)($log['user_email'] ?? '')), 'UTF-8');
    if ($em !== '') {
        $logsByEmail[$em][] = $log;
    }

    $fRaw = str_replace(
        'Xem chi tiết dịch vụ, phản hồi của cửa hàng',
        'Xem chi tiết dịch vụ & phản hồi của cửa hàng',
        (string)($log['features_used'] ?? '')
    );
    $fList = array_filter(array_map('trim', explode(',', $fRaw)));
    foreach ($fList as $fItem) {
        if ($em !== '') {
            $featuresByEmail[$em][$fItem] = $fItem;
        }
        $totalGaEvents++;
        $fLower = mb_strtolower($fItem, 'UTF-8');
        if (str_contains($fLower, 'chọn loại thiết bị')) {
            $gaEventBreakdown['fn_select_device_flow']['count']++;
        } elseif (str_contains($fLower, 'xem giá') || str_contains($fLower, 'so sánh giá') || str_contains($fLower, 'bảng giá')) {
            $gaEventBreakdown['fn_view_prices']['count']++;
        } elseif (str_contains($fLower, 'danh sách cửa hàng')) {
            $gaEventBreakdown['fn_view_shops']['count']++;
        } elseif (str_contains($fLower, 'bản đồ')) {
            $gaEventBreakdown['fn_view_map']['count']++;
        } elseif (str_contains($fLower, 'chi tiết dịch vụ')) {
            $gaEventBreakdown['fn_view_shop_detail']['count']++;
        } elseif (str_contains($fLower, 'gửi yêu cầu')) {
            $gaEventBreakdown['fn_view_repair_request']['count']++;
        } elseif (str_contains($fLower, 'mã yêu cầu')) {
            $gaEventBreakdown['fn_track_request']['count']++;
        } elseif (str_contains($fLower, 'chatbot')) {
            $gaEventBreakdown['fn_open_chatbot']['count']++;
        }
    }
}

$activeRegularUsersCount = count(array_intersect_key($logsByEmail, $userByEmail));
if ($activeRegularUsersCount === 0 && !empty($logsByEmail)) {
    $activeRegularUsersCount = count($logsByEmail);
}

$logsPerPage = 8;
$totalFilteredLogs = count($filteredLogs);
$totalLogPages = max(1, (int)ceil($totalFilteredLogs / $logsPerPage));
$logPage = max(1, min($totalLogPages, (int)($_GET['page'] ?? ($_GET['log_page'] ?? 1))));
$logOffset = ($logPage - 1) * $logsPerPage;
$pagedLogs = array_slice($filteredLogs, $logOffset, $logsPerPage);

$rows = [];
foreach ($pagedLogs as $idx => $log) {
    $rowNumber = $logOffset + $idx + 1;
    $ts = strtotime((string)($log['created_at'] ?? 'now'));
    $dateFormatted = $ts ? date('d/m/Y', $ts) : '';
    $timeFormatted = $ts ? date('H:i:s', $ts) : '';
    $em = mb_strtolower(trim((string)($log['user_email'] ?? '')), 'UTF-8');
    $uInfo = $userByEmail[$em] ?? [];

    $rowFeaturesRaw = str_replace(
        'Xem chi tiết dịch vụ, phản hồi của cửa hàng',
        'Xem chi tiết dịch vụ & phản hồi của cửa hàng',
        (string)($log['features_used'] ?? '')
    );
    $rowFeatures = array_values(array_unique(array_filter(array_map('trim', explode(',', $rowFeaturesRaw)))));
    $features = !empty($featuresByEmail[$em]) ? array_values($featuresByEmail[$em]) : $rowFeatures;

    $modalPayload = [
        'type' => 'log',
        'user_name' => $log['user_name'] ?? 'Khách truy cập',
        'user_email' => $log['user_email'] ?? '',
        'user_phone' => $log['user_phone'] ?? ($uInfo['phone'] ?? ''),
        'user_group' => $uInfo['user_group'] ?? 'Sinh viên / Người đi làm',
        'user_created_at' => !empty($uInfo['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$uInfo['created_at'])) : $dateFormatted . ' ' . $timeFormatted,
        'district' => $log['district'] ?? 'TP.HCM',
        'device_category' => $log['device_category'] ?? 'Điện thoại',
        'device_group' => $uInfo['device_group'] ?? ($log['device_category'] ?? 'Điện thoại'),
        'search_query' => $log['search_query'] ?? '',
        'page_url' => ltrim((string)($log['page_url'] ?? 'index.php'), '/'),
        'search_time' => $dateFormatted . ' lúc ' . $timeFormatted,
        'features' => $features,
        'user_logs' => array_map(static function ($l) {
            $t = strtotime((string)($l['created_at'] ?? 'now'));
            return [
                'time' => $t ? date('d/m/Y H:i:s', $t) : '',
                'device' => $l['device_category'] ?? '',
                'district' => $l['district'] ?? '',
                'query' => $l['search_query'] ?? '',
                'url' => ltrim((string)($l['page_url'] ?? ''), '/'),
            ];
        }, !empty($logsByEmail[$em]) ? $logsByEmail[$em] : [$log]),
    ];

    $rows[] = [
        'id' => (int)($log['id'] ?? 0),
        'row_number' => $rowNumber,
        'date_formatted' => $dateFormatted,
        'time_formatted' => $timeFormatted,
        'initials' => fnLiveUserInitials((string)($log['user_name'] ?? '')),
        'user_name' => $log['user_name'] ?? 'Khách truy cập',
        'user_contact' => ($log['user_phone'] ?? '') ?: ($log['user_email'] ?? 'Khách trực tuyến'),
        'district' => $log['district'] ?? 'TP.HCM',
        'device_badge_class' => fnLiveDeviceBadgeClass((string)($log['device_category'] ?? '')),
        'device_short_label' => fnLiveShortDeviceLabel((string)($log['device_category'] ?? 'Điện thoại')),
        'search_query' => $log['search_query'] ?? '',
        'features_count' => count($features),
        'page_url' => ltrim((string)($log['page_url'] ?? ''), '/'),
        'modal_payload' => $modalPayload,
    ];
}

$userCounts = [];
$userModals = [];
foreach ($regularUsers as $ru) {
    $em = mb_strtolower(trim((string)($ru['email'] ?? '')), 'UTF-8');
    if ($em === '') continue;
    $uLogs = $logsByEmail[$em] ?? [];
    $userCounts[$em] = count($uLogs);
    $createdTs = !empty($ru['created_at']) ? strtotime((string)$ru['created_at']) : false;
    $devs = array_values(array_filter(array_map('trim', explode(',', (string)($ru['device_group'] ?? 'Điện thoại')))));
    $firstLog = $uLogs[0] ?? [];
    $uFeatures = array_values($featuresByEmail[$em] ?? ['Chọn loại thiết bị → hãng → model → lỗi', 'Xem giá dự đoán / giá có nguồn theo model và dịch vụ']);
    $userModals[$em] = [
        'type' => 'user',
        'user_name' => $ru['name'] ?? '',
        'user_email' => $ru['email'] ?? '',
        'user_phone' => $ru['phone'] ?? '',
        'user_group' => $ru['user_group'] ?? 'Sinh viên / Người đi làm',
        'user_created_at' => $createdTs ? date('d/m/Y H:i:s', $createdTs) : '',
        'district' => $ru['district'] ?? 'TP.HCM',
        'device_category' => $firstLog['device_category'] ?? ($devs[0] ?? 'Điện thoại'),
        'device_group' => $ru['device_group'] ?? 'Điện thoại',
        'search_query' => $firstLog['search_query'] ?? '',
        'page_url' => ltrim((string)($firstLog['page_url'] ?? 'index.php'), '/'),
        'search_time' => !empty($firstLog['created_at']) ? date('d/m/Y lúc H:i:s', strtotime((string)$firstLog['created_at'])) : '',
        'features' => $uFeatures,
        'user_logs' => array_map(static function ($l) {
            $t = strtotime((string)($l['created_at'] ?? 'now'));
            return [
                'time' => $t ? date('d/m/Y H:i:s', $t) : '',
                'device' => $l['device_category'] ?? '',
                'district' => $l['district'] ?? '',
                'query' => $l['search_query'] ?? '',
                'url' => ltrim((string)($l['page_url'] ?? ''), '/'),
            ];
        }, $uLogs),
    ];
}

$latestLog = $allSearchLogs[0] ?? null;
$revision = hash('sha256', json_encode([
    count($allSearchLogs),
    $totalGaEvents,
    $latestLog['id'] ?? 0,
    $latestLog['created_at'] ?? '',
    $latestLog['features_used'] ?? '',
    $totalFilteredLogs,
    array_column($gaEventBreakdown, 'count'),
    $userCounts,
]));

echo json_encode([
    'ok' => true,
    'revision' => $revision,
    'server_time' => date('H:i:s — d/m/Y'),
    'kpi' => [
        'total_searches' => count($allSearchLogs),
        'active_users' => $activeRegularUsersCount,
        'total_ga_events' => $totalGaEvents,
    ],
    'user_counts' => $userCounts,
    'user_modals' => $userModals,
    'ga_breakdown' => array_map(static fn($item) => $item['count'], $gaEventBreakdown),
    'pagination' => [
        'filtered_total' => $totalFilteredLogs,
        'page' => $logPage,
        'total_pages' => $totalLogPages,
        'offset' => $logOffset,
        'per_page' => $logsPerPage,
        'range_start' => $totalFilteredLogs > 0 ? ($logOffset + 1) : 0,
        'range_end' => min($logOffset + $logsPerPage, $totalFilteredLogs),
    ],
    'latest_log' => $latestLog ? [
        'id' => (int)($latestLog['id'] ?? 0),
        'user_name' => $latestLog['user_name'] ?? 'Khách truy cập',
        'device_category' => $latestLog['device_category'] ?? 'Điện thoại',
        'district' => $latestLog['district'] ?? 'TP.HCM',
        'search_query' => $latestLog['search_query'] ?? '',
        'features_used' => $latestLog['features_used'] ?? '',
        'page_url' => ltrim((string)($latestLog['page_url'] ?? 'index.php'), '/'),
        'created_at' => $latestLog['created_at'] ?? '',
    ] : null,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
