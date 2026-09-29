<?php
/**
 * FixNear RepairAtlas — PHP Pricing Engine Helper
 * Tính toán báo giá ước tính 3 cấp linh kiện (Standard, OEM, Genuine)
 * Đồng bộ 100% logic với scripts/pricing_engine.py
 */

if (!defined('FIXNEAR_ROOT')) {
    define('FIXNEAR_ROOT', dirname(__DIR__));
}

class RepairAtlasPricing {
    private static $tierMatrix = null;
    private static $brandFactors = null;
    private static $gradeFactors = null;
    private static $overrides = null;
    private static $faultsMap = null;

    /**
     * Nạp toàn bộ cấu hình ma trận giá và faults (Cached)
     */
    public static function getDataDir() {
        if (defined('FIXNEAR_DATA_DIR') && is_dir(FIXNEAR_DATA_DIR)) {
            return rtrim(FIXNEAR_DATA_DIR, '/\\');
        }
        return FIXNEAR_ROOT . '/data';
    }

    public static function init() {
        if (self::$tierMatrix !== null) {
            return;
        }

        $dataDir = self::getDataDir();
        $pricingDir = $dataDir . '/pricing';

        if (file_exists($pricingDir . '/tier-matrix.json')) {
            self::$tierMatrix = json_decode(file_get_contents($pricingDir . '/tier-matrix.json'), true) ?: [];
        } else {
            self::$tierMatrix = [];
        }

        if (file_exists($pricingDir . '/brand-factors.json')) {
            self::$brandFactors = json_decode(file_get_contents($pricingDir . '/brand-factors.json'), true) ?: [];
        } else {
            self::$brandFactors = [];
        }

        if (file_exists($pricingDir . '/grade-factors.json')) {
            self::$gradeFactors = json_decode(file_get_contents($pricingDir . '/grade-factors.json'), true) ?: [];
        } else {
            self::$gradeFactors = [];
        }

        if (file_exists($pricingDir . '/overrides.json')) {
            self::$overrides = json_decode(file_get_contents($pricingDir . '/overrides.json'), true) ?: [];
        } else {
            self::$overrides = [];
        }

        if (file_exists($dataDir . '/faults.json')) {
            $rawFaults = json_decode(file_get_contents($dataDir . '/faults.json'), true) ?: [];
            self::$faultsMap = [];
            foreach ($rawFaults as $f) {
                if (isset($f['id'])) {
                    self::$faultsMap[$f['id']] = $f;
                }
            }
        } else {
            self::$faultsMap = [];
        }
    }

    /**
     * Quy tắc làm tròn giá tiền VND thực tế tại TP.HCM:
     * - Dưới 1 triệu: làm tròn bước 10.000đ
     * - Từ 1 triệu trở lên: làm tròn bước 50.000đ
     */
    public static function roundPrice($val) {
        $val = floatval($val);
        if ($val <= 0) return 0;
        if ($val < 1000000) {
            return (int) (round($val / 10000) * 10000);
        } else {
            return (int) (round($val / 50000) * 50000);
        }
    }

    /**
     * Định dạng số tiền hiển thị chuẩn Việt Nam (vd: 1.250.000đ)
     */
    public static function formatVND($amount) {
        if ($amount <= 0) return 'Miễn phí (0đ)';
        return number_format($amount, 0, ',', '.') . 'đ';
    }

    /**
     * Lấy thông tin chi tiết một Pan bệnh từ faults.json
     */
    public static function getFault($faultId) {
        self::init();
        return self::$faultsMap[$faultId] ?? null;
    }

    /**
     * Lấy danh sách tất cả các pan bệnh
     */
    public static function getAllFaults() {
        self::init();
        return self::$faultsMap ?: [];
    }

    /**
     * Lấy thông tin các cấp linh kiện (Standard, OEM, Genuine)
     */
    public static function getGradeFactors() {
        self::init();
        return self::$gradeFactors ?: [];
    }

    /**
     * Tính toán báo giá cho 1 pan bệnh theo cấp linh kiện cụ thể
     */
    public static function calculatePrice($deviceType, $tier, $brand, $faultId, $grade = 'standard', $modelId = null) {
        self::init();

        // 1. Kiểm tra Overrides thủ công trước
        if ($modelId && isset(self::$overrides['models'][$modelId][$faultId][$grade])) {
            $ov = self::$overrides['models'][$modelId][$faultId][$grade];
            return [
                'min' => (int) $ov['min'],
                'max' => (int) $ov['max'],
                'turnaround' => $ov['turnaround'] ?? '30 - 60 phút',
                'warrantyMonths' => (int) ($ov['warrantyMonths'] ?? 6),
                'grade' => $grade,
                'gradeName' => self::$gradeFactors[$grade]['name'] ?? $grade,
                'isOverride' => true,
                'note' => $ov['note'] ?? 'Ước tính từ mô hình tham khảo FixNear'
            ];
        }

        // 2. Tra trong Tier Matrix
        $faultsInTier = self::$tierMatrix[$deviceType][$tier]['faults'] ?? [];
        $base = $faultsInTier[$faultId] ?? null;

        // Nếu lỗi con không có giá trực tiếp, tra giá lỗi cha
        if (!$base && isset(self::$faultsMap[$faultId]['parentFaultId'])) {
            $parentId = self::$faultsMap[$faultId]['parentFaultId'];
            $base = $faultsInTier[$parentId] ?? null;
        }

        if (!$base) {
            return null;
        }

        // 3. Nhân hệ số Hãng và Hệ số Linh kiện
        $brandKey = strtolower(trim($brand));
        $brandFactor = floatval(self::$brandFactors[$brandKey] ?? 1.0);

        $gradeInfo = self::$gradeFactors[$grade] ?? ['factor' => 1.0, 'warrantyMultiplier' => 1.0, 'name' => $grade];
        $gradeFactor = floatval($gradeInfo['factor'] ?? 1.0);
        $warrantyMult = floatval($gradeInfo['warrantyMultiplier'] ?? 1.0);

        // Trường hợp chẩn đoán 0đ
        if (($base['min'] ?? 0) == 0 && ($base['max'] ?? 0) == 0) {
            $minVnd = 0;
            $maxVnd = 0;
            $warrantyMonths = 0;
        } else {
            $minVnd = self::roundPrice($base['min'] * $brandFactor * $gradeFactor);
            $maxVnd = self::roundPrice($base['max'] * $brandFactor * $gradeFactor);
            if ($minVnd > $maxVnd) {
                $maxVnd = $minVnd;
            }
            $baseWarranty = intval($base['warrantyMonths'] ?? 3);
            $warrantyMonths = max(1, min(24, (int) round($baseWarranty * $warrantyMult)));
        }

        return [
            'min' => $minVnd,
            'max' => $maxVnd,
            'turnaround' => $base['turnaround'] ?? '30 - 60 phút',
            'warrantyMonths' => $warrantyMonths,
            'grade' => $grade,
            'gradeName' => $gradeInfo['name'] ?? $grade,
            'isOverride' => false,
            'note' => ''
        ];
    }

    /**
     * Tính toán toàn bộ ma trận giá 3 cấp cho tất cả các pan bệnh được hỗ trợ của 1 Model
     */
    public static function getModelPrices($deviceType, $tier, $brand, $supportedFaults, $modelId = null) {
        $result = [];
        $grades = ['standard', 'oem', 'genuine'];

        foreach ($supportedFaults as $faultId) {
            $gradeList = [];
            foreach ($grades as $grade) {
                $price = self::calculatePrice($deviceType, $tier, $brand, $faultId, $grade, $modelId);
                if ($price) {
                    $gradeList[$grade] = $price;
                }
            }
            if (!empty($gradeList)) {
                $result[$faultId] = $gradeList;
            }
        }
        return $result;
    }

    /**
     * Lấy danh sách 6 nhóm thiết bị cùng số lượng Model
     */
    public static function getAllDevices() {
        return [
            'phone' => ['id' => 'phone', 'name' => 'Điện Thoại', 'icon' => '📱', 'description' => 'iPhone, Samsung Galaxy, Xiaomi, Oppo, Vivo, Google Pixel...'],
            'win_laptop' => ['id' => 'win_laptop', 'name' => 'Laptop Windows', 'icon' => '💻', 'description' => 'Dell, Asus, HP, Lenovo, Acer, MSI, LG Gram...'],
            'macbook' => ['id' => 'macbook', 'name' => 'MacBook / Mac', 'icon' => '🍎', 'description' => 'MacBook Pro, MacBook Air (M1, M2, M3, Intel)...'],
            'tablet' => ['id' => 'tablet', 'name' => 'Máy Tính Bảng', 'icon' => '📲', 'description' => 'iPad Pro, iPad Air, Galaxy Tab, Xiaomi Pad...'],
            'pc_desktop' => ['id' => 'pc_desktop', 'name' => 'PC / All-in-One', 'icon' => '🖥️', 'description' => 'PC Gaming, Máy trạm Workstation, PC Văn phòng, iMac...'],
            'smartwatch' => ['id' => 'smartwatch', 'name' => 'Đồng Hồ Thông Minh', 'icon' => '⌚', 'description' => 'Apple Watch, Galaxy Watch, Garmin, Huawei...']
        ];
    }

    /**
     * Lấy danh sách các Hãng có trong thư mục catalog của một nhóm thiết bị
     */
    public static function getBrandsByDevice($deviceType) {
        $dir = self::getDataDir() . '/catalog/' . $deviceType;
        if (!is_dir($dir)) return [];

        $brands = [];
        $files = glob($dir . '/*.json');
        foreach ($files as $file) {
            $brandKey = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true) ?: [];
            $count = count($data);
            $displayName = ucfirst($brandKey);
            if ($brandKey === 'win_laptop') $displayName = 'Windows Laptop';
            if ($brandKey === 'apple_desktop') $displayName = 'Apple Desktop';
            if ($brandKey === 'oem_brand') $displayName = 'Máy bộ OEM';
            if ($brandKey === 'gaming') $displayName = 'PC Gaming';
            if ($brandKey === 'workstation') $displayName = 'Workstation';
            if ($brandKey === 'office') $displayName = 'PC Văn phòng';
            if ($brandKey === 'others') $displayName = 'Hãng khác';

            $brands[] = [
                'id' => $brandKey,
                'name' => $displayName,
                'modelCount' => $count
            ];
        }
        return $brands;
    }

    /**
     * Lấy danh sách models theo device và brand
     */
    public static function getModelsByBrand($deviceType, $brand) {
        $file = self::getDataDir() . '/catalog/' . $deviceType . '/' . $brand . '.json';
        if (!file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }

    /**
     * Tìm kiếm Model theo ID trên toàn bộ catalog
     */
    public static function getModelById($modelId) {
        $catalogDir = self::getDataDir() . '/catalog';
        if (!is_dir($catalogDir)) return null;

        $categories = glob($catalogDir . '/*', GLOB_ONLYDIR);
        foreach ($categories as $catDir) {
            $files = glob($catDir . '/*.json');
            foreach ($files as $file) {
                $models = json_decode(file_get_contents($file), true) ?: [];
                foreach ($models as $m) {
                    if (isset($m['id']) && $m['id'] === $modelId) {
                        return $m;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Tìm kiếm Model theo từ khóa (Autocomplete)
     */
    public static function searchModels($keyword, $deviceType = null, $limit = 20) {
        $keyword = mb_strtolower(trim($keyword));
        if (empty($keyword)) return [];

        $results = [];
        $catalogDir = self::getDataDir() . '/catalog';
        $categories = $deviceType ? [$catalogDir . '/' . $deviceType] : glob($catalogDir . '/*', GLOB_ONLYDIR);

        foreach ($categories as $catDir) {
            if (!is_dir($catDir)) continue;
            $files = glob($catDir . '/*.json');
            foreach ($files as $file) {
                $models = json_decode(file_get_contents($file), true) ?: [];
                foreach ($models as $m) {
                    $name = mb_strtolower($m['name'] ?? '');
                    $brand = mb_strtolower($m['brand'] ?? '');
                    $chip = mb_strtolower($m['specs']['chip'] ?? ($m['specs']['cpu'] ?? ''));

                    if (strpos($name, $keyword) !== false || strpos($brand, $keyword) !== false || strpos($chip, $keyword) !== false) {
                        $results[] = $m;
                        if (count($results) >= $limit) {
                            return $results;
                        }
                    }
                }
            }
        }
        return $results;
    }
}
