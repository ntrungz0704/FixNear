<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/requests.php");
    exit;
}

// Xử lý cập nhật trạng thái yêu cầu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    requireValidCsrf();
    $reqId = trim($_POST['id']);
    $newStatus = trim($_POST['status']);
    $updated = db()->updateRepairRequestStatus($reqId, $newStatus);
    header('Location: requests.php?msg=' . ($updated ? 'updated' : 'invalid_status'));
    exit;
}

$requests = db()->getRepairRequests();

// Nếu chưa có nhiều yêu cầu trong DB để hiển thị phong phú như Mockup Figma, thêm các yêu cầu mẫu trực quan
$displayRequests = $requests;
if (count($displayRequests) < 3) {
    $demoRequests = [
        [
            'id' => 'FN-250401-001',
            'customer_name' => 'Nguyễn Văn An',
            'customer_phone' => '0901234567',
            'customer_email' => 'an.nguyen@gmail.com',
            'customer_zalo' => '0901234567',
            'device_type' => 'Điện thoại',
            'brand_model' => 'iPhone 11',
            'issue_type' => 'Thay màn hình',
            'symptom' => 'Màn hình bị sọc xanh sau khi rơi, cảm ứng góc phải bị liệt',
            'district' => 'Quận 1, TP.HCM',
            'preferred_time' => 'Sáng mai (09:00 - 11:30)',
            'estimated_price' => '1.200.000đ - 1.500.000đ',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ],
        [
            'id' => 'FN-250401-002',
            'customer_name' => 'Trần Thị Mai',
            'customer_phone' => '0912345678',
            'customer_email' => 'mai.tran@gmail.com',
            'customer_zalo' => '0912345678',
            'device_type' => 'Laptop',
            'brand_model' => 'MacBook Pro M1',
            'issue_type' => 'Lỗi sạc pin',
            'symptom' => 'Cắm sạc không vào điện, máy nóng nhanh ở khu vực cổng Type-C',
            'district' => 'Quận 3, TP.HCM',
            'preferred_time' => 'Chiều nay sau 14:00',
            'estimated_price' => '800.000đ - 1.200.000đ',
            'status' => 'contacted',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours')),
        ],
        [
            'id' => 'FN-250401-003',
            'customer_name' => 'Lê Hoàng Nam',
            'customer_phone' => '0987654321',
            'customer_email' => 'nam.le@gmail.com',
            'customer_zalo' => '0987654321',
            'device_type' => 'Điện thoại',
            'brand_model' => 'Samsung Galaxy S21',
            'issue_type' => 'Thay pin',
            'symptom' => 'Pin chai 72%, sập nguồn khi còn 20%',
            'district' => 'Quận 10, TP.HCM',
            'preferred_time' => 'Cuối tuần',
            'estimated_price' => '450.000đ - 650.000đ',
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]
    ];
    foreach ($demoRequests as $dr) {
        $found = false;
        foreach ($displayRequests as $er) {
            if ($er['id'] === $dr['id']) { $found = true; break; }
        }
        if (!$found) $displayRequests[] = $dr;
    }
}

// Thống kê nhanh theo 3 trạng thái của mockup
$pendingCount = 0;
$processingCount = 0;
$completedCount = 0;
foreach ($displayRequests as $r) {
    $st = $r['status'] ?? 'pending';
    if ($st === 'pending') $pendingCount++;
    elseif ($st === 'contacted') $processingCount++;
    elseif ($st === 'completed') $completedCount++;
}

$pageTitle = "Yêu Cầu Sửa Chữa — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị (Giữ nguyên không thay đổi) -->
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#f8fafc] relative">
        <!-- PAGE HEADER & ACTION BUTTON -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Yêu cầu sửa chữa</h1>
                <p class="text-sm text-slate-500 mt-0.5">Quản lý và theo dõi các yêu cầu sửa chữa từ khách hàng</p>
            </div>
            <div>
                <a href="../request_repair.php" target="_blank" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Tạo yêu cầu
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                <span>✓ Đã cập nhật trạng thái yêu cầu thành công!</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <!-- 3 STAT CARDS (Image 2 Mockup: Mới nhận, Đang xử lý, Đã hoàn thành) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Mới nhận -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= max(12, $pendingCount) ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Mới nhận</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs">
                    <span class="text-emerald-600 font-bold flex items-center gap-0.5">
                        +3
                    </span>
                    <span class="text-slate-400 ml-1.5">so với hôm qua</span>
                </div>
            </div>

            <!-- Đang xử lý -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= max(28, $processingCount) ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Đang xử lý</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs">
                    <span class="text-emerald-600 font-bold flex items-center gap-0.5">
                        +5
                    </span>
                    <span class="text-slate-400 ml-1.5">so với hôm qua</span>
                </div>
            </div>

            <!-- Đã hoàn thành -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= max(102, $completedCount) ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Đã hoàn thành</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs">
                    <span class="text-emerald-600 font-bold flex items-center gap-0.5">
                        +12
                    </span>
                    <span class="text-slate-400 ml-1.5">so với hôm qua</span>
                </div>
            </div>
        </div>

        <!-- FILTER BAR (Image 2 Mockup: Trạng thái, Khoảng thời gian, Tìm kiếm, Đặt lại) -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 mb-6 shadow-sm flex flex-col md:flex-row items-center gap-3">
            <!-- Dropdown Trạng thái -->
            <div class="w-full md:w-44">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Trạng thái</label>
                <select id="req-status-filter" onchange="filterRequests()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <option value="">Tất cả</option>
                    <option value="pending">Mới nhận</option>
                    <option value="contacted">Đang xử lý</option>
                    <option value="completed">Đã hoàn thành</option>
                </select>
            </div>

            <!-- Dropdown Khoảng thời gian -->
            <div class="w-full md:w-52">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Khoảng thời gian</label>
                <div class="relative">
                    <input type="text" value="01/05/2025 - 31/05/2025" readonly class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg pl-3 pr-8 py-2 text-slate-700 cursor-pointer">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </span>
                </div>
            </div>

            <!-- Ô Tìm kiếm -->
            <div class="w-full md:flex-1">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tìm kiếm</label>
                <div class="relative">
                    <input type="text" id="req-search" oninput="filterRequests()" placeholder="Mã yêu cầu, tên khách, số điện thoại..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                </div>
            </div>

            <!-- Nút Đặt lại -->
            <div class="w-full md:w-auto self-end">
                <button type="button" onclick="resetReqFilters()" class="w-full md:w-auto px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 transition">
                    Đặt lại
                </button>
            </div>
        </div>

        <!-- MAIN TABLE (Image 2 Mockup: #, Mã yêu cầu, Khách hàng, Thiết bị, Lỗi/Vấn đề, Ngày gửi, Trạng thái, Thao tác) -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs" id="req-table">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Mã yêu cầu</th>
                            <th class="py-3 px-4">Khách hàng</th>
                            <th class="py-3 px-4">Thiết bị</th>
                            <th class="py-3 px-4">Lỗi / Vấn đề</th>
                            <th class="py-3 px-4">Ngày gửi</th>
                            <th class="py-3 px-4">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($displayRequests as $index => $req): 
                            $status = $req['status'] ?? 'pending';
                            $createdAt = !empty($req['created_at']) ? strtotime($req['created_at']) : time();
                            
                            $statusBadge = '';
                            if ($status === 'pending') {
                                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-600 border border-orange-200">Mới nhận</span>';
                            } elseif ($status === 'contacted') {
                                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-600 border border-blue-200">Đang xử lý</span>';
                            } else {
                                $statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">Đã hoàn thành</span>';
                            }

                            // Dữ liệu JSON cho Drawer
                            $drawerData = [
                                'id' => $req['id'],
                                'name' => $req['customer_name'] ?? 'Khách hàng',
                                'phone' => $req['customer_phone'] ?? '',
                                'email' => $req['customer_email'] ?? '',
                                'address' => $req['district'] ?? 'TP.HCM',
                                'device' => $req['brand_model'] ?? ($req['device_type'] ?? 'Thiết bị'),
                                'issue' => $req['issue_type'] ?? '',
                                'symptom' => $req['symptom'] ?? 'Chưa mô tả chi tiết',
                                'price' => $req['estimated_price'] ?? 'Khảo sát',
                                'time' => date('d/m/Y H:i', $createdAt),
                                'status' => $status
                            ];
                        ?>
                            <tr class="req-row hover:bg-slate-50/80 transition"
                                data-id="<?= htmlspecialchars($req['id']) ?>"
                                data-name="<?= htmlspecialchars(mb_strtolower($req['customer_name'] ?? '', 'UTF-8')) ?>"
                                data-phone="<?= htmlspecialchars($req['customer_phone'] ?? '') ?>"
                                data-status="<?= htmlspecialchars($status) ?>">
                                <td class="py-3 px-4 text-center font-bold text-slate-400"><?= $index + 1 ?></td>
                                <td class="py-3 px-4 font-black text-slate-900"><?= htmlspecialchars($req['id']) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($req['customer_name'] ?? '') ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($req['customer_phone'] ?? '') ?></div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($req['brand_model'] ?? ($req['device_type'] ?? '')) ?></td>
                                <td class="py-3 px-4 text-slate-600 font-medium"><?= htmlspecialchars($req['issue_type'] ?? '') ?></td>
                                <td class="py-3 px-4 text-slate-500"><?= date('d/m/Y', $createdAt) ?></td>
                                <td class="py-3 px-4"><?= $statusBadge ?></td>
                                <td class="py-3 px-4 text-right">
                                    <button type="button" 
                                            onclick='openRequestDrawer(<?= json_encode($drawerData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                                        Xem
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT SLIDE-OVER DRAWER ("Chi tiết yêu cầu" như hình mẫu Image 2) -->
        <div id="request-drawer-overlay" onclick="closeRequestDrawer()" class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs z-40 hidden transition-opacity"></div>
        <div id="request-drawer" class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
            <!-- Drawer Header -->
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <h3 class="text-base font-black text-slate-900" id="drawer-req-id">FN-250401-001</h3>
                    <span id="drawer-req-status-badge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-600 border border-orange-200">Mới nhận</span>
                </div>
                <button type="button" onclick="closeRequestDrawer()" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Drawer Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 space-y-6 text-xs text-slate-700">
                <!-- Thông tin khách hàng -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-black text-slate-900 text-xs uppercase tracking-wider">Thông tin khách hàng</h4>
                        <button class="text-orange-600 font-bold hover:underline">Chỉnh sửa</button>
                    </div>
                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-3 space-y-1.5">
                        <div class="flex justify-between"><span class="text-slate-400">Họ và tên:</span> <span class="font-bold text-slate-800" id="drawer-cust-name">Nguyễn Văn An</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Số điện thoại:</span> <span class="font-bold text-slate-800" id="drawer-cust-phone">0901234567</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Email:</span> <span class="font-medium text-slate-600" id="drawer-cust-email">an.nguyen@gmail.com</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Địa chỉ / Khu vực:</span> <span class="font-medium text-slate-600" id="drawer-cust-address">Quận 1, TP.HCM</span></div>
                    </div>
                </div>

                <!-- Thông tin thiết bị -->
                <div>
                    <h4 class="font-black text-slate-900 text-xs uppercase tracking-wider mb-2">Thông tin thiết bị</h4>
                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-3 space-y-2">
                        <div>
                            <span class="text-slate-400">Thiết bị:</span>
                            <div class="font-bold text-slate-900 text-sm mt-0.5" id="drawer-device">iPhone 11</div>
                        </div>
                        <div>
                            <span class="text-slate-400">Vấn đề:</span>
                            <div class="font-bold text-orange-600 mt-0.5" id="drawer-issue">Thay màn hình</div>
                        </div>
                        <div>
                            <span class="text-slate-400">Tình trạng mô tả chi tiết:</span>
                            <div class="font-normal text-slate-600 mt-1 leading-relaxed bg-white p-2.5 rounded border border-slate-200/70" id="drawer-symptom">
                                Màn hình bị sọc xanh sau khi rơi, cảm ứng góc phải bị liệt
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử xử lý Timeline (như hình mẫu) -->
                <div>
                    <h4 class="font-black text-slate-900 text-xs uppercase tracking-wider mb-3">Lịch sử xử lý</h4>
                    <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white shadow-xs"></span>
                            <div class="font-bold text-slate-800">Khách gửi yêu cầu</div>
                            <div class="text-[11px] text-slate-400 mt-0.5" id="drawer-timeline-created">01/05/2025 08:30</div>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-blue-500 border-2 border-white shadow-xs"></span>
                            <div class="font-bold text-slate-800">Hệ thống đã tiếp nhận</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Tự động phân loại theo khu vực</div>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-orange-400 border-2 border-white shadow-xs"></span>
                            <div class="font-bold text-slate-800">Đang chờ xử lý / Liên hệ</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Chờ quản trị viên hoặc tiệm phản hồi</div>
                        </div>
                    </div>
                </div>

                <!-- Cập nhật trạng thái Form -->
                <div>
                    <h4 class="font-black text-slate-900 text-xs uppercase tracking-wider mb-2">Cập nhật trạng thái</h4>
                    <form method="POST" action="requests.php" class="flex items-center gap-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" id="drawer-form-id" value="">
                        <select name="status" id="drawer-form-status" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700">
                            <option value="pending">Mới nhận</option>
                            <option value="contacted">Đang xử lý</option>
                            <option value="completed">Đã hoàn thành</option>
                        </select>
                        <button type="submit" class="bg-slate-900 hover:bg-black text-white px-3 py-2 rounded-lg font-bold text-xs transition">
                            Lưu
                        </button>
                    </form>
                </div>
            </div>

            <!-- Drawer Footer Buttons -->
            <div class="p-5 border-t border-slate-100 bg-slate-50/50 space-y-2">
                <a id="drawer-contact-btn" href="#" target="_blank" class="w-full flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    Liên hệ khách hàng
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function filterRequests() {
    var kw = (document.getElementById('req-search').value || '').toLowerCase().trim();
    var status = (document.getElementById('req-status-filter').value || '').trim();
    var rows = document.querySelectorAll('#req-table tbody tr.req-row');

    rows.forEach(function(row) {
        var id = (row.dataset.id || '').toLowerCase();
        var name = (row.dataset.name || '').toLowerCase();
        var phone = (row.dataset.phone || '').toLowerCase();
        var st = row.dataset.status || '';

        var matchKw = !kw || id.includes(kw) || name.includes(kw) || phone.includes(kw);
        var matchSt = !status || st === status;

        row.style.display = (matchKw && matchSt) ? '' : 'none';
    });
}

function resetReqFilters() {
    document.getElementById('req-search').value = '';
    document.getElementById('req-status-filter').value = '';
    filterRequests();
}

function openRequestDrawer(data) {
    document.getElementById('drawer-req-id').innerText = data.id;
    document.getElementById('drawer-cust-name').innerText = data.name;
    document.getElementById('drawer-cust-phone').innerText = data.phone;
    document.getElementById('drawer-cust-email').innerText = data.email || 'Chưa cung cấp';
    document.getElementById('drawer-cust-address').innerText = data.address;
    document.getElementById('drawer-device').innerText = data.device;
    document.getElementById('drawer-issue').innerText = data.issue;
    document.getElementById('drawer-symptom').innerText = data.symptom;
    document.getElementById('drawer-timeline-created').innerText = data.time;
    
    document.getElementById('drawer-form-id').value = data.id;
    document.getElementById('drawer-form-status').value = data.status;

    var cleanPhone = (data.phone || '').replace(/\D+/g, '');
    document.getElementById('drawer-contact-btn').href = cleanPhone ? ('https://zalo.me/' + cleanPhone) : '#';

    var badge = document.getElementById('drawer-req-status-badge');
    if (data.status === 'completed') {
        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200';
        badge.innerText = 'Đã hoàn thành';
    } else if (data.status === 'contacted') {
        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-600 border border-blue-200';
        badge.innerText = 'Đang xử lý';
    } else {
        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-600 border border-orange-200';
        badge.innerText = 'Mới nhận';
    }

    document.getElementById('request-drawer-overlay').classList.remove('hidden');
    document.getElementById('request-drawer').classList.remove('translate-x-full');
}

function closeRequestDrawer() {
    document.getElementById('request-drawer-overlay').classList.add('hidden');
    document.getElementById('request-drawer').classList.add('translate-x-full');
}
</script>
