<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$shop = $id > 0 ? db()->getShopById($id) : null;
$formError = '';

$deviceChoices = [
    'phone' => '📱 Điện thoại',
    'laptop' => '💻 Laptop Windows',
    'mac' => '🍏 MacBook / Mac',
    'tablet' => '📱 Máy tính bảng',
    'pc' => '🖥️ Máy tính bàn / PC',
    'smartwatch' => '⌚ Đồng hồ thông minh'
];

$allDistricts = [
    'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
    'Quận 10', 'Quận 11', 'Quận 12', 'Quận Bình Tân', 'Quận Bình Thạnh',
    'Quận Gò Vấp', 'Quận Phú Nhuận', 'Quận Tân Bình', 'Quận Tân Phú',
    'TP. Thủ Đức', 'Huyện Hóc Môn'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($address) || empty($district) || empty($phone)) {
        $formError = 'Vui lòng điền đầy đủ Tên cửa hàng, Địa chỉ, Quận/Huyện và Số điện thoại hotline!';
    } else {
        $devices = [];
        foreach (array_keys($deviceChoices) as $devKey) {
            if (!empty($_POST['device_' . $devKey])) {
                $devices[] = $devKey;
            }
        }
        if (empty($devices)) {
            $devices = ['laptop', 'phone'];
        }

        $shopData = [
            'name' => $name,
            'address' => $address,
            'ward' => $ward ?: 'Phường trung tâm',
            'district' => $district,
            'phone' => $phone,
            'opening_hours' => trim($_POST['opening_hours'] ?? '08:00 - 21:00'),
            'map_url' => trim($_POST['map_url'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'latitude' => (float)($_POST['latitude'] ?? 10.8538),
            'longitude' => (float)($_POST['longitude'] ?? 106.6263),
            'description' => trim($_POST['description'] ?? ''),
            'student_discount' => trim($_POST['student_discount'] ?? ''),
            'image' => trim($_POST['image'] ?? 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&auto=format&fit=crop&q=80'),
            'devices' => $devices,
            'allows_onsite_watch' => !empty($_POST['allows_onsite_watch']),
            'requires_component_signing' => !empty($_POST['requires_component_signing']),
            'is_verified' => !empty($_POST['is_verified'])
        ];

        if ($id > 0) {
            $shopData['id'] = $id;
        }

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
    <div class="fn-admin-sidebar">
        <a href="shops.php" class="fn-admin-menu-item active">
            &larr; Quay lại danh sách tiệm
        </a>
    </div>

    <div class="fn-admin-content" style="max-width: 800px;">
        <h1 style="font-family: var(--fn-font-heading); font-size: 22px; font-weight: 900; margin-bottom: 20px;">
            <?= $id > 0 ? '✏️ Chỉnh Sửa Cửa Hàng #' . $id : '➕ Thêm Cửa Hàng Mới' ?>
        </h1>

        <?php if (!empty($formError)): ?>
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">
                ⚠️ <?= htmlspecialchars($formError) ?>
            </div>
        <?php endif; ?>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 24px;">
            <form action="shop_edit.php<?= $id > 0 ? '?id='.$id : '' ?>" method="POST">
                <?= csrfField() ?>
                <div class="fn-form-group">
                    <label class="fn-label">Tên cửa hàng: <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" class="fn-input" value="<?= htmlspecialchars($shop['name'] ?? '') ?>" placeholder="Ví dụ: FASTCARE Tô Ký" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="fn-form-group">
                        <label class="fn-label">Quận / Huyện: <span style="color:#ef4444;">*</span></label>
                        <select name="district" class="fn-select" required>
                            <option value="">-- Chọn Quận/Huyện TP.HCM --</option>
                            <?php foreach ($allDistricts as $dist): ?>
                                <option value="<?= htmlspecialchars($dist) ?>" <?= (($shop['district'] ?? '') === $dist) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dist) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (!empty($shop['district']) && !in_array($shop['district'], $allDistricts)): ?>
                                <option value="<?= htmlspecialchars($shop['district']) ?>" selected><?= htmlspecialchars($shop['district']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Phường / Xã:</label>
                        <input type="text" name="ward" class="fn-input" list="common_wards" value="<?= htmlspecialchars($shop['ward'] ?? '') ?>" placeholder="Ví dụ: P. Tân Chánh Hiệp">
                        <datalist id="common_wards">
                            <option value="Tân Chánh Hiệp">
                            <option value="Trung Mỹ Tây">
                            <option value="Đông Hưng Thuận">
                            <option value="Thới An">
                            <option value="Phường 8">
                            <option value="Phường 10">
                            <option value="Bà Điểm">
                            <option value="Bến Nghé">
                            <option value="Bến Thành">
                            <option value="Võ Thị Sáu">
                        </datalist>
                    </div>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Địa chỉ cụ thể: <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="address" class="fn-input" value="<?= htmlspecialchars($shop['address'] ?? '') ?>" placeholder="Số nhà, tên đường..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="fn-form-group">
                        <label class="fn-label">Số điện thoại hotline: <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="phone" class="fn-input" value="<?= htmlspecialchars($shop['phone'] ?? '') ?>" placeholder="0908xxxxxx" required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Giờ mở cửa:</label>
                        <input type="text" name="opening_hours" class="fn-input" value="<?= htmlspecialchars($shop['opening_hours'] ?? '08:00 - 21:00') ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="fn-form-group">
                        <label class="fn-label">Vĩ độ (Latitude) trên Map:</label>
                        <input type="number" step="0.000001" name="latitude" class="fn-input" value="<?= $shop['latitude'] ?? 10.8538 ?>" required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Kinh độ (Longitude) trên Map:</label>
                        <input type="number" step="0.000001" name="longitude" class="fn-input" value="<?= $shop['longitude'] ?? 106.6263 ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="fn-form-group">
                        <label class="fn-label">Link chỉ đường Google Maps:</label>
                        <input type="url" name="map_url" class="fn-input" value="<?= htmlspecialchars($shop['map_url'] ?? '') ?>" placeholder="https://maps.google.com/?q=...">
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Website chính thức:</label>
                        <input type="url" name="website" class="fn-input" value="<?= htmlspecialchars($shop['website'] ?? '') ?>" placeholder="https://example.com">
                    </div>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Link ảnh đại diện cửa hàng:</label>
                    <input type="url" name="image" class="fn-input" value="<?= htmlspecialchars($shop['image'] ?? 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&auto=format&fit=crop&q=80') ?>" required>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Mô tả giới thiệu cửa hàng:</label>
                    <textarea name="description" class="fn-textarea" rows="3"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Chính sách ưu đãi / Khuyến mãi:</label>
                    <input type="text" name="student_discount" class="fn-input" value="<?= htmlspecialchars($shop['student_discount'] ?? '') ?>" placeholder="Ví dụ: Giảm 10% công sửa hoặc tặng dán cường lực">
                </div>

                <!-- Danh mục 6 nhóm thiết bị hỗ trợ -->
                <div class="fn-form-group">
                    <label class="fn-label">Nhóm thiết bị cửa hàng tiếp nhận sửa chữa:</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <?php foreach ($deviceChoices as $devKey => $devLabel): ?>
                            <label style="font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="device_<?= $devKey ?>" value="1" <?= in_array($devKey, (array)($shop['devices'] ?? ['laptop', 'phone'])) ? 'checked' : '' ?>>
                                <span><?= $devLabel ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cờ chính sách & Xác minh -->
                <div style="margin-bottom: 24px; display: flex; gap: 20px; flex-wrap: wrap;">
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="allows_onsite_watch" value="1" <?= !empty($shop['allows_onsite_watch']) ? 'checked' : '' ?>> Cho xem sửa trực tiếp
                    </label>
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="requires_component_signing" value="1" <?= !empty($shop['requires_component_signing']) ? 'checked' : '' ?>> Ký tên lên linh kiện
                    </label>
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px; cursor: pointer; color: #15803d;">
                        <input type="checkbox" name="is_verified" value="1" <?= !empty($shop['is_verified']) ? 'checked' : '' ?>> ✓ Đã xác minh địa chỉ & hotline
                    </label>
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
