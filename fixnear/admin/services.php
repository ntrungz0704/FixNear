<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$services = db()->getServices();
$shops = db()->getShops();
$formError = '';

// Xóa giá dịch vụ đã gán cho cửa hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_price') {
    requireValidCsrf();
    $delId = (int)($_POST['price_id'] ?? 0);
    if ($delId > 0) {
        db()->deleteShopService($delId);
        header("Location: services.php?msg=price_deleted");
        exit;
    }
}

// Thêm khoảng giá mới cho cửa hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_price'])) {
    requireValidCsrf();
    $shop_id = (int)$_POST['shop_id'];
    $service_id = (int)$_POST['service_id'];
    $min_price = (int)$_POST['min_price'];
    $max_price = (int)$_POST['max_price'];
    $warranty = trim($_POST['warranty'] ?? '6 - 12 tháng');
    $turnaround = trim($_POST['turnaround'] ?? '30 - 60 phút');
    $note = trim($_POST['note'] ?? '');

    if ($min_price <= 0 || $max_price < $min_price) {
        $formError = 'Giá tối thiểu phải lớn hơn 0 và giá tối đa không được thấp hơn giá tối thiểu!';
    } else {
        db()->saveShopService([
            'shop_id' => $shop_id,
            'service_id' => $service_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'warranty' => $warranty,
            'warranty_text' => $warranty,
            'turnaround' => $turnaround,
            'turnaround_text' => $turnaround,
            'note' => $note
        ]);
        header("Location: services.php?msg=price_added");
        exit;
    }
}

$selectedShopFilter = (int)($_GET['shop_id'] ?? 0);
$shopPrices = db()->getAllShopServices($selectedShopFilter, 60);

$pageTitle = "Quản Lý Dịch Vụ & Bảng Giá — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <div class="fn-admin-sidebar">
        <a href="index.php" class="fn-admin-menu-item">
            📊 Bảng thống kê
        </a>
        <a href="requests.php" class="fn-admin-menu-item">
            📋 Yêu cầu báo giá
        </a>
        <a href="shops.php" class="fn-admin-menu-item">
            🏪 Quản lý cửa hàng
        </a>
        <a href="services.php" class="fn-admin-menu-item active">
            🏷️ Dịch vụ & Bảng giá
        </a>
        <a href="reviews.php" class="fn-admin-menu-item">
            ⭐ Quản lý đánh giá
        </a>
        <a href="reports.php" class="fn-admin-menu-item">
            🚩 Báo cáo sai sót
        </a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--fn-border);">
            <a href="../index.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="width: 100%;">
                &larr; Xem giao diện web
            </a>
        </div>
    </div>

    <div class="fn-admin-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                    Quản Lý 19 Dịch Vụ & Bảng Giá Cửa Hàng
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                    Chuẩn hóa danh mục 10 lỗi Laptop và 9 lỗi Điện thoại, gán và cập nhật khoảng giá tham khảo theo từng chi nhánh.
                </p>
            </div>
        </div>

        <?php if (!empty($formError)): ?>
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 20px;">
                ⚠️ <?= htmlspecialchars($formError) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'price_added'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 20px;">
                ✓ Đã gán khoảng giá tham khảo cho cửa hàng thành công!
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'price_deleted'): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 20px;">
                ✓ Đã xóa mục giá khỏi danh mục cửa hàng!
            </div>
        <?php endif; ?>

        <!-- Form gán khoảng giá cho tiệm -->
        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 20px; margin-bottom: 30px;">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 14px; color: var(--fn-dark);">
                ➕ Gán Khoảng Giá Mới Cho Cửa Hàng
            </h3>
            <form action="services.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="add_price" value="1">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="fn-label" style="font-size: 12px;">Chọn cửa hàng: <span style="color:#ef4444;">*</span></label>
                        <select name="shop_id" class="fn-select" required>
                            <?php foreach ($shops as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="fn-label" style="font-size: 12px;">Chọn dịch vụ / lỗi: <span style="color:#ef4444;">*</span></label>
                        <select name="service_id" class="fn-select" required>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['id'] ?>">
                                    [<?= $srv['device_type'] === 'laptop' ? 'Laptop' : 'ĐT' ?>] <?= htmlspecialchars($srv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="fn-label" style="font-size: 12px;">Giá min (VNĐ): <span style="color:#ef4444;">*</span></label>
                        <input type="number" step="10000" name="min_price" class="fn-input" placeholder="VD: 350000" required>
                    </div>

                    <div>
                        <label class="fn-label" style="font-size: 12px;">Giá max (VNĐ): <span style="color:#ef4444;">*</span></label>
                        <input type="number" step="10000" name="max_price" class="fn-input" placeholder="VD: 850000" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 12px; align-items: flex-end;">
                    <div>
                        <label class="fn-label" style="font-size: 12px;">Bảo hành:</label>
                        <input type="text" name="warranty" class="fn-input" value="6 - 12 tháng">
                    </div>
                    <div>
                        <label class="fn-label" style="font-size: 12px;">Thời gian sửa:</label>
                        <input type="text" name="turnaround" class="fn-input" value="30 - 45 phút">
                    </div>
                    <div>
                        <label class="fn-label" style="font-size: 12px;">Ghi chú kỹ thuật:</label>
                        <input type="text" name="note" class="fn-input" placeholder="Linh kiện zin / bóc máy...">
                    </div>
                    <div>
                        <button type="submit" class="fn-btn fn-btn-primary fn-btn-sm" style="padding: 10px 16px;">
                            Lưu Giá
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bảng giá thực tế đã gán cho từng cửa hàng -->
        <div style="margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-family: var(--fn-font-heading); font-size: 18px; font-weight: 800; color: var(--fn-dark); margin: 0;">
                    📋 Bảng Giá Đã Gán Tại Các Cửa Hàng (Gần nhất)
                </h2>
                <form method="GET" action="services.php" style="display: flex; gap: 8px; align-items: center;">
                    <select name="shop_id" class="fn-select" onchange="this.form.submit()" style="font-size: 12.5px; padding: 6px 10px;">
                        <option value="0">-- Tất cả cửa hàng --</option>
                        <?php foreach ($shops as $sh): ?>
                            <option value="<?= $sh['id'] ?>" <?= $selectedShopFilter === (int)$sh['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sh['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto; box-shadow: var(--fn-shadow-sm);">
                <table class="fn-price-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Cửa Hàng</th>
                            <th>Dịch Vụ / Lỗi</th>
                            <th>Khoảng Giá</th>
                            <th>Mức Giữa</th>
                            <th>Bảo Hành</th>
                            <th>Thời Gian</th>
                            <th style="text-align: right;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shopPrices)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 24px; color: var(--fn-text-light);">
                                    Không có bảng giá nào phù hợp.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shopPrices as $sp): ?>
                                <tr>
                                    <td>#<?= $sp['id'] ?></td>
                                    <td>
                                        <strong style="color: var(--fn-dark);"><?= htmlspecialchars($sp['shop_name']) ?></strong>
                                    </td>
                                    <td>
                                        <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: <?= ($sp['device_type'] ?? '') === 'laptop' ? '#e0f2fe; color:#0369a1;' : '#fce7f3; color:#be185d;' ?>">
                                            <?= ($sp['device_type'] ?? '') === 'laptop' ? 'Laptop' : 'Điện thoại' ?>
                                        </span>
                                        <span style="font-weight: 700; margin-left: 4px;"><?= htmlspecialchars($sp['service_name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fn-price-range"><?= formatPrice($sp['min_price'] ?? 0) ?> – <?= formatPrice($sp['max_price'] ?? 0) ?></span>
                                    </td>
                                    <td>
                                        <span style="color: #c2410c; font-weight: 700; font-size: 12.5px;">
                                            <?= formatPrice((int) round(((int) ($sp['min_price'] ?? 0) + (int) ($sp['max_price'] ?? 0)) / 2 / 10000) * 10000) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($sp['warranty_text'] ?? $sp['warranty'] ?? '—') ?></td>
                                    <td>⏱️ <?= htmlspecialchars($sp['turnaround_text'] ?? $sp['turnaround'] ?? '—') ?></td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="services.php" onsubmit="return confirm('Bạn có chắc muốn xóa mức giá này?')" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_price">
                                            <input type="hidden" name="price_id" value="<?= $sp['id'] ?>">
                                            <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 4px 8px; font-size: 11px; color: #ef4444;">
                                                🗑️ Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danh sách 19 dịch vụ chuẩn hóa -->
        <h2 style="font-family: var(--fn-font-heading); font-size: 18px; font-weight: 800; margin-bottom: 14px;">
            Danh Mục 19 Dịch Vụ Cốt Lõi Của Hệ Thống
        </h2>

        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow: hidden;">
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
