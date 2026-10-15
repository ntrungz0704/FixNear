<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/users.php");
    exit;
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$allUsers = db()->getUsers();
$regularUsers = array_values(array_filter($allUsers, static fn($u) => ($u['role'] ?? 'user') !== 'admin'));
$allSearchLogs = db()->getSearchLogs();

$logsByEmail = [];
$featuresByEmail = [];
foreach ($allSearchLogs as $log) {
    $em = mb_strtolower(trim((string)($log['user_email'] ?? '')), 'UTF-8');
    if ($em === '') continue;
    $logsByEmail[$em][] = $log;

    $fRaw = str_replace(
        'Xem chi tiết dịch vụ, phản hồi của cửa hàng',
        'Xem chi tiết dịch vụ & phản hồi của cửa hàng',
        (string)($log['features_used'] ?? '')
    );
    foreach (array_filter(array_map('trim', explode(',', $fRaw))) as $fItem) {
        $featuresByEmail[$em][$fItem] = $fItem;
    }
}

$activeSearchingUsersCount = count($logsByEmail);

$filterUserQuery = trim((string)($_GET['user_q'] ?? ($_GET['q'] ?? '')));
$filterDistrict = trim((string)($_GET['district'] ?? ''));
$filterDevice = trim((string)($_GET['device'] ?? ''));
$filterGroup = trim((string)($_GET['user_group'] ?? ''));

$filteredUsers = array_values(array_filter($regularUsers, static function ($u) use ($filterUserQuery, $filterDistrict, $filterDevice, $filterGroup) {
    if ($filterUserQuery !== '') {
        $haystack = implode(' ', [
            $u['name'] ?? '',
            $u['email'] ?? '',
            $u['phone'] ?? '',
            $u['district'] ?? '',
            $u['device_group'] ?? '',
            $u['user_group'] ?? '',
        ]);
        if (mb_stripos($haystack, $filterUserQuery, 0, 'UTF-8') === false) {
            return false;
        }
    }

    if ($filterDistrict !== '' && mb_stripos((string)($u['district'] ?? ''), $filterDistrict, 0, 'UTF-8') === false) {
        return false;
    }

    if ($filterDevice !== '' && mb_stripos((string)($u['device_group'] ?? ''), $filterDevice, 0, 'UTF-8') === false) {
        return false;
    }

    if ($filterGroup !== '' && mb_stripos((string)($u['user_group'] ?? ''), $filterGroup, 0, 'UTF-8') === false) {
        return false;
    }

    return true;
}));

// Pagination: 6 người dùng / trang
$usersPerPage = 6;
$totalFilteredUsers = count($filteredUsers);
$totalUserPages = max(1, (int)ceil($totalFilteredUsers / $usersPerPage));
$userPage = max(1, min($totalUserPages, (int)($_GET['page'] ?? ($_GET['user_page'] ?? 1))));
$userOffset = ($userPage - 1) * $usersPerPage;
$pagedUsers = array_slice($filteredUsers, $userOffset, $usersPerPage);

function fnBuildUserPageUrl(array $overrides = []): string {
    $params = [
        'user_q' => $_GET['user_q'] ?? ($_GET['q'] ?? ''),
        'district' => $_GET['district'] ?? '',
        'device' => $_GET['device'] ?? '',
        'user_group' => $_GET['user_group'] ?? '',
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
    return 'users.php' . ($qs !== '' ? '?' . $qs : '');
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

$groupOptions = [
    'Học sinh / Sinh viên năm 1',
    'Sinh viên năm 2',
    'Sinh viên năm 3 trở lên',
    'Người đã đi làm',
];

$hasActiveFilters = ($filterUserQuery !== '' || $filterDistrict !== '' || $filterDevice !== '' || $filterGroup !== '');

$pageTitle = "Quản lý Người dùng — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#fffaf5]">
        <!-- HEADER & LIVE REALTIME CLOCK -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Quản lý Tài khoản Người dùng</h1>
                <p class="text-sm text-slate-500 mt-0.5">Thông tin tài khoản khách hàng, khu vực sinh sống/học tập và nhóm thiết bị đang sử dụng tại TP.HCM</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="search_logs.php" class="bg-white hover:bg-orange-50 border border-orange-200 rounded-xl px-3.5 py-2 text-xs font-bold text-orange-700 flex items-center gap-1.5 transition shadow-xs">
                    <span>🔍</span>
                    <span>Xem Lịch sử tìm kiếm (<?= count($allSearchLogs) ?>)</span>
                </a>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 text-xs font-bold text-emerald-800 flex items-center gap-2.5 shadow-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Trực tuyến</span>
                    <span class="text-emerald-300">|</span>
                    <span id="fn-live-clock" class="font-mono font-black text-emerald-950 text-[13px] tracking-wider"><?= date('H:i:s — d/m/Y') ?></span>
                </div>
            </div>
        </div>

        <!-- 4 KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= count($regularUsers) ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Tài khoản người dùng</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center text-xl">
                        👥
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Khách hàng đã đăng ký & xác thực hoạt động
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-emerald-600 leading-none"><?= $activeSearchingUsersCount ?> / <?= count($regularUsers) ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Tài khoản có hoạt động tra cứu</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
                        ✅
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    100% người dùng đã tra cứu giá & cửa hàng
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-orange-600 leading-none">7 cụm</div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Khu vực Quận / Huyện TP.HCM</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl">
                        📍
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Phân bổ theo nơi sinh sống & học tập thực tế
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-blue-600 leading-none">6 nhóm</div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Thiết bị đăng ký sử dụng</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xl">
                        💻
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    ĐT · Laptop Win · MacBook · Tablet · Watch · PC
                </div>
            </div>
        </div>

        <!-- BỘ LỌC TÌM KIẾM NGƯỜI DÙNG -->
        <form method="GET" action="users.php" class="bg-white rounded-xl border border-orange-100 p-4 mb-6 shadow-sm">
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
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Nhóm thiết bị sử dụng</label>
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
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Nhóm đối tượng</label>
                    <select name="user_group" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="">-- Tất cả đối tượng --</option>
                        <?php foreach ($groupOptions as $grp): ?>
                            <option value="<?= htmlspecialchars($grp) ?>" <?= $filterGroup === $grp ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grp) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs py-2 px-4 rounded-lg transition cursor-pointer">
                        Tìm kiếm & Lọc
                    </button>
                    <?php if ($hasActiveFilters): ?>
                        <a href="users.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs py-2 px-3 rounded-lg transition whitespace-nowrap">
                            Xóa lọc
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- BẢNG DANH SÁCH TÀI KHOẢN NGƯỜI DÙNG -->
        <div class="bg-white rounded-xl border border-orange-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">👥 Danh sách Tài khoản Người dùng & Nhu cầu Sửa chữa Thiết bị</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <?php if ($totalFilteredUsers > 0): ?>
                            Hiển thị <strong><?= $userOffset + 1 ?>–<?= min($userOffset + $usersPerPage, $totalFilteredUsers) ?></strong> trên tổng số <strong><?= $totalFilteredUsers ?></strong> tài khoản người dùng
                        <?php else: ?>
                            Không tìm thấy tài khoản người dùng phù hợp
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-orange-700 bg-orange-50 border border-orange-200 rounded-md px-3 py-1">
                        Trang <?= $userPage ?> / <?= $totalUserPages ?> • Bấm "Xem chi tiết" để xem hồ sơ
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-[#fff7ed] border-b border-orange-200 text-[11px] font-bold text-orange-950 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Họ và tên</th>
                            <th class="py-3 px-4">Email & SĐT</th>
                            <th class="py-3 px-4">Khu vực (Quận/Huyện)</th>
                            <th class="py-3 px-4">Nhóm thiết bị</th>
                            <th class="py-3 px-4">Thời gian tạo TK</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50 text-slate-700 text-xs">
                        <?php if (empty($pagedUsers)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">Không tìm thấy tài khoản người dùng nào khớp với từ khóa tìm kiếm.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagedUsers as $idx => $u):
                                $rowNumber = $userOffset + $idx + 1;
                                $em = mb_strtolower(trim((string)($u['email'] ?? '')), 'UTF-8');
                                $uLogs = $logsByEmail[$em] ?? [];
                                $sCount = count($uLogs);
                                $createdTs = !empty($u['created_at']) ? strtotime((string)$u['created_at']) : false;
                                $devs = array_values(array_filter(array_map('trim', explode(',', (string)($u['device_group'] ?? 'Điện thoại')))));
                                $firstLog = $uLogs[0] ?? [];
                                $features = array_values($featuresByEmail[$em] ?? ['Chọn loại thiết bị → hãng → model → lỗi', 'Xem giá dự đoán / giá có nguồn theo model và dịch vụ']);

                                $userModalPayload = [
                                    'type' => 'user',
                                    'user_name' => $u['name'] ?? '',
                                    'user_email' => $u['email'] ?? '',
                                    'user_phone' => $u['phone'] ?? '',
                                    'user_group' => $u['user_group'] ?? 'Sinh viên / Người đi làm',
                                    'user_created_at' => $createdTs ? date('d/m/Y H:i:s', $createdTs) : '',
                                    'district' => $u['district'] ?? 'TP.HCM',
                                    'device_category' => $firstLog['device_category'] ?? ($devs[0] ?? 'Điện thoại'),
                                    'device_group' => $u['device_group'] ?? 'Điện thoại',
                                    'search_query' => $firstLog['search_query'] ?? '',
                                    'page_url' => ltrim((string)($firstLog['page_url'] ?? 'index.php'), '/'),
                                    'search_time' => !empty($firstLog['created_at']) ? date('d/m/Y lúc H:i:s', strtotime((string)$firstLog['created_at'])) : '',
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
                                    }, $uLogs),
                                ];
                            ?>
                                <tr class="hover:bg-[#fffaf3] transition">
                                    <td class="py-3 px-4 text-center font-bold text-slate-400"><?= $rowNumber ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                                <?= htmlspecialchars(fnUserInitials((string)($u['name'] ?? ''))) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900"><?= htmlspecialchars($u['name'] ?? '') ?></div>
                                                <div class="text-[11px] text-slate-400"><?= htmlspecialchars($u['user_group'] ?? 'Sinh viên / Người đi làm') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-800"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">📞 <?= htmlspecialchars($u['phone'] ?? 'Chưa cập nhật') ?></div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            📍 <?= htmlspecialchars($u['district'] ?? 'TP.HCM') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1">
                                            <?php foreach (array_slice($devs, 0, 2) as $d): ?>
                                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold border <?= fnDeviceBadgeClass($d) ?>">
                                                    <?= htmlspecialchars(fnShortDeviceLabel($d)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($devs) > 2): ?>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                    +<?= count($devs) - 2 ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <?php if ($createdTs): ?>
                                            <div class="font-bold text-slate-800"><?= date('d/m/Y', $createdTs) ?></div>
                                            <div class="text-[11px] text-slate-500">⏰ <?= date('H:i:s', $createdTs) ?></div>
                                        <?php else: ?>
                                            <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                onclick='fnOpenUserDetailModal(<?= htmlspecialchars(json_encode($userModalPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>)'
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-600 text-orange-700 hover:text-white border border-orange-200 hover:border-orange-600 font-bold text-xs transition cursor-pointer"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Xem chi tiết
                                            </button>
                                            <a
                                                href="search_logs.php?user_q=<?= urlencode((string)($u['name'] ?? '')) ?>"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 font-bold text-[11px] transition"
                                                title="Xem lịch sử tra cứu của người dùng này"
                                            >
                                                🔍 <?= $sCount ?> lượt
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($totalUserPages > 1): ?>
                <div class="px-5 py-3.5 bg-[#fffaf5] border-t border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">
                        Hiển thị <strong class="text-slate-800"><?= $userOffset + 1 ?>–<?= min($userOffset + $usersPerPage, $totalFilteredUsers) ?></strong> / <strong class="text-slate-800"><?= $totalFilteredUsers ?></strong> tài khoản người dùng
                    </div>
                    <div class="flex items-center gap-1.5">
                        <?php if ($userPage > 1): ?>
                            <a href="<?= htmlspecialchars(fnBuildUserPageUrl(['page' => $userPage - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white hover:bg-orange-50 text-xs font-bold text-slate-700 transition">
                                « Trước
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 cursor-not-allowed">
                                « Trước
                            </span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalUserPages; $p++): ?>
                            <a
                                href="<?= htmlspecialchars(fnBuildUserPageUrl(['page' => $p])) ?>"
                                class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center transition <?= $p === $userPage ? 'bg-orange-600 text-white shadow-xs' : 'bg-white border border-orange-200 text-slate-700 hover:bg-orange-50' ?>"
                            >
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($userPage < $totalUserPages): ?>
                            <a href="<?= htmlspecialchars(fnBuildUserPageUrl(['page' => $userPage + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white hover:bg-orange-50 text-xs font-bold text-slate-700 transition">
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

<!-- MODAL XEM CHI TIẾT HỒ SƠ NGƯỜI DÙNG -->
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
                            ✓ Tài khoản hoạt động
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
                    <span class="text-slate-400 font-semibold block mb-1">KHU VỰC SINH SỐNG / HỌC TẬP</span>
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
                    <span>⚡</span> Các bước & Tính năng đã thao tác trên Website FixNear
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
            <a id="fn-md-all-logs-link" href="search_logs.php" class="text-xs font-bold text-orange-600 hover:text-orange-700">
                🔍 Mở trong module Lịch sử tìm kiếm →
            </a>
            <button type="button" onclick="fnCloseUserDetailModal()" class="px-5 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs cursor-pointer transition">
                Đóng cửa sổ
            </button>
        </div>
    </div>
</div>

<script>
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
    document.getElementById('fn-md-all-logs-link').href = 'search_logs.php?user_q=' + encodeURIComponent(name);

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
                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-orange-100 text-orange-800">${log.device || 'Thiết bị'}</span>
                <span class="text-[11px] font-semibold text-slate-500">📍 ${log.district || 'TP.HCM'}</span>
                <span class="text-[11px] font-bold text-orange-600">⏰ ${log.time || ''}</span>
            </div>
            <div class="text-xs font-bold text-slate-900">${log.query || ''}</div>
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
