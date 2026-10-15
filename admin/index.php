<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/index.php");
    exit;
}

$stats = db()->getStats();
$shops = db()->getShops(['include_unverified' => true, 'include_inactive' => true]);
$sourcedShops = array_values(array_filter($shops, static fn($shop) => !empty($shop['address_verified'])));
$requests = db()->getRepairRequests();

// Đếm số model trong catalog
$totalModels = 0;
$catalogDir = __DIR__ . '/../data/catalog';
if (is_dir($catalogDir)) {
    $catFiles = glob($catalogDir . '/*/*.json');
    foreach ($catFiles as $cf) {
        $data = json_decode(file_get_contents($cf), true);
        if (is_array($data)) {
            $totalModels += count($data);
        }
    }
}

$today = new DateTimeImmutable('today');
$chartStart = $today->modify('-29 days');
$dailyRequests = [];
for ($day = 0; $day < 30; $day++) {
    $key = $chartStart->modify("+{$day} days")->format('Y-m-d');
    $dailyRequests[$key] = ['total' => 0, 'completed' => 0];
}
foreach ($requests as $request) {
    $key = substr((string)($request['created_at'] ?? ''), 0, 10);
    if (!isset($dailyRequests[$key])) continue;
    $dailyRequests[$key]['total']++;
    if (($request['status'] ?? '') === 'completed') $dailyRequests[$key]['completed']++;
}
$chartHasData = array_sum(array_column($dailyRequests, 'total')) > 0;
$chartMax = max(1, ...array_column($dailyRequests, 'total'));
$chartPoints = ['total' => [], 'completed' => []];
foreach (array_values($dailyRequests) as $index => $counts) {
    $x = round($index * 600 / 29, 1);
    foreach (['total', 'completed'] as $series) {
        $y = round(180 - ($counts[$series] * 160 / $chartMax), 1);
        $chartPoints[$series][] = $x . ',' . $y;
    }
}

// Thống kê yêu cầu & lọc danh sách chờ xử lý thực tế
$pendingReqCount = 0;
$displayPending = [];

foreach ($requests as $r) {
    if (($r['status'] ?? 'pending') === 'pending') {
        $pendingReqCount++;

        $createdTs = !empty($r['created_at']) ? strtotime($r['created_at']) : false;
        if ($createdTs === false) {
            $timeStr = 'Chưa có thời gian gửi';
            $diffHours = 0;
        } else {
        $diffMinutes = max(1, round((time() - $createdTs) / 60));
        $diffHours = round($diffMinutes / 60, 1);

        // Định dạng thời gian
        if ($diffMinutes < 60) {
            $timeStr = $diffMinutes . ' phút trước';
        } elseif ($diffHours < 24) {
            $timeStr = floor($diffHours) . ' giờ trước';
        } else {
            $timeStr = floor($diffHours / 24) . ' ngày trước';
        }
        }

        // Đánh giá mức độ ưu tiên theo SLA (thời gian chờ phản hồi) và tính cấp bách của lỗi
        $issueLower = mb_strtolower(($r['issue_type'] ?? '') . ' ' . ($r['symptom'] ?? ''));
        $isUrgent = (
            strpos($issueLower, 'vô nước') !== false || 
            strpos($issueLower, 'mất nguồn') !== false || 
            strpos($issueLower, 'sập nguồn') !== false || 
            strpos($issueLower, 'cháy') !== false
        );

        if ($diffHours >= 12 || $isUrgent) {
            $priority = 'Cao';
            $priorityClass = 'bg-red-50 text-red-600 border-red-200';
        } elseif ($diffHours >= 4 || strpos($issueLower, 'màn hình') !== false || strpos($issueLower, 'sạc') !== false || strpos($issueLower, 'pin') !== false) {
            $priority = 'Trung bình';
            $priorityClass = 'bg-amber-50 text-amber-600 border-amber-200';
        } else {
            $priority = 'Thấp';
            $priorityClass = 'bg-blue-50 text-blue-600 border-blue-200';
        }

        $displayPending[] = [
            'id' => $r['id'],
            'title' => ($r['brand_model'] ?? 'Thiết bị') . ' - ' . ($r['issue_type'] ?? 'Sửa chữa'),
            'time' => $timeStr,
            'priority' => $priority,
            'priority_class' => $priorityClass
        ];
    }
}

$pageTitle = "Tổng quan — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị (Giữ nguyên không thay đổi) -->
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#fffaf5]">
        <!-- PAGE TITLE & DATE FILTER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Tổng quan</h1>
                <p class="text-sm text-slate-500 mt-0.5">Cập nhật tình hình hoạt động của FixNear trong thời gian qua</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="bg-white border border-orange-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-700">Dữ liệu đến <?= htmlspecialchars($today->format('d/m/Y')) ?></span>
            </div>
        </div>

        <!-- 4 METRIC STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Card 1: Cửa hàng -->
            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm hover:shadow-md hover:border-orange-200 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= (int)$stats['total_shops'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Cửa hàng</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500"><?= count($sourcedShops) ?> cửa hàng có địa chỉ đối chiếu</div>
            </div>

            <!-- Card 2: Khu vực -->
            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm hover:shadow-md hover:border-orange-200 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= (int)$stats['total_districts'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Khu vực</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">Tính từ danh sách cửa hàng hiện có</div>
            </div>

            <!-- Card 3: Model -->
            <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm hover:shadow-md hover:border-orange-200 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= $totalModels ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Model</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">Tính từ danh mục thiết bị hiện có</div>
            </div>

            <!-- Card 4: Yêu cầu chờ xử lý -->
            <div class="bg-white rounded-xl border border-orange-200 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-orange-600 leading-none"><?= $pendingReqCount ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Yêu cầu chờ xử lý</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-500 text-white shadow-sm shadow-orange-500/20 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 flex items-center text-xs">
                    <span class="text-orange-600 font-bold flex items-center gap-0.5">
                        <?= $pendingReqCount ?>
                    </span>
                    <span class="text-slate-400 ml-1.5">yêu cầu cần tiếp nhận</span>
                </div>
            </div>
        </div>

        <!-- MIDDLE SECTION: CHART (65%) & PENDING REQUESTS LIST (35%) -->
        <div class="bg-white rounded-xl border border-orange-100 px-5 py-3 mb-6 text-sm text-slate-600 flex flex-wrap items-center gap-x-6 gap-y-1" aria-label="Số lượng dữ liệu hiện có">
            <a href="users.php" class="hover:text-orange-600 transition">Tài khoản: <strong><?= (int)($stats['total_users'] ?? 0) ?></strong></a>
            <a href="users.php" class="hover:text-orange-600 transition">Lượt tra cứu: <strong><?= (int)($stats['total_searches'] ?? 0) ?></strong></a>
            <span>Yêu cầu: <strong><?= (int)($stats['total_requests'] ?? 0) ?></strong></span>
            <span>Đánh giá: <strong><?= (int)($stats['total_reviews'] ?? 0) ?></strong></span>
            <span>Báo cáo: <strong><?= (int)($stats['total_reports'] ?? 0) ?></strong></span>
            <span>Liên hệ: <strong><?= (int)($stats['total_contacts'] ?? 0) ?></strong></span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- LEFT: CHART BÁO CÁO YÊU CẦU -->
            <div class="lg:col-span-8 bg-white rounded-xl border border-orange-100 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Lượt yêu cầu sửa chữa</h2>
                        <div class="flex items-center gap-4 mt-2">
                            <div class="flex items-center gap-1.5 text-xs text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-500 inline-block"></span>
                                Tổng yêu cầu
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-300 inline-block"></span>
                                Yêu cầu hoàn thành
                            </div>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">30 ngày gần nhất</span>
                </div>

                <?php if (!$chartHasData): ?>
                    <div class="h-64 flex items-center justify-center rounded-xl bg-orange-50/40 text-sm text-slate-500">Chưa có yêu cầu sửa chữa trong 30 ngày gần nhất.</div>
                <?php else: ?>
                    <div class="relative h-64 w-full">
                        <svg class="w-full h-[220px]" viewBox="0 0 600 200" preserveAspectRatio="none" role="img" aria-label="Biểu đồ số yêu cầu sửa chữa thực tế trong 30 ngày">
                            <line x1="0" y1="180" x2="600" y2="180" stroke="#fdba74" stroke-width="1" />
                            <polyline points="<?= htmlspecialchars(implode(' ', $chartPoints['total'])) ?>" fill="none" stroke="#ea580c" stroke-width="3" />
                            <polyline points="<?= htmlspecialchars(implode(' ', $chartPoints['completed'])) ?>" fill="none" stroke="#fdba74" stroke-width="2.5" />
                        </svg>
                        <div class="flex justify-between text-xs font-semibold text-slate-400"><span><?= $chartStart->format('d/m') ?></span><span><?= $today->format('d/m') ?></span></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: YÊU CẦU CHỜ XỬ LÝ -->
            <div class="lg:col-span-4 bg-white rounded-xl border border-orange-100 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-bold text-slate-900">Yêu cầu chờ xử lý</h2>
                        <a href="requests.php" class="text-xs font-bold text-orange-600 hover:text-orange-700">Xem tất cả</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($displayPending)): ?>
                            <div class="py-10 text-center text-slate-400">
                                <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div class="text-xs font-semibold text-slate-600">Hiện không có yêu cầu nào chờ xử lý</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Tất cả yêu cầu đã được phản hồi hoặc giải quyết</div>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($displayPending, 0, 4) as $item): ?>
                                <div class="p-3 rounded-lg border border-orange-100 bg-[#fffaf5] hover:bg-orange-50/70 hover:border-orange-200 transition flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-extrabold text-slate-800"><?= htmlspecialchars($item['id']) ?></span>
                                            <span class="text-[10px] font-semibold text-slate-400">• <?= htmlspecialchars($item['time']) ?></span>
                                        </div>
                                        <div class="text-xs font-medium text-slate-600 mt-1 truncate" title="<?= htmlspecialchars($item['title']) ?>">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border <?= $item['priority_class'] ?>">
                                        <?= htmlspecialchars($item['priority']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-center">
                    <a href="requests.php" class="inline-block w-full py-2 bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-bold rounded-lg border border-orange-200 transition">
                        Đi tới danh sách yêu cầu &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- BOTTOM SECTION: DANH SÁCH CỬA HÀNG TRÊN HỆ THỐNG -->
        <div class="bg-white rounded-xl border border-orange-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-orange-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Cửa hàng trên hệ thống</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Danh sách các cửa hàng đang được quản lý trên FixNear</p>
                </div>
                <a href="shops.php" class="text-xs font-bold text-orange-600 hover:text-orange-700">Xem tất cả cửa hàng &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-[#fff7ed] border-b border-orange-200 text-[11px] font-bold text-orange-950 uppercase tracking-wider">
                            <th class="py-3 px-5">Cửa hàng</th>
                            <th class="py-3 px-5">Khu vực</th>
                            <th class="py-3 px-5">Hotline</th>
                            <th class="py-3 px-5">Trạng thái</th>
                            <th class="py-3 px-5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50 text-slate-700 text-xs">
                        <?php 
                        $recentShops = array_slice($shops, 0, 5);
                        foreach ($recentShops as $s): 
                            $isActive = (isset($s['is_active']) ? (int)$s['is_active'] : 1) === 1;
                        ?>
                            <tr class="hover:bg-[#fffaf3] transition">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            🏪
                                        </div>
                                        <div>
                                            <a href="../shop_detail.php?id=<?= $s['id'] ?>" target="_blank" class="font-bold text-slate-900 hover:text-orange-600 transition">
                                                <?= htmlspecialchars($s['name']) ?>
                                            </a>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($s['address']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 font-medium text-slate-600">
                                    <?= htmlspecialchars($s['district'] ?? 'TP.HCM') ?>
                                </td>
                                <td class="py-3.5 px-5 font-medium text-slate-600">
                                    <?= htmlspecialchars($s['phone'] ?? 'Chưa có SĐT') ?>
                                </td>
                                <td class="py-3.5 px-5">
                                    <?php if ($isActive): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Hiển thị
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Đang ẩn
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="shop_edit.php?id=<?= $s['id'] ?>" class="p-1.5 text-slate-400 hover:text-orange-600 hover:bg-orange-50 rounded-md transition" title="Chỉnh sửa">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                    </div>
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
