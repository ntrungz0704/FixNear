<?php
$pageTitle = "Đăng Nhập — FixNear";
require_once __DIR__ . '/../config/db.php';

$error = '';
$redirect = safeLocalRedirect($_GET['redirect'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    if (!enforceRateLimit('login', 8, 900)) {
        http_response_code(429);
        $error = 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng đợi 15 phút rồi thử lại.';
    }
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
    $password = (string)($_POST['password'] ?? '');

    $user = $error === '' && filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 150
        ? db()->getUserByEmail($email)
        : null;
    $authenticated = false;
    if ($user && is_string($user['password'] ?? null) && password_verify($password, $user['password'])) {
        $authenticated = true;
    }

    if ($authenticated) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: " . $redirect);
        }
        exit;
    } elseif ($error === '') {
        $error = "Email hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại!";
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-container" style="max-width: 460px; padding: 60px 20px;">
    <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 36px 32px; box-shadow: var(--fn-shadow);">
        <div style="text-align: center; margin-bottom: 28px;">
            <div class="fn-logo-icon" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 22px;">🔧</div>
            <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                Đăng Nhập FixNear
            </h1>
            <p style="font-size: 13.5px; color: var(--fn-dark-muted); margin-top: 6px;">
                Đăng nhập tài khoản để lưu cửa hàng yêu thích và gửi đánh giá cộng đồng
            </p>
        </div>

        <?php if ($error): ?>
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 6px; color: #991b1b; font-size: 13px; margin-bottom: 20px; font-weight: 600;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php?redirect=<?= urlencode($redirect) ?>" method="POST" id="login-form">
            <?= csrfField() ?>
            <div class="fn-form-group">
                <label class="fn-label" for="login-email">Địa chỉ Email:</label>
                <input type="email" name="email" id="login-email" class="fn-input" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" spellcheck="false" maxlength="150" required>
            </div>

            <div class="fn-form-group">
                <label class="fn-label" for="login-password">Mật khẩu:</label>
                <input type="password" name="password" id="login-password" class="fn-input" placeholder="••••••••" autocomplete="current-password" maxlength="200" required>
            </div>

            <button type="submit" class="fn-btn fn-btn-primary" style="width: 100%; margin-top: 10px; padding: 13px; font-size: 15px;">
                Đăng Nhập
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px; font-size: 13.5px; color: var(--fn-dark-muted);">
            Chưa có tài khoản? <a href="register.php" style="color: var(--fn-primary); font-weight: 700;">Đăng ký ngay</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
