<?php
$pageTitle = "Đăng Ký Thành Viên — FixNear";
require_once __DIR__ . '/../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    if (!enforceRateLimit('register', 5, 3600)) {
        http_response_code(429);
        $error = 'Bạn đã gửi quá nhiều yêu cầu đăng ký. Vui lòng thử lại sau.';
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($error !== '') {
        // Giữ thông báo giới hạn đã đặt ở trên.
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = "Vui lòng điền đầy đủ các thông tin bắt buộc.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Địa chỉ email không đúng định dạng.";
    } elseif (mb_strlen($name) > 100 || mb_strlen($email) > 150 || mb_strlen($phone) > 20) {
        $error = "Một hoặc nhiều trường vượt quá độ dài cho phép.";
    } elseif (strlen($password) < 10 || strlen($password) > 200) {
        $error = "Mật khẩu phải có từ 10 đến 200 ký tự.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp! Vui lòng kiểm tra lại.";
    } elseif (db()->getUserByEmail($email)) {
        $error = "Email này đã được đăng ký tài khoản trong hệ thống.";
    } else {
        $newUser = db()->createUser([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user'
        ]);
        if ($newUser === false) {
            $error = "Không thể tạo tài khoản lúc này hoặc email vừa được đăng ký. Vui lòng thử lại.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUser['id'];
            $_SESSION['user_name'] = $newUser['name'];
            $_SESSION['user_role'] = 'user';
            header("Location: index.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-container" style="max-width: 480px; padding: 60px 20px;">
    <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 36px 32px; box-shadow: var(--fn-shadow);">
        <div style="text-align: center; margin-bottom: 28px;">
            <div class="fn-logo-icon" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">⚡</div>
            <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                Đăng Ký Thành Viên FixNear
            </h1>
            <p style="font-size: 13.5px; color: var(--fn-dark-muted); margin-top: 6px;">
                Cộng đồng tra cứu và chia sẻ trải nghiệm sửa chữa tại TP.HCM
            </p>
        </div>

        <?php if ($error): ?>
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 6px; color: #991b1b; font-size: 13px; margin-bottom: 20px; font-weight: 600;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="fn-reg-form">
            <?= csrfField() ?>
            <div class="fn-form-group">
                <label class="fn-label" for="register-name">Họ và tên của bạn:</label>
                <input type="text" id="register-name" name="name" class="fn-input" placeholder="Ví dụ: Nguyễn Văn Nam" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="name" maxlength="100" required>
            </div>

            <div class="fn-form-group">
                <label class="fn-label" for="register-email">Địa chỉ Email:</label>
                <input type="email" id="register-email" name="email" class="fn-input" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" spellcheck="false" maxlength="150" required>
            </div>

            <div class="fn-form-group">
                <label class="fn-label" for="register-phone">Số điện thoại liên hệ:</label>
                <input type="tel" id="register-phone" name="phone" class="fn-input" placeholder="Ví dụ: 0901234567 (hoặc để trống)" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel" inputmode="tel" maxlength="20">
            </div>

            <div class="fn-form-group">
                <label class="fn-label" for="reg-password">Mật khẩu:</label>
                <input type="password" name="password" id="reg-password" class="fn-input" placeholder="Tối thiểu 10 ký tự" autocomplete="new-password" required minlength="10" maxlength="200">
            </div>

            <div class="fn-form-group">
                <label class="fn-label" for="reg-confirm-password">Xác nhận mật khẩu:</label>
                <input type="password" name="confirm_password" id="reg-confirm-password" class="fn-input" placeholder="Nhập lại chính xác mật khẩu" autocomplete="new-password" required minlength="10" maxlength="200" aria-describedby="pwd-match-msg">
                <small id="pwd-match-msg" style="display: none; font-size: 12px; margin-top: 6px; font-weight: 700;"></small>
            </div>

            <button type="submit" class="fn-btn fn-btn-primary" style="width: 100%; margin-top: 10px; padding: 13px; font-size: 15px;">
                Tạo Tài Khoản
            </button>
        </form>

        <script>
        const p1 = document.getElementById('reg-password');
        const p2 = document.getElementById('reg-confirm-password');
        const msg = document.getElementById('pwd-match-msg');
        function checkPwd() {
            if (!p2.value) { msg.style.display = 'none'; return; }
            msg.style.display = 'block';
            if (p1.value === p2.value) {
                msg.textContent = '✓ Mật khẩu hoàn toàn trùng khớp';
                msg.style.color = '#16a34a';
                p2.style.borderColor = '#16a34a';
            } else {
                msg.textContent = '✗ Mật khẩu xác nhận chưa khớp!';
                msg.style.color = '#dc2626';
                p2.style.borderColor = '#dc2626';
            }
        }
        p1.addEventListener('input', checkPwd);
        p2.addEventListener('input', checkPwd);
        </script>

        <div style="text-align: center; margin-top: 24px; font-size: 13.5px; color: var(--fn-dark-muted);">
            Đã có tài khoản? <a href="login.php" style="color: var(--fn-primary); font-weight: 700;">Đăng nhập</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
