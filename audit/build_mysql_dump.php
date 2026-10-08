<?php
/**
 * Script sinh file fixnear_db.sql hoàn chỉnh từ cấu trúc dữ liệu chuẩn của FixNear
 */

$rootDir = dirname(__DIR__);
$dataDir = $rootDir . '/data';

function readJson($path) {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function escapeSql($val) {
    if ($val === null) return 'NULL';
    if (is_bool($val)) return $val ? '1' : '0';
    if (is_int($val) || is_float($val)) return (string)$val;
    $val = str_replace(["\\", "'"], ["\\\\", "''"], (string)$val);
    return "'" . $val . "'";
}

$sql = "-- ==========================================================\n";
$sql .= "-- CƠ SỞ DỮ LIỆU CHUẨN FIXNEAR (MYSQL / MARIADB)\n";
$sql .= "-- Tech Stack: HTML5, CSS3, JavaScript, PHP 8.x, MySQL\n";
$sql .= "-- Tự động tương thích 100% với XAMPP phpMyAdmin / Laragon\n";
$sql .= "-- ==========================================================\n\n";
$sql .= "CREATE DATABASE IF NOT EXISTS `fixnear_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sql .= "USE `fixnear_db`;\n\n";

// 1. USERS
$sql .= "-- 1. BẢNG USERS\n";
$sql .= "DROP TABLE IF EXISTS `users`;\n";
$sql .= "CREATE TABLE `users` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `name` VARCHAR(150) NOT NULL,\n";
$sql .= "  `email` VARCHAR(150) NOT NULL UNIQUE,\n";
$sql .= "  `password` VARCHAR(255) NOT NULL,\n";
$sql .= "  `role` ENUM('admin', 'user') DEFAULT 'user',\n";
$sql .= "  `phone` VARCHAR(50) DEFAULT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$users = readJson($dataDir . '/users.json');
$userRows = [];
foreach ($users as $u) {
    $userRows[] = sprintf(
        "(%s, %s, %s, %s, %s, %s, %s)",
        escapeSql($u['id']),
        escapeSql($u['name']),
        escapeSql($u['email']),
        escapeSql($u['password']),
        escapeSql($u['role'] ?? 'user'),
        escapeSql($u['phone'] ?? null),
        escapeSql($u['created_at'] ?? date('Y-m-d H:i:s'))
    );
}
if ($userRows) {
    $sql .= "INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `created_at`) VALUES\n";
    $sql .= implode(",\n", $userRows) . ";\n\n";
}

// 2. SHOPS
$sql .= "-- 2. BẢNG SHOPS (68 Cửa Hàng Tại TP.HCM)\n";
$sql .= "DROP TABLE IF EXISTS `shops`;\n";
$sql .= "CREATE TABLE `shops` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `name` VARCHAR(255) NOT NULL,\n";
$sql .= "  `address` VARCHAR(255) NOT NULL,\n";
$sql .= "  `ward` VARCHAR(100) NOT NULL,\n";
$sql .= "  `district` VARCHAR(100) NOT NULL,\n";
$sql .= "  `phone` VARCHAR(50) NOT NULL,\n";
$sql .= "  `opening_hours` VARCHAR(100) DEFAULT '08:00 - 21:00',\n";
$sql .= "  `map_url` TEXT,\n";
$sql .= "  `latitude` DECIMAL(10, 6) DEFAULT 10.853800,\n";
$sql .= "  `longitude` DECIMAL(10, 6) DEFAULT 106.626300,\n";
$sql .= "  `description` TEXT,\n";
$sql .= "  `google_rating` DECIMAL(2, 1) DEFAULT NULL,\n";
$sql .= "  `google_reviews_count` INT DEFAULT NULL,\n";
$sql .= "  `devices` VARCHAR(150) DEFAULT 'laptop,phone',\n";
$sql .= "  `is_verified` TINYINT(1) DEFAULT 0,\n";
$sql .= "  `allows_onsite_watch` TINYINT(1) DEFAULT 0,\n";
$sql .= "  `requires_component_signing` TINYINT(1) DEFAULT 0,\n";
$sql .= "  `source_url` TEXT,\n";
$sql .= "  `verified_at` DATETIME DEFAULT NULL,\n";
$sql .= "  `google_place_id` VARCHAR(255) DEFAULT NULL,\n";
$sql .= "  `google_verified_at` DATETIME DEFAULT NULL,\n";
$sql .= "  `student_discount_source_url` TEXT,\n";
$sql .= "  `student_discount_verified_at` DATETIME DEFAULT NULL,\n";
$sql .= "  `policy_source_url` TEXT,\n";
$sql .= "  `policy_verified_at` DATETIME DEFAULT NULL,\n";
$sql .= "  `student_discount` VARCHAR(255) DEFAULT NULL,\n";
$sql .= "  `image` TEXT,\n";
$sql .= "  `website` VARCHAR(255) DEFAULT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$shops = readJson($dataDir . '/shops.json');
$sql .= "INSERT INTO `shops` (`id`, `name`, `address`, `ward`, `district`, `phone`, `opening_hours`, `map_url`, `latitude`, `longitude`, `description`, `google_rating`, `google_reviews_count`, `devices`, `is_verified`, `allows_onsite_watch`, `requires_component_signing`, `student_discount`, `image`, `website`, `created_at`) VALUES\n";
$shopRows = [];
foreach ($shops as $s) {
    $devStr = is_array($s['devices'] ?? null) ? implode(',', $s['devices']) : ($s['devices'] ?? 'laptop,phone');
    $shopRows[] = sprintf(
        "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        escapeSql($s['id']),
        escapeSql($s['name']),
        escapeSql($s['address']),
        escapeSql($s['ward'] ?? ''),
        escapeSql($s['district']),
        escapeSql($s['phone']),
        escapeSql($s['opening_hours'] ?? '08:00 - 21:00'),
        escapeSql($s['map_url'] ?? ''),
        escapeSql($s['latitude'] ?? 10.8538),
        escapeSql($s['longitude'] ?? 106.6263),
        escapeSql($s['description'] ?? ''),
        escapeSql(!empty($s['google_place_id']) && !empty($s['google_verified_at']) ? ($s['google_rating'] ?? null) : null),
        escapeSql(!empty($s['google_place_id']) && !empty($s['google_verified_at']) ? ($s['google_reviews_count'] ?? null) : null),
        escapeSql($devStr),
        escapeSql(!empty($s['source_url']) && !empty($s['verified_at']) ? 1 : 0),
        escapeSql(!empty($s['policy_source_url']) && !empty($s['policy_verified_at']) && !empty($s['allows_onsite_watch']) ? 1 : 0),
        escapeSql(!empty($s['policy_source_url']) && !empty($s['policy_verified_at']) && !empty($s['requires_component_signing']) ? 1 : 0),
        escapeSql(!empty($s['student_discount_source_url']) && !empty($s['student_discount_verified_at']) ? ($s['student_discount'] ?? null) : null),
        escapeSql($s['image'] ?? ''),
        escapeSql($s['website'] ?? null),
        escapeSql($s['created_at'] ?? date('Y-m-d H:i:s'))
    );
}
$sql .= implode(",\n", $shopRows) . ";\n\n";

// 3. SERVICES
$sql .= "-- 3. BẢNG SERVICES (18 Nhóm Dịch Vụ Sửa Chữa Chuẩn)\n";
$sql .= "DROP TABLE IF EXISTS `services`;\n";
$sql .= "CREATE TABLE `services` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `name` VARCHAR(150) NOT NULL,\n";
$sql .= "  `device_type` VARCHAR(50) NOT NULL,\n";
$sql .= "  `devices` VARCHAR(150) DEFAULT NULL,\n";
$sql .= "  `icon` VARCHAR(50) DEFAULT 'tool',\n";
$sql .= "  `description` TEXT\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$services = readJson($dataDir . '/services.json');
$sql .= "INSERT INTO `services` (`id`, `name`, `device_type`, `devices`, `icon`, `description`) VALUES\n";
$serviceRows = [];
foreach ($services as $srv) {
    $devStr = is_array($srv['devices'] ?? null) ? implode(',', $srv['devices']) : ($srv['devices'] ?? '');
    $serviceRows[] = sprintf(
        "(%s, %s, %s, %s, %s, %s)",
        escapeSql($srv['id']),
        escapeSql($srv['name']),
        escapeSql($srv['device_type'] ?? 'all'),
        escapeSql($devStr),
        escapeSql($srv['icon'] ?? 'tool'),
        escapeSql($srv['description'] ?? '')
    );
}
$sql .= implode(",\n", $serviceRows) . ";\n\n";

// 4. SHOP_SERVICES
$sql .= "-- 4. BẢNG SHOP_SERVICES (Bảng Giá & Dịch Vụ Chi Tiết Từng Cửa Hàng)\n";
$sql .= "DROP TABLE IF EXISTS `shop_services`;\n";
$sql .= "CREATE TABLE `shop_services` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `shop_id` INT NOT NULL,\n";
$sql .= "  `service_id` INT NOT NULL,\n";
$sql .= "  `min_price` INT DEFAULT 0,\n";
$sql .= "  `max_price` INT DEFAULT 0,\n";
$sql .= "  `warranty_text` VARCHAR(100) DEFAULT NULL,\n";
$sql .= "  `turnaround_text` VARCHAR(100) DEFAULT NULL,\n";
$sql .= "  `source_url` TEXT,\n";
$sql .= "  `verified_at` DATETIME DEFAULT NULL,\n";
$sql .= "  `note` TEXT,\n";
$sql .= "  INDEX `idx_shop` (`shop_id`),\n";
$sql .= "  INDEX `idx_service` (`service_id`)\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$shopServices = readJson($dataDir . '/shop_services.json');
$sql .= "INSERT INTO `shop_services` (`id`, `shop_id`, `service_id`, `min_price`, `max_price`, `warranty_text`, `turnaround_text`, `note`) VALUES\n";
$ssBatches = [];
$batchCount = 0;
foreach ($shopServices as $ss) {
    $ssBatches[] = sprintf(
        "(%s, %s, %s, %s, %s, %s, %s, %s)",
        escapeSql($ss['id']),
        escapeSql($ss['shop_id']),
        escapeSql($ss['service_id']),
        escapeSql($ss['min_price'] ?? 0),
        escapeSql($ss['max_price'] ?? 0),
        escapeSql($ss['warranty_text'] ?? '6 - 12 tháng'),
        escapeSql($ss['turnaround_text'] ?? '30 - 60 phút'),
        escapeSql($ss['note'] ?? '')
    );
}
$sql .= implode(",\n", $ssBatches) . ";\n\n";

// 5. REPAIR_REQUESTS
$sql .= "-- 5. BẢNG REPAIR_REQUESTS (Hồ Sơ Yêu Cầu Báo Giá & Đặt Lịch Sửa Chữa)\n";
$sql .= "DROP TABLE IF EXISTS `repair_requests`;\n";
$sql .= "CREATE TABLE `repair_requests` (\n";
$sql .= "  `id` VARCHAR(30) PRIMARY KEY,\n";
$sql .= "  `customer_name` VARCHAR(150) NOT NULL,\n";
$sql .= "  `customer_email` VARCHAR(150) DEFAULT NULL,\n";
$sql .= "  `customer_phone` VARCHAR(50) NOT NULL,\n";
$sql .= "  `device_type` VARCHAR(100) NOT NULL,\n";
$sql .= "  `brand_model` VARCHAR(150) DEFAULT NULL,\n";
$sql .= "  `issue_type` VARCHAR(200) DEFAULT NULL,\n";
$sql .= "  `symptom` TEXT,\n";
$sql .= "  `district` VARCHAR(100) DEFAULT NULL,\n";
$sql .= "  `preferred_time` VARCHAR(100) DEFAULT NULL,\n";
$sql .= "  `estimated_price` VARCHAR(150) DEFAULT NULL,\n";
$sql .= "  `status` ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$reqs = readJson($dataDir . '/repair_requests.json');
if (!empty($reqs)) {
    $sql .= "INSERT INTO `repair_requests` (`id`, `customer_name`, `customer_email`, `customer_phone`, `device_type`, `brand_model`, `issue_type`, `symptom`, `district`, `preferred_time`, `estimated_price`, `status`, `created_at`) VALUES\n";
    $reqRows = [];
    foreach ($reqs as $r) {
        $reqRows[] = sprintf(
            "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            escapeSql($r['id']),
            escapeSql($r['customer_name']),
            escapeSql($r['customer_email'] ?? null),
            escapeSql($r['customer_phone']),
            escapeSql($r['device_type']),
            escapeSql($r['brand_model'] ?? ''),
            escapeSql($r['issue_type'] ?? ''),
            escapeSql($r['symptom'] ?? ''),
            escapeSql($r['district'] ?? ''),
            escapeSql($r['preferred_time'] ?? ''),
            escapeSql($r['estimated_price'] ?? ''),
            escapeSql($r['status'] ?? 'pending'),
            escapeSql($r['created_at'] ?? date('Y-m-d H:i:s'))
        );
    }
    $sql .= implode(",\n", $reqRows) . ";\n\n";
}

// 6. REVIEWS
$sql .= "-- 6. BẢNG REVIEWS (Đánh Giá Minh Bạch Của Sinh Viên & Khách Hàng)\n";
$sql .= "DROP TABLE IF EXISTS `reviews`;\n";
$sql .= "CREATE TABLE `reviews` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `shop_id` INT NOT NULL,\n";
$sql .= "  `user_id` INT DEFAULT NULL,\n";
$sql .= "  `user_name` VARCHAR(150) NOT NULL,\n";
$sql .= "  `rating` INT NOT NULL,\n";
$sql .= "  `device_name` VARCHAR(150) DEFAULT NULL,\n";
$sql .= "  `service_repaired` VARCHAR(200) DEFAULT NULL,\n";
$sql .= "  `comment` TEXT NOT NULL,\n";
$sql .= "  `is_hidden` TINYINT(1) DEFAULT 0,\n";
$sql .= "  `origin` VARCHAR(32) NOT NULL DEFAULT 'seed',\n";
$sql .= "  `admin_reply` TEXT DEFAULT NULL,\n";
$sql .= "  `admin_reply_at` DATETIME DEFAULT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
$sql .= "  INDEX `idx_shop_review` (`shop_id`)\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$reviews = readJson($dataDir . '/reviews.json');
if (!empty($reviews)) {
    $sql .= "INSERT INTO `reviews` (`id`, `shop_id`, `user_id`, `user_name`, `rating`, `device_name`, `service_repaired`, `comment`, `is_hidden`, `origin`, `admin_reply`, `admin_reply_at`, `created_at`) VALUES\n";
    $rvRows = [];
    foreach ($reviews as $rv) {
        $rvRows[] = sprintf(
            "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            escapeSql($rv['id']),
            escapeSql($rv['shop_id']),
            escapeSql($rv['user_id'] ?? null),
            escapeSql($rv['user_name']),
            escapeSql($rv['rating'] ?? 5),
            escapeSql($rv['device_name'] ?? null),
            escapeSql($rv['service_repaired'] ?? null),
            escapeSql($rv['comment']),
            escapeSql(!empty($rv['is_hidden']) ? 1 : 0),
            escapeSql($rv['origin'] ?? 'seed'),
            escapeSql($rv['admin_reply'] ?? null),
            escapeSql($rv['admin_reply_at'] ?? null),
            escapeSql($rv['created_at'] ?? date('Y-m-d H:i:s'))
        );
    }
    $sql .= implode(",\n", $rvRows) . ";\n\n";
}

// 7. WRONG_INFO_REPORTS
$sql .= "-- 7. BẢNG WRONG_INFO_REPORTS (Báo Cáo Thông Tin Sai Lệch Từ Cộng Đồng)\n";
$sql .= "DROP TABLE IF EXISTS `wrong_info_reports`;\n";
$sql .= "CREATE TABLE `wrong_info_reports` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `shop_id` INT NOT NULL,\n";
$sql .= "  `shop_name` VARCHAR(200) DEFAULT NULL,\n";
$sql .= "  `user_id` INT DEFAULT NULL,\n";
$sql .= "  `user_name` VARCHAR(150) DEFAULT 'Khách vãng lai',\n";
$sql .= "  `reporter_phone` VARCHAR(50) DEFAULT NULL,\n";
$sql .= "  `reason` VARCHAR(255) NOT NULL,\n";
$sql .= "  `details` TEXT,\n";
$sql .= "  `status` ENUM('pending', 'resolved') DEFAULT 'pending',\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$reports = readJson($dataDir . '/reports.json');
if (!empty($reports)) {
    $sql .= "INSERT INTO `wrong_info_reports` (`id`, `shop_id`, `shop_name`, `user_id`, `user_name`, `reporter_phone`, `reason`, `details`, `status`, `created_at`) VALUES\n";
    $rpRows = [];
    foreach ($reports as $rp) {
        $rpRows[] = sprintf(
            "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            escapeSql($rp['id']),
            escapeSql($rp['shop_id']),
            escapeSql($rp['shop_name'] ?? null),
            escapeSql($rp['user_id'] ?? null),
            escapeSql($rp['user_name'] ?? 'Khách vãng lai'),
            escapeSql($rp['reporter_phone'] ?? null),
            escapeSql($rp['reason']),
            escapeSql($rp['details'] ?? ''),
            escapeSql($rp['status'] ?? 'pending'),
            escapeSql($rp['created_at'] ?? date('Y-m-d H:i:s'))
        );
    }
    $sql .= implode(",\n", $rpRows) . ";\n\n";
}

// 8. CONTACT_MESSAGES
$sql .= "-- 8. BẢNG CONTACT_MESSAGES (Tin Nhắn Liên Hệ Hợp Tác)\n";
$sql .= "DROP TABLE IF EXISTS `contact_messages`;\n";
$sql .= "CREATE TABLE `contact_messages` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `name` VARCHAR(150) NOT NULL,\n";
$sql .= "  `email` VARCHAR(150) NOT NULL,\n";
$sql .= "  `phone` VARCHAR(50) DEFAULT NULL,\n";
$sql .= "  `subject` VARCHAR(255) NOT NULL,\n";
$sql .= "  `message` TEXT NOT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlFile = $dataDir . '/fixnear_db.sql';
file_put_contents($sqlFile, $sql);
echo "Đã tạo thành công file fixnear_db.sql tại: " . $sqlFile . "\n";
echo "Dung lượng file: " . number_format(filesize($sqlFile)) . " bytes.\n";
