<?php
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
requireValidCsrf();
if (!empty($_SESSION['admin_user_id']) && empty($_POST['logout_admin'])) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    header("Location: index.php");
    exit;
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header("Location: index.php");
exit;
