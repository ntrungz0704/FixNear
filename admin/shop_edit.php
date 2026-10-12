<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$shop = $id > 0 ? db()->getShopById($id) : null;
$formErrors = [];
$deviceChoices = [
    'phone' => 'Điện thoại', 'laptop' => 'Laptop Windows', 'mac' => 'MacBook / Mac',
    'tablet' => 'Máy tính bảng', 'pc' => 'Máy tính bàn', 'smartwatch' => 'Đồng hồ thông minh',
];
if ($id > 0 && !$shop) {
    header('Location: shops.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $devices = array_values(array_intersect(array_keys($deviceChoices), (array) ($_POST['devices'] ?? [])));
    $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

    $shopData = [
        'name' => trim($_POST['name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'ward' => trim($_POST['ward'] ?? ''),
        'district' => trim($_POST['district'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'opening_hours' => trim($_POST['opening_hours'] ?? ''),
        'map_url' => trim($_POST['map_url'] ?? ''),
        'latitude' => $latitude === false ? '' : $latitude,
        'longitude' => $longitude === false ? '' : $longitude,
        'description' => trim($_POST['description'] ?? ''),
        'student_discount' => trim($_POST['student_discount'] ?? ''),
        'image' => trim($_POST['image'] ?? ''),
        'website' => trim($_POST['website'] ?? ''),
        'devices' => $devices,
        'allows_onsite_watch' => !empty($_POST['allows_onsite_watch']),
        'requires_component_signing' => !empty($_POST['requires_component_signing']),
        'is_verified' => !empty($shop['is_verified']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    foreach (['name' => 'tên cửa hàng', 'address' => 'địa chỉ', 'ward' => 'phường/xã', 'district' => 'quận/huyện', 'opening_hours' => 'giờ mở cửa'] as $key => $label) {
        if ($shopData[$key] === '') $formErrors[] = "Vui lòng nhập {$label}.";
    }
    $phoneDigits = preg_replace('/\D+/', '', $shopData['phone']);
    if (strlen($phoneDigits) < 6 || strlen($phoneDigits) > 12) $formErrors[] = 'Số điện thoại cần có 6–12 chữ số (hỗ trợ cả hotline 1800/1900).';
    if ($latitude === false || $longitude === false || $latitude < 8 || $latitude > 24 || $longitude < 102 || $longitude > 110) {
        $formErrors[] = 'Nhập tọa độ thực của cửa hàng tại Việt Nam (Vĩ độ: 8-24, Kinh độ: 102-110).';
    }
    if (!$devices) $formErrors[] = 'Chọn ít nhất một nhóm thiết bị có nguồn xác nhận.';
    foreach (['website' => 'website', 'map_url' => 'link chỉ đường', 'image' => 'link ảnh'] as $key => $label) {
        if ($shopData[$key] !== '' && (!filter_var($shopData[$key], FILTER_VALIDATE_URL) || !in_array(parse_url($shopData[$key], PHP_URL_SCHEME), ['http', 'https'], true))) {
            $formErrors[] = "{$label} phải là URL http hoặc https hợp lệ.";
        }
    }
    $shop = array_merge($shop ?? [], $shopData);

    if ($id > 0) {
        $shopData['id'] = $id;
    }

    if (!$formErrors) {
        if (db()->saveShop($shopData) !== false) {
            header("Location: shops.php?msg=saved");
            exit;
        }
        $formErrors[] = 'Không thể lưu cửa hàng lúc này. Vui lòng thử lại.';
    }
}

$pageTitle = ($id > 0 ? "Chỉnh Sửa Cửa Hàng" : "Thêm Cửa Hàng Mới") . " — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị (Giữ nguyên không thay đổi) -->
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#f8fafc]">
        <!-- Breadcrumb & Head -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="shops.php" class="hover:text-orange-600 transition">Quản lý cửa hàng</a>
                    <span>/</span>
                    <span class="text-slate-600"><?= $id > 0 ? 'Chỉnh sửa #' . $id : 'Thêm mới' ?></span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= $id > 0 ? 'Chỉnh sửa thông tin cửa hàng' : 'Thêm cửa hàng mới' ?>
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Cập nhật chi tiết địa chỉ, hotline, giờ hoạt động và các chính sách dịch vụ của cửa hàng
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="shops.php" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    &larr; Quay lại danh sách
                </a>
            </div>
        </div>

        <?php if ($formErrors): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-6 shadow-sm">
                <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wide">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Chưa lưu được thông tin cửa hàng:
                </div>
                <ul class="list-disc list-inside text-xs mt-2 space-y-1">
                    <?php foreach ($formErrors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="shop_edit.php<?= $id > 0 ? '?id='.$id : '' ?>" method="POST" class="space-y-6">
            <?= csrfField() ?>

            <!-- KHỐI 1: THÔNG TIN CƠ BẢN & LIÊN HỆ -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">1</span>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide">Thông tin nhận diện & Liên hệ</h2>
                    </div>
                    <!-- Switch trạng thái hiển thị -->
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <span class="text-xs font-bold text-slate-600">Trạng thái hiển thị:</span>
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" <?= (isset($shop['is_active']) ? (int)$shop['is_active'] : 1) === 1 ? 'checked' : '' ?>>
                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600 relative"></div>
                        <span class="text-xs font-bold text-emerald-600 peer-checked:inline hidden">Hiển thị</span>
                        <span class="text-xs font-bold text-slate-400 inline peer-checked:hidden">Đang ẩn</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-name">
                            Tên cửa hàng <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-name" type="text" name="name" value="<?= htmlspecialchars($shop['name'] ?? '') ?>" placeholder="Ví dụ: FASTCARE Tô Ký" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-phone">
                            Số điện thoại hotline <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-phone" type="tel" name="phone" value="<?= htmlspecialchars($shop['phone'] ?? '') ?>" placeholder="Ví dụ: 0908 123 456 hoặc 1800 2057" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-hours">
                            Giờ mở cửa <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-hours" type="text" name="opening_hours" value="<?= htmlspecialchars($shop['opening_hours'] ?? '') ?>" placeholder="Ví dụ: T2–CN 08:00–21:00" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-website">
                            Website chính thức
                        </label>
                        <input id="shop-website" type="url" name="website" value="<?= htmlspecialchars($shop['website'] ?? '') ?>" placeholder="https://fastcare.vn" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-discount">
                            Ưu đãi học sinh / sinh viên (nếu có)
                        </label>
                        <input id="shop-discount" type="text" name="student_discount" value="<?= htmlspecialchars($shop['student_discount'] ?? '') ?>" placeholder="Ví dụ: Giảm 10% tối đa 100k cho HSSV" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    </div>
                </div>
            </div>

            <!-- KHỐI 2: ĐỊA CHỈ & TỌA ĐỘ BẢN ĐỒ -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center gap-2.5 pb-4 mb-5 border-b border-slate-100">
                    <span class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">2</span>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide">Địa chỉ & Tọa độ bản đồ</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-address">
                            Địa chỉ chi tiết (Số nhà, tên đường) <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-address" type="text" name="address" value="<?= htmlspecialchars($shop['address'] ?? '') ?>" placeholder="Số nhà, tên đường" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-ward">
                            Phường / Xã <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-ward" type="text" name="ward" value="<?= htmlspecialchars($shop['ward'] ?? '') ?>" placeholder="Ví dụ: Phường 14" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-district">
                            Quận / Huyện <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-district" type="text" name="district" value="<?= htmlspecialchars($shop['district'] ?? '') ?>" placeholder="Ví dụ: Quận 10" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-latitude">
                            Vĩ độ (Latitude) <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-latitude" type="number" step="0.000001" name="latitude" value="<?= htmlspecialchars((string) ($shop['latitude'] ?? '')) ?>" placeholder="Ví dụ: 10.7769" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-longitude">
                            Kinh độ (Longitude) <span class="text-red-500">*</span>
                        </label>
                        <input id="shop-longitude" type="number" step="0.000001" name="longitude" value="<?= htmlspecialchars((string) ($shop['longitude'] ?? '')) ?>" placeholder="Ví dụ: 106.7009" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-map">
                            Link Google Maps chỉ đường
                        </label>
                        <input id="shop-map" type="url" name="map_url" value="<?= htmlspecialchars($shop['map_url'] ?? '') ?>" placeholder="https://maps.google.com/?q=..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    </div>
                </div>
            </div>

            <!-- KHỐI 3: HÌNH ẢNH & MÔ TẢ -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center gap-2.5 pb-4 mb-5 border-b border-slate-100">
                    <span class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">3</span>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide">Hình ảnh & Mô tả</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <?php $currentImg = $shop['raw_image'] ?? $shop['image'] ?? ''; ?>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-image">
                            Link ảnh mặt tiền cửa hàng
                        </label>
                        <input id="shop-image" type="url" name="image" value="<?= htmlspecialchars($currentImg) ?>" placeholder="https://website-cua-hang.vn/anh-mat-tien.jpg" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" oninput="previewImage(this.value)">
                        
                        <div id="image-preview-wrap" class="mt-3 <?= !empty($currentImg) ? '' : 'hidden' ?>">
                            <div class="text-[11px] font-bold text-slate-500 mb-1">Xem trước ảnh:</div>
                            <img id="image-preview" src="<?= htmlspecialchars($currentImg) ?>" alt="Preview" class="h-36 w-64 object-cover rounded-lg border border-slate-200 shadow-xs">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="shop-description">
                            Mô tả giới thiệu dịch vụ
                        </label>
                        <textarea id="shop-description" name="description" rows="3" placeholder="Nhập giới thiệu tóm tắt về năng lực, chuyên môn sửa chữa..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 leading-relaxed"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- KHỐI 4: THIẾT BỊ HỖ TRỢ & CHÍNH SÁCH -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center gap-2.5 pb-4 mb-5 border-b border-slate-100">
                    <span class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">4</span>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide">Nhóm thiết bị & Chính sách</h2>
                </div>

                <div class="mb-5">
                    <div class="text-xs font-bold text-slate-700 mb-2.5">
                        Nhóm thiết bị cửa hàng nhận sửa <span class="text-red-500">*</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach ($deviceChoices as $key => $label): ?>
                            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-slate-200/90 hover:border-orange-300 hover:bg-orange-50/30 cursor-pointer transition text-xs font-semibold text-slate-700">
                                <input type="checkbox" name="devices[]" value="<?= $key ?>" <?= in_array($key, (array) ($shop['devices'] ?? []), true) ? 'checked' : '' ?> class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500">
                                <span><?= htmlspecialchars($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <div class="text-xs font-bold text-slate-700 mb-2.5">Chính sách minh bạch</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-lg border border-slate-200/90 hover:bg-slate-50 cursor-pointer transition text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="allows_onsite_watch" value="1" <?= !empty($shop['allows_onsite_watch']) ? 'checked' : '' ?> class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500">
                            <span>Cho phép khách xem sửa chữa trực tiếp</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-lg border border-slate-200/90 hover:bg-slate-50 cursor-pointer transition text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="requires_component_signing" value="1" <?= !empty($shop['requires_component_signing']) ? 'checked' : '' ?> class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500">
                            <span>Hỗ trợ ký tên niêm phong linh kiện</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- NÚT LƯU & HỦY BỎ -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="shops.php" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg transition">
                    Hủy bỏ
                </a>
                <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Lưu thông tin cửa hàng
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function previewImage(url) {
    var wrap = document.getElementById('image-preview-wrap');
    var img = document.getElementById('image-preview');
    if (!url || !url.match(/^https?:\/\//i)) {
        wrap.classList.add('hidden');
        return;
    }
    wrap.classList.remove('hidden');
    img.src = url;
}
</script>
