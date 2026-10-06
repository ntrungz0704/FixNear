<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$shop = $id > 0 ? db()->getShopById($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $devices = [];
    if (!empty($_POST['device_laptop'])) $devices[] = 'laptop';
    if (!empty($_POST['device_phone'])) $devices[] = 'phone';

    $shopData = [
        'name' => trim($_POST['name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'ward' => trim($_POST['ward'] ?? 'Tân Chánh Hiệp'),
        'district' => trim($_POST['district'] ?? 'Quận 12'),
        'phone' => trim($_POST['phone'] ?? ''),
        'opening_hours' => trim($_POST['opening_hours'] ?? '08:00 - 21:00'),
        'map_url' => trim($_POST['map_url'] ?? ''),
        'latitude' => (float)($_POST['latitude'] ?? 10.8538),
        'longitude' => (float)($_POST['longitude'] ?? 106.6263),
        'description' => trim($_POST['description'] ?? ''),
        'student_discount' => trim($_POST['student_discount'] ?? ''),
        'image' => trim($_POST['image'] ?? ''),
        'devices' => !empty($devices) ? $devices : ['laptop', 'phone'],
        'allows_onsite_watch' => !empty($_POST['allows_onsite_watch']),
        'requires_component_signing' => !empty($_POST['requires_component_signing']),
        'is_verified' => false
    ];

    if ($id > 0) {
        $shopData['id'] = $id;
    }

    db()->saveShop($shopData);
    header("Location: shops.php?msg=saved");
    exit;
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

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 24px;">
            <form action="shop_edit.php<?= $id > 0 ? '?id='.$id : '' ?>" method="POST">
                <?= csrfField() ?>
                <div class="fn-form-group">
                    <label class="fn-label">Tên cửa hàng:</label>
                    <input type="text" name="name" class="fn-input" value="<?= htmlspecialchars($shop['name'] ?? '') ?>" placeholder="Ví dụ: FASTCARE Tô Ký" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="fn-form-group">
                        <label class="fn-label">Phường / Xã:</label>
                        <select name="ward" class="fn-select" required>
                            <option value="Tân Chánh Hiệp" <?= ($shop['ward'] ?? '') === 'Tân Chánh Hiệp' ? 'selected' : '' ?>>P. Tân Chánh Hiệp (Quanh trường)</option>
                            <option value="Trung Mỹ Tây" <?= ($shop['ward'] ?? '') === 'Trung Mỹ Tây' ? 'selected' : '' ?>>P. Trung Mỹ Tây (Tô Ký)</option>
                            <option value="Đông Hưng Thuận" <?= ($shop['ward'] ?? '') === 'Đông Hưng Thuận' ? 'selected' : '' ?>>P. Đông Hưng Thuận (Chợ Cầu)</option>
                            <option value="Thới An" <?= ($shop['ward'] ?? '') === 'Thới An' ? 'selected' : '' ?>>P. Thới An (Lê Văn Khương)</option>
                            <option value="Phường 8" <?= ($shop['ward'] ?? '') === 'Phường 8' ? 'selected' : '' ?>>Phường 8 (Gò Vấp)</option>
                            <option value="Phường 10" <?= ($shop['ward'] ?? '') === 'Phường 10' ? 'selected' : '' ?>>Phường 10 (Gò Vấp)</option>
                            <option value="Bà Điểm" <?= ($shop['ward'] ?? '') === 'Bà Điểm' ? 'selected' : '' ?>>Xã Bà Điểm (Hóc Môn)</option>
                        </select>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Quận / Huyện:</label>
                        <select name="district" class="fn-select" required>
                            <option value="Quận 12" <?= ($shop['district'] ?? '') === 'Quận 12' ? 'selected' : '' ?>>Quận 12</option>
                            <option value="Quận Gò Vấp" <?= ($shop['district'] ?? '') === 'Quận Gò Vấp' ? 'selected' : '' ?>>Quận Gò Vấp</option>
                            <option value="Huyện Hóc Môn" <?= ($shop['district'] ?? '') === 'Huyện Hóc Môn' ? 'selected' : '' ?>>Huyện Hóc Môn</option>
                        </select>
                    </div>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Địa chỉ cụ thể:</label>
                    <input type="text" name="address" class="fn-input" value="<?= htmlspecialchars($shop['address'] ?? '') ?>" placeholder="Số nhà, tên đường..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="fn-form-group">
                        <label class="fn-label">Số điện thoại hotline:</label>
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

                <div class="fn-form-group">
                    <label class="fn-label">Link chỉ đường Google Maps:</label>
                    <input type="url" name="map_url" class="fn-input" value="<?= htmlspecialchars($shop['map_url'] ?? '') ?>" placeholder="https://maps.google.com/?q=...">
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Link ảnh đại diện cửa hàng:</label>
                    <input type="url" name="image" class="fn-input" value="<?= htmlspecialchars($shop['image'] ?? '') ?>" placeholder="URL ảnh từ website chính thức (nếu có)">
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Mô tả giới thiệu cửa hàng:</label>
                    <textarea name="description" class="fn-textarea" rows="3"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
                </div>

                <div class="fn-form-group">
                    <label class="fn-label">Chính sách ưu đãi / Khuyến mãi:</label>
                    <input type="text" name="student_discount" class="fn-input" value="<?= htmlspecialchars($shop['student_discount'] ?? '') ?>" placeholder="Ví dụ: Giảm 10% công sửa hoặc tặng dán cường lực">
                </div>

                <div style="margin-bottom: 20px; display: flex; gap: 24px; flex-wrap: wrap;">
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="device_laptop" value="1" <?= in_array('laptop', $shop['devices'] ?? ['laptop']) ? 'checked' : '' ?>> Sửa Laptop
                    </label>
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="device_phone" value="1" <?= in_array('phone', $shop['devices'] ?? ['phone']) ? 'checked' : '' ?>> Sửa Điện Thoại
                    </label>
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="allows_onsite_watch" value="1" <?= !empty($shop['allows_onsite_watch']) ? 'checked' : '' ?>> Cho xem sửa trực tiếp
                    </label>
                    <label style="font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="requires_component_signing" value="1" <?= !empty($shop['requires_component_signing']) ? 'checked' : '' ?>> Ký tên lên linh kiện
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
