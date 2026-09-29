<?php
$installerAllowed = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && (getenv('FIXNEAR_ENABLE_INSTALLER') === '1');
if (!$installerAllowed) {
    http_response_code(404);
    exit('Not found');
}

$pageTitle = "Khởi Tạo Cơ Sở Dữ Liệu MySQL — FixNear Installer";
require_once __DIR__ . '/includes/header.php';

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'fixnear_db';

$status = '';
$error = '';

if (isset($_POST['install_db'])) {
    requireValidCsrf();
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sql = file_get_contents(__DIR__ . '/fixnear_db.sql');
        $pdo->exec($sql);
        $status = "Đã nhập bản dữ liệu phát triển vào MySQL. Lưu ý: website hiện dùng JSON làm nguồn dữ liệu runtime.";
    } catch (Exception $e) {
        $error = "Không thể kết nối MySQL: " . $e->getMessage() . ". Hãy đảm bảo bạn đã bật nút Start MySQL trong phần mềm XAMPP Control Panel!";
    }
}
?>

<div class="fn-container" style="max-width: 650px; padding: 60px 20px;">
    <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 36px; box-shadow: var(--fn-shadow);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="fn-logo-icon" style="margin: 0 auto 12px; width: 50px; height: 50px; font-size: 24px;">⚡</div>
            <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                Trình Cài Đặt MySQL 1-Click
            </h1>
            <p style="font-size: 14px; color: var(--fn-dark-muted); margin-top: 6px;">
                Công cụ cục bộ để nhập bản dữ liệu phát triển cũ vào XAMPP; không dùng công cụ này trên host production.
            </p>
        </div>

        <?php if ($status): ?>
            <div style="background: #dcfce7; border-left: 4px solid #10b981; padding: 16px; border-radius: 6px; color: #166534; font-size: 14.5px; margin-bottom: 24px; line-height: 1.5;">
                🎉 <strong>Thành công:</strong> <?= htmlspecialchars($status) ?>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="index.php" class="fn-btn fn-btn-primary" style="flex: 1;">Vào Trang Chủ Ngay</a>
                <a href="admin/index.php" class="fn-btn fn-btn-secondary" style="flex: 1;">Vào Trang Quản Trị</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 14px; border-radius: 6px; color: #991b1b; font-size: 13.5px; margin-bottom: 20px; line-height: 1.5;">
                    ⚠️ <strong>Lỗi:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div style="background: #f8fafc; border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 18px; font-size: 13px; color: var(--fn-dark-muted); margin-bottom: 24px; line-height: 1.6;">
                <p><strong>💡 Lưu ý quan trọng:</strong></p>
                <ul style="padding-left: 20px; margin-top: 6px;">
                    <li>FixNear hiện đọc và ghi dữ liệu runtime từ JSON.</li>
                    <li>File SQL chỉ phục vụ tham khảo/phát triển và chứa dữ liệu chưa được đối soát đầy đủ. Không nhập lên production.</li>
                </ul>
            </div>

            <form action="install.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="install_db" value="1">
                <button type="submit" class="fn-btn fn-btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                    🚀 Bắt Đầu Cài Đặt CSDL MySQL Vào XAMPP
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" style="font-size: 13px; color: var(--fn-primary); font-weight: 700;">
                    &larr; Bỏ qua, tiếp tục sử dụng với File Database
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
