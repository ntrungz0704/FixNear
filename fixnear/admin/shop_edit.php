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
        'is_verified' => !empty($shop['is_verified'])
    ];

    foreach (['name' => 'tên cửa hàng', 'address' => 'địa chỉ', 'ward' => 'phường/xã', 'district' => 'quận/huyện', 'opening_hours' => 'giờ mở cửa'] as $key => $label) {
        if ($shopData[$key] === '') $formErrors[] = "Vui lòng nhập {$label}.";
    }
    $phoneDigits = preg_replace('/\D+/', '', $shopData['phone']);
    if (strlen($phoneDigits) < 9 || strlen($phoneDigits) > 12) $formErrors[] = 'Số điện thoại cần có 9–12 chữ số.';
    if ($latitude === false || $longitude === false || $latitude < 8 || $latitude > 24 || $longitude < 102 || $longitude > 110) {
        $formErrors[] = 'Nhập tọa độ thực của cửa hàng tại Việt Nam; không dùng tọa độ mẫu.';
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
        db()->saveShop($shopData);
        header("Location: shops.php?msg=saved");
        exit;
    }
}

$pageTitle = ($id > 0 ? "Chỉnh Sửa Cửa Hàng" : "Thêm Cửa Hàng Mới") . " — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>


    <div class="fn-admin-content" style="max-width: 800px;">
        <h1 style="font-family: var(--fn-font-heading); font-size: 22px; font-weight: 900; margin-bottom: 20px;">
            <?= $id > 0 ? '✏️ Chỉnh Sửa Cửa Hàng #' . $id : '➕ Thêm Cửa Hàng Mới' ?>
        </h1>
        <p class="fn-admin-form-note">Nhập thông tin từ website chính thức hoặc nguồn đã đối chiếu. Bản ghi mới chỉ công khai sau khi có nguồn địa chỉ trong dữ liệu kiểm chứng.</p>
        <?php if ($formErrors): ?>
            <div class="fn-admin-form-errors" role="alert">
                <strong>Chưa lưu được cửa hàng:</strong>
                <ul><?php foreach ($formErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="fn-admin-form-panel" style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 24px;">
            <form action="shop_edit.php<?= $id > 0 ? '?id='.$id : '' ?>" method="POST">
                <?= csrfField() ?>
                <div class="fn-form-group">
                    <label class="fn-label" for="shop-name">Tên cửa hàng:</label>
                    <input id="shop-name" type="text" name="name" class="fn-input" value="<?= htmlspecialchars($shop['name'] ?? '') ?>" placeholder="Ví dụ: FASTCARE Tô Ký" autocomplete="organization" required>
                </div>

                <div class="fn-admin-form-row">
                    <div class="fn-form-group">
                        <label class="fn-label" for="shop-ward">Phường / Xã:</label>
                        <input id="shop-ward" type="text" name="ward" class="fn-input" value="<?= htmlspecialchars($shop['ward'] ?? '') ?>" placeholder="Tên phường hoặc xã" autocomplete="address-level3" required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label" for="shop-district">Quận / Huyện:</label>
                        <input id="shop-district" type="text" name="district" class="fn-input" value="<?= htmlspecialchars($shop['district'] ?? '') ?>" placeholder="Ví dụ: Quận 7" autocomplete="address-level2" required>
                    </div>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label" for="shop-address">Địa chỉ cụ thể:</label>
                    <input id="shop-address" type="text" name="address" class="fn-input" value="<?= htmlspecialchars($shop['address'] ?? '') ?>" placeholder="Số nhà, tên đường và địa phương" autocomplete="street-address" required>
                </div>

                <div class="fn-admin-form-row">
                    <div class="fn-form-group">
                        <label class="fn-label" for="shop-phone">Số điện thoại hotline:</label>
                        <input id="shop-phone" type="tel" inputmode="tel" name="phone" class="fn-input" value="<?= htmlspecialchars($shop['phone'] ?? '') ?>" placeholder="Ví dụ: 0908 123 456" autocomplete="tel" required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label" for="shop-hours">Giờ mở cửa:</label>
                        <input id="shop-hours" type="text" name="opening_hours" class="fn-input" value="<?= htmlspecialchars($shop['opening_hours'] ?? '') ?>" placeholder="Ví dụ: T2–CN 08:00–21:00" required>
                    </div>
                </div>

                <div class="fn-admin-form-row">
                    <div class="fn-form-group">
                        <label class="fn-label" for="shop-latitude">Vĩ độ trên bản đồ:</label>
                        <input id="shop-latitude" type="number" step="0.000001" name="latitude" class="fn-input" value="<?= htmlspecialchars((string) ($shop['latitude'] ?? '')) ?>" placeholder="Ví dụ: 10.7769" inputmode="decimal" required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label" for="shop-longitude">Kinh độ trên bản đồ:</label>
                        <input id="shop-longitude" type="number" step="0.000001" name="longitude" class="fn-input" value="<?= htmlspecialchars((string) ($shop['longitude'] ?? '')) ?>" placeholder="Ví dụ: 106.7009" inputmode="decimal" required>
                    </div>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label" for="shop-website">Website chính thức:</label>
                    <input id="shop-website" type="url" name="website" class="fn-input" value="<?= htmlspecialchars($shop['website'] ?? '') ?>" placeholder="https://website-cua-hang.vn" inputmode="url" autocomplete="url">
                </div>

                <div class="fn-form-group">
                    <label class="fn-label" for="shop-map">Link chỉ đường Google Maps:</label>
                    <input id="shop-map" type="url" name="map_url" class="fn-input" value="<?= htmlspecialchars($shop['map_url'] ?? '') ?>" placeholder="https://maps.google.com/?q=…" inputmode="url">
                </div>

                <div class="fn-form-group">
                    <label class="fn-label" for="shop-image">Ảnh hoặc logo từ website chính thức:</label>
                    <input id="shop-image" type="url" name="image" class="fn-input" value="<?= htmlspecialchars($shop['image'] ?? '') ?>" placeholder="https://website-cua-hang.vn/anh.jpg" inputmode="url">
                    <small style="display:block;margin-top:5px;color:#64748b;">Ảnh nhập ở đây được lưu để đối chiếu; trang công khai chỉ dùng ảnh nguồn đã duyệt.</small>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label" for="shop-description">Mô tả giới thiệu cửa hàng:</label>
                    <textarea id="shop-description" name="description" class="fn-textarea" rows="3"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label" for="shop-discount">Ưu đãi có nguồn (nếu có):</label>
                    <input id="shop-discount" type="text" name="student_discount" class="fn-input" value="<?= htmlspecialchars($shop['student_discount'] ?? '') ?>" placeholder="Để trống nếu chưa xác nhận">
                </div>

                <fieldset class="fn-admin-device-fieldset">
                    <legend>Nhóm thiết bị cửa hàng nhận sửa</legend>
                    <div class="fn-admin-device-options">
                        <?php foreach ($deviceChoices as $key => $label): ?>
                            <label><input type="checkbox" name="devices[]" value="<?= $key ?>" <?= in_array($key, (array) ($shop['devices'] ?? []), true) ? 'checked' : '' ?>> <?= htmlspecialchars($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="fn-admin-device-options fn-admin-policy-options">
                    <label><input type="checkbox" name="allows_onsite_watch" value="1" <?= !empty($shop['allows_onsite_watch']) ? 'checked' : '' ?>> Cho xem sửa trực tiếp</label>
                    <label><input type="checkbox" name="requires_component_signing" value="1" <?= !empty($shop['requires_component_signing']) ? 'checked' : '' ?>> Cho ký tên linh kiện</label>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="fn-btn fn-btn-primary">
                        💾 Lưu Thông Tin Cửa Hàng
                    </button>
                    <a href="shops.php" class="fn-btn fn-btn-secondary">Hủy bỏ</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
