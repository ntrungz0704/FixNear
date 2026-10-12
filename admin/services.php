<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$db = FixNearDB::getInstance();
$pdo = $db->isUsingMySQL() ? $db->getPdo() : null;

// Lấy danh sách dịch vụ và loại bỏ dịch vụ "Kiểm tra chẩn đoán toàn diện" (ID = 18)
$allRawServices = db()->getServices();
$services = array_values(array_filter($allRawServices, fn($s) => (int)$s['id'] !== 18 && !str_contains(mb_strtolower($s['name'], 'UTF-8'), 'chẩn đoán')));
$shops = db()->getShops(['include_unverified' => true]);

// Tạo map lookup nhanh
$servicesMap = [];
foreach ($services as $srv) {
    $servicesMap[(int)$srv['id']] = $srv;
}
$shopsMap = [];
foreach ($shops as $sh) {
    $shopsMap[(int)$sh['id']] = $sh;
}

// Bản đồ lookup kiểm tra dịch vụ đã có của từng cửa hàng: [shop_id => [service_id => ['id' => ..., 'min_price' => ..., 'max_price' => ...]]]
$shopExistingServicesMap = [];
if ($pdo) {
    $stmtEx = $pdo->query("SELECT id, shop_id, service_id, min_price, max_price FROM shop_services WHERE service_id != 18");
    while ($row = $stmtEx->fetch(PDO::FETCH_ASSOC)) {
        $sid = (int)$row['shop_id'];
        $svid = (int)$row['service_id'];
        $shopExistingServicesMap[$sid][$svid] = [
            'id' => (int)$row['id'],
            'min_price' => (int)$row['min_price'],
            'max_price' => (int)$row['max_price'],
        ];
    }
} else {
    $rawItems = json_decode(@file_get_contents(FIXNEAR_DATA_DIR . 'shop_services.json'), true) ?: [];
    foreach ($rawItems as $it) {
        $sid = (int)($it['shop_id'] ?? 0);
        $svid = (int)($it['service_id'] ?? 0);
        if ($svid === 18) continue;
        $shopExistingServicesMap[$sid][$svid] = [
            'id' => (int)($it['id'] ?? 0),
            'min_price' => (int)($it['min_price'] ?? 0),
            'max_price' => (int)($it['max_price'] ?? 0),
        ];
    }
}

function fnContainsDevice($devices, $target): bool {
    if (is_array($devices)) {
        return in_array($target, $devices, true);
    }
    return str_contains((string)$devices, (string)$target);
}

// Bảng ánh xạ icon biểu tượng và màu nền gradient đại diện cho từng dịch vụ
function fnGetServiceIconMeta($serviceId, $serviceName = ''): array {
    $sid = (int)$serviceId;
    $map = [
        1 => ['emoji' => '🖥️', 'grad' => 'linear-gradient(135deg, #2563eb, #1d4ed8)', 'name' => 'Thay màn hình'],
        2 => ['emoji' => '🪟', 'grad' => 'linear-gradient(135deg, #0284c7, #0369a1)', 'name' => 'Ép mặt kính'],
        3 => ['emoji' => '🔋', 'grad' => 'linear-gradient(135deg, #16a34a, #15803d)', 'name' => 'Thay Pin'],
        4 => ['emoji' => '🔌', 'grad' => 'linear-gradient(135deg, #d97706, #b45309)', 'name' => 'Chân sạc'],
        5 => ['emoji' => '💧', 'grad' => 'linear-gradient(135deg, #0891b2, #0e7490)', 'name' => 'Vô nước'],
        6 => ['emoji' => '⚡', 'grad' => 'linear-gradient(135deg, #dc2626, #b91c1c)', 'name' => 'Mất nguồn'],
        7 => ['emoji' => '📷', 'grad' => 'linear-gradient(135deg, #7c3aed, #6d28d9)', 'name' => 'Camera'],
        8 => ['emoji' => '🔊', 'grad' => 'linear-gradient(135deg, #4f46e5, #4338ca)', 'name' => 'Loa rè'],
        9 => ['emoji' => '🎙️', 'grad' => 'linear-gradient(135deg, #db2777, #be185d)', 'name' => 'Micro'],
        10 => ['emoji' => '⌨️', 'grad' => 'linear-gradient(135deg, #475569, #334155)', 'name' => 'Bàn phím'],
        11 => ['emoji' => '🖱️', 'grad' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)', 'name' => 'Trackpad'],
        12 => ['emoji' => '🛠️', 'grad' => 'linear-gradient(135deg, #ea580c, #c2410c)', 'name' => 'Bản lề / Vỏ'],
        13 => ['emoji' => '💾', 'grad' => 'linear-gradient(135deg, #059669, #047857)', 'name' => 'Ổ cứng SSD'],
        14 => ['emoji' => '🧠', 'grad' => 'linear-gradient(135deg, #2563eb, #1e40af)', 'name' => 'Bộ nhớ RAM'],
        15 => ['emoji' => '🧹', 'grad' => 'linear-gradient(135deg, #0d9488, #0f766e)', 'name' => 'Vệ sinh tra keo'],
        16 => ['emoji' => '💿', 'grad' => 'linear-gradient(135deg, #475569, #1e293b)', 'name' => 'Cài Hệ điều hành'],
        17 => ['emoji' => '🗄️', 'grad' => 'linear-gradient(135deg, #e11d48, #be123c)', 'name' => 'Cứu dữ liệu'],
    ];

    if (isset($map[$sid])) {
        return $map[$sid];
    }

    $lower = mb_strtolower($serviceName, 'UTF-8');
    if (str_contains($lower, 'màn hình')) return ['emoji' => '🖥️', 'grad' => 'linear-gradient(135deg, #2563eb, #1d4ed8)'];
    if (str_contains($lower, 'kính')) return ['emoji' => '🪟', 'grad' => 'linear-gradient(135deg, #0284c7, #0369a1)'];
    if (str_contains($lower, 'pin')) return ['emoji' => '🔋', 'grad' => 'linear-gradient(135deg, #16a34a, #15803d)'];
    if (str_contains($lower, 'sạc')) return ['emoji' => '🔌', 'grad' => 'linear-gradient(135deg, #d97706, #b45309)'];
    if (str_contains($lower, 'nước')) return ['emoji' => '💧', 'grad' => 'linear-gradient(135deg, #0891b2, #0e7490)'];
    if (str_contains($lower, 'nguồn')) return ['emoji' => '⚡', 'grad' => 'linear-gradient(135deg, #dc2626, #b91c1c)'];
    if (str_contains($lower, 'camera')) return ['emoji' => '📷', 'grad' => 'linear-gradient(135deg, #7c3aed, #6d28d9)'];
    if (str_contains($lower, 'loa')) return ['emoji' => '🔊', 'grad' => 'linear-gradient(135deg, #4f46e5, #4338ca)'];
    if (str_contains($lower, 'micro') || str_contains($lower, 'mic')) return ['emoji' => '🎙️', 'grad' => 'linear-gradient(135deg, #db2777, #be185d)'];
    if (str_contains($lower, 'phím')) return ['emoji' => '⌨️', 'grad' => 'linear-gradient(135deg, #475569, #334155)'];
    if (str_contains($lower, 'chuột') || str_contains($lower, 'trackpad')) return ['emoji' => '🖱️', 'grad' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'];
    if (str_contains($lower, 'bản lề') || str_contains($lower, 'vỏ')) return ['emoji' => '🛠️', 'grad' => 'linear-gradient(135deg, #ea580c, #c2410c)'];
    if (str_contains($lower, 'ssd') || str_contains($lower, 'ổ cứng')) return ['emoji' => '💾', 'grad' => 'linear-gradient(135deg, #059669, #047857)'];
    if (str_contains($lower, 'ram')) return ['emoji' => '🧠', 'grad' => 'linear-gradient(135deg, #2563eb, #1e40af)'];
    if (str_contains($lower, 'vệ sinh')) return ['emoji' => '🧹', 'grad' => 'linear-gradient(135deg, #0d9488, #0f766e)'];
    if (str_contains($lower, 'cài') || str_contains($lower, 'phần mềm') || str_contains($lower, 'hệ điều hành')) return ['emoji' => '💿', 'grad' => 'linear-gradient(135deg, #475569, #1e293b)'];
    if (str_contains($lower, 'dữ liệu')) return ['emoji' => '🗄️', 'grad' => 'linear-gradient(135deg, #e11d48, #be123c)'];

    return ['emoji' => '🔧', 'grad' => 'linear-gradient(135deg, #ea580c, #c2410c)'];
}

$priceFormError = '';
$priceFormValues = $_POST;
$activeMode = trim($_GET['mode'] ?? 'edit'); // 'edit' hoặc 'add'

// 1. XỬ LÝ CẬP NHẬT KHOẢNG GIÁ (EDIT IN-PLACE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_price') {
    requireValidCsrf();
    $price_id = (int)($_POST['price_id'] ?? 0);
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $min_price = filter_var($_POST['min_price'] ?? null, FILTER_VALIDATE_INT);
    $max_price = filter_var($_POST['max_price'] ?? null, FILTER_VALIDATE_INT);
    $warranty = trim($_POST['warranty'] ?? '');
    $turnaround = trim($_POST['turnaround'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($price_id <= 0 || !isset($shopsMap[$shop_id]) || !isset($servicesMap[$service_id])) {
        $priceFormError = 'Dữ liệu cửa hàng hoặc dịch vụ không hợp lệ.';
    } elseif ($min_price === false || $max_price === false || $min_price <= 0 || $max_price < $min_price) {
        $priceFormError = 'Giá phải lớn hơn 0 và giá tối đa không được thấp hơn giá tối thiểu.';
    } elseif ($warranty === '' || $turnaround === '') {
        $priceFormError = 'Vui lòng nhập thời gian bảo hành và thời gian sửa chữa.';
    } else {
        db()->saveShopService([
            'id' => $price_id,
            'shop_id' => $shop_id,
            'service_id' => $service_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'warranty_text' => $warranty,
            'turnaround_text' => $turnaround,
            'note' => $note
        ]);
        $queryBack = [
            'shop_id' => (int)($_POST['return_shop_id'] ?? 0),
            'device' => trim($_POST['return_device'] ?? 'all'),
            'service_id' => (int)($_POST['return_service_id'] ?? 0),
            'price_range' => trim($_POST['return_price_range'] ?? 'all'),
            'sort' => trim($_POST['return_sort'] ?? 'newest'),
            'q' => trim($_POST['return_q'] ?? ''),
            'page' => max(1, (int)($_POST['return_page'] ?? 1)),
            'detail' => $price_id,
            'msg' => 'updated'
        ];
        header("Location: services.php?" . http_build_query(array_filter($queryBack, fn($v) => $v !== '' && $v !== 0 && $v !== 'all')));
        exit;
    }
}

// 2. XỬ LÝ THÊM KHOẢNG GIÁ MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_price']) || (isset($_POST['action']) && $_POST['action'] === 'add_price'))) {
    requireValidCsrf();
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $min_price = filter_var($_POST['min_price'] ?? null, FILTER_VALIDATE_INT);
    $max_price = filter_var($_POST['max_price'] ?? null, FILTER_VALIDATE_INT);
    $warranty = trim($_POST['warranty'] ?? '');
    $turnaround = trim($_POST['turnaround'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!isset($shopsMap[$shop_id]) || !isset($servicesMap[$service_id])) {
        $priceFormError = 'Vui lòng chọn cửa hàng và dịch vụ hợp lệ trong hệ thống.';
    } elseif (!isset($_POST['add_price']) && isset($shopExistingServicesMap[$shop_id][$service_id])) {
        $ex = $shopExistingServicesMap[$shop_id][$service_id];
        $priceFormError = 'Cửa hàng này đã có bảng giá cho dịch vụ này rồi (Mã #' . $ex['id'] . ': ' . number_format($ex['min_price']) . 'đ – ' . number_format($ex['max_price']) . 'đ). Vui lòng chọn dịch vụ khác hoặc chuyển sang danh sách bên trái để chỉnh sửa khoảng giá.';
    } elseif ($min_price === false || $max_price === false || $min_price <= 0 || $max_price < $min_price) {
        $priceFormError = 'Giá phải lớn hơn 0 và giá tối đa không được thấp hơn giá tối thiểu.';
    } elseif ($warranty === '' || $turnaround === '') {
        $priceFormError = 'Vui lòng nhập thời gian bảo hành và thời gian sửa chữa.';
    } else {
        $saved = db()->saveShopService([
            'shop_id' => $shop_id,
            'service_id' => $service_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'warranty_text' => $warranty,
            'turnaround_text' => $turnaround,
            'note' => $note
        ]);
        if ($saved !== false) {
            $newId = (int)($saved['id'] ?? 0);
            if (isset($_POST['add_price'])) {
                header("Location: services.php?msg=price_added");
            } else {
                header("Location: services.php?shop_id=" . $shop_id . "&detail=" . $newId . "&msg=added");
            }
            exit;
        }
        $priceFormError = 'Không thể lưu mức giá lúc này. Vui lòng thử lại.';
    }
}

// 3. XỬ LÝ XÓA MỨC GIÁ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_price') {
    requireValidCsrf();
    $price_id = (int)($_POST['price_id'] ?? 0);
    if ($price_id > 0) {
        if (db()->deleteShopService($price_id)) {
            $returnParams = [
                'shop_id' => (int)($_POST['return_shop_id'] ?? 0),
                'device' => trim($_POST['return_device'] ?? 'all'),
                'service_id' => (int)($_POST['return_service_id'] ?? 0),
                'price_range' => trim($_POST['return_price_range'] ?? 'all'),
                'sort' => trim($_POST['return_sort'] ?? 'newest'),
                'q' => trim($_POST['return_q'] ?? ''),
                'page' => max(1, (int)($_POST['return_page'] ?? 1)),
                'msg' => 'deleted'
            ];
            header('Location: services.php?' . http_build_query(array_filter($returnParams, fn($v) => $v !== '' && $v !== 0 && $v !== 'all')));
            exit;
        }
        $priceFormError = 'Không thể xóa mức giá lúc này. Vui lòng thử lại.';
    }
}

// 4. ĐỌC CÁC THAM SỐ BỘ LỌC, TÌM KIẾM & SẮP XẾP
$selectedShopFilter = max(0, (int)($_GET['shop_id'] ?? 0));
$selectedDeviceFilter = trim($_GET['device'] ?? 'all');
$selectedServiceFilter = max(0, (int)($_GET['service_id'] ?? 0));
$selectedPriceRangeFilter = trim($_GET['price_range'] ?? 'all');
$selectedSort = trim($_GET['sort'] ?? 'newest');
$searchQuery = trim($_GET['q'] ?? '');
$pricePage = max(1, (int)($_GET['page'] ?? 1));
$pricePageSize = 15;

// Thống kê toàn hệ thống (Đã loại bỏ hoàn toàn dịch vụ chẩn đoán miễn phí)
$totalAllPrices = 0;
$laptopCount = 0;
$phoneCount = 0;

if ($pdo) {
    $totalAllPrices = (int)$pdo->query("SELECT COUNT(*) FROM shop_services ss WHERE ss.service_id != 18 AND ss.max_price > 0")->fetchColumn();
    $laptopCount = (int)$pdo->query("SELECT COUNT(*) FROM shop_services ss JOIN services s ON s.id = ss.service_id WHERE ss.service_id != 18 AND ss.max_price > 0 AND (s.device_type = 'laptop' OR s.devices LIKE '%laptop%')")->fetchColumn();
    $phoneCount = (int)$pdo->query("SELECT COUNT(*) FROM shop_services ss JOIN services s ON s.id = ss.service_id WHERE ss.service_id != 18 AND ss.max_price > 0 AND (s.device_type = 'phone' OR s.devices LIKE '%phone%')")->fetchColumn();
} else {
    $rawItems = json_decode(@file_get_contents(FIXNEAR_DATA_DIR . 'shop_services.json'), true) ?: [];
    foreach ($rawItems as $it) {
        if ((int)($it['service_id'] ?? 0) === 18 || (int)($it['max_price'] ?? 0) <= 0) continue;
        $totalAllPrices++;
        $srv = $servicesMap[$it['service_id'] ?? 0] ?? [];
        $dt = $srv['device_type'] ?? '';
        $devs = $srv['devices'] ?? '';
        if ($dt === 'laptop' || fnContainsDevice($devs, 'laptop')) $laptopCount++;
        if ($dt === 'phone' || fnContainsDevice($devs, 'phone')) $phoneCount++;
    }
}

// 5. TRUY VẤN DỮ LIỆU ĐÃ LỌC & PHÂN TRANG (LOẠI BỎ KIỂM TRA CHẨN ĐOÁN ID 18)
$filteredShopPrices = [];
$totalFilteredCount = 0;

if ($pdo) {
    $where = ["ss.service_id != 18", "ss.max_price > 0"];
    $params = [];

    // Tìm kiếm từ khóa
    if ($searchQuery !== '') {
        $where[] = "(sh.name LIKE :q1 OR sh.address LIKE :q2 OR s.name LIKE :q3 OR ss.note LIKE :q4 OR sh.district LIKE :q5)";
        $kw = '%' . $searchQuery . '%';
        $params[':q1'] = $kw;
        $params[':q2'] = $kw;
        $params[':q3'] = $kw;
        $params[':q4'] = $kw;
        $params[':q5'] = $kw;
    }

    // Lọc theo cửa hàng
    if ($selectedShopFilter > 0) {
        $where[] = "ss.shop_id = :shop_id";
        $params[':shop_id'] = $selectedShopFilter;
    }

    // Lọc theo dịch vụ
    if ($selectedServiceFilter > 0) {
        $where[] = "ss.service_id = :service_id";
        $params[':service_id'] = $selectedServiceFilter;
    }

    // Lọc theo thiết bị
    if ($selectedDeviceFilter === 'laptop') {
        $where[] = "(s.device_type = 'laptop' OR s.devices LIKE '%laptop%')";
    } elseif ($selectedDeviceFilter === 'phone') {
        $where[] = "(s.device_type = 'phone' OR s.devices LIKE '%phone%')";
    } elseif ($selectedDeviceFilter === 'tablet') {
        $where[] = "s.devices LIKE '%tablet%'";
    } elseif ($selectedDeviceFilter === 'pc') {
        $where[] = "s.devices LIKE '%pc%'";
    }

    // Lọc theo khoảng giá thực tế
    if ($selectedPriceRangeFilter === 'under_500k') {
        $where[] = "ss.max_price <= 500000";
    } elseif ($selectedPriceRangeFilter === '500k_1m') {
        $where[] = "ss.min_price >= 500000 AND ss.max_price <= 1000000";
    } elseif ($selectedPriceRangeFilter === '1m_2m') {
        $where[] = "ss.min_price >= 1000000 AND ss.max_price <= 2000000";
    } elseif ($selectedPriceRangeFilter === 'over_2m') {
        $where[] = "ss.max_price > 2000000";
    }

    // Sắp xếp
    $orderClause = match ($selectedSort) {
        'oldest' => 'ss.id ASC',
        'price_asc' => 'ss.min_price ASC, ss.max_price ASC',
        'price_desc' => 'ss.max_price DESC, ss.min_price DESC',
        'shop_name' => 'sh.name ASC, ss.id DESC',
        'service_name' => 's.name ASC, ss.id DESC',
        default => 'ss.id DESC'
    };

    $whereSql = implode(" AND ", $where);

    // Đếm tổng số bản ghi
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM shop_services ss
        JOIN services s ON s.id = ss.service_id
        JOIN shops sh ON sh.id = ss.shop_id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $totalFilteredCount = (int)$countStmt->fetchColumn();

    $pricePageCount = max(1, (int)ceil($totalFilteredCount / $pricePageSize));
    $pricePage = min($pricePageCount, max(1, $pricePage));
    $offset = ($pricePage - 1) * $pricePageSize;

    // Lấy danh sách trang
    $dataStmt = $pdo->prepare("
        SELECT ss.*, s.name AS service_name, s.device_type, s.devices, s.description AS service_desc,
               sh.name AS shop_name, sh.address AS shop_address, sh.district AS shop_district,
               sh.phone AS shop_phone, sh.map_url AS shop_map_url, sh.is_verified AS shop_verified
        FROM shop_services ss
        JOIN services s ON s.id = ss.service_id
        JOIN shops sh ON sh.id = ss.shop_id
        WHERE {$whereSql}
        ORDER BY {$orderClause}
        LIMIT {$pricePageSize} OFFSET {$offset}
    ");
    $dataStmt->execute($params);
    $filteredShopPrices = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Fallback JSON
    $rawItems = json_decode(@file_get_contents(FIXNEAR_DATA_DIR . 'shop_services.json'), true) ?: [];
    $allEnriched = [];
    foreach ($rawItems as $it) {
        if ((int)($it['service_id'] ?? 0) === 18 || (int)($it['max_price'] ?? 0) <= 0) continue;
        $s = $servicesMap[$it['service_id'] ?? 0] ?? [];
        $sh = $shopsMap[$it['shop_id'] ?? 0] ?? [];
        $it['service_name'] = $s['name'] ?? 'Dịch vụ';
        $it['device_type'] = $s['device_type'] ?? '';
        $it['devices'] = $s['devices'] ?? '';
        $it['service_desc'] = $s['description'] ?? '';
        $it['shop_name'] = $sh['name'] ?? 'Cửa hàng';
        $it['shop_address'] = $sh['address'] ?? '';
        $it['shop_district'] = $sh['district'] ?? '';
        $it['shop_phone'] = $sh['phone'] ?? '';
        $it['shop_map_url'] = $sh['map_url'] ?? '';
        $it['shop_verified'] = $sh['is_verified'] ?? 0;
        $allEnriched[] = $it;
    }

    $filtered = array_filter($allEnriched, function ($item) use ($searchQuery, $selectedShopFilter, $selectedServiceFilter, $selectedDeviceFilter, $selectedPriceRangeFilter) {
        if ($selectedShopFilter > 0 && (int)($item['shop_id'] ?? 0) !== $selectedShopFilter) return false;
        if ($selectedServiceFilter > 0 && (int)($item['service_id'] ?? 0) !== $selectedServiceFilter) return false;
        if ($selectedDeviceFilter === 'laptop' && !($item['device_type'] === 'laptop' || fnContainsDevice($item['devices'] ?? '', 'laptop'))) return false;
        if ($selectedDeviceFilter === 'phone' && !($item['device_type'] === 'phone' || fnContainsDevice($item['devices'] ?? '', 'phone'))) return false;
        if ($selectedDeviceFilter === 'tablet' && !fnContainsDevice($item['devices'] ?? '', 'tablet')) return false;
        if ($selectedDeviceFilter === 'pc' && !fnContainsDevice($item['devices'] ?? '', 'pc')) return false;

        $min = (int)($item['min_price'] ?? 0);
        $max = (int)($item['max_price'] ?? 0);
        if ($selectedPriceRangeFilter === 'under_500k' && $max > 500000) return false;
        if ($selectedPriceRangeFilter === '500k_1m' && !($min >= 500000 && $max <= 1000000)) return false;
        if ($selectedPriceRangeFilter === '1m_2m' && !($min >= 1000000 && $max <= 2000000)) return false;
        if ($selectedPriceRangeFilter === 'over_2m' && $max <= 2000000) return false;

        if ($searchQuery !== '') {
            $kw = mb_strtolower($searchQuery, 'UTF-8');
            $txt = mb_strtolower(($item['shop_name'] ?? '') . ' ' . ($item['shop_address'] ?? '') . ' ' . ($item['service_name'] ?? '') . ' ' . ($item['note'] ?? ''), 'UTF-8');
            if (!str_contains($txt, $kw)) return false;
        }
        return true;
    });

    usort($filtered, function ($a, $b) use ($selectedSort) {
        $minA = (int)($a['min_price'] ?? 0);
        $maxA = (int)($a['max_price'] ?? 0);
        $minB = (int)($b['min_price'] ?? 0);
        $maxB = (int)($b['max_price'] ?? 0);
        return match ($selectedSort) {
            'oldest' => (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0),
            'price_asc' => $minA === $minB ? $maxA <=> $maxB : $minA <=> $minB,
            'price_desc' => $maxB === $maxA ? $minB <=> $minA : $maxB <=> $maxA,
            'shop_name' => strcmp($a['shop_name'] ?? '', $b['shop_name'] ?? ''),
            'service_name' => strcmp($a['service_name'] ?? '', $b['service_name'] ?? ''),
            default => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0)
        };
    });

    $totalFilteredCount = count($filtered);
    $pricePageCount = max(1, (int)ceil($totalFilteredCount / $pricePageSize));
    $pricePage = min($pricePageCount, max(1, $pricePage));
    $filteredShopPrices = array_slice($filtered, ($pricePage - 1) * $pricePageSize, $pricePageSize);
}

// 6. XÁC ĐỊNH MỤC ĐƯỢC CHỌN ĐỂ CHỈNH SỬA Ở CỘT PHẢI
$selectedPriceId = (int)($_GET['detail'] ?? 0);
$selectedPrice = null;

if ($selectedPriceId > 0) {
    foreach ($filteredShopPrices as $sp) {
        if ((int)$sp['id'] === $selectedPriceId) {
            $selectedPrice = $sp;
            break;
        }
    }
    // Nếu item không nằm trong trang hiện tại, query trực tiếp
    if (!$selectedPrice && $pdo) {
        $detailStmt = $pdo->prepare("
            SELECT ss.*, s.name AS service_name, s.device_type, s.devices, s.description AS service_desc,
                   sh.name AS shop_name, sh.address AS shop_address, sh.district AS shop_district,
                   sh.phone AS shop_phone, sh.map_url AS shop_map_url, sh.is_verified AS shop_verified
            FROM shop_services ss
            JOIN services s ON s.id = ss.service_id
            JOIN shops sh ON sh.id = ss.shop_id
            WHERE ss.id = :id LIMIT 1
        ");
        $detailStmt->execute([':id' => $selectedPriceId]);
        $selectedPrice = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// Nếu chưa chọn hoặc item bị ẩn, mặc định chọn ngay item đầu tiên trong danh sách
if (!$selectedPrice && !empty($filteredShopPrices) && $activeMode !== 'add') {
    $selectedPrice = $filteredShopPrices[0];
    $selectedPriceId = (int)$selectedPrice['id'];
}

$pageTitle = "Quản Lý Dịch Vụ & Bảng Giá — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
/* ===== FIXNEAR SERVICES ADMIN - MASTER-DETAIL QUICK EDIT REDESIGN ===== */
.svc-container { margin-bottom: 40px; }
.svc-page-head { margin-bottom: 22px; }
.svc-page-head h1 { font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark); margin: 0 0 4px; }
.svc-page-head p { font-size: 13.5px; color: var(--fn-dark-muted); margin: 0; }

/* 3 STATS CARDS CHUẨN (ĐÃ LOẠI BỎ 0Đ CHẨN ĐOÁN) */
.svc-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
.svc-stat-card {
    background: var(--fn-surface);
    border: 1px solid var(--fn-border);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    color: inherit;
    transition: transform .15s, box-shadow .15s, border-color .15s;
    box-shadow: 0 2px 6px rgba(0,0,0,.02);
}
.svc-stat-card:hover {
    transform: translateY(-2px);
    border-color: #fdba74;
    box-shadow: 0 6px 18px rgba(234,88,12,.09);
}
.svc-stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.svc-stat-icon.orange { background: #fff7ed; color: #ea580c; }
.svc-stat-icon.blue   { background: #eff6ff; color: #2563eb; }
.svc-stat-icon.pink   { background: #fdf2f8; color: #db2777; }
.svc-stat-label { font-size: 12px; color: var(--fn-dark-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
.svc-stat-value { font-family: var(--fn-font-heading); font-size: 26px; font-weight: 900; color: var(--fn-dark); line-height: 1.1; margin-top: 2px; }

/* 2-COLUMN MASTER-DETAIL LAYOUT */
.svc-layout-2col {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(380px, 1fr);
    gap: 22px;
    align-items: flex-start;
}

/* CỘT TRÁI: BỘ LỌC + TÌM KIẾM + DANH SÁCH */
.svc-list-panel {
    background: var(--fn-surface);
    border: 1px solid var(--fn-border);
    border-radius: 14px;
    box-shadow: var(--fn-shadow-sm);
    overflow: hidden;
}

.svc-filter-bar {
    padding: 16px 18px;
    background: #fafaf9;
    border-bottom: 1px solid var(--fn-border);
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.svc-search-row {
    display: flex;
    gap: 8px;
    align-items: center;
}
.svc-search-input-wrap {
    position: relative;
    flex: 1;
    min-width: 0;
}
.svc-search-input-wrap svg {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 15px; height: 15px; color: #94a3b8; pointer-events: none;
}
.svc-search-input {
    width: 100%; height: 38px;
    padding: 0 14px 0 36px;
    border: 1px solid var(--fn-border);
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
}
.svc-search-input:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234,88,12,.12);
}
.svc-search-btn {
    height: 38px;
    padding: 0 16px;
    background: #ea580c;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .12s;
}
.svc-search-btn:hover { background: #c2410c; }

/* Hàng 2: Cửa hàng + Dịch vụ (2 cột lớn) */
.svc-filter-grid-top {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

/* Hàng 3: Thiết bị + Khoảng giá + Sắp xếp (3 cột cân đối) */
.svc-filter-grid-sub {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.svc-select {
    width: 100%;
    min-width: 0;
    height: 36px;
    padding: 0 28px 0 10px;
    border: 1px solid var(--fn-border);
    border-radius: 8px;
    font-size: 12.5px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 10px center / 13px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    color: var(--fn-dark);
    font-weight: 600;
    cursor: pointer;
    outline: none;
    box-sizing: border-box;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    transition: border-color .15s;
}
.svc-select:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 2px rgba(234,88,12,.1);
}

/* Footer trạng thái kết quả */
.svc-filter-footer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 4px;
    flex-wrap: wrap;
    gap: 8px;
    border-top: 1px dashed #e2e8f0;
    margin-top: 2px;
}
.svc-filter-count-badge {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.svc-filter-count-badge strong {
    color: var(--fn-dark);
    font-weight: 800;
}
.svc-btn-reset-clean {
    font-size: 11.5px;
    color: #c2410c;
    text-decoration: none;
    font-weight: 700;
    background: #fff7ed;
    padding: 3px 10px;
    border-radius: 6px;
    border: 1px solid #fed7aa;
    transition: all .12s;
}
.svc-btn-reset-clean:hover {
    background: #ffedd5;
    border-color: #fb923c;
}

/* DANH SÁCH MỨC GIÁ */
.svc-price-list {
    max-height: 840px;
    overflow-y: auto;
}
.svc-price-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 15px 18px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background .12s, border-left .12s;
    text-decoration: none;
    color: inherit;
    position: relative;
}
.svc-price-card:hover { background: #fafaf9; }
.svc-price-card.active {
    background: #fff8f5;
    border-left: 4px solid #ea580c;
}
.svc-card-avatar {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 21px; flex-shrink: 0;
    user-select: none;
    box-shadow: 0 3px 8px rgba(0, 0, 0, .09);
    transition: transform .12s, box-shadow .12s;
}
.svc-price-card:hover .svc-card-avatar {
    transform: scale(1.06);
    box-shadow: 0 4px 12px rgba(0, 0, 0, .14);
}

.svc-card-body { flex: 1; min-width: 0; }
.svc-card-shop-row {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 3px;
}
.svc-card-shop-name {
    font-size: 13.5px; font-weight: 800; color: var(--fn-dark);
}
.svc-shop-direct-link {
    display: inline-flex; align-items: center;
    padding: 1px 6px; border-radius: 4px;
    background: #f1f5f9; color: #0284c7;
    font-size: 11px; font-weight: 700;
    text-decoration: none; border: 1px solid #e2e8f0;
    transition: background .12s;
}
.svc-shop-direct-link:hover { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }

.svc-card-service-name {
    font-size: 13px; font-weight: 700; color: #334155;
    display: flex; align-items: center; gap: 6px;
}
.svc-device-tag {
    font-size: 10px; font-weight: 800; padding: 1px 6px;
    border-radius: 4px; text-transform: uppercase;
}
.svc-device-tag.laptop { background: #eff6ff; color: #1d4ed8; }
.svc-device-tag.phone  { background: #fdf2f8; color: #be185d; }
.svc-device-tag.all    { background: #fff7ed; color: #c2410c; }

.svc-card-sub-info {
    font-size: 11.5px; color: var(--fn-dark-muted);
    margin-top: 4px; display: flex; gap: 12px;
}
.svc-card-right {
    text-align: right; flex-shrink: 0;
    display: flex; flex-direction: column; align-items: flex-end; gap: 4px;
}
.svc-badge-price {
    display: inline-flex; align-items: center;
    font-size: 12px; font-weight: 800; color: #c2410c;
    background: #fff7ed; border: 1px solid #fed7aa;
    padding: 3px 8px; border-radius: 6px; white-space: nowrap;
}

/* PHÂN TRANG */
.svc-pagination {
    padding: 14px 18px; background: #fafaf9;
    border-top: 1px solid var(--fn-border);
    display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.svc-page-nav-btns { display: flex; gap: 4px; }
.svc-page-btn {
    min-width: 32px; height: 32px; padding: 0 10px;
    border: 1px solid var(--fn-border); border-radius: 7px;
    background: #fff; color: var(--fn-dark);
    font-size: 12.5px; font-weight: 700; text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
}
.svc-page-btn:hover { border-color: #ea580c; color: #ea580c; background: #fff7ed; }
.svc-page-btn.active { background: #ea580c; border-color: #ea580c; color: #fff; }

/* CỘT PHẢI: MASTER-DETAIL QUICK EDIT PANEL */
.svc-right-edit-card {
    background: var(--fn-surface);
    border: 1px solid var(--fn-border);
    border-radius: 14px;
    box-shadow: var(--fn-shadow-sm);
    overflow: hidden;
    position: sticky;
    top: 76px;
}
.svc-panel-top-tabs {
    display: flex;
    background: #f1f5f9;
    border-bottom: 1px solid var(--fn-border);
}
.svc-tab-btn {
    flex: 1; padding: 13px 16px;
    font-size: 13.5px; font-weight: 800;
    text-align: center; text-decoration: none;
    color: #64748b; background: transparent;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: all .15s; border-bottom: 3px solid transparent;
}
.svc-tab-btn.active {
    color: #ea580c; background: #fff;
    border-bottom-color: #ea580c;
}

.svc-edit-panel-body { padding: 20px; }

/* HEADER CỬA HÀNG VÀ DỊCH VỤ TRÊN FORM */
.svc-shop-target-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 18px;
}
.svc-shop-target-header {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;
    margin-bottom: 6px;
}
.svc-shop-target-name {
    font-size: 15px; font-weight: 800; color: var(--fn-dark); line-height: 1.3;
}
.svc-shop-target-actions {
    display: flex; gap: 6px; flex-shrink: 0;
}
.svc-shop-target-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 8px; border-radius: 6px;
    font-size: 11.5px; font-weight: 700; text-decoration: none;
    border: 1px solid #cbd5e1; background: #fff; color: #334155;
    transition: all .12s;
}
.svc-shop-target-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
.svc-shop-target-btn.primary {
    background: #0284c7; border-color: #0284c7; color: #fff;
}
.svc-shop-target-btn.primary:hover { background: #0369a1; }

.svc-service-badge-banner {
    display: flex; align-items: center; justify-content: space-between;
    background: #fff7ed; border: 1px solid #fed7aa;
    border-radius: 8px; padding: 10px 14px; margin-bottom: 18px;
}
.svc-service-badge-title {
    font-size: 14px; font-weight: 800; color: #9a3412;
}

/* REALTIME PRICE PREVIEW BOX KHI GÕ */
.svc-calc-preview-box {
    background: #fafaf9;
    border: 1px dashed #cbd5e1;
    border-radius: 9px;
    padding: 12px 14px;
    margin-bottom: 16px;
    font-size: 12.5px;
    color: #475569;
}
.svc-calc-preview-val {
    font-size: 16px; font-weight: 900; color: #ea580c;
}

/* FORM FIELDS */
.svc-form-row-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;
}
.svc-form-group { margin-bottom: 14px; }
.svc-label-text {
    display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 5px;
}
.svc-input-text {
    width: 100%; height: 38px; padding: 0 12px;
    border: 1px solid var(--fn-border); border-radius: 8px;
    font-size: 13.5px; background: #fff; color: var(--fn-dark);
    box-sizing: border-box; outline: none; transition: border-color .15s;
}
.svc-input-text:focus { border-color: #ea580c; }

/* ACTION BUTTONS */
.svc-form-actions-row {
    display: flex; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9;
}
.svc-btn-save-main {
    flex: 1; height: 42px;
    background: #ea580c; color: #fff; border: none;
    border-radius: 8px; font-size: 14px; font-weight: 800;
    cursor: pointer; transition: background .12s;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
}
.svc-btn-save-main:hover { background: #c2410c; }
.svc-btn-delete-side {
    height: 42px; padding: 0 14px;
    background: #fff; border: 1px solid #fca5a5;
    border-radius: 8px; color: #dc2626; font-size: 12.5px; font-weight: 700;
    cursor: pointer; transition: all .12s;
}
.svc-btn-delete-side:hover { background: #fee2e2; border-color: #ef4444; }

/* TOASTS */
.svc-alert-toast {
    padding: 12px 16px; border-radius: 9px;
    font-size: 13px; font-weight: 700; margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}
.svc-alert-toast.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.svc-alert-toast.danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* Làm mờ các dịch vụ đã có khi thêm mới */
.svc-input-text option:disabled,
option.svc-opt-dimmed {
    color: #94a3b8 !important;
    background-color: #f8fafc !important;
    font-style: italic !important;
}
.svc-add-hint-box {
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.45;
}
.svc-add-hint-box.info {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.svc-add-hint-box.warning {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #c2410c;
}

@media (max-width: 1100px) {
    .svc-layout-2col { grid-template-columns: 1fr; }
    .svc-right-edit-card { position: static; }
    .svc-stats-row { grid-template-columns: 1fr; }
    .svc-filter-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content svc-container">

        <!-- HEADER TRANG -->
        <div class="svc-page-head">
            <h1>Dịch vụ &amp; Bảng giá sửa chữa</h1>
            <p>Quản lý và điều chỉnh khoảng giá linh kiện/công sửa của 68 chi nhánh cửa hàng trên hệ thống FixNear.</p>
        </div>

        <!-- THÔNG BÁO FLASH MESSAGE -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="svc-alert-toast success">✓ Đã cập nhật khoảng giá dịch vụ thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="svc-alert-toast success">✓ Đã gán khoảng giá dịch vụ mới cho cửa hàng thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="svc-alert-toast danger">✓ Đã xóa mức giá dịch vụ thành công!</div>
        <?php endif; ?>

        <!-- 3 STATS CARDS SỐ LIỆU CHUẨN XÁC -->
        <div class="svc-stats-row">
            <a href="services.php" class="svc-stat-card">
                <div class="svc-stat-icon orange">🏷️</div>
                <div>
                    <div class="svc-stat-label">Tổng mức giá sửa chữa</div>
                    <div class="svc-stat-value"><?= number_format($totalAllPrices) ?></div>
                </div>
            </a>
            <a href="services.php?device=laptop" class="svc-stat-card">
                <div class="svc-stat-icon blue">💻</div>
                <div>
                    <div class="svc-stat-label">Dịch vụ Laptop</div>
                    <div class="svc-stat-value"><?= number_format($laptopCount) ?></div>
                </div>
            </a>
            <a href="services.php?device=phone" class="svc-stat-card">
                <div class="svc-stat-icon pink">📱</div>
                <div>
                    <div class="svc-stat-label">Dịch vụ Điện thoại</div>
                    <div class="svc-stat-value"><?= number_format($phoneCount) ?></div>
                </div>
            </a>
        </div>

        <!-- LAYOUT MASTER-DETAIL QUICK EDIT -->
        <div class="svc-layout-2col">

            <!-- CỘT TRÁI: TÌM KIẾM, PHÂN LOẠI & DANH SÁCH -->
            <div class="svc-list-panel">

                <!-- THANH TÌM KIẾM & BỘ LỌC TOÀN DIỆN -->
                <form method="GET" action="services.php" class="svc-filter-bar">
                    <!-- Hàng 1: Ô tìm kiếm -->
                    <div class="svc-search-row">
                        <div class="svc-search-input-wrap">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/>
                            </svg>
                            <input type="text" name="q" class="svc-search-input"
                                   placeholder="Tìm tên tiệm, loại dịch vụ, quận/huyện, ghi chú linh kiện..."
                                   value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
                        </div>
                        <button type="submit" class="svc-search-btn">
                            Tìm kiếm
                        </button>
                    </div>

                    <!-- Hàng 2: Cửa hàng + Dịch vụ (2 cột rộng rãi) -->
                    <div class="svc-filter-grid-top">
                        <!-- Cửa hàng -->
                        <select name="shop_id" class="svc-select" onchange="this.form.submit()" title="Chọn cửa hàng">
                            <option value="0">🏪 Tất cả cửa hàng (<?= count($shops) ?>)</option>
                            <?php foreach ($shops as $sh): ?>
                                <option value="<?= $sh['id'] ?>" <?= $selectedShopFilter === (int)$sh['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sh['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Dịch vụ sửa chữa (Đã có icon đại diện) -->
                        <select name="service_id" class="svc-select" onchange="this.form.submit()" title="Chọn dịch vụ">
                            <option value="0">🛠️ Tất cả dịch vụ (<?= count($services) ?>)</option>
                            <?php foreach ($services as $srv):
                                $sMeta = fnGetServiceIconMeta($srv['id'], $srv['name']);
                            ?>
                                <option value="<?= $srv['id'] ?>" <?= $selectedServiceFilter === (int)$srv['id'] ? 'selected' : '' ?>>
                                    <?= $sMeta['emoji'] ?> <?= htmlspecialchars($srv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hàng 3: Thiết bị + Khoảng giá + Sắp xếp (3 cột cân đối) -->
                    <div class="svc-filter-grid-sub">
                        <!-- Thiết bị -->
                        <select name="device" class="svc-select" onchange="this.form.submit()" title="Chọn loại thiết bị">
                            <option value="all" <?= $selectedDeviceFilter === 'all' ? 'selected' : '' ?>>💻📱 Tất cả thiết bị</option>
                            <option value="laptop" <?= $selectedDeviceFilter === 'laptop' ? 'selected' : '' ?>>💻 Laptop</option>
                            <option value="phone" <?= $selectedDeviceFilter === 'phone' ? 'selected' : '' ?>>📱 Điện thoại</option>
                            <option value="tablet" <?= $selectedDeviceFilter === 'tablet' ? 'selected' : '' ?>>📟 Tablet</option>
                            <option value="pc" <?= $selectedDeviceFilter === 'pc' ? 'selected' : '' ?>>🖥️ PC Desktop</option>
                        </select>

                        <!-- Khoảng giá -->
                        <select name="price_range" class="svc-select" onchange="this.form.submit()" title="Chọn khoảng giá">
                            <option value="all" <?= $selectedPriceRangeFilter === 'all' ? 'selected' : '' ?>>💵 Mọi mức giá</option>
                            <option value="under_500k" <?= $selectedPriceRangeFilter === 'under_500k' ? 'selected' : '' ?>>&le; 500.000đ</option>
                            <option value="500k_1m" <?= $selectedPriceRangeFilter === '500k_1m' ? 'selected' : '' ?>>500k – 1 triệu</option>
                            <option value="1m_2m" <?= $selectedPriceRangeFilter === '1m_2m' ? 'selected' : '' ?>>1tr – 2 triệu</option>
                            <option value="over_2m" <?= $selectedPriceRangeFilter === 'over_2m' ? 'selected' : '' ?>>&gt; 2 triệu</option>
                        </select>

                        <!-- Sắp xếp -->
                        <select name="sort" class="svc-select" onchange="this.form.submit()" title="Sắp xếp kết quả">
                            <option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>⏱️ Mới nhất</option>
                            <option value="oldest" <?= $selectedSort === 'oldest' ? 'selected' : '' ?>>⏱️ Cũ nhất</option>
                            <option value="price_asc" <?= $selectedSort === 'price_asc' ? 'selected' : '' ?>>💵 Giá: Thấp → Cao</option>
                            <option value="price_desc" <?= $selectedSort === 'price_desc' ? 'selected' : '' ?>>💵 Giá: Cao → Thấp</option>
                            <option value="shop_name" <?= $selectedSort === 'shop_name' ? 'selected' : '' ?>>🏪 Tiệm: A → Z</option>
                            <option value="service_name" <?= $selectedSort === 'service_name' ? 'selected' : '' ?>>🛠️ Dịch vụ: A → Z</option>
                        </select>
                    </div>

                    <!-- Hàng 4: Footer trạng thái kết quả & Nút xóa lọc -->
                    <div class="svc-filter-footer-row">
                        <div class="svc-filter-count-badge">
                            Tìm thấy <strong><?= number_format($totalFilteredCount) ?></strong> mức giá sửa chữa
                            <?php if ($selectedShopFilter > 0 && isset($shopsMap[$selectedShopFilter])): ?>
                                &nbsp;tại <strong style="color:var(--fn-primary);"><?= htmlspecialchars($shopsMap[$selectedShopFilter]['name']) ?></strong>
                            <?php endif; ?>
                        </div>
                        <?php if ($searchQuery !== '' || $selectedShopFilter > 0 || $selectedDeviceFilter !== 'all' || $selectedServiceFilter > 0 || $selectedPriceRangeFilter !== 'all' || $selectedSort !== 'newest'): ?>
                            <a href="services.php" class="svc-btn-reset-clean">✕ Bỏ lọc</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- DANH SÁCH MỨC GIÁ (BẤM VÀO SẼ MỞ FORM SỬA NGAY) -->
                <div class="svc-price-list">
                    <?php if (empty($filteredShopPrices)): ?>
                        <div style="padding: 60px 20px; text-align: center; color: var(--fn-dark-muted);">
                            <div style="font-size: 42px; margin-bottom: 10px;">🔍</div>
                            <div style="font-size: 15px; font-weight: 800; color: var(--fn-dark);">Không tìm thấy bảng giá nào</div>
                            <div style="font-size: 13px; margin-top: 4px;">Vui lòng thử từ khóa khác hoặc bấm nút "Bỏ lọc" bên trên.</div>
                            <div style="margin-top: 14px;">
                                <a href="services.php" class="fn-btn fn-btn-sm fn-btn-primary">Xem tất cả bảng giá</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filteredShopPrices as $sp):
                            $spId = (int)$sp['id'];
                            $min = (int)($sp['min_price'] ?? 0);
                            $max = (int)($sp['max_price'] ?? 0);
                            $dt = $sp['device_type'] ?? '';
                            $isActive = ($activeMode === 'edit' && $spId === $selectedPriceId);

                            $iconMeta = fnGetServiceIconMeta($sp['service_id'] ?? 0, $sp['service_name'] ?? '');

                            $itemUrlParams = array_filter([
                                'shop_id' => $selectedShopFilter,
                                'device' => $selectedDeviceFilter,
                                'service_id' => $selectedServiceFilter,
                                'price_range' => $selectedPriceRangeFilter,
                                'sort' => $selectedSort,
                                'q' => $searchQuery,
                                'page' => $pricePage,
                                'detail' => $spId,
                                'mode' => 'edit'
                            ], fn($v) => $v !== '' && $v !== 0 && $v !== 'all');
                        ?>
                            <div class="svc-price-card<?= $isActive ? ' active' : '' ?>"
                                 onclick="window.location.href='services.php?<?= htmlspecialchars(http_build_query($itemUrlParams)) ?>'">
                                <!-- Avatar icon đại diện dịch vụ -->
                                <div class="svc-card-avatar" style="background: <?= $iconMeta['grad'] ?>;" title="<?= htmlspecialchars($iconMeta['name']) ?>">
                                    <?= $iconMeta['emoji'] ?>
                                </div>

                                <!-- Cột giữa: Tên tiệm, dịch vụ & link truy cập -->
                                <div class="svc-card-body">
                                    <div class="svc-card-shop-row">
                                        <span class="svc-card-shop-name"><?= htmlspecialchars($sp['shop_name']) ?></span>

                                        <!-- Mở trực tiếp tiệm ngoài web khách hàng -->
                                        <a href="../shop_detail.php?id=<?= (int)$sp['shop_id'] ?>"
                                           target="_blank"
                                           class="svc-shop-direct-link"
                                           onclick="event.stopPropagation();"
                                           title="Mở trang chi tiết cửa hàng này ngoài web">
                                            Xem tiệm ↗
                                        </a>

                                        <!-- Lọc nhanh riêng tiệm này -->
                                        <?php if ($selectedShopFilter !== (int)$sp['shop_id']): ?>
                                            <a href="services.php?shop_id=<?= (int)$sp['shop_id'] ?>"
                                               class="svc-shop-direct-link"
                                               onclick="event.stopPropagation();"
                                               title="Lọc tất cả bảng giá của riêng tiệm này"
                                               style="color:#ea580c; background:#fff7ed; border-color:#fed7aa;">
                                                Lọc tiệm
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="svc-card-service-name">
                                        <span><?= htmlspecialchars($sp['service_name']) ?></span>
                                        <span class="svc-device-tag <?= $avatarClass ?>">
                                            <?= $dt === 'laptop' ? 'Laptop' : ($dt === 'phone' ? 'Điện thoại' : 'Thiết bị') ?>
                                        </span>
                                    </div>

                                    <div class="svc-card-sub-info">
                                        <span>🛡️ <?= htmlspecialchars($sp['warranty_text'] ?? '—') ?></span>
                                        <span>⏱️ <?= htmlspecialchars($sp['turnaround_text'] ?? '—') ?></span>
                                        <span>📍 <?= htmlspecialchars($sp['shop_district'] ?? 'TP.HCM') ?></span>
                                    </div>
                                </div>

                                <!-- Cột phải: Khoảng giá niêm yết & ID -->
                                <div class="svc-card-right">
                                    <span class="svc-badge-price">
                                        <?= formatPrice($min) ?> – <?= formatPrice($max) ?>
                                    </span>
                                    <span class="svc-card-id-tag">#<?= $spId ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- PHÂN TRANG -->
                <?php if ($pricePageCount > 1): ?>
                    <div class="svc-pagination">
                        <div style="font-size:12px; color:var(--fn-dark-muted); font-weight:700;">
                            Trang <?= $pricePage ?> / <?= $pricePageCount ?> &nbsp;·&nbsp;
                            <?= count($filteredShopPrices) ?> / <?= number_format($totalFilteredCount) ?> mục
                        </div>
                        <div class="svc-page-nav-btns">
                            <?php
                            $baseNavParams = array_filter([
                                'shop_id' => $selectedShopFilter,
                                'device' => $selectedDeviceFilter,
                                'service_id' => $selectedServiceFilter,
                                'price_range' => $selectedPriceRangeFilter,
                                'sort' => $selectedSort,
                                'q' => $searchQuery,
                                'detail' => $selectedPriceId,
                                'mode' => $activeMode
                            ], fn($v) => $v !== '' && $v !== 0 && $v !== 'all');

                            if ($pricePage > 1): ?>
                                <a class="svc-page-btn" href="services.php?<?= htmlspecialchars(http_build_query(array_merge($baseNavParams, ['page' => $pricePage - 1]))) ?>">
                                    ← Trước
                                </a>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $pricePage - 2);
                            $endPage = min($pricePageCount, $pricePage + 2);
                            for ($p = $startPage; $p <= $endPage; $p++):
                            ?>
                                <a class="svc-page-btn<?= $p === $pricePage ? ' active' : '' ?>"
                                   href="services.php?<?= htmlspecialchars(http_build_query(array_merge($baseNavParams, ['page' => $p]))) ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pricePage < $pricePageCount): ?>
                                <a class="svc-page-btn" href="services.php?<?= htmlspecialchars(http_build_query(array_merge($baseNavParams, ['page' => $pricePage + 1]))) ?>">
                                    Sau →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- CỘT PHẢI: FORM CHỈNH SỬA KHOẢNG GIÁ NGAY LẬP TỨC (QUICK EDIT) -->
            <div class="svc-right-panel">
                <div class="svc-right-edit-card">

                    <!-- TAB CHUYỂN ĐỔI CHẾ ĐỘ SỬA HOẶC THÊM MỚI -->
                    <div class="svc-panel-top-tabs">
                        <a href="services.php?<?= htmlspecialchars(http_build_query(array_filter([
                            'shop_id' => $selectedShopFilter,
                            'device' => $selectedDeviceFilter,
                            'service_id' => $selectedServiceFilter,
                            'price_range' => $selectedPriceRangeFilter,
                            'sort' => $selectedSort,
                            'q' => $searchQuery,
                            'page' => $pricePage,
                            'detail' => $selectedPriceId,
                            'mode' => 'edit'
                        ], fn($v) => $v !== '' && $v !== 0 && $v !== 'all'))) ?>"
                           class="svc-tab-btn<?= $activeMode !== 'add' ? ' active' : '' ?>">
                            ✏️ Chỉnh sửa bảng giá <?= $selectedPrice ? ('#' . $selectedPrice['id']) : '' ?>
                        </a>
                        <a href="services.php?<?= htmlspecialchars(http_build_query(array_filter([
                            'shop_id' => $selectedShopFilter,
                            'device' => $selectedDeviceFilter,
                            'service_id' => $selectedServiceFilter,
                            'price_range' => $selectedPriceRangeFilter,
                            'sort' => $selectedSort,
                            'q' => $searchQuery,
                            'page' => $pricePage,
                            'mode' => 'add'
                        ], fn($v) => $v !== '' && $v !== 0 && $v !== 'all'))) ?>"
                           class="svc-tab-btn<?= $activeMode === 'add' ? ' active' : '' ?>">
                            ➕ Thêm dịch vụ vào tiệm
                        </a>
                    </div>

                    <div class="svc-edit-panel-body">
                        <?php if ($priceFormError !== ''): ?>
                            <div class="svc-alert-toast danger">⚠️ <?= htmlspecialchars($priceFormError) ?></div>
                        <?php endif; ?>

                        <!-- CHẾ ĐỘ 1: CHỈNH SỬA KHOẢNG GIÁ (MẶC ĐỊNH KHI BẤM VÀO BẤT KỲ MỤC NÀO) -->
                        <?php if ($activeMode !== 'add' && $selectedPrice):
                            $curMin = (int)($selectedPrice['min_price'] ?? 0);
                            $curMax = (int)($selectedPrice['max_price'] ?? 0);
                            $curAvg = (int)round(($curMin + $curMax) / 2);
                            $selShopId = (int)$selectedPrice['shop_id'];
                        ?>
                            <!-- Khối thông tin cửa hàng có liên kết trực tiếp -->
                            <div class="svc-shop-target-box">
                                <div class="svc-shop-target-header">
                                    <div>
                                        <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#64748b; margin-bottom:2px;">
                                            🏪 Cửa hàng áp dụng
                                        </div>
                                        <div class="svc-shop-target-name">
                                            <?= htmlspecialchars($selectedPrice['shop_name']) ?>
                                        </div>
                                    </div>
                                    <div class="svc-shop-target-actions">
                                        <a href="../shop_detail.php?id=<?= $selShopId ?>" target="_blank" class="svc-shop-target-btn primary" title="Xem trên web">
                                            Xem tiệm ↗
                                        </a>
                                        <a href="shop_edit.php?id=<?= $selShopId ?>" class="svc-shop-target-btn" title="Chỉnh sửa thông tin cửa hàng">
                                            ⚙️ Sửa tiệm
                                        </a>
                                    </div>
                                </div>
                                <div style="font-size:12px; color:#475569;">
                                    📍 <?= htmlspecialchars($selectedPrice['shop_address']) ?>
                                    <?php if (!empty($selectedPrice['shop_phone'])): ?>
                                        &nbsp;·&nbsp; 📞 <a href="tel:<?= htmlspecialchars($selectedPrice['shop_phone']) ?>" style="color:#0284c7; font-weight:700;"><?= htmlspecialchars($selectedPrice['shop_phone']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php
                            $selIconMeta = fnGetServiceIconMeta($selectedPrice['service_id'] ?? 0, $selectedPrice['service_name'] ?? '');
                            ?>
                            <!-- Banner dịch vụ đang chỉnh sửa (Có icon đại diện) -->
                            <div class="svc-service-badge-banner">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:38px; height:38px; border-radius:10px; background:<?= $selIconMeta['grad'] ?>; display:flex; align-items:center; justify-content:center; font-size:19px; box-shadow:0 2px 6px rgba(0,0,0,.1); flex-shrink:0;">
                                        <?= $selIconMeta['emoji'] ?>
                                    </div>
                                    <div>
                                        <div style="font-size:10.5px; font-weight:800; text-transform:uppercase; color:#c2410c;">
                                            🛠️ Dịch vụ sửa chữa
                                        </div>
                                        <div class="svc-service-badge-title">
                                            <?= htmlspecialchars($selectedPrice['service_name']) ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="svc-device-tag <?= $selectedPrice['device_type'] === 'laptop' ? 'laptop' : 'phone' ?>" style="font-size:11px; padding:3px 8px;">
                                    <?= $selectedPrice['device_type'] === 'laptop' ? '💻 Laptop' : '📱 Điện thoại' ?>
                                </span>
                            </div>

                            <!-- FORM CẬP NHẬT KHOẢNG GIÁ TRỰC TIẾP -->
                            <form action="services.php" method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_price">
                                <input type="hidden" name="price_id" value="<?= $selectedPrice['id'] ?>">
                                <input type="hidden" name="shop_id" value="<?= $selectedPrice['shop_id'] ?>">
                                <input type="hidden" name="service_id" value="<?= $selectedPrice['service_id'] ?>">

                                <!-- Giữ nguyên ngữ cảnh bộ lọc khi redirect -->
                                <input type="hidden" name="return_shop_id" value="<?= $selectedShopFilter ?>">
                                <input type="hidden" name="return_device" value="<?= htmlspecialchars($selectedDeviceFilter) ?>">
                                <input type="hidden" name="return_service_id" value="<?= $selectedServiceFilter ?>">
                                <input type="hidden" name="return_price_range" value="<?= htmlspecialchars($selectedPriceRangeFilter) ?>">
                                <input type="hidden" name="return_sort" value="<?= htmlspecialchars($selectedSort) ?>">
                                <input type="hidden" name="return_q" value="<?= htmlspecialchars($searchQuery) ?>">
                                <input type="hidden" name="return_page" value="<?= $pricePage ?>">

                                <!-- 2 Ô NHẬP KHOẢNG GIÁ -->
                                <div class="svc-form-row-2">
                                    <div>
                                        <label class="svc-label-text">Giá tối thiểu (VNĐ) *</label>
                                        <input type="number" id="edit-min-price" name="min_price" class="svc-input-text"
                                               value="<?= htmlspecialchars((string)$curMin) ?>"
                                               min="1000" step="1000" inputmode="numeric" required oninput="calcRealtimePrice()">
                                    </div>
                                    <div>
                                        <label class="svc-label-text">Giá tối đa (VNĐ) *</label>
                                        <input type="number" id="edit-max-price" name="max_price" class="svc-input-text"
                                               value="<?= htmlspecialchars((string)$curMax) ?>"
                                               min="1000" step="1000" inputmode="numeric" required oninput="calcRealtimePrice()">
                                    </div>
                                </div>

                                <!-- TÍNH TOÁN REALTIME KHI ADMIN GÕ SỐ -->
                                <div class="svc-calc-preview-box">
                                    Mức giữa ước tính: <span id="preview-avg-val" class="svc-calc-preview-val"><?= formatPrice($curAvg) ?></span>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                        Khoảng niêm yết: <span id="preview-range-val" style="font-weight:700; color:#334155;"><?= formatPrice($curMin) ?> – <?= formatPrice($curMax) ?></span>
                                    </div>
                                </div>

                                <!-- BẢO HÀNH & THỜI GIAN SỬA -->
                                <div class="svc-form-row-2">
                                    <div>
                                        <label class="svc-label-text">Thời gian bảo hành *</label>
                                        <input type="text" name="warranty" class="svc-input-text"
                                               value="<?= htmlspecialchars($selectedPrice['warranty_text'] ?? '6 - 12 tháng') ?>" required>
                                    </div>
                                    <div>
                                        <label class="svc-label-text">Thời gian xử lý *</label>
                                        <input type="text" name="turnaround" class="svc-input-text"
                                               value="<?= htmlspecialchars($selectedPrice['turnaround_text'] ?? '30 - 60 phút') ?>" required>
                                    </div>
                                </div>

                                <!-- GHI CHÚ LINH KIỆN -->
                                <div class="svc-form-group">
                                    <label class="svc-label-text">Ghi chú điều kiện &amp; linh kiện</label>
                                    <input type="text" name="note" class="svc-input-text"
                                           value="<?= htmlspecialchars($selectedPrice['note'] ?? '') ?>"
                                           placeholder="Tên linh kiện loại 1, bảo hành đổi mới, bao công tháo ráp...">
                                </div>

                                <!-- CỤM NÚT LƯU CẬP NHẬT & XÓA -->
                                <div class="svc-form-actions-row">
                                    <button type="submit" class="svc-btn-save-main">
                                        💾 Lưu cập nhật khoảng giá
                                    </button>
                                </div>
                            </form>

                            <!-- Form xóa độc lập -->
                            <form method="POST" action="services.php" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mức giá này khỏi hệ thống?')" style="margin-top:10px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_price">
                                <input type="hidden" name="price_id" value="<?= $selectedPrice['id'] ?>">
                                <input type="hidden" name="return_shop_id" value="<?= $selectedShopFilter ?>">
                                <input type="hidden" name="return_device" value="<?= htmlspecialchars($selectedDeviceFilter) ?>">
                                <input type="hidden" name="return_service_id" value="<?= $selectedServiceFilter ?>">
                                <input type="hidden" name="return_price_range" value="<?= htmlspecialchars($selectedPriceRangeFilter) ?>">
                                <input type="hidden" name="return_sort" value="<?= htmlspecialchars($selectedSort) ?>">
                                <input type="hidden" name="return_q" value="<?= htmlspecialchars($searchQuery) ?>">
                                <input type="hidden" name="return_page" value="<?= $pricePage ?>">
                                <button type="submit" class="svc-btn-delete-side" style="width:100%;">
                                    🗑️ Xóa mức giá này khỏi cửa hàng
                                </button>
                            </form>

                        <!-- CHẾ ĐỘ 2: THÊM DỊCH VỤ VÀO TIỆM -->
                        <?php else: ?>
                            <form action="services.php" method="POST" id="form-add-price">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add_price">

                                <div class="svc-form-group">
                                    <label class="svc-label-text">Chọn cửa hàng áp dụng *</label>
                                    <select name="shop_id" id="add-shop-id" class="svc-input-text" required onchange="handleShopChangeAdd(this.value)">
                                        <option value="">-- Chọn cửa hàng --</option>
                                        <?php foreach ($shops as $s): ?>
                                            <option value="<?= (int)$s['id'] ?>"
                                                <?= ((int)($priceFormValues['shop_id'] ?? $selectedShopFilter) === (int)$s['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="svc-form-group">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                        <label class="svc-label-text" style="margin-bottom:0;">Chọn dịch vụ sửa chữa *</label>
                                        <span id="add-service-count-hint" style="font-size:11.5px; font-weight:700; color:#64748b;"></span>
                                    </div>
                                    <select name="service_id" id="add-service-id" class="svc-input-text" required>
                                        <option value="">-- Chọn dịch vụ --</option>
                                        <?php foreach ($services as $srv):
                                            $sMeta = fnGetServiceIconMeta($srv['id'], $srv['name']);
                                        ?>
                                            <option value="<?= (int)$srv['id'] ?>"
                                                data-raw-name="<?= htmlspecialchars($srv['name']) ?>"
                                                data-emoji="<?= htmlspecialchars($sMeta['emoji']) ?>"
                                                data-device="<?= $srv['device_type'] === 'laptop' ? 'Laptop' : 'ĐT' ?>"
                                                <?= ((int)($priceFormValues['service_id'] ?? $selectedServiceFilter) === (int)$srv['id']) ? 'selected' : '' ?>>
                                                <?= $sMeta['emoji'] ?> [<?= $srv['device_type'] === 'laptop' ? 'Laptop' : 'ĐT' ?>] <?= htmlspecialchars($srv['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="add-service-status-alert" class="svc-add-hint-box" style="display:none;"></div>
                                </div>

                                <div class="svc-form-row-2">
                                    <div>
                                        <label class="svc-label-text">Giá tối thiểu (VNĐ) *</label>
                                        <input type="number" id="add-min-price" name="min_price" class="svc-input-text"
                                               placeholder="Ví dụ: 350000" min="1000" step="1000" inputmode="numeric"
                                               value="<?= htmlspecialchars((string)($priceFormValues['min_price'] ?? '')) ?>" required oninput="calcRealtimePriceAdd()">
                                    </div>
                                    <div>
                                        <label class="svc-label-text">Giá tối đa (VNĐ) *</label>
                                        <input type="number" id="add-max-price" name="max_price" class="svc-input-text"
                                               placeholder="Ví dụ: 850000" min="1000" step="1000" inputmode="numeric"
                                               value="<?= htmlspecialchars((string)($priceFormValues['max_price'] ?? '')) ?>" required oninput="calcRealtimePriceAdd()">
                                    </div>
                                </div>

                                <div id="add-price-preview-box" class="svc-calc-preview-box" style="display:none;">
                                    Mức giữa ước tính: <span id="add-preview-avg" class="svc-calc-preview-val">0đ</span>
                                </div>

                                <div class="svc-form-row-2">
                                    <div>
                                        <label class="svc-label-text">Thời gian bảo hành *</label>
                                        <input type="text" name="warranty" class="svc-input-text"
                                               value="<?= htmlspecialchars((string)($priceFormValues['warranty'] ?? '6 - 12 tháng')) ?>" required>
                                    </div>
                                    <div>
                                        <label class="svc-label-text">Thời gian xử lý *</label>
                                        <input type="text" name="turnaround" class="svc-input-text"
                                               value="<?= htmlspecialchars((string)($priceFormValues['turnaround'] ?? '30 - 60 phút')) ?>" required>
                                    </div>
                                </div>

                                <div class="svc-form-group">
                                    <label class="svc-label-text">Ghi chú linh kiện / điều kiện</label>
                                    <input type="text" name="note" class="svc-input-text"
                                           placeholder="Linh kiện zin bóc máy, bảo hành 1 đổi 1..."
                                           value="<?= htmlspecialchars((string)($priceFormValues['note'] ?? '')) ?>">
                                </div>

                                <div class="svc-form-actions-row">
                                    <button type="submit" class="svc-btn-save-main">
                                        ➕ Lưu dịch vụ vào tiệm
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
// Dữ liệu dịch vụ đã có của từng shop
const shopExistingServices = <?= json_encode($shopExistingServicesMap) ?>;

// Hàm định dạng tiền tệ Việt Nam VNĐ
function formatVND(num) {
    if (isNaN(num) || num <= 0) return '0đ';
    return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + 'đ';
}

// Xử lý làm mờ và khóa các dịch vụ đã có khi chọn cửa hàng
function handleShopChangeAdd(shopId) {
    const sId = parseInt(shopId, 10);
    const serviceSelect = document.getElementById('add-service-id');
    const countHint = document.getElementById('add-service-count-hint');
    const statusAlert = document.getElementById('add-service-status-alert');
    if (!serviceSelect) return;

    const existingServices = (sId && shopExistingServices[sId]) ? shopExistingServices[sId] : {};
    const existingCount = Object.keys(existingServices).length;
    let availableCount = 0;

    const options = serviceSelect.querySelectorAll('option');
    options.forEach(opt => {
        if (!opt.value) return;
        const svcId = parseInt(opt.value, 10);
        const rawName = opt.getAttribute('data-raw-name') || opt.textContent.trim();
        const emoji = opt.getAttribute('data-emoji') || '🛠️';
        const device = opt.getAttribute('data-device') || '';

        if (sId && existingServices[svcId]) {
            // Dịch vụ đã có trong tiệm -> Làm mờ và vô hiệu hóa (disabled)
            opt.disabled = true;
            opt.classList.add('svc-opt-dimmed');
            const exInfo = existingServices[svcId];
            const priceText = (exInfo.min_price && exInfo.max_price)
                ? (formatVND(exInfo.min_price) + ' – ' + formatVND(exInfo.max_price))
                : 'Đã có';
            opt.textContent = `${emoji} [${device}] ${rawName} — (Đã có: ${priceText})`;

            if (serviceSelect.value == svcId) {
                serviceSelect.value = '';
            }
        } else {
            // Dịch vụ chưa có -> Cho phép chọn bình thường
            opt.disabled = false;
            opt.classList.remove('svc-opt-dimmed');
            opt.textContent = `${emoji} [${device}] ${rawName}`;
            availableCount++;
        }
    });

    if (sId) {
        if (countHint) {
            countHint.innerHTML = `Có thể thêm: <strong style="color:#16a34a;">${availableCount}</strong> dịch vụ (Đã có ${existingCount})`;
        }
        if (statusAlert) {
            if (availableCount === 0) {
                statusAlert.style.display = 'block';
                statusAlert.className = 'svc-add-hint-box warning';
                statusAlert.innerHTML = `⚠️ <strong>Cửa hàng này đã có đủ tất cả dịch vụ!</strong> Tất cả các dịch vụ đã được thiết lập bảng giá. Bạn có thể chọn dịch vụ ở danh sách bên trái để chỉnh sửa giá thay vì thêm mới.`;
            } else {
                statusAlert.style.display = 'none';
                statusAlert.innerHTML = '';
            }
        }
    } else {
        if (countHint) countHint.textContent = '';
        if (statusAlert) statusAlert.style.display = 'none';
    }
}

// Tính nhẩm mức giữa realtime khi chỉnh sửa
function calcRealtimePrice() {
    const minInput = document.getElementById('edit-min-price');
    const maxInput = document.getElementById('edit-max-price');
    const avgEl = document.getElementById('preview-avg-val');
    const rangeEl = document.getElementById('preview-range-val');
    if (!minInput || !maxInput || !avgEl || !rangeEl) return;

    const min = parseFloat(minInput.value) || 0;
    const max = parseFloat(maxInput.value) || 0;
    if (min > 0 && max >= min) {
        const avg = (min + max) / 2;
        avgEl.textContent = formatVND(avg);
        rangeEl.textContent = formatVND(min) + ' – ' + formatVND(max);
    } else {
        avgEl.textContent = '—';
        rangeEl.textContent = 'Vui lòng nhập giá hợp lệ';
    }
}

// Tính nhẩm realtime khi thêm mới
function calcRealtimePriceAdd() {
    const minInput = document.getElementById('add-min-price');
    const maxInput = document.getElementById('add-max-price');
    const previewBox = document.getElementById('add-price-preview-box');
    const avgEl = document.getElementById('add-preview-avg');
    if (!minInput || !maxInput || !previewBox || !avgEl) return;

    const min = parseFloat(minInput.value) || 0;
    const max = parseFloat(maxInput.value) || 0;
    if (min > 0 && max >= min) {
        previewBox.style.display = 'block';
        avgEl.textContent = formatVND((min + max) / 2);
    } else {
        previewBox.style.display = 'none';
    }
}

// Khởi chạy khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    calcRealtimePrice();
    calcRealtimePriceAdd();
    const addShopSelect = document.getElementById('add-shop-id');
    if (addShopSelect && addShopSelect.value) {
        handleShopChangeAdd(addShopSelect.value);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
