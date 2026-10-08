<?php
/**
 * FixNear RepairAtlas — Catalog & Pricing API
 * Cung cấp dữ liệu JSON cho Repair Wizard, Search Autocomplete & Model Detail
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/pricing_engine.php';
require_once __DIR__ . '/../includes/price_evidence.php';

$action = $_GET['action'] ?? 'devices';
$device = $_GET['device'] ?? '';
$brand = $_GET['brand'] ?? '';
$modelId = $_GET['id'] ?? '';
$query = $_GET['q'] ?? '';

switch ($action) {
    case 'devices':
        $devices = RepairAtlasPricing::getAllDevices();
        // Tính tổng số model cho từng device
        foreach ($devices as $key => &$dev) {
            $brands = RepairAtlasPricing::getBrandsByDevice($key);
            $totalModels = 0;
            foreach ($brands as $b) {
                $totalModels += $b['modelCount'];
            }
            $dev['brandCount'] = count($brands);
            $dev['modelCount'] = $totalModels;
        }
        echo json_encode(['success' => true, 'data' => array_values($devices)], JSON_UNESCAPED_UNICODE);
        break;

    case 'brands':
        if (empty($device)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu tham số device'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $brands = RepairAtlasPricing::getBrandsByDevice($device);
        echo json_encode(['success' => true, 'device' => $device, 'data' => $brands], JSON_UNESCAPED_UNICODE);
        break;

    case 'models':
        if (empty($device) || empty($brand)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu tham số device hoặc brand'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $models = RepairAtlasPricing::getModelsByBrand($device, $brand);
        // Trả về danh sách tóm tắt
        $summaryList = array_map(function($m) {
            return [
                'id' => $m['id'],
                'name' => $m['name'],
                'brand' => $m['brand'],
                'tier' => $m['tier'],
                'releaseYear' => $m['releaseYear'],
                'specs' => $m['specs'],
                'faultCount' => count($m['supportedFaults'] ?? []),
                'knownIssueCount' => count($m['knownIssues'] ?? []),
                'knownIssues' => $m['knownIssues'] ?? []
            ];
        }, $models);
        echo json_encode(['success' => true, 'device' => $device, 'brand' => $brand, 'data' => $summaryList], JSON_UNESCAPED_UNICODE);
        break;

    case 'model_detail':
        if (empty($modelId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu tham số id'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $model = RepairAtlasPricing::getModelById($modelId);
        if (!$model) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy model: ' . htmlspecialchars($modelId)], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Tính ma trận giá 3 cấp
        $prices = RepairAtlasPricing::getModelPrices(
            $model['deviceType'],
            $model['tier'],
            $model['brand'],
            $model['supportedFaults'],
            $model['id']
        );

        // Nạp thông tin chi tiết từng lỗi
        $faultDetails = [];
        foreach ($model['supportedFaults'] as $fId) {
            $f = RepairAtlasPricing::getFault($fId);
            if ($f) {
                $faultDetails[$fId] = $f;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'model' => $model,
                'prices' => $prices,
                'price_status' => 'MODEL_ESTIMATE',
                'price_disclaimer' => 'Ba mức giá là dự đoán bằng ma trận FixNear. Thời gian, bảo hành và loại linh kiện trong mô hình chưa được cửa hàng xác nhận.',
                'source_quotes' => FixNearPriceEvidence::forModel((string) $model['id']),
                'faults' => $faultDetails,
                'grades' => RepairAtlasPricing::getGradeFactors()
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'search':
        $results = RepairAtlasPricing::searchModels($query, $device ?: null, 15);
        $summaryResults = array_map(function($m) {
            return [
                'id' => $m['id'],
                'name' => $m['name'],
                'brand' => $m['brand'],
                'deviceType' => $m['deviceType'],
                'tier' => $m['tier'],
                'releaseYear' => $m['releaseYear'],
                'specsSummary' => $m['specs']['screen'] ?? ($m['specs']['chip'] ?? ($m['specs']['cpu'] ?? ''))
            ];
        }, $results);
        echo json_encode(['success' => true, 'query' => $query, 'data' => $summaryResults], JSON_UNESCAPED_UNICODE);
        break;

    case 'faults':
        $allFaults = RepairAtlasPricing::getAllFaults();
        echo json_encode(['success' => true, 'data' => array_values($allFaults)], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ'], JSON_UNESCAPED_UNICODE);
        break;
}
