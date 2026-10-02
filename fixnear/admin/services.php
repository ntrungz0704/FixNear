<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$services = db()->getServices();
$shops = db()->getShops();

// Thêm khoảng giá mới cho cửa hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_price'])) {
    requireValidCsrf();
    $shop_id = (int)$_POST['shop_id'];
    $service_id = (int)$_POST['service_id'];
    $min_price = (float)$_POST['min_price'];
    $max_price = (float)$_POST['max_price'];
    $warranty = trim($_POST['warranty'] ?? '6 tháng');
    $turnaround = trim($_POST['turnaround'] ?? '30 phút');
    $note = trim($_POST['note'] ?? '');

    db()->saveShopService([
        'shop_id' => $shop_id,
        'service_id' => $service_id,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'warranty' => $warranty,
        'turnaround' => $turnaround,
        'note' => $note
    ]);
    header("Location: services.php?msg=price_added");
    exit;
}

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
        <a href="contacts.php" class="fn-admin-menu-item">
            💬 Tin nhắn liên hệ
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
                    Quản Lý 19 Lỗi & Khoảng Giá Tham Khảo
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted);">
                    Chuẩn hóa danh mục 10 lỗi Laptop và 9 lỗi Điện thoại theo thiết kế đề tài.
                </p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 20px;">
                ✓ Đã cập nhật bảng giá tham khảo thành công!
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
                        <label class="fn-label" style="font-size: 12px;">Chọn cửa hàng:</label>
                        <select name="shop_id" class="fn-select" required>
                            <?php foreach ($shops as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="fn-label" style="font-size: 12px;">Chọn dịch vụ / lỗi:</label>
                        <select name="service_id" class="fn-select" required>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['id'] ?>">
                                    [<?= $srv['device_type'] === 'laptop' ? 'Laptop' : 'ĐT' ?>] <?= htmlspecialchars($srv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="fn-label" style="font-size: 12px;">Giá min (VNĐ):</label>
                        <input type="number" name="min_price" class="fn-input" placeholder="VD: 350000" required>
                    </div>

                    <div>
                        <label class="fn-label" style="font-size: 12px;">Giá max (VNĐ):</label>
                        <input type="number" name="max_price" class="fn-input" placeholder="VD: 850000" required>
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
                        <label class="fn-label" style="font-size: 12px;">Ghi chú:</label>
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
