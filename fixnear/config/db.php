<?php
/**
 * FixNear - Hệ Thống Quản Trị Cơ Sở Dữ Liệu Kép (Dual-Engine Storage)
 * Hỗ trợ tự động:
 * 1. MySQL (XAMPP PDO) khi MySQL đang chạy
 * 2. Tự động chuyển sang JSON File Database khi MySQL tắt, đảm bảo 100% không bao giờ lỗi!
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
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted) && hash_equals(csrfToken(), $submitted);
}

function requireValidCsrf() {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    }
}

class FixNearDB {
    private static $instance = null;
    private $data_dir = '';
    public $radius_auto_expanded = false;
    public $applied_radius = 0;

    private function annotateShopVerification($shop) {
        // A value existing in seed JSON is not proof. Public verification requires
        // a traceable source and a timestamp so stale claims can be audited.
        $shop['source_verified'] = !empty($shop['source_url']) && !empty($shop['verified_at']);
        $shop['google_rating_verified'] = !empty($shop['google_place_id']) && !empty($shop['google_verified_at']);
        $shop['student_discount_verified'] = !empty($shop['student_discount_source_url']) && !empty($shop['student_discount_verified_at']);
        $shop['service_policy_verified'] = !empty($shop['policy_source_url']) && !empty($shop['policy_verified_at']);
        $shop['is_verified'] = $shop['source_verified'];
        return $shop;
    }

    private function __construct() {
        $this->data_dir = FIXNEAR_DATA_DIR;
        if (!is_dir($this->data_dir) && !mkdir($this->data_dir, 0750, true) && !is_dir($this->data_dir)) {
            throw new RuntimeException('Không thể tạo thư mục dữ liệu FixNear.');
        }
        if (!is_readable($this->data_dir) || !is_writable($this->data_dir)) {
            throw new RuntimeException('Thư mục dữ liệu FixNear cần quyền đọc và ghi.');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isUsingMySQL() {
        return false;
    }

    // Đọc file JSON an toàn
    private function readJson($filename) {
        $file = $this->data_dir . $filename;
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    // Ghi file JSON an toàn
    private function writeJson($filename, $data) {
        $file = $this->data_dir . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        // Replace atomically so an interrupted write cannot corrupt the JSON database.
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
        $shops = $this->readJson('shops.json');

        if (!empty($filters['device'])) {
            $device = strtolower($filters['device']);
            $shops = array_filter($shops, function($s) use ($device) {
                $devs = $s['devices'] ?? [];
                if ($device === 'win_laptop' || $device === 'laptop') {
                    return in_array('laptop', $devs);
                }
                if ($device === 'mac' || $device === 'macbook') {
                    return in_array('mac', $devs) || in_array('laptop', $devs);
                }
                if ($device === 'pc' || $device === 'pc_desktop') {
                    return in_array('pc', $devs) || in_array('laptop', $devs);
                }
                if ($device === 'tablet') {
                    return in_array('tablet', $devs) || in_array('phone', $devs);
                }
                if ($device === 'smartwatch') {
                    return in_array('smartwatch', $devs);
                }
                return in_array($device, $devs);
            });
        }

        if (!empty($filters['district'])) {
            $district = trim($filters['district']);
            $shops = array_filter($shops, function($s) use ($district) {
                if (strcasecmp($s['district'], $district) === 0) {
                    return true;
                }
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
            $shops = array_filter($shops, function($s) use ($valid_shop_ids) {
                return in_array($s['id'], $valid_shop_ids);
            });
        }

        if (!empty($filters['keyword'])) {
            $kw = mb_strtolower($filters['keyword'], 'UTF-8');
            $shops = array_filter($shops, function($s) use ($kw) {
                return mb_stripos($s['name'], $kw, 0, 'UTF-8') !== false ||
                       mb_stripos($s['address'], $kw, 0, 'UTF-8') !== false ||
                       mb_stripos($s['description'], $kw, 0, 'UTF-8') !== false;
            });
        }

        // Tính toán khoảng cách nếu người dùng truyền tọa độ GPS
        if (!empty($filters['user_lat']) && !empty($filters['user_lng'])) {
            $uLat = (float)$filters['user_lat'];
            $uLng = (float)$filters['user_lng'];
            foreach ($shops as &$shop) {
                $shop['distance_km'] = $this->calculateDistance($uLat, $uLng, (float)$shop['latitude'], (float)$shop['longitude']);
            }
            unset($shop);

            // Lọc theo bán kính nếu có
            if (!empty($filters['radius_km'])) {
                $maxRadius = (float)$filters['radius_km'];
                $this->applied_radius = $maxRadius;
                $filteredByRadius = array_filter($shops, function($s) use ($maxRadius) {
                    return $s['distance_km'] <= $maxRadius;
                });
                if (!empty($filteredByRadius)) {
                    $shops = $filteredByRadius;
                    $this->radius_auto_expanded = false;
                } else {
                    // Nếu ngoài bán kính, tự động mở rộng hiển thị các tiệm gần nhất
                    $this->radius_auto_expanded = true;
                }
            }

            // Sắp xếp theo khoảng cách gần nhất
            usort($shops, function($a, $b) {
                return $a['distance_km'] <=> $b['distance_km'];
            });
        }

        return array_values(array_map([$this, 'annotateShopVerification'], $shops));
    }

    public function getShopById($id, $user_lat = null, $user_lng = null) {
        $shops = $this->readJson('shops.json');
        if ($user_lat === null || $user_lng === null) {
            $user_lat = $_GET['user_lat'] ?? ($_COOKIE['fixnear_lat'] ?? null);
            $user_lng = $_GET['user_lng'] ?? ($_COOKIE['fixnear_lng'] ?? null);
        }
        foreach ($shops as $shop) {
            if ($shop['id'] == $id) {
                // Chỉ gọi là khoảng cách tới người dùng khi có tọa độ do họ cung cấp.
                if ($user_lat !== null && $user_lng !== null && is_numeric($user_lat) && is_numeric($user_lng)) {
                    $shop['distance_km'] = $this->calculateDistance((float)$user_lat, (float)$user_lng, (float)$shop['latitude'], (float)$shop['longitude']);
                }
                return $this->annotateShopVerification($shop);
            }
        }
        return null;
    }

    public function saveShop($data) {
        $shops = $this->readJson('shops.json');
        if (!empty($data['id'])) {
            // Update
            foreach ($shops as $key => $s) {
                if ($s['id'] == $data['id']) {
                    $shops[$key] = array_merge($s, $data);
                    $this->writeJson('shops.json', $shops);
                    return $data['id'];
                }
            }
        } else {
            // Insert
            $maxId = 0;
            foreach ($shops as $s) {
                if ($s['id'] > $maxId) $maxId = $s['id'];
            }
            $data['id'] = $maxId + 1;
            $data['google_rating'] = $data['google_rating'] ?? null;
            $data['google_reviews_count'] = $data['google_reviews_count'] ?? 0;
            $data['is_verified'] = false;
            $shops[] = $data;
            $this->writeJson('shops.json', $shops);
            return $data['id'];
        }
        return false;
    }

    public function deleteShop($id) {
        $shops = $this->readJson('shops.json');
        $shops = array_filter($shops, function($s) use ($id) {
            return $s['id'] != $id;
        });
        $this->writeJson('shops.json', array_values($shops));
        return true;
    }

    // ================= DỊCH VỤ & BẢNG GIÁ THAM KHẢO =================
    public function getServices($device_type = null) {
        $services = $this->readJson('services.json');
        if ($device_type) {
            $device_type = strtolower($device_type);
            $services = array_filter($services, function($srv) use ($device_type) {
                if (!empty($srv['devices']) && is_array($srv['devices'])) {
                    return in_array($device_type, $srv['devices']);
                }
                $dt = strtolower($srv['device_type'] ?? 'all');
                if ($dt === 'all') return true;
                if ($dt === $device_type) return true;
                if (($device_type === 'mac' || $device_type === 'pc') && $dt === 'laptop') return true;
                if ($device_type === 'tablet' && ($dt === 'phone' || $dt === 'tablet')) return true;
                return false;
            });
        }
        return array_values($services);
    }

    public function getServiceById($id) {
        $services = $this->readJson('services.json');
        foreach ($services as $srv) {
            if ($srv['id'] == $id) return $srv;
        }
        return null;
    }

    public function getServicesByShop($shop_id) {
        $shop_services = $this->readJson('shop_services.json');
        $services = $this->readJson('services.json');
        $services_map = [];
        foreach ($services as $s) {
            $services_map[$s['id']] = $s;
        }

        $result = [];
        foreach ($shop_services as $ss) {
            if ($ss['shop_id'] == $shop_id) {
                $service_info = $services_map[$ss['service_id']] ?? null;
                if ($service_info) {
                    $result[] = array_merge($ss, [
                        'service_name' => $service_info['name'],
                        'device_type' => $service_info['device_type'],
                        'icon' => $service_info['icon'],
                        'description' => $service_info['description']
                    ]);
                }
            }
        }
        return $result;
    }

    public function saveShopService($data) {
        $shop_services = $this->readJson('shop_services.json');
        if (!empty($data['id'])) {
            foreach ($shop_services as $k => $item) {
                if ($item['id'] == $data['id']) {
                    $shop_services[$k] = array_merge($item, $data);
                    $this->writeJson('shop_services.json', $shop_services);
                    return $data['id'];
                }
            }
        } else {
            $maxId = 0;
            foreach ($shop_services as $item) {
                if ($item['id'] > $maxId) $maxId = $item['id'];
            }
            $data['id'] = $maxId + 1;
            $shop_services[] = $data;
            $this->writeJson('shop_services.json', $shop_services);
            return $data['id'];
        }
        return false;
    }

    public function deleteShopService($id) {
        $shop_services = $this->readJson('shop_services.json');
        $shop_services = array_filter($shop_services, function($s) use ($id) {
            return $s['id'] != $id;
        });
        $this->writeJson('shop_services.json', array_values($shop_services));
        return true;
    }

    // ================= ĐÁNH GIÁ (REVIEWS) =================
    public function getReviewsByShop($shop_id) {
        $reviews = $this->readJson('reviews.json');
        $filtered = [];
        foreach ($reviews as $rev) {
            $isTraceable = !empty($rev['verified_at']) || (($rev['origin'] ?? '') === 'user_submission');
            if ($rev['shop_id'] == $shop_id && empty($rev['is_hidden']) && $isTraceable) {
                $filtered[] = $rev;
            }
        }
        // Mới nhất lên đầu
        usort($filtered, function($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });
        return $filtered;
    }

    public function getAllReviews() {
        $reviews = $this->readJson('reviews.json');
        $shops = $this->readJson('shops.json');
        $shop_names = [];
        foreach ($shops as $s) {
            $shop_names[$s['id']] = $s['name'];
        }

        foreach ($reviews as &$rev) {
            $rev['shop_name'] = $shop_names[$rev['shop_id']] ?? 'Cửa hàng không xác định';
        }
        return $reviews;
    }

    public function addReview($data) {
        return $this->withJsonLock('reviews.json', function() use ($data) {
            $reviews = $this->readJson('reviews.json');
            $maxId = 0;
            foreach ($reviews as $review) $maxId = max($maxId, (int)($review['id'] ?? 0));
            $data['id'] = $maxId + 1;
            $data['is_hidden'] = true; // Chờ admin duyệt trước khi hiển thị công khai
            $data['origin'] = 'user_submission';
            $data['moderation_status'] = 'pending';
            $data['created_at'] = date('Y-m-d H:i:s');
            $reviews[] = $data;
            return $this->writeJson('reviews.json', $reviews) ? $data['id'] : false;
        });
    }

    public function toggleReviewVisibility($id) {
        $reviews = $this->readJson('reviews.json');
        foreach ($reviews as &$r) {
            if ($r['id'] == $id) {
                $r['is_hidden'] = !($r['is_hidden'] ?? false);
                $this->writeJson('reviews.json', $reviews);
                return true;
            }
        }
        return false;
    }

    public function deleteReview($id) {
        $reviews = $this->readJson('reviews.json');
        $reviews = array_filter($reviews, function($r) use ($id) {
            return $r['id'] != $id;
        });
        $this->writeJson('reviews.json', array_values($reviews));
        return true;
    }

    public function replyReview($id, $reply_text) {
        $reviews = $this->readJson('reviews.json');
        $now = date('Y-m-d H:i:s');
        foreach ($reviews as &$r) {
            if ($r['id'] == $id) {
                $r['admin_reply'] = $reply_text;
                $r['admin_reply_at'] = $now;
                $this->writeJson('reviews.json', $reviews);
                break;
            }
        }
        return true;
    }

    // ================= YÊU CẦU BÁO GIÁ & SỬA CHỮA (REPAIR REQUESTS) =================
    public function getRepairRequests() {
        $reqs = $this->readJson('repair_requests.json');
        if (!is_array($reqs)) $reqs = [];
        usort($reqs, function($a, $b) {
            return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
        });
        return $reqs;
    }

    public function addRepairRequest($data) {
        return $this->withJsonLock('repair_requests.json', function() use ($data) {
            $reqs = $this->readJson('repair_requests.json');
            do {
                $id = 'FN-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $exists = false;
                foreach ($reqs as $request) {
                    if (($request['id'] ?? '') === $id) { $exists = true; break; }
                }
            } while ($exists);
            $data['id'] = $id;
            $data['status'] = 'pending';
            $data['status_history'] = [[
                'status' => 'pending',
                'changed_at' => date('Y-m-d H:i:s'),
                'changed_by' => 'Hệ thống'
            ]];
            $data['created_at'] = date('Y-m-d H:i:s');
            array_unshift($reqs, $data);
            return $this->writeJson('repair_requests.json', $reqs) ? $data : false;
        });
    }

    public function updateRepairRequestStatus($id, $status) {
        $allowedStatuses = ['pending', 'reviewing', 'matched', 'contacted', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }
        $reqs = $this->readJson('repair_requests.json');
        $updated = false;
        foreach ($reqs as &$r) {
            if (($r['id'] ?? '') === $id) {
                $r['status'] = $status;
                $r['status_history'] = is_array($r['status_history'] ?? null) ? $r['status_history'] : [];
                $r['status_history'][] = [
                    'status' => $status,
                    'changed_at' => date('Y-m-d H:i:s'),
                    'changed_by' => (string)($_SESSION['user_name'] ?? 'Quản trị viên')
                ];
                $this->writeJson('repair_requests.json', $reqs);
                $updated = true;
                break;
            }
        }
        if (!$updated) return false;
        return true;
    }

    // ================= BÁO CÁO THÔNG TIN SAI (REPORTS) =================
    public function addReport($data) {
        return $this->withJsonLock('reports.json', function() use ($data) {
            $reports = $this->readJson('reports.json');
            $maxId = 0;
            foreach ($reports as $report) $maxId = max($maxId, (int)($report['id'] ?? 0));
            $data['id'] = $maxId + 1;
            $data['status'] = 'pending';
            $data['created_at'] = date('Y-m-d H:i:s');
            $reports[] = $data;
            return $this->writeJson('reports.json', $reports) ? $data['id'] : false;
        });
    }

    public function addContactMessage($data) {
        return $this->withJsonLock('contact_messages.json', function() use ($data) {
            $messages = $this->readJson('contact_messages.json');
            $data['id'] = 'MSG-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $data['created_at'] = date('Y-m-d H:i:s');
            array_unshift($messages, $data);
            return $this->writeJson('contact_messages.json', $messages) ? $data : false;
        });
    }

    public function getReports() {
        $reports = $this->readJson('reports.json');
        usort($reports, function($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });
        return $reports;
    }

    public function updateReportStatus($id, $status) {
        $reports = $this->readJson('reports.json');
        foreach ($reports as &$rp) {
            if ($rp['id'] == $id) {
                $rp['status'] = $status;
                $this->writeJson('reports.json', $reports);
                return true;
            }
        }
        return false;
    }

    // ================= TÀI KHOẢN (USERS) =================
    public function getUserByEmail($email) {
        $users = $this->readJson('users.json');
        foreach ($users as $u) {
            if (strtolower(trim($u['email'])) === strtolower(trim($email))) {
                return $u;
            }
        }
        return null;
    }

    public function getUserById($id) {
        $users = $this->readJson('users.json');
        foreach ($users as $u) {
            if ($u['id'] == $id) return $u;
        }
        return null;
    }

    public function createUser($data) {
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
        $shops = $this->readJson('shops.json');
        $services = $this->readJson('services.json');
        $reviews = $this->readJson('reviews.json');
        $reports = $this->readJson('reports.json');
        $requests = $this->readJson('repair_requests.json');
        if (!is_array($requests)) $requests = [];

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
            'pending_requests' => $pending_requests
        ];
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
