<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$services = db()->getServices();
$shops = db()->getShops(['include_unverified' => true]);
$priceFormError = '';
$priceFormValues = $_POST;

// Thêm khoảng giá mới cho cửa hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_price'])) {
    requireValidCsrf();
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $min_price = filter_var($_POST['min_price'] ?? null, FILTER_VALIDATE_INT);
    $max_price = filter_var($_POST['max_price'] ?? null, FILTER_VALIDATE_INT);
    $warranty = trim($_POST['warranty'] ?? '');
    $turnaround = trim($_POST['turnaround'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!in_array($shop_id, array_map(static fn($shop) => (int) $shop['id'], $shops), true)
        || !in_array($service_id, array_map(static fn($service) => (int) $service['id'], $services), true)) {
        $priceFormError = 'Chọn cửa hàng và dịch vụ có trong hệ thống.';
    } elseif ($min_price === false || $max_price === false || $min_price <= 0 || $max_price < $min_price) {
        $priceFormError = 'Giá phải lớn hơn 0 và giá tối đa không được thấp hơn giá tối thiểu.';
    } elseif ($warranty === '' || $turnaround === '') {
        $priceFormError = 'Nhập điều kiện bảo hành và thời gian sửa đã được cửa hàng xác nhận.';
    } else {
        db()->saveShopService([
            'shop_id' => $shop_id,
            'service_id' => $service_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'warranty_text' => $warranty,
            'turnaround_text' => $turnaround,
            'note' => $note
        ]);
        header("Location: services.php?msg=price_added");
        exit;
    }
}

$pageTitle = "Quản Lý Dịch Vụ & Bảng Giá — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>


    <div class="fn-admin-content">
        <div class="fn-admin-page-head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                    Dịch vụ &amp; bảng giá cửa hàng
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                    <?= count($services) ?> dịch vụ trong hệ thống. Khoảng giá do quản trị nhập cần được đối chiếu với cửa hàng trước khi công bố.
                </p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 20px;">
                ✓ Đã cập nhật bảng giá tham khảo thành công!
            </div>
        <?php endif; ?>

        <!-- Form gán khoảng giá cho tiệm -->
        <div class="fn-admin-form-panel" style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px; margin-bottom: 30px;">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 14px; color: var(--fn-dark);">
                ➕ Gán khoảng giá cho cửa hàng
            </h3>
            <?php if ($priceFormError !== ''): ?><div class="fn-admin-form-errors" role="alert"><?= htmlspecialchars($priceFormError) ?></div><?php endif; ?>
            <form action="services.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="add_price" value="1">
                <div class="fn-admin-price-top">
                    <div>
                        <label for="price-shop" class="fn-label" style="font-size: 12px;">Chọn cửa hàng:</label>
                        <select id="price-shop" name="shop_id" class="fn-select" required>
                            <?php foreach ($shops as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= (int) ($priceFormValues['shop_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="price-service" class="fn-label" style="font-size: 12px;">Chọn dịch vụ / lỗi:</label>
                        <select id="price-service" name="service_id" class="fn-select" required>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= (int) $srv['id'] ?>" <?= (int) ($priceFormValues['service_id'] ?? 0) === (int) $srv['id'] ? 'selected' : '' ?>>
                                    [<?= $srv['device_type'] === 'laptop' ? 'Laptop' : 'ĐT' ?>] <?= htmlspecialchars($srv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="price-min" class="fn-label" style="font-size: 12px;">Giá tối thiểu (VNĐ):</label>
                        <input id="price-min" type="number" min="1" step="1" name="min_price" class="fn-input" value="<?= htmlspecialchars((string) ($priceFormValues['min_price'] ?? '')) ?>" placeholder="Ví dụ: 350000" inputmode="numeric" required>
                    </div>

                    <div>
                        <label for="price-max" class="fn-label" style="font-size: 12px;">Giá tối đa (VNĐ):</label>
                        <input id="price-max" type="number" min="1" step="1" name="max_price" class="fn-input" value="<?= htmlspecialchars((string) ($priceFormValues['max_price'] ?? '')) ?>" placeholder="Ví dụ: 850000" inputmode="numeric" required>
                    </div>
                </div>

                <div class="fn-admin-price-bottom">
                    <div>
                        <label for="price-warranty" class="fn-label" style="font-size: 12px;">Bảo hành đã xác nhận:</label>
                        <input id="price-warranty" type="text" name="warranty" class="fn-input" value="<?= htmlspecialchars((string) ($priceFormValues['warranty'] ?? '')) ?>" placeholder="Ví dụ: 6 tháng" required>
                    </div>
                    <div>
                        <label for="price-time" class="fn-label" style="font-size: 12px;">Thời gian sửa đã xác nhận:</label>
                        <input id="price-time" type="text" name="turnaround" class="fn-input" value="<?= htmlspecialchars((string) ($priceFormValues['turnaround'] ?? '')) ?>" placeholder="Ví dụ: 30–45 phút" required>
                    </div>
                    <div>
                        <label for="price-note" class="fn-label" style="font-size: 12px;">Ghi chú:</label>
                        <input id="price-note" type="text" name="note" class="fn-input" value="<?= htmlspecialchars((string) ($priceFormValues['note'] ?? '')) ?>" placeholder="Tên linh kiện và điều kiện áp dụng">
                    </div>
                    <div>
                        <button type="submit" class="fn-btn fn-btn-primary fn-btn-sm" style="padding: 10px 16px;">
                            Lưu khoảng giá
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danh mục dịch vụ trong dữ liệu -->
        <h2 style="font-family: var(--fn-font-heading); font-size: 18px; font-weight: 800; margin-bottom: 14px;">
            Danh mục <?= count($services) ?> dịch vụ trong hệ thống
        </h2>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto;">
            <table class="fn-price-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Tên Lỗi / Dịch Vụ</th>
                        <th>Loại Thiết Bị</th>
                        <th>Mô Tả Lỗi Chi Tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $srv): ?>
                        <tr>
                            <td><strong>#<?= $srv['id'] ?></strong></td>
                            <td style="font-weight: 700; color: var(--fn-dark);">
                                <?= htmlspecialchars($srv['name']) ?>
                            </td>
                            <td>
                                <span style="font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: <?= $srv['device_type'] === 'laptop' ? '#e0f2fe; color:#0369a1;' : '#fce7f3; color:#be185d;' ?>">
                                    <?= $srv['device_type'] === 'laptop' ? '💻 Laptop' : '📱 Điện thoại' ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; color: var(--fn-dark-muted);">
                                <?= htmlspecialchars($srv['description']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
