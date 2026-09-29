import json
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
data_dir = os.path.join(base_dir, 'data')

with open(os.path.join(data_dir, 'shops.json'), 'r', encoding='utf-8') as f:
    shops = json.load(f)

with open(os.path.join(data_dir, 'services.json'), 'r', encoding='utf-8') as f:
    services = json.load(f)

with open(os.path.join(data_dir, 'users.json'), 'r', encoding='utf-8') as f:
    users = json.load(f)

with open(os.path.join(data_dir, 'reviews.json'), 'r', encoding='utf-8') as f:
    reviews = json.load(f)

def esc(s):
    if s is None:
        return 'NULL'
    s_val = str(s).replace('\\', '\\\\').replace("'", "\\'").replace('\n', '\\n')
    return f"'{s_val}'"

sql = """-- ==========================================================
-- CƠ SỞ DỮ LIỆU DỰ ÁN FIXNEAR - NỀN TẢNG KẾT NỐI SỬA CHỮA UY TÍN TẠI TP.HCM
-- Dữ liệu thực tế 68 cửa hàng uy tín phủ khắp 17 quận nội thành TP.HCM (4 cửa hàng/quận)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `fixnear_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fixnear_db`;

-- 1. Bảng USERS
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') DEFAULT 'user',
  `phone` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng SHOPS
DROP TABLE IF EXISTS `shops`;
CREATE TABLE `shops` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `ward` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `opening_hours` VARCHAR(100) DEFAULT '08:00 - 21:00',
  `map_url` TEXT,
  `latitude` DECIMAL(10, 6) DEFAULT 10.853800,
  `longitude` DECIMAL(10, 6) DEFAULT 106.626300,
  `description` TEXT,
  `google_rating` DECIMAL(2, 1) DEFAULT 5.0,
  `google_reviews_count` INT DEFAULT 1,
  `devices` VARCHAR(100) DEFAULT 'laptop,phone',
  `is_verified` TINYINT(1) DEFAULT 1,
  `allows_onsite_watch` TINYINT(1) DEFAULT 1,
  `requires_component_signing` TINYINT(1) DEFAULT 1,
  `student_discount` VARCHAR(255) DEFAULT NULL,
  `image` TEXT,
  `website` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng SERVICES
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `device_type` VARCHAR(50) NOT NULL,
  `devices` VARCHAR(100) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'tool',
  `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng BOOKINGS (Lịch hẹn sửa chữa & yêu cầu báo giá)
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL DEFAULT 1,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) DEFAULT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `device_type` VARCHAR(100) NOT NULL,
  `issue_description` TEXT,
  `preferred_date` DATE NOT NULL,
  `preferred_time` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'contacted', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng REVIEWS
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL,
  `device_name` VARCHAR(100) DEFAULT NULL,
  `service_repaired` VARCHAR(150) DEFAULT NULL,
  `comment` TEXT NOT NULL,
  `is_hidden` TINYINT(1) DEFAULT 0,
  `admin_reply` TEXT DEFAULT NULL,
  `admin_reply_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng WRONG_INFO_REPORTS (Báo cáo thông tin sai)
DROP TABLE IF EXISTS `wrong_info_reports`;
CREATE TABLE `wrong_info_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL,
  `reporter_name` VARCHAR(100) NOT NULL,
  `reporter_phone` VARCHAR(50) DEFAULT NULL,
  `reason` VARCHAR(200) NOT NULL,
  `details` TEXT,
  `status` ENUM('pending', 'resolved') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""

# Insert Users
user_rows = []
for u in users:
    user_rows.append(f"({u['id']}, {esc(u.get('name'))}, {esc(u.get('email'))}, {esc(u.get('password'))}, {esc(u.get('role', 'user'))}, {esc(u.get('phone', ''))})")
sql += "\n-- Dữ liệu tài khoản\nINSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`) VALUES\n" + ",\n".join(user_rows) + ";\n"

# Insert Services
svc_rows = []
for s in services:
    devs = ','.join(s.get('devices', [])) if isinstance(s.get('devices'), list) else str(s.get('devices', ''))
    svc_rows.append(f"({s['id']}, {esc(s.get('name'))}, {esc(s.get('device_type', 'all'))}, {esc(devs)}, {esc(s.get('icon', 'tool'))}, {esc(s.get('description'))})")
sql += "\n-- Dữ liệu dịch vụ & lỗi\nINSERT INTO `services` (`id`, `name`, `device_type`, `devices`, `icon`, `description`) VALUES\n" + ",\n".join(svc_rows) + ";\n"

# Insert Shops
shop_rows = []
for s in shops:
    devs = ','.join(s.get('devices', [])) if isinstance(s.get('devices'), list) else str(s.get('devices', ''))
    v_ver = 1 if s.get('is_verified') else 0
    v_wat = 1 if s.get('allows_onsite_watch') else 0
    v_sig = 1 if s.get('requires_component_signing') else 0
    row = f"({s['id']}, {esc(s.get('name'))}, {esc(s.get('address'))}, {esc(s.get('ward'))}, {esc(s.get('district'))}, {esc(s.get('phone'))}, {esc(s.get('opening_hours', '08:00 - 21:00'))}, {esc(s.get('map_url'))}, {s.get('latitude', 10.8538)}, {s.get('longitude', 106.6263)}, {esc(s.get('description'))}, {s.get('google_rating', 5.0)}, {s.get('google_reviews_count', 1)}, {esc(devs)}, {v_ver}, {v_wat}, {v_sig}, {esc(s.get('student_discount', ''))}, {esc(s.get('image'))}, {esc(s.get('website'))})"
    shop_rows.append(row)

sql += "\n-- Dữ liệu 68 cửa hàng uy tín (17 quận nội thành TP.HCM)\nINSERT INTO `shops` (`id`, `name`, `address`, `ward`, `district`, `phone`, `opening_hours`, `map_url`, `latitude`, `longitude`, `description`, `google_rating`, `google_reviews_count`, `devices`, `is_verified`, `allows_onsite_watch`, `requires_component_signing`, `student_discount`, `image`, `website`) VALUES\n" + ",\n".join(shop_rows) + ";\n"

# Insert Reviews
rev_rows = []
for r in reviews:
    h = 1 if r.get('is_hidden') else 0
    row = f"({r['id']}, {r['shop_id']}, {r.get('user_id', 1)}, {esc(r.get('user_name'))}, {r.get('rating', 5)}, {esc(r.get('device_name', ''))}, {esc(r.get('service_repaired', ''))}, {esc(r.get('comment'))}, {h}, {esc(r.get('admin_reply'))}, {esc(r.get('admin_reply_at'))}, {esc(r.get('created_at', '2026-09-15 10:00:00'))})"
    rev_rows.append(row)

sql += "\n-- Dữ liệu đánh giá thực tế\nINSERT INTO `reviews` (`id`, `shop_id`, `user_id`, `user_name`, `rating`, `device_name`, `service_repaired`, `comment`, `is_hidden`, `admin_reply`, `admin_reply_at`, `created_at`) VALUES\n" + ",\n".join(rev_rows) + ";\n"

out_file = os.path.join(base_dir, 'fixnear_db.sql')
with open(out_file, 'w', encoding='utf-8') as f:
    f.write(sql)

print(f"Generated {out_file} successfully with {len(shops)} shops and {len(reviews)} reviews!")
