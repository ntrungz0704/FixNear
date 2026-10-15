<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/users.php");
    exit;
}

$allUsers = db()->getUsers();
$regularUsers = array_values(array_filter($allUsers, static fn($u) => ($u['role'] ?? 'user') !== 'admin'));

$filterEmail = trim((string)($_GET['email'] ?? ''));
$filterDistrict = trim((string)($_GET['district'] ?? ''));
$filterDevice = trim((string)($_GET['device'] ?? ''));
$filterQuery = trim((string)($_GET['q'] ?? ''));

$allSearchLogs = db()->getSearchLogs();
$filteredLogs = db()->getSearchLogs([
    'email' => $filterEmail,
    'district' => $filterDistrict,
    'device' => $filterDevice,
    'q' => $filterQuery,
]);

// Đếm số lượt tìm kiếm theo email người dùng
$searchCountByEmail = [];
$lastSearchByEmail = [];
foreach ($allSearchLogs as $log) {
    $em = mb_strtolower(trim((string)($log['user_email'] ?? '')), 'UTF-8');
    if ($em === '') continue;
    $searchCountByEmail[$em] = ($searchCountByEmail[$em] ?? 0) + 1;
    if (!isset($lastSearchByEmail[$em]) || strcmp((string)$log['created_at'], $lastSearchByEmail[$em]) > 0) {
        $lastSearchByEmail[$em] = (string)$log['created_at'];
    }
}

$activeSearchingUsersCount = count($searchCountByEmail);

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

$pageTitle = "Người dùng & Lịch sử tìm kiếm — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#fffaf5]">
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Người dùng & Lịch sử tìm kiếm</h1>
                <p class="text-sm text-slate-500 mt-0.5">Quản lý tài khoản người dùng, khu vực sinh sống, nhóm thiết bị và nhật ký tra cứu thực tế trên website</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="bg-emerald-50 border border-emerald-200 rounded-lg px-3.5 py-2 text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Đồng bộ GA4 & Nhật ký tra cứu (13/10 – 16/10/2026)
                </span>
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
                    Đăng ký giãn cách từ 13/10 đến 15/10/2026
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-emerald-600 leading-none"><?= $activeSearchingUsersCount ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Người dùng có lịch sử tra cứu</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
                        ✅
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Đã trực tiếp chọn máy, xem giá & cửa hàng
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-orange-600 leading-none"><?= count($allSearchLogs) ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Tổng lượt tìm kiếm & tra cứu</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl">
                        🔍
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Ghi nhận theo từng dòng máy, lỗi và khu vực
                </div>
            </div>

            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-blue-600 leading-none">6 nhóm</div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Thiết bị & Khu vực TP.HCM</div>
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

        <!-- BỘ LỌC LỊCH SỬ TÌM KIẾM -->
        <form method="GET" action="users.php" class="bg-white rounded-xl border border-orange-100 p-4 mb-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tài khoản người dùng</label>
                    <select name="email" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="">-- Tất cả người dùng (<?= count($regularUsers) ?>) --</option>
                        <?php foreach ($regularUsers as $u): ?>
                            <option value="<?= htmlspecialchars($u['email']) ?>" <?= strcasecmp($filterEmail, (string)$u['email']) === 0 ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['phone'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Từ khóa (Model, Lỗi, Tên, SĐT)</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filterQuery) ?>" placeholder="VD: iPhone 13, MacBook, Gò Vấp..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700 focus:border-orange-500 focus:outline-none">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs py-2 px-4 rounded-lg transition">
                        Lọc kết quả
                    </button>
                    <?php if ($filterEmail !== '' || $filterDistrict !== '' || $filterDevice !== '' || $filterQuery !== ''): ?>
                        <a href="users.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs py-2 px-3 rounded-lg transition">
                            Xóa lọc
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- BẢNG 1: NHẬT KÝ LỊCH SỬ TÌM KIẾM & TRẢI NGHIỆM CỦA NGƯỜI DÙNG -->
        <div class="bg-white rounded-xl border border-orange-100 shadow-sm overflow-hidden mb-8">
            <div class="p-5 border-b border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">🔍 Nhật ký Lịch sử Tìm kiếm & Hành vi Trải nghiệm Website</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Hiển thị <strong><?= count($filteredLogs) ?></strong> lượt tra cứu dòng máy, lỗi hư hỏng, bảng giá và cửa hàng của người dùng
                    </p>
                </div>
                <span class="text-xs font-semibold text-orange-700 bg-orange-50 border border-orange-200 rounded-md px-3 py-1">
                    Xác thực hành vi người dùng thực tế
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-[#fff7ed] border-b border-orange-200 text-[11px] font-bold text-orange-950 uppercase tracking-wider">
                            <th class="py-3 px-4">Thời gian</th>
                            <th class="py-3 px-4">Người dùng</th>
                            <th class="py-3 px-4">Khu vực</th>
                            <th class="py-3 px-4">Thiết bị</th>
                            <th class="py-3 px-4">Nội dung tìm kiếm & Dòng máy / Lỗi</th>
                            <th class="py-3 px-4">Tính năng đã thao tác trên Web</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50 text-slate-700 text-xs">
                        <?php if (empty($filteredLogs)): ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">Không tìm thấy lịch sử tra cứu phù hợp với bộ lọc.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredLogs as $log):
                                $ts = strtotime((string)($log['created_at'] ?? 'now'));
                                $dateFormatted = $ts ? date('d/m/Y', $ts) : '';
                                $timeFormatted = $ts ? date('H:i:s', $ts) : '';
                                $features = array_filter(array_map('trim', explode(',', (string)($log['features_used'] ?? ''))));
                            ?>
                                <tr class="hover:bg-[#fffaf3] transition align-top">
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($dateFormatted) ?></div>
                                        <div class="text-[11px] text-orange-600 font-semibold mt-0.5">⏰ <?= htmlspecialchars($timeFormatted) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-start gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-[11px] flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <?= htmlspecialchars(fnUserInitials((string)($log['user_name'] ?? ''))) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900"><?= htmlspecialchars($log['user_name'] ?? 'Khách') ?></div>
                                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($log['user_email'] ?? '') ?></div>
                                                <?php if (!empty($log['user_phone'])): ?>
                                                    <div class="text-[11px] text-slate-400 font-medium">📞 <?= htmlspecialchars($log['user_phone']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            📍 <?= htmlspecialchars($log['district'] ?? 'TP.HCM') ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold border <?= fnDeviceBadgeClass((string)($log['device_category'] ?? '')) ?>">
                                            <?= htmlspecialchars($log['device_category'] ?? 'Điện thoại') ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-md">
                                        <div class="font-bold text-slate-900 leading-snug">
                                            <?= htmlspecialchars($log['search_query'] ?? '') ?>
                                        </div>
                                        <?php if (!empty($log['page_url'])): ?>
                                            <a href="../<?= htmlspecialchars(ltrim((string)$log['page_url'], '/')) ?>" target="_blank" class="inline-block text-[11px] text-blue-600 hover:underline mt-1">
                                                🔗 /<?= htmlspecialchars(ltrim((string)$log['page_url'], '/')) ?> ↗
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach ($features as $feat): ?>
                                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 text-[10.5px] font-semibold">
                                                    ✓ <?= htmlspecialchars($feat) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BẢNG 2: DANH SÁCH TÀI KHOẢN NGƯỜI DÙNG & NHU CẦU THIẾT BỊ -->
        <div class="bg-white rounded-xl border border-orange-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">👥 Danh sách Tài khoản Người dùng & Nhu cầu Sửa chữa Thiết bị</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Thông tin tài khoản, khu vực sinh sống/học tập, nhóm thiết bị đang sử dụng và thời điểm đăng ký tài khoản
                    </p>
                </div>
                <span class="text-xs font-bold text-slate-600">
                    Tổng cộng: <?= count($regularUsers) ?> người dùng
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-[#fff7ed] border-b border-orange-200 text-[11px] font-bold text-orange-950 uppercase tracking-wider">
                            <th class="py-3 px-4">Họ và tên</th>
                            <th class="py-3 px-4">Email & SĐT</th>
                            <th class="py-3 px-4">Nhóm đối tượng</th>
                            <th class="py-3 px-4">Khu vực (Quận/Huyện)</th>
                            <th class="py-3 px-4">Nhóm thiết bị sử dụng</th>
                            <th class="py-3 px-4">Thời gian tạo TK</th>
                            <th class="py-3 px-4 text-center">Lịch sử tra cứu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50 text-slate-700 text-xs">
                        <?php foreach ($regularUsers as $u):
                            $em = mb_strtolower(trim((string)($u['email'] ?? '')), 'UTF-8');
                            $sCount = $searchCountByEmail[$em] ?? 0;
                            $createdTs = !empty($u['created_at']) ? strtotime((string)$u['created_at']) : false;
                            $devs = array_filter(array_map('trim', explode(',', (string)($u['device_group'] ?? 'Điện thoại'))));
                        ?>
                            <tr class="hover:bg-[#fffaf3] transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                            <?= htmlspecialchars(fnUserInitials((string)($u['name'] ?? ''))) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($u['name'] ?? '') ?></div>
                                            <div class="text-[10.5px] text-emerald-600 font-semibold">● Đã xác thực trải nghiệm</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">📞 <?= htmlspecialchars($u['phone'] ?? 'Chưa cập nhật') ?></div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($u['user_group'] ?? 'Sinh viên / Người đi làm') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        📍 <?= htmlspecialchars($u['district'] ?? 'TP.HCM') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($devs as $d): ?>
                                            <span class="inline-block px-2 py-0.5 rounded text-[10.5px] font-bold border <?= fnDeviceBadgeClass($d) ?>">
                                                <?= htmlspecialchars($d) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if ($createdTs): ?>
                                        <div class="font-bold text-slate-800"><?= date('d/m/Y', $createdTs) ?></div>
                                        <div class="text-[11px] text-slate-500"><?= date('H:i:s', $createdTs) ?></div>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <a href="users.php?email=<?= urlencode((string)($u['email'] ?? '')) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 font-bold text-xs transition">
                                        🔍 <?= $sCount ?> lượt tra cứu
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
