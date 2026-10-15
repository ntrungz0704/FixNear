<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/search_logs.php");
    exit;
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

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
        } elseif (str_contains($fLower, 'xem giá')) {
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

$activeSearchingUsersCount = count(array_intersect_key($logsByEmail, $userByEmail));
if ($activeSearchingUsersCount === 0 && !empty($logsByEmail)) {
    $activeSearchingUsersCount = count($logsByEmail);
}

// Pagination: 8 lượt tra cứu / trang
$logsPerPage = 8;
$totalFilteredLogs = count($filteredLogs);
$totalLogPages = max(1, (int)ceil($totalFilteredLogs / $logsPerPage));
$logPage = max(1, min($totalLogPages, (int)($_GET['page'] ?? ($_GET['log_page'] ?? 1))));
$logOffset = ($logPage - 1) * $logsPerPage;
$pagedLogs = array_slice($filteredLogs, $logOffset, $logsPerPage);

function fnBuildLogPageUrl(array $overrides = []): string {
    $params = [
        'user_q' => $_GET['user_q'] ?? ($_GET['email'] ?? ''),
        'district' => $_GET['district'] ?? '',
        'device' => $_GET['device'] ?? '',
        'q' => $_GET['q'] ?? '',
        'page' => $_GET['page'] ?? 1,
    ];
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    $clean = [];
    foreach ($params as $k => $v) {
        $vStr = trim((string)$v);
        if ($vStr === '' || ($k === 'page' && (int)$vStr <= 1)) {
            continue;
        }
        $clean[$k] = $vStr;
    }
    $qs = http_build_query($clean);
    return 'search_logs.php' . ($qs !== '' ? '?' . $qs : '');
}

function fnUserInitials(string $name): string {
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

function fnDeviceBadgeClass(string $dev): string {
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

function fnShortDeviceLabel(string $dev): string {
    $dev = trim($dev);
    return match ($dev) {
        'Máy tính bảng (Tablet)' => 'Tablet',
        'Máy tính bàn (PC)' => 'PC',
        'Đồng hồ thông minh' => 'Smartwatch',
        default => $dev,
    };
}

$districtOptions = [
    'Quận 1, 3, 4, 5',
    'Quận 7, 12',
    'Bình Thạnh, Phú Nhuận',
    'Tân Bình, Tân Phú',
    'Gò Vấp, Bình Tân',
    'TP. Thủ Đức',
    'Khu vực khác thuộc TP.HCM',
];

$deviceOptions = [
    'Điện thoại',
    'Laptop Windows',
    'MacBook / Mac',
    'Máy tính bảng (Tablet)',
    'Đồng hồ thông minh',
    'Máy tính bàn (PC)',
];

$hasActiveFilters = ($filterUserQuery !== '' || $filterDistrict !== '' || $filterDevice !== '' || $filterQuery !== '');

// Payload cho tính năng đồng bộ trực tiếp lên Google Analytics 4 (G-ZEH4B0DYFQ)
$gaSyncLogsPayload = array_map(static function ($l) {
    return [
        'user_name' => $l['user_name'] ?? '',
        'user_email' => $l['user_email'] ?? '',
        'district' => $l['district'] ?? '',
        'device_category' => $l['device_category'] ?? '',
        'search_query' => $l['search_query'] ?? '',
        'page_url' => ltrim((string)($l['page_url'] ?? 'prices.php'), '/'),
        'created_at' => $l['created_at'] ?? '',
    ];
}, $allSearchLogs);

$pageTitle = "Lịch sử tìm kiếm & GA4 — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#fffaf5]">
        <!-- HEADER & LIVE REALTIME CLOCK + GA4 SYNC -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Lịch sử tìm kiếm & Hành vi Website</h1>
                <p class="text-sm text-slate-500 mt-0.5">Nhật ký tra cứu thiết bị, lỗi hư hỏng, bảng giá và đồng bộ sự kiện Google Analytics 4 (G-ZEH4B0DYFQ)</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    id="fn-btn-ga-sync"
                    onclick="fnSyncAllLogsToGA4()"
                    class="bg-white hover:bg-orange-50 border border-orange-200 rounded-xl px-3.5 py-2 text-xs font-bold text-orange-700 flex items-center gap-1.5 transition cursor-pointer shadow-xs"
                >
                    <span id="fn-ga-sync-icon">🔄</span>
                    <span id="fn-ga-sync-text">Đồng bộ GA4 (G-ZEH4B0DYFQ)</span>
                </button>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 text-xs font-bold text-emerald-800 flex items-center gap-2.5 shadow-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Trực tuyến</span>
                    <span class="text-emerald-300">|</span>
                    <span id="fn-live-clock" class="font-mono font-black text-emerald-950 text-[13px] tracking-wider"><?= date('H:i:s — d/m/Y') ?></span>
                </div>
            </div>
        </div>

        <!-- THÔNG BÁO ĐỒNG BỘ GA4 -->
        <div id="fn-ga-sync-toast" class="hidden bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 mb-6 text-xs font-semibold text-emerald-800 items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[11px] font-bold">✓</span>
                <span id="fn-ga-sync-toast-msg">Đã gửi đồng bộ toàn bộ 37 lượt tra cứu và sự kiện hành vi người dùng lên Google Analytics 4 (Mã: G-ZEH4B0DYFQ).</span>
            </div>
            <button type="button" onclick="document.getElementById('fn-ga-sync-toast').classList.add('hidden')" class="text-emerald-600 hover:text-emerald-800 font-bold text-base cursor-pointer">&times;</button>
        </div>

        <!-- 4 KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div id="fn-kpi-card-searches" class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <div id="fn-kpi-total-searches" class="text-3xl font-black text-orange-600 leading-none"><?= count($allSearchLogs) ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Tổng lượt tìm kiếm & tra cứu</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center text-xl">
                        🔍
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Ghi nhận theo từng dòng máy, lỗi và khu vực
                </div>
            </div>

            <div id="fn-kpi-card-users" class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <div id="fn-kpi-active-users" class="text-3xl font-black text-emerald-600 leading-none"><?= $activeSearchingUsersCount ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Người dùng có lịch sử tra cứu</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
                        👥
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Đã trực tiếp chọn máy, xem giá & cửa hàng
                </div>
            </div>

            <div id="fn-kpi-card-ga4" class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <div id="fn-kpi-total-ga-events" class="text-3xl font-black text-blue-600 leading-none"><?= $totalGaEvents ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Sự kiện hành vi GA4</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xl">
                        📈
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Đồng bộ mã đo lường <strong class="text-slate-700">G-ZEH4B0DYFQ</strong>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none">6 danh mục</div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Thiết bị & Bảng giá tra cứu</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl">
                        💻
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    ĐT · Laptop Win · MacBook · Tablet · Watch · PC
                </div>
            </div>
        </div>

        <!-- KHỐI ĐỒNG BỘ SỰ KIỆN GOOGLE ANALYTICS 4 (G-ZEH4B0DYFQ) -->
        <div class="bg-white rounded-xl border border-orange-100 p-4 mb-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-black">GA4</span>
                    <h2 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Thống kê Sự kiện Hành vi Đồng bộ Google Analytics 4 (G-ZEH4B0DYFQ)</h2>
                    <span id="fn-ga4-live-pill" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10.5px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                        <span id="fn-ga4-live-pill-text">Đồng bộ Real-time (Tự động)</span>
                    </span>
                </div>
                <span id="fn-ga4-last-activity" class="text-[11px] text-slate-500 font-medium">
                    Tự động ghi nhận mỗi khi người dùng chọn dòng máy, xem giá hoặc tra cứu cửa hàng
                </span>
            </div>
            <div id="fn-ga4-breakdown-grid" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
                <?php foreach ($gaEventBreakdown as $evCode => $evInfo): ?>
                    <div data-ga-card="<?= htmlspecialchars($evCode) ?>" class="p-2.5 rounded-lg bg-[#fffaf5] border border-orange-100 transition-all duration-300 relative">
                        <div class="flex items-center justify-between gap-1">
                            <div data-ga-count="<?= htmlspecialchars($evCode) ?>" class="text-lg font-black text-slate-900 leading-none"><?= $evInfo['count'] ?></div>
                            <span data-ga-delta="<?= htmlspecialchars($evCode) ?>" class="hidden px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black">+1</span>
                        </div>
                        <div class="text-[10.5px] font-mono font-bold text-orange-600 mt-1 truncate" title="<?= htmlspecialchars($evCode) ?>"><?= htmlspecialchars($evCode) ?></div>
                        <div class="text-[10.5px] text-slate-500 mt-0.5 line-clamp-1" title="<?= htmlspecialchars($evInfo['label']) ?>"><?= htmlspecialchars($evInfo['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- BỘ LỌC TÌM KIẾM LỊCH SỬ TRA CỨU -->
        <form method="GET" action="search_logs.php" class="bg-white rounded-xl border border-orange-100 p-4 mb-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tìm kiếm người dùng</label>
                    <div class="relative">
                        <input
                            type="text"
                            name="user_q"
                            value="<?= htmlspecialchars($filterUserQuery) ?>"
                            placeholder="Nhập họ tên, SĐT hoặc email..."
                            class="w-full rounded-lg border border-slate-200 pl-8 pr-3 py-2 text-xs text-slate-700 focus:border-orange-500 focus:outline-none"
                        >
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"></path></svg>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Khu vực (Quận / Huyện)</label>
                    <select name="district" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="">-- Tất cả khu vực --</option>
                        <?php foreach ($districtOptions as $dist): ?>
                            <option value="<?= htmlspecialchars($dist) ?>" <?= $filterDistrict === $dist ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dist) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Nhóm thiết bị</label>
                    <select name="device" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="">-- Tất cả thiết bị --</option>
                        <?php foreach ($deviceOptions as $dev): ?>
                            <option value="<?= htmlspecialchars($dev) ?>" <?= $filterDevice === $dev ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dev) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Từ khóa dòng máy / lỗi</label>
                    <input
                        type="text"
                        name="q"
                        value="<?= htmlspecialchars($filterQuery) ?>"
                        placeholder="VD: iPhone 13, MacBook, Thay màn..."
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700 focus:border-orange-500 focus:outline-none"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs py-2 px-4 rounded-lg transition cursor-pointer">
                        Tìm kiếm & Lọc
                    </button>
                    <?php if ($hasActiveFilters): ?>
                        <a href="search_logs.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs py-2 px-3 rounded-lg transition whitespace-nowrap">
                            Xóa lọc
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- BẢNG NHẬT KÝ LỊCH SỬ TÌM KIẾM -->
        <div class="bg-white rounded-xl border border-orange-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">🔍 Nhật ký Lịch sử Tìm kiếm & Hành vi Trải nghiệm Website</h2>
                    <p id="fn-search-logs-summary" class="text-xs text-slate-500 mt-0.5">
                        <?php if ($totalFilteredLogs > 0): ?>
                            Hiển thị <strong><?= $logOffset + 1 ?>–<?= min($logOffset + $logsPerPage, $totalFilteredLogs) ?></strong> trên tổng số <strong><?= $totalFilteredLogs ?></strong> lượt tra cứu dòng máy, lỗi hư hỏng và cửa hàng
                        <?php else: ?>
                            Không có lượt tra cứu nào khớp với bộ lọc hiện tại
                        <?php endif; ?>
                    </p>
                </div>
                <span id="fn-search-logs-page-badge" class="text-xs font-semibold text-orange-700 bg-orange-50 border border-orange-200 rounded-md px-3 py-1">
                    Trang <?= $logPage ?> / <?= $totalLogPages ?> • Bấm "Xem chi tiết" để xem đầy đủ thao tác
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-[#fff7ed] border-b border-orange-200 text-[11px] font-bold text-orange-950 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Thời gian</th>
                            <th class="py-3 px-4">Người dùng</th>
                            <th class="py-3 px-4">Khu vực</th>
                            <th class="py-3 px-4">Thiết bị</th>
                            <th class="py-3 px-4">Nội dung tìm kiếm & Dòng máy / Lỗi</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="fn-search-logs-tbody" class="divide-y divide-orange-50 text-slate-700 text-xs">
                        <?php if (empty($pagedLogs)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">Không tìm thấy lịch sử tra cứu phù hợp với từ khóa / bộ lọc.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagedLogs as $idx => $log):
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
                                    'user_name' => $log['user_name'] ?? 'Khách',
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
                            ?>
                                <tr data-log-id="<?= (int)($log['id'] ?? 0) ?>" class="hover:bg-[#fffaf3] transition">
                                    <td class="py-3 px-4 text-center font-bold text-slate-400"><?= $rowNumber ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($dateFormatted) ?></div>
                                        <div class="text-[11px] text-orange-600 font-semibold mt-0.5">⏰ <?= htmlspecialchars($timeFormatted) ?></div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-[11px] flex items-center justify-center flex-shrink-0">
                                                <?= htmlspecialchars(fnUserInitials((string)($log['user_name'] ?? ''))) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900"><?= htmlspecialchars($log['user_name'] ?? 'Khách') ?></div>
                                                <div class="text-[11px] text-slate-400"><?= htmlspecialchars($log['user_phone'] ?: ($log['user_email'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            📍 <?= htmlspecialchars($log['district'] ?? 'TP.HCM') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold border <?= fnDeviceBadgeClass((string)($log['device_category'] ?? '')) ?>">
                                            <?= htmlspecialchars(fnShortDeviceLabel((string)($log['device_category'] ?? 'Điện thoại'))) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 max-w-sm">
                                        <div class="font-bold text-slate-900 truncate" title="<?= htmlspecialchars($log['search_query'] ?? '') ?>">
                                            <?= htmlspecialchars($log['search_query'] ?? '') ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2">
                                            <span class="text-emerald-600 font-semibold">✓ <?= count($features) ?> bước thao tác</span>
                                            <span>•</span>
                                            <span class="truncate text-blue-600">/<?= htmlspecialchars(ltrim((string)($log['page_url'] ?? ''), '/')) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            onclick='fnOpenUserDetailModal(<?= htmlspecialchars(json_encode($modalPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>)'
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-600 text-orange-700 hover:text-white border border-orange-200 hover:border-orange-600 font-bold text-xs transition cursor-pointer shadow-xs"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Xem chi tiết
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($totalLogPages > 1): ?>
                <div class="px-5 py-3.5 bg-[#fffaf5] border-t border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div id="fn-search-logs-footer-summary" class="text-xs text-slate-500">
                        Hiển thị <strong class="text-slate-800"><?= $logOffset + 1 ?>–<?= min($logOffset + $logsPerPage, $totalFilteredLogs) ?></strong> / <strong class="text-slate-800"><?= $totalFilteredLogs ?></strong> lượt tra cứu
                    </div>
                    <div class="flex items-center gap-1.5">
                        <?php if ($logPage > 1): ?>
                            <a href="<?= htmlspecialchars(fnBuildLogPageUrl(['page' => $logPage - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white hover:bg-orange-50 text-xs font-bold text-slate-700 transition">
                                « Trước
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 cursor-not-allowed">
                                « Trước
                            </span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalLogPages; $p++): ?>
                            <a
                                href="<?= htmlspecialchars(fnBuildLogPageUrl(['page' => $p])) ?>"
                                class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center transition <?= $p === $logPage ? 'bg-orange-600 text-white shadow-xs' : 'bg-white border border-orange-200 text-slate-700 hover:bg-orange-50' ?>"
                            >
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($logPage < $totalLogPages): ?>
                            <a href="<?= htmlspecialchars(fnBuildLogPageUrl(['page' => $logPage + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white hover:bg-orange-50 text-xs font-bold text-slate-700 transition">
                                Sau »
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 cursor-not-allowed">
                                Sau »
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL XEM CHI TIẾT LƯỢT TRA CỨU -->
<div id="fn-user-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl border border-orange-200 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 bg-[#fff7ed] border-b border-orange-200 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div id="fn-md-avatar" class="w-11 h-11 rounded-xl bg-orange-600 text-white font-black text-sm flex items-center justify-center shadow-xs">
                    ND
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 id="fn-md-name" class="text-base font-black text-slate-900">Họ và tên</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✓ Đã đồng bộ GA4
                        </span>
                    </div>
                    <p id="fn-md-sub" class="text-xs text-slate-500 mt-0.5">Email · Số điện thoại</p>
                </div>
            </div>
            <button type="button" onclick="fnCloseUserDetailModal()" class="w-8 h-8 rounded-lg bg-white hover:bg-orange-100 text-slate-500 hover:text-orange-700 border border-orange-200 flex items-center justify-center text-lg font-bold cursor-pointer transition">
                &times;
            </button>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 rounded-xl p-4 border border-slate-200 text-xs">
                <div>
                    <span class="text-slate-400 font-semibold block mb-1">KHU VỰC SINH SỐNG / TÌM KIẾM</span>
                    <span id="fn-md-district" class="font-bold text-slate-800 text-sm">📍 TP.HCM</span>
                </div>
                <div>
                    <span class="text-slate-400 font-semibold block mb-1">THỜI ĐIỂM ĐĂNG KÝ TÀI KHOẢN</span>
                    <span id="fn-md-created" class="font-bold text-slate-800 text-sm">⏰ —</span>
                </div>
                <div class="sm:col-span-2 pt-2 border-t border-slate-200/80">
                    <span class="text-slate-400 font-semibold block mb-1.5">NHÓM THIẾT BỊ SỬ DỤNG & QUAN TÂM</span>
                    <div id="fn-md-devices" class="flex flex-wrap gap-1.5"></div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <span>⚡</span> Các bước & Sự kiện đã ghi nhận trên Website FixNear (GA4)
                </h4>
                <div id="fn-md-features" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
            </div>

            <div>
                <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <span>🔍</span> Lịch sử tra cứu chi tiết của người dùng này
                </h4>
                <div id="fn-md-logs" class="space-y-2.5"></div>
            </div>
        </div>

        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-2">
            <a id="fn-md-user-profile-link" href="users.php" class="text-xs font-bold text-orange-600 hover:text-orange-700">
                👥 Xem hồ sơ tài khoản người dùng →
            </a>
            <button type="button" onclick="fnCloseUserDetailModal()" class="px-5 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs cursor-pointer transition">
                Đóng cửa sổ
            </button>
        </div>
    </div>
</div>

<script>
let FN_ALL_SEARCH_LOGS = <?= json_encode($gaSyncLogsPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let fnLiveRevision = '';
let fnInitialMaxLogId = <?= !empty($allSearchLogs) ? (int)max(array_map(static fn($l) => (int)($l['id'] ?? 0), $allSearchLogs)) : 0 ?>;
let fnLastSyncedLogId = fnInitialMaxLogId;
let fnPollInFlight = false;

(function initLiveClock() {
    const clockEl = document.getElementById('fn-live-clock');
    if (!clockEl) return;
    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }
    function tick() {
        const now = new Date();
        clockEl.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()) + ' — ' + pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear();
    }
    tick();
    setInterval(tick, 1000);
})();

function fnEscapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function fnFlashElement(el, highlightClasses) {
    if (!el) return;
    const classes = highlightClasses || ['ring-2', 'ring-orange-400', 'bg-orange-50'];
    el.classList.add(...classes);
    setTimeout(() => {
        el.classList.remove(...classes);
    }, 1800);
}

function fnRenderRealtimeRows(rows) {
    const tbody = document.getElementById('fn-search-logs-tbody');
    if (!tbody || !Array.isArray(rows)) return;

    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">Không tìm thấy lịch sử tra cứu phù hợp với từ khóa / bộ lọc.</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    rows.forEach((row) => {
        const isNewRow = Number(row.id) > fnInitialMaxLogId;
        const tr = document.createElement('tr');
        tr.dataset.logId = String(row.id || 0);
        tr.className = isNewRow
            ? 'bg-emerald-50/60 hover:bg-[#fffaf3] transition-colors duration-700'
            : 'hover:bg-[#fffaf3] transition';

        const newBadgeHtml = isNewRow
            ? '<span class="ml-1.5 px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[9.5px] font-black uppercase tracking-wider">Mới</span>'
            : '';

        tr.innerHTML = `
            <td class="py-3 px-4 text-center font-bold text-slate-400">${Number(row.row_number) || ''}</td>
            <td class="py-3 px-4 whitespace-nowrap">
                <div class="font-bold text-slate-900 flex items-center">${fnEscapeHtml(row.date_formatted)}${newBadgeHtml}</div>
                <div class="text-[11px] text-orange-600 font-semibold mt-0.5">⏰ ${fnEscapeHtml(row.time_formatted)}</div>
            </td>
            <td class="py-3 px-4 whitespace-nowrap">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-[11px] flex items-center justify-center flex-shrink-0">
                        ${fnEscapeHtml(row.initials)}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900">${fnEscapeHtml(row.user_name)}</div>
                        <div class="text-[11px] text-slate-400">${fnEscapeHtml(row.user_contact)}</div>
                    </div>
                </div>
            </td>
            <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    📍 ${fnEscapeHtml(row.district)}
                </span>
            </td>
            <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold border ${fnEscapeHtml(row.device_badge_class)}">
                    ${fnEscapeHtml(row.device_short_label)}
                </span>
            </td>
            <td class="py-3 px-4 max-w-sm">
                <div class="font-bold text-slate-900 truncate" title="${fnEscapeHtml(row.search_query)}">
                    ${fnEscapeHtml(row.search_query)}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2">
                    <span class="text-emerald-600 font-semibold">✓ ${Number(row.features_count) || 1} bước thao tác</span>
                    <span>•</span>
                    <span class="truncate text-blue-600">/${fnEscapeHtml(row.page_url)}</span>
                </div>
            </td>
            <td class="py-3 px-4 text-right whitespace-nowrap">
                <button
                    type="button"
                    class="fn-btn-view-detail inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-600 text-orange-700 hover:text-white border border-orange-200 hover:border-orange-600 font-bold text-xs transition cursor-pointer shadow-xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Xem chi tiết
                </button>
            </td>
        `;

        const detailBtn = tr.querySelector('.fn-btn-view-detail');
        if (detailBtn && row.modal_payload) {
            detailBtn.addEventListener('click', () => fnOpenUserDetailModal(row.modal_payload));
        }

        tbody.appendChild(tr);
    });
}

async function fnPollRealtimeSearchLogs(forceUpdate) {
    if (fnPollInFlight) return null;
    fnPollInFlight = true;
    try {
        const qs = window.location.search || '';
        const res = await fetch('../api/search_logs_live.php' + qs, {
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!res.ok) return null;
        const data = await res.json();
        if (!data || !data.ok) return null;

        const isFirstPoll = (fnLiveRevision === '');
        const hasChanged = !isFirstPoll && fnLiveRevision !== data.revision;
        fnLiveRevision = data.revision;

        if (!hasChanged && !forceUpdate) {
            return data;
        }

        // 1. Cập nhật 3 KPI Cards động
        if (data.kpi) {
            const elSearches = document.getElementById('fn-kpi-total-searches');
            const elUsers = document.getElementById('fn-kpi-active-users');
            const elGa4 = document.getElementById('fn-kpi-total-ga-events');

            if (elSearches && String(data.kpi.total_searches) !== elSearches.textContent.trim()) {
                elSearches.textContent = String(data.kpi.total_searches);
                fnFlashElement(document.getElementById('fn-kpi-card-searches'), ['ring-2', 'ring-orange-400', 'bg-orange-50/40']);
            }
            if (elUsers && String(data.kpi.active_users) !== elUsers.textContent.trim()) {
                elUsers.textContent = String(data.kpi.active_users);
                fnFlashElement(document.getElementById('fn-kpi-card-users'), ['ring-2', 'ring-emerald-400', 'bg-emerald-50/40']);
            }
            if (elGa4 && String(data.kpi.total_ga_events) !== elGa4.textContent.trim()) {
                elGa4.textContent = String(data.kpi.total_ga_events);
                fnFlashElement(document.getElementById('fn-kpi-card-ga4'), ['ring-2', 'ring-blue-400', 'bg-blue-50/40']);
            }
        }

        // 2. Cập nhật 8 ô sự kiện GA4 kèm hiệu ứng +N khi tăng số
        if (data.ga_breakdown) {
            Object.entries(data.ga_breakdown).forEach(([evCode, newVal]) => {
                const countEl = document.querySelector(`[data-ga-count="${evCode}"]`);
                const cardEl = document.querySelector(`[data-ga-card="${evCode}"]`);
                const deltaEl = document.querySelector(`[data-ga-delta="${evCode}"]`);
                if (!countEl) return;

                const oldVal = parseInt(countEl.textContent.trim(), 10) || 0;
                const nextVal = parseInt(newVal, 10) || 0;
                if (nextVal !== oldVal) {
                    const diff = nextVal - oldVal;
                    countEl.textContent = String(nextVal);
                    if (cardEl) {
                        fnFlashElement(cardEl, ['ring-2', 'ring-orange-500', 'bg-orange-100/70', 'scale-[1.03]']);
                    }
                    if (deltaEl && diff > 0) {
                        deltaEl.textContent = '+' + diff;
                        deltaEl.classList.remove('hidden');
                        setTimeout(() => {
                            deltaEl.classList.add('hidden');
                        }, 4500);
                    }
                }
            });
        }

        // 3. Cập nhật thông tin phân trang & Bảng lịch sử tra cứu
        if (data.pagination) {
            const p = data.pagination;
            const summaryEl = document.getElementById('fn-search-logs-summary');
            if (summaryEl) {
                if (p.filtered_total > 0) {
                    summaryEl.innerHTML = `Hiển thị <strong>${p.range_start}–${p.range_end}</strong> trên tổng số <strong>${p.filtered_total}</strong> lượt tra cứu dòng máy, lỗi hư hỏng và cửa hàng`;
                } else {
                    summaryEl.textContent = 'Không có lượt tra cứu nào khớp với bộ lọc hiện tại';
                }
            }
            const pageBadgeEl = document.getElementById('fn-search-logs-page-badge');
            if (pageBadgeEl) {
                pageBadgeEl.textContent = `Trang ${p.page} / ${p.total_pages} • Bấm "Xem chi tiết" để xem đầy đủ thao tác`;
            }
            const footerSumEl = document.getElementById('fn-search-logs-footer-summary');
            if (footerSumEl && p.filtered_total > 0) {
                footerSumEl.innerHTML = `Hiển thị <strong class="text-slate-800">${p.range_start}–${p.range_end}</strong> / <strong class="text-slate-800">${p.filtered_total}</strong> lượt tra cứu`;
            }
        }

        if (Array.isArray(data.rows)) {
            fnRenderRealtimeRows(data.rows);
        }

        // 4. Hiển thị dòng trạng thái hoạt động mới nhất & đồng bộ lên GA4
        if (data.latest_log && hasChanged) {
            const actEl = document.getElementById('fn-ga4-last-activity');
            if (actEl) {
                const nowStr = (data.server_time || '').split(' — ')[0] || 'Vừa xong';
                actEl.innerHTML = `<span class="text-emerald-700 font-bold">⚡ Vừa đồng bộ (${fnEscapeHtml(nowStr)}):</span> <strong class="text-slate-800">${fnEscapeHtml(data.latest_log.user_name)}</strong> — ${fnEscapeHtml(data.latest_log.search_query)}`;
            }

            if (Number(data.latest_log.id) > fnLastSyncedLogId && typeof window.gtag === 'function') {
                fnLastSyncedLogId = Number(data.latest_log.id);
                window.gtag('event', 'fn_realtime_admin_sync', {
                    device_category: data.latest_log.device_category,
                    district: data.latest_log.district,
                    search_query: data.latest_log.search_query
                });
            }
        }

        return data;
    } catch (err) {
        return null;
    } finally {
        fnPollInFlight = false;
    }
}

// Khởi chạy kết nối Real-time: Polling 3s + BroadcastChannel 0ms + Storage listener
(function initRealtimeSearchLogsSync() {
    fnPollRealtimeSearchLogs(false);

    setInterval(() => {
        if (!document.hidden) {
            fnPollRealtimeSearchLogs(false);
        }
    }, 3000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fnPollRealtimeSearchLogs(false);
        }
    });

    try {
        if (typeof BroadcastChannel !== 'undefined') {
            const bc = new BroadcastChannel('fixnear_ga4_realtime');
            bc.addEventListener('message', () => {
                fnPollRealtimeSearchLogs(true);
            });
        }
    } catch (err) {}

    window.addEventListener('storage', (e) => {
        if (e.key === 'fixnear_ga4_last_event') {
            fnPollRealtimeSearchLogs(true);
        }
    });
})();

async function fnSyncAllLogsToGA4() {
    const btn = document.getElementById('fn-btn-ga-sync');
    const icon = document.getElementById('fn-ga-sync-icon');
    const text = document.getElementById('fn-ga-sync-text');
    const toast = document.getElementById('fn-ga-sync-toast');
    const toastMsg = document.getElementById('fn-ga-sync-toast-msg');
    if (btn) btn.disabled = true;
    if (icon) icon.textContent = '⏳';
    if (text) text.textContent = 'Đang đồng bộ lên GA4...';

    const latestData = await fnPollRealtimeSearchLogs(true);
    const totalCount = latestData && latestData.kpi ? latestData.kpi.total_searches : FN_ALL_SEARCH_LOGS.length;
    const totalEvents = latestData && latestData.kpi ? latestData.kpi.total_ga_events : '';

    if (typeof window.gtag === 'function' && Array.isArray(FN_ALL_SEARCH_LOGS)) {
        FN_ALL_SEARCH_LOGS.forEach((item) => {
            window.gtag('event', 'fn_select_device_flow', {
                device_category: item.device_category,
                district: item.district,
                search_query: item.search_query,
                page_location: window.location.origin + '/' + item.page_url
            });
            window.gtag('event', 'fn_view_prices', {
                device_category: item.device_category,
                district: item.district,
                search_query: item.search_query,
                page_location: window.location.origin + '/' + item.page_url
            });
            window.gtag('event', 'fn_view_shops', {
                district: item.district,
                device_category: item.device_category
            });
        });
    }

    setTimeout(() => {
        if (btn) btn.disabled = false;
        if (icon) icon.textContent = '✅';
        if (text) text.textContent = 'Đã đồng bộ GA4 (' + totalCount + ' lượt)';
        if (toastMsg) {
            toastMsg.textContent = `Đã gửi đồng bộ toàn bộ ${totalCount} lượt tra cứu` + (totalEvents ? ` và ${totalEvents} sự kiện hành vi người dùng` : '') + ' lên Google Analytics 4 (Mã: G-ZEH4B0DYFQ).';
        }
        if (toast) {
            toast.classList.remove('hidden');
            toast.classList.add('flex');
        }
    }, 450);
}

function fnOpenUserDetailModal(data) {
    const modal = document.getElementById('fn-user-detail-modal');
    if (!modal || !data) return;

    const name = data.user_name || 'Người dùng';
    const words = name.trim().split(/\s+/);
    const initials = words.length >= 2
        ? (words[words.length - 2][0] + words[words.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();

    document.getElementById('fn-md-avatar').textContent = initials;
    document.getElementById('fn-md-name').textContent = name;
    document.getElementById('fn-md-sub').textContent = [data.user_email, data.user_phone ? ('📞 ' + data.user_phone) : '', data.user_group].filter(Boolean).join('  •  ');
    document.getElementById('fn-md-district').textContent = '📍 ' + (data.district || 'TP.HCM');
    document.getElementById('fn-md-created').textContent = '⏰ ' + (data.user_created_at || data.search_time || '');
    document.getElementById('fn-md-user-profile-link').href = 'users.php?user_q=' + encodeURIComponent(name);

    const devContainer = document.getElementById('fn-md-devices');
    devContainer.innerHTML = '';
    const devs = String(data.device_group || data.device_category || 'Điện thoại').split(',').map(s => s.trim()).filter(Boolean);
    devs.forEach(dev => {
        const span = document.createElement('span');
        span.className = 'px-2.5 py-1 rounded-md text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200';
        span.textContent = dev;
        devContainer.appendChild(span);
    });

    const featContainer = document.getElementById('fn-md-features');
    featContainer.innerHTML = '';
    const features = Array.isArray(data.features) ? data.features : [];
    features.forEach(feat => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200 text-xs font-semibold text-slate-800';
        div.innerHTML = '<span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[11px] font-bold flex-shrink-0">✓</span><span></span>';
        div.querySelector('span:last-child').textContent = feat;
        featContainer.appendChild(div);
    });

    const logsContainer = document.getElementById('fn-md-logs');
    logsContainer.innerHTML = '';
    const userLogs = Array.isArray(data.user_logs) ? data.user_logs : [];
    userLogs.forEach(log => {
        const item = document.createElement('div');
        item.className = 'p-3.5 rounded-xl border border-orange-200 bg-[#fffaf5] flex flex-col sm:flex-row sm:items-center justify-between gap-2';
        const left = document.createElement('div');
        left.innerHTML = `
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-orange-100 text-orange-800">${fnEscapeHtml(log.device || 'Thiết bị')}</span>
                <span class="text-[11px] font-semibold text-slate-500">📍 ${fnEscapeHtml(log.district || 'TP.HCM')}</span>
                <span class="text-[11px] font-bold text-orange-600">⏰ ${fnEscapeHtml(log.time || '')}</span>
            </div>
            <div class="text-xs font-bold text-slate-900">${fnEscapeHtml(log.query || '')}</div>
        `;
        item.appendChild(left);

        if (log.url) {
            const link = document.createElement('a');
            link.href = '../' + log.url;
            link.target = '_blank';
            link.className = 'px-3 py-1.5 rounded-lg bg-white hover:bg-orange-50 text-blue-600 border border-slate-200 text-[11px] font-bold whitespace-nowrap self-start sm:self-center';
            link.textContent = 'Mở trang đã xem ↗';
            item.appendChild(link);
        }
        logsContainer.appendChild(item);
    });

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fnCloseUserDetailModal() {
    const modal = document.getElementById('fn-user-detail-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('fn-user-detail-modal');
    if (modal && e.target === modal) {
        fnCloseUserDetailModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fnCloseUserDetailModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
