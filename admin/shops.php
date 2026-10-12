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

// Xử lý Bật/Tắt trạng thái hiển thị bằng POST (hoặc AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    requireValidCsrf();
    $shopId = (int)($_POST['shop_id'] ?? 0);
    $res = db()->toggleShopStatus($shopId);
    
    // Nếu gọi qua fetch / AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $res !== false, 'is_active' => $res]);
        exit;
    }
    
    header("Location: shops.php?msg=status_updated");
    exit;
}

$shops = db()->getShops(['include_unverified' => true]);
$totalShops = count($shops);

$activeCount = 0;
foreach ($shops as $s) {
    if ((isset($s['is_active']) ? (int)$s['is_active'] : 1) === 1) {
        $activeCount++;
    }
}
$hiddenCount = $totalShops - $activeCount;

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
        <!-- PAGE HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Quản lý cửa hàng</h1>
                <p class="text-sm text-slate-500 mt-0.5">Quản lý thông tin và bật/tắt hiển thị các cửa hàng trên hệ thống FixNear</p>
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
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'status_updated'): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                <span>✓ Đã cập nhật trạng thái hiển thị của cửa hàng!</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700 font-bold">&times;</button>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                <span>✓ Đã xóa cửa hàng khỏi hệ thống!</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <!-- 4 STAT CARDS -->
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
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= count($allDistricts) ?></div>
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
                        <div class="text-3xl font-black text-emerald-600 leading-none" id="stat-active-count"><?= $activeCount ?></div>
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

            <!-- Card 4: Đang ẩn -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-amber-600 leading-none" id="stat-hidden-count"><?= $hiddenCount ?></div>
                        <div class="text-xs font-bold text-slate-500 mt-2">Đang ẩn</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Tắt hiển thị tạm thời
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 mb-6 shadow-sm flex flex-col md:flex-row items-center gap-3">
            <!-- Search -->
            <div class="w-full md:flex-1">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Tìm kiếm</label>
                <div class="relative">
                    <input type="text" id="shop-search" oninput="applyFilters()" placeholder="Tìm theo tên cửa hàng, địa chỉ..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                </div>
            </div>

            <!-- Quận / Huyện -->
            <div class="w-full md:w-52">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Quận / Huyện</label>
                <select id="district-filter" onchange="applyFilters()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <option value="">Tất cả</option>
                    <?php foreach ($allDistricts as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Trạng thái -->
            <div class="w-full md:w-44">
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Trạng thái</label>
                <select id="status-filter" onchange="applyFilters()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <option value="">Tất cả</option>
                    <option value="1">Đang hiển thị</option>
                    <option value="0">Đang ẩn</option>
                </select>
            </div>

            <!-- Đặt lại -->
            <div class="w-full md:w-auto self-end">
                <button type="button" onclick="resetShopFilters()" class="w-full md:w-auto px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 transition">
                    Đặt lại
                </button>
            </div>
        </div>

        <!-- MAIN TABLE (Tối đa 10 hàng mỗi trang, cột trạng thái là nút bật/tắt, không còn cột xem) -->
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
                            <th class="py-3 px-4 text-center">Trạng thái (Bật/Tắt)</th>
                            <th class="py-3 px-4">Cập nhật</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($shops as $index => $s): 
                            $isActive = (isset($s['is_active']) ? (int)$s['is_active'] : 1) === 1;
                            $imgCount = !empty($s['raw_image']) ? 3 : 0;
                            $formattedDate = !empty($s['address_verified_at']) ? date('d/m/Y', strtotime($s['address_verified_at'])) : '01/05/2025';
                        ?>
                            <tr class="shop-row hover:bg-slate-50/80 transition"
                                data-id="<?= (int)$s['id'] ?>"
                                data-name="<?= htmlspecialchars(mb_strtolower($s['name'], 'UTF-8')) ?>"
                                data-address="<?= htmlspecialchars(mb_strtolower($s['address'], 'UTF-8')) ?>"
                                data-district="<?= htmlspecialchars($s['district'] ?? '') ?>"
                                data-active="<?= $isActive ? '1' : '0' ?>">
                                <td class="py-3 px-4 text-center font-bold text-slate-400 row-number"><?= $index + 1 ?></td>
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
                                <!-- Cột Trạng thái: Nút Bật / Tắt trực tiếp -->
                                <td class="py-3 px-4 text-center">
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox" 
                                               class="sr-only peer status-toggle" 
                                               data-id="<?= (int)$s['id'] ?>"
                                               <?= $isActive ? 'checked' : '' ?>
                                               onchange="toggleShopStatus(<?= (int)$s['id'] ?>, this)">
                                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600 relative"></div>
                                        <span class="status-label text-[11px] font-bold <?= $isActive ? 'text-emerald-600' : 'text-slate-400' ?>">
                                            <?= $isActive ? 'Hiển thị' : 'Đang ẩn' ?>
                                        </span>
                                    </label>
                                </td>
                                <td class="py-3 px-4 text-slate-500"><?= $formattedDate ?></td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="shop_edit.php?id=<?= $s['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                                            ✏️ Chỉnh sửa
                                        </a>
                                        <form method="post" action="shops.php" onsubmit="return confirm('Bạn có chắc chắn muốn xóa cửa hàng này khỏi hệ thống?')" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="delete" value="<?= $s['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-red-50 text-red-600 border border-slate-200 rounded-lg text-xs font-bold transition">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="shops-empty-row" style="display: none;">
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="text-3xl mb-2">🔍</div>
                                <div class="text-sm font-bold text-slate-700">Không tìm thấy cửa hàng nào</div>
                                <div class="text-xs mt-1">Hãy thử tìm bằng từ khóa hoặc bộ lọc khác.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION BAR (Tối đa 10 cửa hàng mỗi trang) -->
            <div class="p-4 border-t border-slate-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                <div id="shop-pagination-info" class="font-medium">
                    Hiển thị 1 - 10 của <?= $totalShops ?> cửa hàng
                </div>
                <div class="flex items-center gap-1" id="shop-pagination-controls">
                    <!-- Nút phân trang render bằng JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form ẩn dùng gửi CSRF khi toggle -->
<form id="csrf-toggle-form" style="display:none;">
    <?= csrfField() ?>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
var PAGE_SIZE = 10;
var currentPage = 1;
var matchedRows = [];

function getCsrfToken() {
    var input = document.querySelector('#csrf-toggle-form input[name="csrf_token"]');
    return input ? input.value : '';
}

function applyFilters() {
    var kw = (document.getElementById('shop-search').value || '').toLowerCase().trim();
    var district = (document.getElementById('district-filter').value || '').trim();
    var status = (document.getElementById('status-filter').value || '').trim();
    var allRows = Array.from(document.querySelectorAll('#shops-table tbody tr.shop-row'));

    matchedRows = allRows.filter(function(row) {
        var name = (row.dataset.name || '').toLowerCase();
        var address = (row.dataset.address || '').toLowerCase();
        var dist = row.dataset.district || '';
        var active = row.dataset.active || '1';

        var matchKw = !kw || name.includes(kw) || address.includes(kw);
        var matchDist = !district || dist === district;
        var matchSt = !status || active === status;

        return matchKw && matchDist && matchSt;
    });

    currentPage = 1;
    renderPagination();
}

function renderPagination() {
    var allRows = Array.from(document.querySelectorAll('#shops-table tbody tr.shop-row'));
    var emptyRow = document.getElementById('shops-empty-row');
    var totalMatched = matchedRows.length;
    var totalPages = Math.ceil(totalMatched / PAGE_SIZE) || 1;

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    // Ẩn tất cả trước
    allRows.forEach(function(row) { row.style.display = 'none'; });

    if (totalMatched === 0) {
        if (emptyRow) emptyRow.style.display = '';
    } else {
        if (emptyRow) emptyRow.style.display = 'none';
        var startIndex = (currentPage - 1) * PAGE_SIZE;
        var endIndex = Math.min(startIndex + PAGE_SIZE, totalMatched);

        for (var i = startIndex; i < endIndex; i++) {
            matchedRows[i].style.display = '';
            var numCell = matchedRows[i].querySelector('.row-number');
            if (numCell) numCell.textContent = (i + 1);
        }
    }

    // Cập nhật text thống kê
    var info = document.getElementById('shop-pagination-info');
    if (info) {
        if (totalMatched === 0) {
            info.textContent = 'Hiển thị 0 của 0 cửa hàng';
        } else {
            var startDisplay = (currentPage - 1) * PAGE_SIZE + 1;
            var endDisplay = Math.min(currentPage * PAGE_SIZE, totalMatched);
            info.textContent = 'Hiển thị ' + startDisplay + ' - ' + endDisplay + ' của ' + totalMatched + ' cửa hàng';
        }
    }

    // Render nút trang
    var controls = document.getElementById('shop-pagination-controls');
    if (!controls) return;
    controls.innerHTML = '';

    if (totalPages <= 1) return;

    // Nút Trước
    var prevBtn = document.createElement('button');
    prevBtn.className = 'w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-500 font-bold disabled:opacity-40';
    prevBtn.textContent = '<';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = function() { if (currentPage > 1) { currentPage--; renderPagination(); } };
    controls.appendChild(prevBtn);

    // Các trang
    for (var p = 1; p <= totalPages; p++) {
        (function(pageIndex) {
            var btn = document.createElement('button');
            btn.className = 'w-7 h-7 rounded font-bold ' + 
                (pageIndex === currentPage 
                    ? 'bg-orange-600 text-white' 
                    : 'border border-slate-200 text-slate-700 hover:bg-slate-50');
            btn.textContent = pageIndex;
            btn.onclick = function() {
                currentPage = pageIndex;
                renderPagination();
            };
            controls.appendChild(btn);
        })(p);
    }

    // Nút Sau
    var nextBtn = document.createElement('button');
    nextBtn.className = 'w-7 h-7 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 text-slate-500 font-bold disabled:opacity-40';
    nextBtn.textContent = '>';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = function() { if (currentPage < totalPages) { currentPage++; renderPagination(); } };
    controls.appendChild(nextBtn);
}

function resetShopFilters() {
    document.getElementById('shop-search').value = '';
    document.getElementById('district-filter').value = '';
    document.getElementById('status-filter').value = '';
    applyFilters();
}

function toggleShopStatus(shopId, checkbox) {
    var label = checkbox.closest('label').querySelector('.status-label');
    var isChecked = checkbox.checked;
    var row = checkbox.closest('tr');

    var formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('shop_id', shopId);
    formData.append('csrf_token', getCsrfToken());

    fetch('shops.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var activeState = isChecked ? '1' : '0';
            if (row) row.dataset.active = activeState;
            if (label) {
                label.textContent = isChecked ? 'Hiển thị' : 'Đang ẩn';
                label.className = 'status-label text-[11px] font-bold ' + (isChecked ? 'text-emerald-600' : 'text-slate-400');
            }
            // Cập nhật thẻ KPI
            updateKpiCounters();
        } else {
            alert('Không thể cập nhật trạng thái cửa hàng.');
            checkbox.checked = !isChecked;
        }
    })
    .catch(function() {
        alert('Lỗi kết nối khi cập nhật trạng thái.');
        checkbox.checked = !isChecked;
    });
}

function updateKpiCounters() {
    var allRows = document.querySelectorAll('#shops-table tbody tr.shop-row');
    var activeCount = 0;
    allRows.forEach(function(r) {
        if (r.dataset.active === '1') activeCount++;
    });
    var hiddenCount = allRows.length - activeCount;

    var actEl = document.getElementById('stat-active-count');
    var hidEl = document.getElementById('stat-hidden-count');
    if (actEl) actEl.textContent = activeCount;
    if (hidEl) hidEl.textContent = hiddenCount;
}

// Khởi chạy khi load
document.addEventListener('DOMContentLoaded', function() {
    applyFilters();
});
</script>
