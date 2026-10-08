<?php
/**
 * FixNear - Hệ Thống Quản Trị Cơ Sở Dữ Liệu MySQL Chuẩn & Fallback An Toàn
 * Hỗ trợ:
 * 1. MySQL (XAMPP / MariaDB PDO) kết nối trực tiếp CSDL fixnear_db
 * 2. Tự động khởi tạo database & import fixnear_db.sql nếu chưa có
 * 3. Fallback mượt mà sang JSON nếu MySQL chưa được khởi động
 */

require_once __DIR__ . '/app.php';

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken() {
    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$submitted) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded['csrf_token'])) {
                $submitted = $decoded['csrf_token'];
            }
        }
    }
    return is_string($submitted) && $submitted !== '' && hash_equals(csrfToken(), $submitted);
}

function requireValidCsrf() {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    }
}

class FixNearDB {
    private static $instance = null;
    private $pdo = null;
    private $is_mysql = false;
    private $data_dir = '';
    public $radius_auto_expanded = false;
    public $applied_radius = 0;

    private function annotateShopVerification($shop) {
        static $addressEvidence = null;
        if ($addressEvidence === null) {
            $evidenceFile = dirname(__DIR__) . '/data/shop-address-evidence.json';
            $addressEvidence = is_file($evidenceFile) ? (json_decode(file_get_contents($evidenceFile), true) ?: []) : [];
        }
        $recordId = (string) ($shop['id'] ?? '');
        $recordHash = hash('sha256', (string) ($shop['name'] ?? '') . '|' . explode(',', (string) ($shop['address'] ?? ''))[0]);
        $sourceKey = hash_equals((string) ($addressEvidence['recordHashes'][$recordId] ?? ''), $recordHash)
            ? ($addressEvidence['shops'][$recordId] ?? '') : '';
        $shop['address_source_url'] = $sourceKey !== '' ? ($addressEvidence['sources'][$sourceKey] ?? '') : '';
        $shop['address_verified_at'] = $shop['address_source_url'] !== '' ? ($addressEvidence['checkedAt'] ?? '') : '';
        $shop['address_verified'] = $shop['address_source_url'] !== '' && $shop['address_verified_at'] !== '';
        if ($shop['address_verified']) {
            // Tìm theo địa chỉ đã đối chiếu; URL/pin lưu trong dữ liệu mẫu có thể đã cũ.
            $shop['map_url'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(($shop['name'] ?? '') . ', ' . ($shop['address'] ?? ''));
        }
        $shop['coordinates_verified'] = false;
        if (isset($addressEvidence['phoneOverrides'][(string) ($shop['id'] ?? '')])) {
            $shop['phone'] = $addressEvidence['phoneOverrides'][(string) $shop['id']];
        }
        $shop['phone_verified'] = $shop['address_verified'];
        $shop['source_verified'] = !empty($shop['source_url']) && !empty($shop['verified_at']);
        $shop['google_rating_verified'] = !empty($shop['google_place_id']) && !empty($shop['google_verified_at']);
        $shop['student_discount_verified'] = !empty($shop['student_discount_source_url']) && !empty($shop['student_discount_verified_at']);
        $shop['service_policy_verified'] = !empty($shop['policy_source_url']) && !empty($shop['policy_verified_at']);
        // Cờ seed is_verified cũ không phải bằng chứng độc lập.
        $shop['is_verified'] = $shop['source_verified'];
        // Không dùng ảnh stock làm ảnh chi nhánh. Chỉ dùng logo lấy từ website
        // hệ thống; cửa hàng chưa có ảnh chính thức sẽ hiện nhãn trung thực.
        $websiteHost = strtolower((string) (parse_url((string) ($shop['website'] ?? ''), PHP_URL_HOST) ?: ''));
        $websiteHost = preg_replace('/^www\./', '', $websiteHost);
        $websiteLogos = [
            'dienthoaivui.com.vn' => 'https://cdns.dienthoaivui.com.vn/logo.png',
            'fastcare.vn' => 'https://cdn.fastcare.vn/uploads/2025/03/logo-fastcare.png',
            'chamsocdidong.com' => 'https://chamsocdidong.com/images/config/logo-24h-do-011_1767087076.svg',
            'viendidong.com' => 'https://viendidong.com/wp-content/uploads/2024/11/VDD_logo-06.png',
            'saigonso.com' => 'assets/images/shop-sites/saigonso-2026-10-07.png',
            'baohanhone.com' => 'https://cdn.hstatic.net/themes/1000338578/1001463942/14/logo-baohanhone.png?v=614',
            'suachualaptop24h.com' => 'https://suachualaptop24h.com/images/config/sua-chua-logo_1776322654.jpg',
            'iservice.vn' => 'https://theme.hstatic.net/1000353777/1000698522/14/logo.svg?v=1373',
            'icare.center' => 'https://icare.center/uploads/images/setup/trang%20chu/logo-icare(1).svg',
            'libbyrepaircenter.com' => 'https://media.base44.com/images/public/69b928045b5e8103fd70fccd/84613508a_LibbyLogo3D.png',
            'cares.vn' => 'https://cares.vn/wp-content/uploads/elementor/thumbs/logo-cares-1-qkim89c9qeg1uozpdslwn3424w4l9465jf6kkovdvk.png',
            'benhvienlaptop.com' => 'https://benhvienlaptop.com/wp-content/uploads/2026/08/Logo-benhvienlaptop-3.png',
            'capcuulaptop.com' => 'https://capcuulaptop.com/wp-content/uploads/2015/11/logo.png',
            'thegioilaptop24h.com' => 'https://www.thegioilaptop24h.com/assets/frontend/img/logo.png',
            'giahuymobile.com' => 'https://cdn.hstatic.net/files/200000290713/file/logo_mobi_80bb9b049ab440ce94c3a49f1a4a0279.png',
        ];
        $shop['image'] = $websiteLogos[$websiteHost] ?? '';
        $shop['image_kind'] = $websiteHost === 'saigonso.com' ? 'website_snapshot' : ($shop['image'] !== '' ? 'website_logo' : 'none');
        return $shop;
    }

    private function __construct() {
        $this->data_dir = FIXNEAR_DATA_DIR;
        if (!is_dir($this->data_dir) && !mkdir($this->data_dir, 0750, true) && !is_dir($this->data_dir)) {
            // Log fallback
        }
        $this->initMySQL();
    }

    private function initMySQL() {
        if (!extension_loaded('pdo_mysql') || (defined('ENABLE_MYSQL') && !ENABLE_MYSQL) || getenv('FIXNEAR_DISABLE_MYSQL') === '1') {
            $this->is_mysql = false;
            return;
        }

        // Kiểm tra nhanh cổng kết nối MySQL (0.15s) tránh độ trễ TCP timeout khi MySQL chưa bật
        $probe = @fsockopen(DB_HOST, DB_PORT, $errno, $errstr, 0.15);
        if (!$probe) {
            $this->is_mysql = false;
            return;
        }
        fclose($probe);

        try {
            $timeout = defined('DB_TIMEOUT') ? (int)DB_TIMEOUT : 1;
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => $timeout
            ]);
            $this->is_mysql = true;
            $this->ensureTablesExist();
        } catch (PDOException $e) {
            // Nếu lỗi là chưa có database (1049 Unknown database), tự động tạo CSDL và nạp bảng
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                if (FIXNEAR_IS_PRODUCTION) {
                    $this->is_mysql = false;
                    $this->pdo = null;
                    return;
                }
                try {
                    $rawPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $sqlFile = file_exists(dirname(__DIR__) . '/data/fixnear_db.sql')
                        ? dirname(__DIR__) . '/data/fixnear_db.sql'
                        : dirname(__DIR__) . '/fixnear_db.sql';
                    if (file_exists($sqlFile)) {
                        $rawPdo->exec("USE `" . DB_NAME . "`;\n" . file_get_contents($sqlFile));
                    }
                    $this->pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]);
                    $this->is_mysql = true;
                } catch (Exception $ex) {
                    $this->is_mysql = false;
                    $this->pdo = null;
                }
            } else {
                $this->is_mysql = false;
                $this->pdo = null;
            }
        }
    }

    private function ensureTablesExist() {
        if (!$this->pdo) return;
        // SQL đi kèm chứa dữ liệu mẫu; môi trường production phải tự cấp CSDL sạch.
        if (FIXNEAR_IS_PRODUCTION) return;
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'shops'");
            if (!$stmt->fetch()) {
                $sqlFile = file_exists(dirname(__DIR__) . '/data/fixnear_db.sql')
                    ? dirname(__DIR__) . '/data/fixnear_db.sql'
                    : dirname(__DIR__) . '/fixnear_db.sql';
                if (file_exists($sqlFile)) {
                    $this->pdo->exec(file_get_contents($sqlFile));
                }
            }
        } catch (Exception $e) {
            // Bỏ qua lỗi kiểm tra bảng
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isUsingMySQL() {
        return $this->is_mysql && $this->pdo !== null;
    }

    public function getPdo() {
        return $this->pdo;
    }

    // Đọc file JSON fallback an toàn
    private function readJson($filename) {
        $file = $this->data_dir . $filename;
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    // Ghi file JSON fallback an toàn
    private function writeJson($filename, $data) {
        $file = $this->data_dir . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }
        if (!rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }

    private function withJsonLock(string $filename, callable $callback) {
        $lockPath = $this->data_dir . $filename . '.lock';
        $handle = fopen($lockPath, 'c');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) fclose($handle);
            return false;
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    // ================= DỮ LIỆU CỬA HÀNG (SHOPS) =================
    public function getShops($filters = []) {
        if ($this->isUsingMySQL()) {
            $sql = "SELECT * FROM shops WHERE 1=1";
            $params = [];

            if (!empty($filters['device'])) {
                $dev = strtolower(trim($filters['device']));
                $dev = ['win_laptop' => 'laptop', 'macbook' => 'mac', 'pc_desktop' => 'pc'][$dev] ?? $dev;
                $sql .= " AND FIND_IN_SET(:dev, REPLACE(devices, ' ', '')) > 0";
                $params[':dev'] = $dev;
            }

            if (!empty($filters['district'])) {
                $district = trim($filters['district']);
                $sql .= " AND (district LIKE :district OR address LIKE :district_addr)";
                $params[':district'] = '%' . $district . '%';
                $params[':district_addr'] = '%' . $district . '%';
            }

            if (!empty($filters['ward'])) {
                $ward = trim($filters['ward']);
                $sql .= " AND (ward LIKE :ward OR district LIKE :ward_dist OR address LIKE :ward_addr)";
                $params[':ward'] = '%' . $ward . '%';
                $params[':ward_dist'] = '%' . $ward . '%';
                $params[':ward_addr'] = '%' . $ward . '%';
            }

            if (!empty($filters['service_id'])) {
                $sql .= " AND id IN (SELECT shop_id FROM shop_services WHERE service_id = :service_id)";
                $params[':service_id'] = (int)$filters['service_id'];
            }

            if (!empty($filters['keyword'])) {
                $kw = '%' . trim($filters['keyword']) . '%';
                $sql .= " AND (name LIKE :kw1 OR address LIKE :kw2 OR description LIKE :kw3)";
                $params[':kw1'] = $kw;
                $params[':kw2'] = $kw;
                $params[':kw3'] = $kw;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($shops as &$shop) {
                if (isset($shop['devices']) && is_string($shop['devices'])) {
                    $shop['devices'] = array_filter(array_map('trim', explode(',', $shop['devices'])));
                }
                $shop = $this->annotateShopVerification($shop);
            }
            unset($shop);
            if (empty($filters['include_unverified'])) {
                $shops = array_values(array_filter($shops, static fn($shop) => !empty($shop['address_verified'])));
            }

            // Tính GPS & Bán kính
            if (!empty($filters['user_lat']) && !empty($filters['user_lng'])) {
                $uLat = (float)$filters['user_lat'];
                $uLng = (float)$filters['user_lng'];
                foreach ($shops as &$shop) {
                    $shop['distance_km'] = $this->calculateDistance($uLat, $uLng, (float)$shop['latitude'], (float)$shop['longitude']);
                }
                unset($shop);

                if (!empty($filters['radius_km'])) {
                    $maxRadius = (float)$filters['radius_km'];
                    $this->applied_radius = $maxRadius;
                    $filtered = array_filter($shops, fn($s) => ($s['distance_km'] ?? 999) <= $maxRadius);

                    if (empty($filtered) && empty($filters['strict_radius'])) {
                        $this->radius_auto_expanded = true;
                        $maxRadius = 15.0;
                        $this->applied_radius = $maxRadius;
                        $filtered = array_filter($shops, fn($s) => ($s['distance_km'] ?? 999) <= $maxRadius);
                    }
                    $shops = $filtered;
                }
                usort($shops, fn($a, $b) => ($a['distance_km'] ?? 999) <=> ($b['distance_km'] ?? 999));
            } else {
                usort($shops, function($a, $b) {
                    $aRating = !empty($a['google_rating_verified']) ? (float) ($a['google_rating'] ?? 0) : 0.0;
                    $bRating = !empty($b['google_rating_verified']) ? (float) ($b['google_rating'] ?? 0) : 0.0;
                    $rDiff = $bRating <=> $aRating;
                    if ($rDiff !== 0) return $rDiff;
                    $aCount = !empty($a['google_rating_verified']) ? (int) ($a['google_reviews_count'] ?? 0) : 0;
                    $bCount = !empty($b['google_rating_verified']) ? (int) ($b['google_reviews_count'] ?? 0) : 0;
                    return ($bCount <=> $aCount) ?: strnatcasecmp($a['name'], $b['name']);
                });
            }

            return array_values($shops);
        }

        // --- Fallback JSON ---
        $shops = $this->readJson('shops.json');

        if (!empty($filters['device'])) {
            $device = strtolower($filters['device']);
            $device = ['win_laptop' => 'laptop', 'macbook' => 'mac', 'pc_desktop' => 'pc'][$device] ?? $device;
            $shops = array_filter($shops, function($s) use ($device) {
                $devs = $s['devices'] ?? [];
                return in_array($device, $devs, true);
            });
        }

        if (!empty($filters['district'])) {
            $district = trim($filters['district']);
            $shops = array_filter($shops, function($s) use ($district) {
                if (strcasecmp($s['district'], $district) === 0) return true;
                $pattern = '/(?:\b|^)' . preg_quote($district, '/') . '(?!\d)/ui';
                return preg_match($pattern, $s['district']) || preg_match($pattern, $s['address']);
            });
        }

        if (!empty($filters['ward'])) {
            $ward = $filters['ward'];
            $shops = array_filter($shops, function($s) use ($ward) {
                return stripos($s['ward'], $ward) !== false || 
                       stripos($s['district'], $ward) !== false || 
                       stripos($s['address'], $ward) !== false;
            });
        }

        if (!empty($filters['service_id'])) {
            $service_id = (int)$filters['service_id'];
            $shop_services = $this->readJson('shop_services.json');
            $valid_shop_ids = [];
            foreach ($shop_services as $ss) {
                if ($ss['service_id'] == $service_id) {
                    $valid_shop_ids[] = $ss['shop_id'];
                }
            }
            $shops = array_filter($shops, fn($s) => in_array($s['id'], $valid_shop_ids));
        }

        if (!empty($filters['keyword'])) {
            $kw = mb_strtolower($filters['keyword'], 'UTF-8');
            $shops = array_filter($shops, function($s) use ($kw) {
                return mb_stripos($s['name'], $kw, 0, 'UTF-8') !== false ||
                       mb_stripos($s['address'], $kw, 0, 'UTF-8') !== false ||
                       mb_stripos($s['description'], $kw, 0, 'UTF-8') !== false;
            });
        }

        if (!empty($filters['user_lat']) && !empty($filters['user_lng'])) {
            $uLat = (float)$filters['user_lat'];
            $uLng = (float)$filters['user_lng'];
            foreach ($shops as &$shop) {
                $shop['distance_km'] = $this->calculateDistance($uLat, $uLng, (float)$shop['latitude'], (float)$shop['longitude']);
            }
            unset($shop);

            if (!empty($filters['radius_km'])) {
                $maxRadius = (float)$filters['radius_km'];
                $this->applied_radius = $maxRadius;
                $filtered = array_filter($shops, fn($s) => ($s['distance_km'] ?? 999) <= $maxRadius);

                if (empty($filtered) && empty($filters['strict_radius'])) {
                    $this->radius_auto_expanded = true;
                    $maxRadius = 15.0;
                    $this->applied_radius = $maxRadius;
                    $filtered = array_filter($shops, fn($s) => ($s['distance_km'] ?? 999) <= $maxRadius);
                }
                $shops = $filtered;
            }
            usort($shops, fn($a, $b) => ($a['distance_km'] ?? 999) <=> ($b['distance_km'] ?? 999));
        } else {
            usort($shops, function($a, $b) {
                $aRating = !empty($a['google_place_id']) && !empty($a['google_verified_at']) ? (float) ($a['google_rating'] ?? 0) : 0.0;
                $bRating = !empty($b['google_place_id']) && !empty($b['google_verified_at']) ? (float) ($b['google_rating'] ?? 0) : 0.0;
                $rDiff = $bRating <=> $aRating;
                if ($rDiff !== 0) return $rDiff;
                return strnatcasecmp($a['name'], $b['name']);
            });
        }

        $shops = array_values(array_map([$this, 'annotateShopVerification'], $shops));
        if (empty($filters['include_unverified'])) {
            $shops = array_values(array_filter($shops, static fn($shop) => !empty($shop['address_verified'])));
        }
        return $shops;
    }

    public function getShopById($id, $user_lat = null, $user_lng = null) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("SELECT * FROM shops WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$id]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$shop) return null;
            if (isset($shop['devices']) && is_string($shop['devices'])) {
                $shop['devices'] = array_filter(array_map('trim', explode(',', $shop['devices'])));
            }
            if (!empty($user_lat) && !empty($user_lng)) {
                $shop['distance_km'] = $this->calculateDistance((float)$user_lat, (float)$user_lng, (float)$shop['latitude'], (float)$shop['longitude']);
            }
            $shop = $this->annotateShopVerification($shop);
            return isAdmin() || !empty($shop['address_verified']) ? $shop : null;
        }

        $shops = $this->readJson('shops.json');
        foreach ($shops as $s) {
            if ($s['id'] == $id) {
                if (!empty($user_lat) && !empty($user_lng)) {
                    $s['distance_km'] = $this->calculateDistance((float)$user_lat, (float)$user_lng, (float)$s['latitude'], (float)$s['longitude']);
                }
                $s = $this->annotateShopVerification($s);
                return isAdmin() || !empty($s['address_verified']) ? $s : null;
            }
        }
        return null;
    }

    public function saveShop($data) {
        if ($this->isUsingMySQL()) {
            $devStr = is_array($data['devices'] ?? null) ? implode(',', $data['devices']) : ($data['devices'] ?? 'laptop,phone');
            if (!empty($data['id'])) {
                $stmt = $this->pdo->prepare("
                    UPDATE shops SET 
                        name = :name, address = :address, ward = :ward, district = :district, phone = :phone, 
                        opening_hours = :opening_hours, map_url = :map_url, latitude = :latitude, longitude = :longitude, 
                        description = :description, devices = :devices, is_verified = :is_verified, 
                        allows_onsite_watch = :allows_onsite_watch, requires_component_signing = :requires_component_signing, 
                        student_discount = :student_discount, image = :image, website = :website
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => (int)$data['id'],
                    ':name' => $data['name'] ?? '',
                    ':address' => $data['address'] ?? '',
                    ':ward' => $data['ward'] ?? '',
                    ':district' => $data['district'] ?? '',
                    ':phone' => $data['phone'] ?? '',
                    ':opening_hours' => $data['opening_hours'] ?? '08:00 - 21:00',
                    ':map_url' => $data['map_url'] ?? '',
                    ':latitude' => $data['latitude'] ?? 10.8538,
                    ':longitude' => $data['longitude'] ?? 106.6263,
                    ':description' => $data['description'] ?? '',
                    ':devices' => $devStr,
                    ':is_verified' => !empty($data['is_verified']) ? 1 : 0,
                    ':allows_onsite_watch' => !empty($data['allows_onsite_watch']) ? 1 : 0,
                    ':requires_component_signing' => !empty($data['requires_component_signing']) ? 1 : 0,
                    ':student_discount' => $data['student_discount'] ?? null,
                    ':image' => $data['image'] ?? '',
                    ':website' => $data['website'] ?? null
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO shops (name, address, ward, district, phone, opening_hours, map_url, latitude, longitude, description, devices, is_verified, allows_onsite_watch, requires_component_signing, student_discount, image, website, created_at)
                    VALUES (:name, :address, :ward, :district, :phone, :opening_hours, :map_url, :latitude, :longitude, :description, :devices, :is_verified, :allows_onsite_watch, :requires_component_signing, :student_discount, :image, :website, NOW())
                ");
                $stmt->execute([
                    ':name' => $data['name'] ?? '',
                    ':address' => $data['address'] ?? '',
                    ':ward' => $data['ward'] ?? '',
                    ':district' => $data['district'] ?? '',
                    ':phone' => $data['phone'] ?? '',
                    ':opening_hours' => $data['opening_hours'] ?? '08:00 - 21:00',
                    ':map_url' => $data['map_url'] ?? '',
                    ':latitude' => $data['latitude'] ?? 10.8538,
                    ':longitude' => $data['longitude'] ?? 106.6263,
                    ':description' => $data['description'] ?? '',
                    ':devices' => $devStr,
                    ':is_verified' => !empty($data['is_verified']) ? 1 : 0,
                    ':allows_onsite_watch' => !empty($data['allows_onsite_watch']) ? 1 : 0,
                    ':requires_component_signing' => !empty($data['requires_component_signing']) ? 1 : 0,
                    ':student_discount' => $data['student_discount'] ?? null,
                    ':image' => $data['image'] ?? '',
                    ':website' => $data['website'] ?? null
                ]);
                $data['id'] = (int)$this->pdo->lastInsertId();
            }
            return $data;
        }

        return $this->withJsonLock('shops.json', function() use ($data) {
            $shops = $this->readJson('shops.json');
            if (!empty($data['id'])) {
                foreach ($shops as $idx => $s) {
                    if ($s['id'] == $data['id']) {
                        $shops[$idx] = array_merge($s, $data);
                        return $this->writeJson('shops.json', $shops) ? $shops[$idx] : false;
                    }
                }
            } else {
                $maxId = 0;
                foreach ($shops as $s) $maxId = max($maxId, (int)$s['id']);
                $data['id'] = $maxId + 1;
                $shops[] = $data;
                return $this->writeJson('shops.json', $shops) ? $data : false;
            }
            return false;
        });
    }

    public function deleteShop($id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("DELETE FROM shops WHERE id = :id");
            return $stmt->execute([':id' => (int)$id]);
        }

        return $this->withJsonLock('shops.json', function() use ($id) {
            $shops = $this->readJson('shops.json');
            $newShops = array_filter($shops, fn($s) => $s['id'] != $id);
            return $this->writeJson('shops.json', array_values($newShops));
        });
    }

    // ================= DỊCH VỤ SỬA CHỮA (SERVICES) =================
    public function getServices($device_type = null) {
        $deviceAliases = ['win_laptop' => 'laptop', 'macbook' => 'mac', 'pc_desktop' => 'pc'];
        if ($device_type !== null) $device_type = $deviceAliases[$device_type] ?? $device_type;
        if ($this->isUsingMySQL()) {
            if (!empty($device_type) && $device_type !== 'all') {
                $stmt = $this->pdo->prepare("SELECT * FROM services WHERE device_type = :dt OR devices LIKE :dev OR device_type = 'all' ORDER BY id ASC");
                $stmt->execute([':dt' => $device_type, ':dev' => '%' . $device_type . '%']);
            } else {
                $stmt = $this->pdo->query("SELECT * FROM services ORDER BY id ASC");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $services = $this->readJson('services.json');
        if ($device_type && $device_type !== 'all') {
            $services = array_filter($services, function($s) use ($device_type) {
                $devs = $s['devices'] ?? [];
                if (is_string($devs)) $devs = explode(',', $devs);
                return ($s['device_type'] ?? '') === $device_type || 
                       in_array($device_type, $devs) || 
                       ($s['device_type'] ?? '') === 'all';
            });
        }
        return array_values($services);
    }

    public function getServiceById($id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("SELECT * FROM services WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $services = $this->readJson('services.json');
        foreach ($services as $s) {
            if ($s['id'] == $id) return $s;
        }
        return null;
    }

    public function getServicesByShop($shop_id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("
                SELECT ss.*, s.name as service_name, s.device_type, s.icon, s.description as service_desc, s.description 
                FROM shop_services ss 
                JOIN services s ON ss.service_id = s.id 
                WHERE ss.shop_id = :shop_id 
                ORDER BY ss.id ASC
            ");
            $stmt->execute([':shop_id' => (int)$shop_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $shop_services = $this->readJson('shop_services.json');
        $services = $this->readJson('services.json');
        $result = [];

        foreach ($shop_services as $ss) {
            if ($ss['shop_id'] == $shop_id) {
                foreach ($services as $s) {
                    if ($s['id'] == $ss['service_id']) {
                        $result[] = array_merge($ss, [
                            'service_name' => $s['name'],
                            'device_type' => $s['device_type'],
                            'icon' => $s['icon'],
                            'service_desc' => $s['description'] ?? '',
                            'description' => $s['description'] ?? ''
                        ]);
                        break;
                    }
                }
            }
        }
        return $result;
    }

    public function saveShopService($data) {
        if ($this->isUsingMySQL()) {
            if (!empty($data['id'])) {
                $stmt = $this->pdo->prepare("
                    UPDATE shop_services SET 
                        shop_id = :shop_id, service_id = :service_id, min_price = :min_price, 
                        max_price = :max_price, warranty_text = :warranty_text, turnaround_text = :turnaround_text, note = :note
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => (int)$data['id'],
                    ':shop_id' => (int)$data['shop_id'],
                    ':service_id' => (int)$data['service_id'],
                    ':min_price' => (int)($data['min_price'] ?? 0),
                    ':max_price' => (int)($data['max_price'] ?? 0),
                    ':warranty_text' => $data['warranty_text'] ?? '6 - 12 tháng',
                    ':turnaround_text' => $data['turnaround_text'] ?? '30 - 60 phút',
                    ':note' => $data['note'] ?? ''
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO shop_services (shop_id, service_id, min_price, max_price, warranty_text, turnaround_text, note)
                    VALUES (:shop_id, :service_id, :min_price, :max_price, :warranty_text, :turnaround_text, :note)
                ");
                $stmt->execute([
                    ':shop_id' => (int)$data['shop_id'],
                    ':service_id' => (int)$data['service_id'],
                    ':min_price' => (int)($data['min_price'] ?? 0),
                    ':max_price' => (int)($data['max_price'] ?? 0),
                    ':warranty_text' => $data['warranty_text'] ?? '6 - 12 tháng',
                    ':turnaround_text' => $data['turnaround_text'] ?? '30 - 60 phút',
                    ':note' => $data['note'] ?? ''
                ]);
                $data['id'] = (int)$this->pdo->lastInsertId();
            }
            return $data;
        }

        return $this->withJsonLock('shop_services.json', function() use ($data) {
            $items = $this->readJson('shop_services.json');
            if (!empty($data['id'])) {
                foreach ($items as $idx => $it) {
                    if ($it['id'] == $data['id']) {
                        $items[$idx] = array_merge($it, $data);
                        return $this->writeJson('shop_services.json', $items) ? $items[$idx] : false;
                    }
                }
            } else {
                $maxId = 0;
                foreach ($items as $it) $maxId = max($maxId, (int)$it['id']);
                $data['id'] = $maxId + 1;
                $items[] = $data;
                return $this->writeJson('shop_services.json', $items) ? $data : false;
            }
            return false;
        });
    }

    public function deleteShopService($id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("DELETE FROM shop_services WHERE id = :id");
            return $stmt->execute([':id' => (int)$id]);
        }

        return $this->withJsonLock('shop_services.json', function() use ($id) {
            $items = $this->readJson('shop_services.json');
            $newItems = array_filter($items, fn($it) => $it['id'] != $id);
            return $this->writeJson('shop_services.json', array_values($newItems));
        });
    }

    // ================= ĐÁNH GIÁ (REVIEWS) =================
    public function getReviewsByShop($shop_id) {
        if ($this->isUsingMySQL()) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE shop_id = :shop_id AND is_hidden = 0 AND origin = 'user_submission' ORDER BY created_at DESC");
                $stmt->execute([':shop_id' => (int)$shop_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // CSDL cũ thiếu cột origin: không công bố review seed.
                return [];
            }
        }

        $reviews = $this->readJson('reviews.json');
        $filtered = array_filter($reviews, fn($r) => $r['shop_id'] == $shop_id
            && empty($r['is_hidden']) && ($r['origin'] ?? '') === 'user_submission');
        usort($filtered, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return array_values($filtered);
    }

    public function getAllReviews() {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->query("
                SELECT r.*, s.name as shop_name 
                FROM reviews r 
                LEFT JOIN shops s ON r.shop_id = s.id 
                ORDER BY r.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $reviews = $this->readJson('reviews.json');
        $shops = $this->readJson('shops.json');
        $shopMap = [];
        foreach ($shops as $s) $shopMap[$s['id']] = $s['name'];

        foreach ($reviews as &$r) {
            $r['shop_name'] = $shopMap[$r['shop_id']] ?? 'Không rõ';
        }
        usort($reviews, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return $reviews;
    }

    public function addReview($data) {
        if ($this->isUsingMySQL()) {
            $reviewParams = [
                ':shop_id' => (int)$data['shop_id'],
                ':user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
                ':user_name' => $data['user_name'] ?? 'Khách vãng lai',
                ':rating' => (int)($data['rating'] ?? 5),
                ':device_name' => $data['device_name'] ?? null,
                ':service_repaired' => $data['service_repaired'] ?? null,
                ':comment' => $data['comment'] ?? ''
            ];
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO reviews (shop_id, user_id, user_name, rating, device_name, service_repaired, comment, is_hidden, origin, admin_reply, created_at)
                    VALUES (:shop_id, :user_id, :user_name, :rating, :device_name, :service_repaired, :comment, 1, 'user_submission', NULL, NOW())
                ");
                $stmt->execute($reviewParams);
            } catch (Exception $e) {
                // Schema cũ: lưu để admin xem, nhưng giữ ẩn vì không phân biệt được nguồn.
                $stmt = $this->pdo->prepare("
                    INSERT INTO reviews (shop_id, user_id, user_name, rating, device_name, service_repaired, comment, is_hidden, admin_reply, created_at)
                    VALUES (:shop_id, :user_id, :user_name, :rating, :device_name, :service_repaired, :comment, 1, NULL, NOW())
                ");
                $stmt->execute($reviewParams);
            }
            $data['id'] = (int)$this->pdo->lastInsertId();
            return $data;
        }

        return $this->withJsonLock('reviews.json', function() use ($data) {
            $reviews = $this->readJson('reviews.json');
            $maxId = 0;
            foreach ($reviews as $r) $maxId = max($maxId, (int)$r['id']);
            $data['id'] = $maxId + 1;
            $data['is_hidden'] = true;
            $data['origin'] = 'user_submission';
            $data['created_at'] = date('Y-m-d H:i:s');
            $reviews[] = $data;
            return $this->writeJson('reviews.json', $reviews) ? $data : false;
        });
    }

    public function toggleReviewVisibility($id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("UPDATE reviews SET is_hidden = CASE WHEN is_hidden = 1 THEN 0 ELSE 1 END WHERE id = :id");
            return $stmt->execute([':id' => (int)$id]);
        }

        return $this->withJsonLock('reviews.json', function() use ($id) {
            $reviews = $this->readJson('reviews.json');
            foreach ($reviews as &$r) {
                if ($r['id'] == $id) {
                    $r['is_hidden'] = !($r['is_hidden'] ?? false);
                    return $this->writeJson('reviews.json', $reviews);
                }
            }
            return false;
        });
    }

    public function deleteReview($id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("DELETE FROM reviews WHERE id = :id");
            return $stmt->execute([':id' => (int)$id]);
        }

        return $this->withJsonLock('reviews.json', function() use ($id) {
            $reviews = $this->readJson('reviews.json');
            $newReviews = array_filter($reviews, fn($r) => $r['id'] != $id);
            return $this->writeJson('reviews.json', array_values($newReviews));
        });
    }

    public function replyReview($id, $reply_text) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("UPDATE reviews SET admin_reply = :reply, admin_reply_at = NOW() WHERE id = :id");
            return $stmt->execute([':reply' => $reply_text, ':id' => (int)$id]);
        }

        return $this->withJsonLock('reviews.json', function() use ($id, $reply_text) {
            $reviews = $this->readJson('reviews.json');
            foreach ($reviews as &$r) {
                if ($r['id'] == $id) {
                    $r['admin_reply'] = $reply_text;
                    $r['admin_reply_at'] = date('Y-m-d H:i:s');
                    return $this->writeJson('reviews.json', $reviews);
                }
            }
            return false;
        });
    }

    // ================= YÊU CẦU BÁO GIÁ & SỬA CHỮA (REPAIR_REQUESTS) =================
    public function getRepairRequests() {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->query("SELECT * FROM repair_requests ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $reqs = $this->readJson('repair_requests.json');
        if (!is_array($reqs)) return [];
        usort($reqs, fn($a, $b) => strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0));
        return $reqs;
    }

    public function addRepairRequest($data) {
        if ($this->isUsingMySQL()) {
            if (empty($data['id'])) {
                $data['id'] = 'FN-' . rand(1000, 9999);
            }
            // Keep existing installations compatible with the separate Zalo contact field.
            foreach ([
                'customer_zalo' => "ALTER TABLE repair_requests ADD COLUMN customer_zalo VARCHAR(50) DEFAULT NULL AFTER customer_phone",
                'user_id' => "ALTER TABLE repair_requests ADD COLUMN user_id INT DEFAULT NULL AFTER id"
            ] as $column => $migration) {
                if (!$this->pdo->query("SHOW COLUMNS FROM repair_requests LIKE '" . $column . "'")->fetch()) {
                    $this->pdo->exec($migration);
                }
            }
            $stmt = $this->pdo->prepare("
                INSERT INTO repair_requests (id, user_id, customer_name, customer_email, customer_phone, customer_zalo, device_type, brand_model, issue_type, symptom, district, preferred_time, estimated_price, status, created_at)
                VALUES (:id, :user_id, :customer_name, :customer_email, :customer_phone, :customer_zalo, :device_type, :brand_model, :issue_type, :symptom, :district, :preferred_time, :estimated_price, :status, NOW())
            ");
            $stmt->execute([
                ':id' => $data['id'],
                ':user_id' => $data['user_id'] ?? null,
                ':customer_name' => $data['customer_name'] ?? '',
                ':customer_email' => $data['customer_email'] ?? null,
                ':customer_phone' => $data['customer_phone'] ?? '',
                ':customer_zalo' => $data['customer_zalo'] ?? '',
                ':device_type' => $data['device_type'] ?? '',
                ':brand_model' => $data['brand_model'] ?? '',
                ':issue_type' => $data['issue_type'] ?? '',
                ':symptom' => $data['symptom'] ?? '',
                ':district' => $data['district'] ?? '',
                ':preferred_time' => $data['preferred_time'] ?? '',
                ':estimated_price' => $data['estimated_price'] ?? '',
                ':status' => $data['status'] ?? 'pending'
            ]);
            return $data;
        }

        return $this->withJsonLock('repair_requests.json', function() use ($data) {
            $reqs = $this->readJson('repair_requests.json');
            if (!is_array($reqs)) $reqs = [];
            
            if (empty($data['id'])) {
                $data['id'] = 'FN-' . rand(1000, 9999);
            }
            if (empty($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (empty($data['status'])) {
                $data['status'] = 'pending';
            }
            
            array_unshift($reqs, $data);
            return $this->writeJson('repair_requests.json', $reqs) ? $data : false;
        });
    }

    public function updateRepairRequestStatus($id, $status) {
        $allowed = ['pending', 'contacted', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("UPDATE repair_requests SET status = :status WHERE id = :id");
            return $stmt->execute([':status' => $status, ':id' => $id]);
        }

        return $this->withJsonLock('repair_requests.json', function() use ($id, $status) {
            $reqs = $this->readJson('repair_requests.json');
            if (!is_array($reqs)) return false;
            $found = false;
            foreach ($reqs as &$r) {
                if (($r['id'] ?? '') === $id) {
                    $r['status'] = $status;
                    $r['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $this->writeJson('repair_requests.json', $reqs);
                return true;
            }
            return false;
        });
    }

    // ================= BÁO CÁO THÔNG TIN SAI & LIÊN HỆ =================
    public function addReport($data) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("
                INSERT INTO wrong_info_reports (shop_id, shop_name, user_id, user_name, reporter_phone, reason, details, status, created_at)
                VALUES (:shop_id, :shop_name, :user_id, :user_name, :reporter_phone, :reason, :details, 'pending', NOW())
            ");
            $stmt->execute([
                ':shop_id' => (int)($data['shop_id'] ?? 0),
                ':shop_name' => $data['shop_name'] ?? null,
                ':user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
                ':user_name' => $data['user_name'] ?? 'Khách vãng lai',
                ':reporter_phone' => $data['reporter_phone'] ?? null,
                ':reason' => $data['reason'] ?? '',
                ':details' => $data['details'] ?? ''
            ]);
            return $data;
        }

        return $this->withJsonLock('reports.json', function() use ($data) {
            $reports = $this->readJson('reports.json');
            $maxId = 0;
            foreach ($reports as $r) $maxId = max($maxId, (int)$r['id']);
            $data['id'] = $maxId + 1;
            $data['status'] = 'pending';
            $data['created_at'] = date('Y-m-d H:i:s');
            $reports[] = $data;
            return $this->writeJson('reports.json', $reports) ? $data : false;
        });
    }

    public function addContactMessage($data) {
        $subject = $data['subject'] ?? ($data['type'] ?? '');
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? null;
        $message = $data['message'] ?? '';
        $status = $data['status'] ?? 'pending';

        if ($this->isUsingMySQL()) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO contact_messages (name, email, phone, subject, message, created_at)
                    VALUES (:name, :email, :phone, :subject, :message, NOW())
                ");
                $ok = $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':subject' => $subject,
                    ':message' => $message
                ]);
                if ($ok) {
                    $data['id'] = (int)$this->pdo->lastInsertId();
                    $data['subject'] = $subject;
                    $data['status'] = $status;
                    return $data;
                }
                return false;
            } catch (Exception $e) {
                error_log("addContactMessage MySQL error: " . $e->getMessage());
                return false;
            }
        }

        return $this->withJsonLock('contact_messages.json', function() use ($name, $email, $phone, $subject, $message, $status) {
            $msgs = $this->readJson('contact_messages.json');
            if (!is_array($msgs)) $msgs = [];
            $maxId = 0;
            foreach ($msgs as $m) $maxId = max($maxId, (int)($m['id'] ?? 0));
            $newItem = [
                'id' => $maxId + 1,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'type' => $subject,
                'message' => $message,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $msgs[] = $newItem;
            return $this->writeJson('contact_messages.json', $msgs) ? $newItem : false;
        });
    }

    public function getContactMessages() {
        if ($this->isUsingMySQL()) {
            try {
                $stmt = $this->pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("getContactMessages MySQL error: " . $e->getMessage());
            }
        }

        $msgs = $this->readJson('contact_messages.json');
        if (!is_array($msgs)) return [];
        usort($msgs, fn($a, $b) => strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0));
        return $msgs;
    }

    public function updateContactMessageStatus($id, $status) {
        $allowed = ['pending', 'read', 'replied'];
        if (!in_array($status, $allowed, true)) return false;

        if ($this->isUsingMySQL()) {
            try {
                $stmt = $this->pdo->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
                return $stmt->execute([':status' => $status, ':id' => (int)$id]);
            } catch (Exception $e) {
                return true;
            }
        }

        return $this->withJsonLock('contact_messages.json', function() use ($id, $status) {
            $msgs = $this->readJson('contact_messages.json');
            if (!is_array($msgs)) return false;
            foreach ($msgs as &$m) {
                if (((int)($m['id'] ?? 0)) === (int)$id) {
                    $m['status'] = $status;
                    return $this->writeJson('contact_messages.json', $msgs);
                }
            }
            return false;
        });
    }

    public function deleteContactMessage($id) {
        if ($this->isUsingMySQL()) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
                return $stmt->execute([':id' => (int)$id]);
            } catch (Exception $e) {
                error_log("deleteContactMessage MySQL error: " . $e->getMessage());
                return false;
            }
        }

        return $this->withJsonLock('contact_messages.json', function() use ($id) {
            $msgs = $this->readJson('contact_messages.json');
            if (!is_array($msgs)) return false;
            $newMsgs = array_filter($msgs, fn($m) => ((int)($m['id'] ?? 0)) !== (int)$id);
            return $this->writeJson('contact_messages.json', array_values($newMsgs));
        });
    }

    // ================= YÊU THÍCH CỬA HÀNG (FAVORITES) =================
    public function getFavorites($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) return [];

        if ($this->isUsingMySQL()) {
            try {
                $stmt = $this->pdo->prepare("SELECT shop_id FROM favorites WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (Exception $e) {
                // Nếu bảng favorites chưa có trong MySQL, tiếp tục fallback JSON
            }
        }

        $favs = $this->readJson('favorites.json');
        if (!is_array($favs)) return [];
        $shopIds = [];
        foreach ($favs as $f) {
            if ((int)($f['user_id'] ?? 0) === $userId && !empty($f['shop_id'])) {
                $shopIds[] = (int)$f['shop_id'];
            }
        }
        return array_values(array_unique($shopIds));
    }

    public function isFavorite($userId, $shopId) {
        $favorites = $this->getFavorites($userId);
        return in_array((int)$shopId, array_map('intval', $favorites), true);
    }

    public function toggleFavorite($userId, $shopId) {
        $userId = (int)$userId;
        $shopId = (int)$shopId;
        if ($userId <= 0 || $shopId <= 0) return false;

        $isFav = $this->isFavorite($userId, $shopId);
        $newFavorited = !$isFav;

        if ($this->isUsingMySQL()) {
            try {
                if ($newFavorited) {
                    $stmt = $this->pdo->prepare("INSERT IGNORE INTO favorites (user_id, shop_id, created_at) VALUES (:u, :s, NOW())");
                    $stmt->execute([':u' => $userId, ':s' => $shopId]);
                } else {
                    $stmt = $this->pdo->prepare("DELETE FROM favorites WHERE user_id = :u AND shop_id = :s");
                    $stmt->execute([':u' => $userId, ':s' => $shopId]);
                }
                return ['favorited' => $newFavorited];
            } catch (Exception $e) {
                // Fallback qua JSON nếu bảng MySQL chưa được tạo
            }
        }

        return $this->withJsonLock('favorites.json', function() use ($userId, $shopId, $newFavorited) {
            $favs = $this->readJson('favorites.json');
            if (!is_array($favs)) $favs = [];
            if ($newFavorited) {
                $favs[] = [
                    'user_id' => $userId,
                    'shop_id' => $shopId,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $favs = array_values(array_filter($favs, function($f) use ($userId, $shopId) {
                    return !((int)($f['user_id'] ?? 0) === $userId && (int)($f['shop_id'] ?? 0) === $shopId);
                }));
            }
            $this->writeJson('favorites.json', $favs);
            return ['favorited' => $newFavorited];
        });
    }

    public function getUserFavoriteShops($userId) {
        $shopIds = $this->getFavorites($userId);
        if (empty($shopIds)) return [];
        $result = [];
        foreach ($shopIds as $sid) {
            $s = $this->getShopById($sid);
            if ($s) $result[] = $s;
        }
        return $result;
    }

    public function getReports() {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->query("SELECT * FROM wrong_info_reports ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $reports = $this->readJson('reports.json');
        usort($reports, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return $reports;
    }

    public function updateReportStatus($id, $status) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("UPDATE wrong_info_reports SET status = :status WHERE id = :id");
            return $stmt->execute([':status' => $status, ':id' => (int)$id]);
        }

        return $this->withJsonLock('reports.json', function() use ($id, $status) {
            $reports = $this->readJson('reports.json');
            foreach ($reports as &$r) {
                if ($r['id'] == $id) {
                    $r['status'] = $status;
                    return $this->writeJson('reports.json', $reports);
                }
            }
            return false;
        });
    }

    // ================= TÀI KHOẢN NGƯỜI DÙNG (USERS) =================
    public function getUserByEmail($email) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $stmt->execute([':email' => trim($email)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $users = $this->readJson('users.json');
        foreach ($users as $u) {
            if (strcasecmp(trim($u['email'] ?? ''), trim($email)) === 0) {
                return $u;
            }
        }
        return null;
    }

    public function getUserById($id) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $users = $this->readJson('users.json');
        foreach ($users as $u) {
            if ($u['id'] == $id) return $u;
        }
        return null;
    }

    public function createUser($data) {
        if ($this->isUsingMySQL()) {
            if ($this->getUserByEmail($data['email'])) return false;
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password, role, phone, created_at)
                VALUES (:name, :email, :password, :role, :phone, NOW())
            ");
            $stmt->execute([
                ':name' => $data['name'] ?? '',
                ':email' => $data['email'] ?? '',
                ':password' => $data['password'] ?? '',
                ':role' => $data['role'] ?? 'user',
                ':phone' => $data['phone'] ?? null
            ]);
            $data['id'] = (int)$this->pdo->lastInsertId();
            return $data;
        }

        return $this->withJsonLock('users.json', function() use ($data) {
            $users = $this->readJson('users.json');
            foreach ($users as $user) {
                if (strcasecmp(trim((string)($user['email'] ?? '')), trim((string)($data['email'] ?? ''))) === 0) return false;
            }
            $maxId = 0;
            foreach ($users as $user) $maxId = max($maxId, (int)($user['id'] ?? 0));
            $data['id'] = $maxId + 1;
            $data['role'] = $data['role'] ?? 'user';
            $data['created_at'] = date('Y-m-d H:i:s');
            $users[] = $data;
            return $this->writeJson('users.json', $users) ? $data : false;
        });
    }

    // ================= THỐNG KÊ (STATS CHO ADMIN) =================
    public function getStats() {
        if ($this->isUsingMySQL()) {
            try {
                $totalShops = (int)$this->pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn();
                $verifiedShops = (int)$this->pdo->query("SELECT COUNT(*) FROM shops WHERE source_url IS NOT NULL AND source_url != '' AND verified_at IS NOT NULL")->fetchColumn();
                $totalDistricts = (int)$this->pdo->query("SELECT COUNT(DISTINCT district) FROM shops WHERE district != ''")->fetchColumn();
                $avgRating = $this->pdo->query("SELECT ROUND(AVG(google_rating), 1) FROM shops WHERE google_place_id IS NOT NULL AND google_place_id != '' AND google_verified_at IS NOT NULL AND google_rating IS NOT NULL")->fetchColumn();
                $totalServices = (int)$this->pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
                $totalReviews = (int)$this->pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
                $pendingReports = (int)$this->pdo->query("SELECT COUNT(*) FROM wrong_info_reports WHERE status = 'pending'")->fetchColumn();
                $totalRequests = (int)$this->pdo->query("SELECT COUNT(*) FROM repair_requests")->fetchColumn();
                $pendingRequests = (int)$this->pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'pending'")->fetchColumn();
                $totalContacts = 0;
                $pendingContacts = 0;
                try {
                    $totalContacts = (int)$this->pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
                    $pendingContacts = (int)$this->pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'pending'")->fetchColumn();
                } catch (Exception $e) {
                    // Cột status có thể chưa tồn tại nếu dùng CSDL cũ
                }

                return [
                    'total_shops' => $totalShops,
                    'verified_shops' => $verifiedShops,
                    'total_districts' => $totalDistricts,
                    'average_shop_rating' => $avgRating ? (float)$avgRating : null,
                    'total_services' => $totalServices,
                    'total_reviews' => $totalReviews,
                    'pending_reports' => $pendingReports,
                    'total_requests' => $totalRequests,
                    'pending_requests' => $pendingRequests,
                    'total_contacts' => $totalContacts,
                    'pending_contacts' => $pendingContacts
                ];
            } catch (Exception $e) {
                // Nếu bảng chưa sẵn sàng, tiếp tục dùng JSON
            }
        }

        $shops = $this->readJson('shops.json');
        $services = $this->readJson('services.json');
        $reviews = $this->readJson('reviews.json');
        $reports = $this->readJson('reports.json');
        $requests = $this->readJson('repair_requests.json');
        if (!is_array($requests)) $requests = [];
        $contacts = $this->readJson('contact_messages.json');
        if (!is_array($contacts)) $contacts = [];

        $pending_reports = 0;
        foreach ($reports as $rp) {
            if (($rp['status'] ?? 'pending') === 'pending') {
                $pending_reports++;
            }
        }

        $pending_requests = 0;
        foreach ($requests as $rq) {
            if (($rq['status'] ?? 'pending') === 'pending') {
                $pending_requests++;
            }
        }

        $pending_contacts = 0;
        foreach ($contacts as $c) {
            if (($c['status'] ?? 'pending') === 'pending') {
                $pending_contacts++;
            }
        }

        $districts = [];
        $ratingTotal = 0.0;
        $ratingCount = 0;
        $verifiedShops = 0;
        foreach ($shops as $shop) {
            $district = trim((string)($shop['district'] ?? ''));
            if ($district !== '') $districts[mb_strtolower($district, 'UTF-8')] = $district;
            if (!empty($shop['google_place_id']) && !empty($shop['google_verified_at']) && isset($shop['google_rating']) && is_numeric($shop['google_rating'])) {
                $ratingTotal += (float)$shop['google_rating'];
                $ratingCount++;
            }
            if (!empty($shop['source_url']) && !empty($shop['verified_at'])) $verifiedShops++;
        }

        return [
            'total_shops' => count($shops),
            'verified_shops' => $verifiedShops,
            'total_districts' => count($districts),
            'average_shop_rating' => $ratingCount ? round($ratingTotal / $ratingCount, 1) : null,
            'total_services' => count($services),
            'total_reviews' => count($reviews),
            'pending_reports' => $pending_reports,
            'total_requests' => count($requests),
            'pending_requests' => $pending_requests,
            'total_contacts' => count($contacts),
            'pending_contacts' => $pending_contacts
        ];
    }

    /** Counts shown to visitors must match the shops they can actually open. */
    public function getPublicStats(): array {
        $stats = $this->getStats();
        $shops = $this->getShops();
        $districts = [];
        foreach ($shops as $shop) {
            $district = trim((string)($shop['district'] ?? ''));
            if ($district !== '') {
                $districts[mb_strtolower($district, 'UTF-8')] = true;
            }
        }
        $stats['total_shops'] = count($shops);
        $stats['total_districts'] = count($districts);
        return $stats;
    }

    // Công thức tính khoảng cách Haversine (km)
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earth_radius * $c, 1);
    }

    public function updateUserPassword($id, $passwordHash) {
        if ($this->isUsingMySQL()) {
            $stmt = $this->pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id");
            return $stmt->execute([':pwd' => $passwordHash, ':id' => (int)$id]);
        }

        $users = $this->readJson('users.json');
        foreach ($users as &$user) {
            if ((int)($user['id'] ?? 0) === (int)$id) {
                $user['password'] = $passwordHash;
                return $this->writeJson('users.json', $users);
            }
        }
        return false;
    }

    public function createOrUpdateAdmin(string $email, string $name, string $passwordHash): bool {
        if ($this->isUsingMySQL()) {
            $existing = $this->getUserByEmail($email);
            if ($existing) {
                $stmt = $this->pdo->prepare("UPDATE users SET name = :name, password = :pwd, role = 'admin' WHERE id = :id");
                return $stmt->execute([':name' => $name, ':pwd' => $passwordHash, ':id' => $existing['id']]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, role, phone, created_at) VALUES (:name, :email, :pwd, 'admin', '', NOW())");
                return $stmt->execute([':name' => $name, ':email' => $email, ':pwd' => $passwordHash]);
            }
        }

        $users = $this->readJson('users.json');
        foreach ($users as &$user) {
            if (strcasecmp(trim((string)($user['email'] ?? '')), $email) === 0) {
                $user['name'] = $name;
                $user['password'] = $passwordHash;
                $user['role'] = 'admin';
                $user['updated_at'] = date('Y-m-d H:i:s');
                return $this->writeJson('users.json', $users);
            }
        }
        unset($user);
        $maxId = 0;
        foreach ($users as $user) $maxId = max($maxId, (int)($user['id'] ?? 0));
        $users[] = [
            'id' => $maxId + 1,
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => 'admin',
            'phone' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->writeJson('users.json', $users);
    }
}

// Helper functions dùng chung toàn bộ view
function db() {
    return FixNearDB::getInstance();
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return db()->getUserById($_SESSION['user_id']);
}

function isAdmin() {
    $u = currentUser();
    return $u && ($u['role'] ?? '') === 'admin';
}

function formatPrice($num) {
    return number_format((float)$num, 0, ',', '.') . 'đ';
}
