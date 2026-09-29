<?php
require_once __DIR__ . '/../config/db.php';
$currentUser = currentUser();
$assetPrefix = str_contains(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/admin/') ? '../' : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title><?= htmlspecialchars($pageTitle ?? 'FixNear — Tra Cứu Sửa Chữa Thiết Bị Tại TP.HCM') ?></title>
    <meta name="description" content="FixNear — Nền tảng thử nghiệm tra cứu bản ghi cửa hàng và nhóm dịch vụ sửa chữa thiết bị tại TP.HCM.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔧</text></svg>">
    
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <!-- Tailwind CSS (HTML5, CSS3 Utility Stack) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        fnblue: '#2563eb',
                        fndark: '#0f172a'
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= $assetPrefix ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<a class="fn-skip-link" href="#fn-main-content">Bỏ qua điều hướng</a>
<div id="fn-main-content" tabindex="-1">
