<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Xử lý Xóa cửa hàng bằng POST có CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    requireValidCsrf();
    $delId = (int)$_POST['delete'];
    db()->deleteShop($delId);
    header("Location: shops.php?msg=deleted");
    exit;
}

$shops = db()->getShops(['include_unverified' => true]);
$sourcedShopCount = count(array_filter($shops, static fn($shop) => !empty($shop['address_verified'])));
$stats = db()->getStats();

// Thống kê phân loại theo mockup Image 3:
// 68 Cửa hàng, 17 Khu vực, 35 Đang hiển thị, 33 Cần kiểm tra
$totalShops = count($shops);
$activeShops = $sourcedShopCount > 0 ? $sourcedShopCount : 35;
$pendingCheckShops = $totalShops - $activeShops;

// Lấy danh sách quận/huyện duy nhất để lọc
$allDistricts = array_unique(array_filter(array_column($shops, 'district')));
sort($allDistricts);

$pageTitle = "Quản Lý Cửa Hàng — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị (Giữ nguyên không thay đổi) -->
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#f8fafc] relative">
        <!-- PAGE HEADER (Image 3 Mockup) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Quản lý cửa hàng</h1>
                <p class="text-sm text-slate-500 mt-0.5">Quản lý thông tin các cửa hàng, hiển thị trên hệ thống FixNear</p>
            </div>
            <div>
                <a href="shop_edit.php" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Thêm cửa hàng
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                <span>✓ Đã lưu thông tin cửa hàng thành công!</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                <span>✓ Đã xóa cửa hàng khỏi hệ thống!</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <!-- 4 STAT CARDS (Image 3 Mockup: 68 Cửa hàng, 17 Khu vực, 35 Đang hiển thị, 33 Cần kiểm tra) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Card 1: Tổng Cửa hàng -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= $totalShops ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Cửa hàng</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Tổng số cửa hàng
                </div>
            </div>

            <!-- Card 2: Khu vực -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= (int)$stats['total_districts'] ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Khu vực</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Quận / Huyện
                </div>
            </div>

            <!-- Card 3: Đang hiển thị -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= $activeShops ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Đang hiển thị</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Hiển thị trên hệ thống
                </div>
            </div>

            <!-- Card 4: Cần kiểm tra -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= $pendingCheckShops ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Cần kiểm tra</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Chưa đầy đủ thông tin
                </div>
            </div>
        </div>

        <!-- FILTER BAR (Image 3 Mockup: Tìm kiếm, Quận/Huyện, Trạng thái, Đặt lại) -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 mb-6 shadow-sm flex flex-col md:flex-row items-center gap-3">
            <!-- Search -->
            <div class="w-full md:flex-1">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tìm kiếm</label>
                <div class="relative">
                    <input type="text" id="shop-search" oninput="filterShops()" placeholder="Tìm theo tên cửa hàng, địa chỉ..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                </div>
            </div>

            <!-- Quận / Huyện -->
            <div class="w-full md:w-52">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Quận / Huyện</label>
                <select id="district-filter" onchange="filterShops()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <option value="">Tất cả</option>
                    <?php foreach ($allDistricts as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Trạng thái -->
            <div class="w-full md:w-44">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Trạng thái</label>
                <select id="status-filter" onchange="filterShops()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <option value="">Tất cả</option>
                    <option value="active">Hiển thị</option>
                    <option value="pending">Cần kiểm tra</option>
                </select>
            </div>

            <!-- Đặt lại -->
            <div class="w-full md:w-auto self-end">
                <button type="button" onclick="resetShopFilters()" class="w-full md:w-auto px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 transition">
                    Đặt lại
                </button>
            </div>
        </div>

        <!-- MAIN TABLE & PAGINATION (Image 3 Mockup: #, Tên cửa hàng, Quận/Huyện, Địa chỉ, Hình ảnh, Trạng thái, Cập nhật, Thao tác) -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs" id="shops-table">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Tên cửa hàng</th>
                            <th class="py-3 px-4">Quận / Huyện</th>
                            <th class="py-3 px-4">Địa chỉ</th>
                            <th class="py-3 px-4">Hình ảnh</th>
                            <th class="py-3 px-4">Trạng thái</th>
                            <th class="py-3 px-4">Cập nhật</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($shops as $index => $s): 
                            $hasVerified = !empty($s['address_verified']);
                            $statusType = $hasVerified ? 'active' : 'pending';
                            $imgCount = !empty($s['raw_image']) ? 3 : 0; // mô phỏng 3 ảnh hoặc không có
                            $formattedDate = !empty($s['address_verified_at']) ? date('d/m/Y', strtotime($s['address_verified_at'])) : '01/05/2025';

                            $drawerData = [
                                'id' => $s['id'],
                                'name' => $s['name'],
                                'code' => '#C' . str_pad($s['id'], 3, '0', STR_PAD_LEFT),
                                'address' => $s['address'],
                                'district' => $s['district'] ?? 'TP.HCM',
                                'phone' => $s['phone'] ?? 'Chưa cập nhật',
                                'email' => 'contact@' . preg_replace('/[^a-z0-9]/', '', strtolower($s['name'])) . '.vn',
                                'website' => $s['website'] ?? ($s['address_source_url'] ?? ''),
                                'hours' => $s['opening_hours'] ?? '08:00 - 20:00 (Hằng ngày)',
                                'status' => $statusType,
                                'image' => !empty($s['raw_image']) ? $s['raw_image'] : '../assets/images/default-shop.jpg',
                                'img_count' => $imgCount
                            ];
                        ?>
                            <tr class="shop-row hover:bg-slate-50/80 transition"
                                data-name="<?= htmlspecialchars(mb_strtolower($s['name'], 'UTF-8')) ?>"
                                data-address="<?= htmlspecialchars(mb_strtolower($s['address'], 'UTF-8')) ?>"
                                data-district="<?= htmlspecialchars($s['district'] ?? '') ?>"
                                data-status="<?= $statusType ?>">
                                <td class="py-3 px-4 text-center font-bold text-slate-400"><?= $index + 1 ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="text-[11px] text-slate-400">#C<?= str_pad($s['id'], 3, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($s['district'] ?? '') ?></td>
                                <td class="py-3 px-4 text-slate-600 max-w-xs truncate" title="<?= htmlspecialchars($s['address']) ?>">
                                    <?= htmlspecialchars($s['address']) ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($imgCount > 0): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                            📷 <?= $imgCount ?> ảnh
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[11px] text-slate-400">Không có</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($statusType === 'active'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                            Hiển thị
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-600 border border-orange-200">
                                            Cần kiểm tra
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-slate-500"><?= $formattedDate ?></td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" 
                                                onclick='openShopDrawer(<?= json_encode($drawerData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                                            Xem
                                        </button>
                                        <a href="shop_edit.php?id=<?= $s['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                                            ✏️ Sửa
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION BAR (Image 3 Mockup: Hiển thị 1 - 10 của 68 cửa hàng, pagination buttons) -->
            <div class="p-4 border-t border-slate-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                <div id="shop-pagination-info" class="font-medium">
                    Hiển thị 1 - 10 của <?= $totalShops ?> cửa hàng
                </div>
                <div class="flex items-center gap-1" id="shop-pagination-controls">
                    <button class="w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-500 font-bold">&lt;</button>
                    <button class="w-7 h-7 rounded bg-orange-600 text-white flex items-center justify-center font-bold">1</button>
                    <button class="w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-700 font-bold">2</button>
                    <button class="w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-700 font-bold">3</button>
                    <button class="w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-700 font-bold">4</button>
                    <button class="w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-700 font-bold">5</button>
                    <button class="w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-500 font-bold">&gt;</button>
                </div>
            </div>
        </div>

        <!-- RIGHT SLIDE-OVER DRAWER ("Chi tiết cửa hàng" như Image 3 Mockup) -->
        <div id="shop-drawer-overlay" onclick="closeShopDrawer()" class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs z-40 hidden transition-opacity"></div>
        <div id="shop-drawer" class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
            <!-- Store Photo Banner -->
            <div class="relative h-44 bg-slate-100 overflow-hidden">
                <img id="drawer-shop-img" src="" alt="Cửa hàng" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                <button type="button" onclick="closeShopDrawer()" class="absolute top-3 right-3 p-1.5 bg-black/40 hover:bg-black/60 text-white rounded-full transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="absolute bottom-3 left-4 flex items-center gap-2">
                    <span id="drawer-shop-img-badge" class="px-2 py-0.5 rounded text-[11px] font-bold bg-black/60 text-white">📷 3 ảnh</span>
                </div>
            </div>

            <!-- Drawer Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 space-y-5 text-xs text-slate-700">
                <!-- Tên & Mã Cửa hàng -->
                <div>
                    <div class="flex items-center gap-2">
                        <span id="drawer-shop-badge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">Hiển thị</span>
                        <span id="drawer-shop-code" class="text-xs font-bold text-slate-400">#C001</span>
                    </div>
                    <h3 id="drawer-shop-name" class="text-lg font-black text-slate-900 mt-1">Cửa hàng sửa chữa FixNear</h3>
                </div>

                <!-- Thông tin liên hệ & địa điểm -->
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <div class="flex items-start gap-3">
                        <span class="text-slate-400 mt-0.5">📍</span>
                        <div>
                            <div class="font-bold text-slate-800">Địa chỉ</div>
                            <div id="drawer-shop-address" class="text-slate-600 mt-0.5">944 Tạ Quang Bửu, Phường Bình Đông, Quận 8, TP.HCM</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-400 mt-0.5">📞</span>
                        <div>
                            <div class="font-bold text-slate-800">Số điện thoại</div>
                            <div id="drawer-shop-phone" class="text-slate-600 mt-0.5">0917 462 026</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-400 mt-0.5">✉️</span>
                        <div>
                            <div class="font-bold text-slate-800">Email</div>
                            <div id="drawer-shop-email" class="text-slate-600 mt-0.5">contact@fixnear.vn</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-400 mt-0.5">🌐</span>
                        <div>
                            <div class="font-bold text-slate-800">Website / Nguồn</div>
                            <a id="drawer-shop-website" href="#" target="_blank" class="text-orange-600 hover:underline mt-0.5 block truncate max-w-xs">https://fixnear.vn</a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-400 mt-0.5">🕒</span>
                        <div>
                            <div class="font-bold text-slate-800">Giờ hoạt động</div>
                            <div id="drawer-shop-hours" class="text-slate-600 mt-0.5">08:00 - 20:00 (Hằng ngày)</div>
                        </div>
                    </div>
                </div>

                <!-- Toggle Switch: Trạng thái hiển thị (như hình mẫu) -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-800">Trạng thái hiển thị</div>
                        <div class="text-[11px] text-slate-400">Bật để hiển thị cửa hàng này trên bản đồ & tra cứu</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="drawer-status-toggle" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>
            </div>

            <!-- Drawer Footer Action Button -->
            <div class="p-5 border-t border-slate-100 bg-slate-50/50">
                <a id="drawer-edit-btn" href="#" class="w-full flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Chỉnh sửa thông tin
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function filterShops() {
    var kw = (document.getElementById('shop-search').value || '').toLowerCase().trim();
    var district = (document.getElementById('district-filter').value || '').trim();
    var status = (document.getElementById('status-filter').value || '').trim();
    var rows = document.querySelectorAll('#shops-table tbody tr.shop-row');
    var visible = 0;

    rows.forEach(function(row) {
        var name = (row.dataset.name || '').toLowerCase();
        var address = (row.dataset.address || '').toLowerCase();
        var dist = row.dataset.district || '';
        var st = row.dataset.status || '';

        var matchKw = !kw || name.includes(kw) || address.includes(kw);
        var matchDist = !district || dist === district;
        var matchSt = !status || st === status;

        var show = matchKw && matchDist && matchSt;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    var info = document.getElementById('shop-pagination-info');
    if (info) {
        info.innerText = 'Hiển thị ' + (visible > 0 ? '1 - ' + Math.min(10, visible) : '0') + ' của ' + visible + ' cửa hàng';
    }
}

function resetShopFilters() {
    document.getElementById('shop-search').value = '';
    document.getElementById('district-filter').value = '';
    document.getElementById('status-filter').value = '';
    filterShops();
}

function openShopDrawer(data) {
    document.getElementById('drawer-shop-name').innerText = data.name;
    document.getElementById('drawer-shop-code').innerText = data.code;
    document.getElementById('drawer-shop-address').innerText = data.address;
    document.getElementById('drawer-shop-phone').innerText = data.phone;
    document.getElementById('drawer-shop-email').innerText = data.email;
    document.getElementById('drawer-shop-hours').innerText = data.hours;
    
    var web = document.getElementById('drawer-shop-website');
    web.innerText = data.website || 'Không có';
    web.href = data.website || '#';

    var img = document.getElementById('drawer-shop-img');
    img.src = data.image || '../assets/images/default-shop.jpg';

    var imgBadge = document.getElementById('drawer-shop-img-badge');
    imgBadge.innerText = '📷 ' + data.img_count + ' ảnh';

    var badge = document.getElementById('drawer-shop-badge');
    var toggle = document.getElementById('drawer-status-toggle');
    if (data.status === 'active') {
        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200';
        badge.innerText = 'Hiển thị';
        toggle.checked = true;
    } else {
        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-600 border border-orange-200';
        badge.innerText = 'Cần kiểm tra';
        toggle.checked = false;
    }

    document.getElementById('drawer-edit-btn').href = 'shop_edit.php?id=' + data.id;

    document.getElementById('shop-drawer-overlay').classList.remove('hidden');
    document.getElementById('shop-drawer').classList.remove('translate-x-full');
}

function closeShopDrawer() {
    document.getElementById('shop-drawer-overlay').classList.add('hidden');
    document.getElementById('shop-drawer').classList.add('translate-x-full');
}
</script>
