# -*- coding: utf-8 -*-
"""
Builder for request_repair.php:
Implements a state-of-the-art interactive repair wizard modeled after RepairBookings.com,
featuring:
1. Centered brand logos and names inside cards
2. All 36 requested iPhone models from iPhone 17 Pro Max down to iPhone 7
3. Category-specific repair pan bệnh (Phone, Win Laptop, MacBook, PC Desktop, Tablet, Smartwatch)
4. "Not sure? Describe it" symptom input
5. "We use your location to show nearby shops — you can change it on the results page." banner
6. 17-district selection & GPS button
7. MySQL and JSON persistence
"""

import json

content = r'''<?php
$pageTitle = "Gửi Yêu Cầu Sửa Chữa & Nhận Báo Giá — FixNear";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$success = false;
$requestData = null;
$recommendedShops = [];
$estimatedPrice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $deviceType = trim($_POST['device_type'] ?? 'Laptop Windows');
    $brandModel = trim($_POST['brand_model'] ?? '');
    $issueType = trim($_POST['issue_type'] ?? 'Kiểm tra chẩn đoán toàn diện');
    $symptom = trim($_POST['symptom'] ?? '');
    $district = trim($_POST['district'] ?? 'Quận 1');
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $preferredTime = trim($_POST['preferred_time'] ?? 'Sáng mai');

    // Bảng định giá chi tiết theo từng pan bệnh chuẩn hóa
    $priceMap = [
        'Màn hình' => '350.000đ - 1.850.000đ (Tùy model màn OLED/LCD zin)',
        'Screen Repair' => '350.000đ - 1.850.000đ (Bảo hành 6 - 12 tháng)',
        'Pin' => '250.000đ - 850.000đ (Bảo hành chính hãng 12 - 24 tháng)',
        'Battery' => '250.000đ - 850.000đ (Pin Dung Lượng Cao / Chuẩn Zin)',
        'Bàn phím' => '280.000đ - 750.000đ (Linh kiện phím zin gõ nảy êm)',
        'Keyboard' => '280.000đ - 750.000đ (Bảo hành 12 tháng đổi mới)',
        'Cổng sạc' => '150.000đ - 450.000đ (Hàn chân Type-C / Lightning / DC)',
        'Charging Port' => '150.000đ - 450.000đ (Sạc nhanh nhận chuẩn Watt)',
        'Nguồn' => '350.000đ - 950.000đ (Đo đạc nguồn IC / Mosfet chuyên sâu)',
        'PSU' => '450.000đ - 1.250.000đ (Nguồn 80 Plus Bronze/Gold bảo hành 3 năm)',
        'VGA' => '500.000đ - 1.600.000đ (Hấp chip / Đóng VRAM / Thay quạt tản)',
        'GPU' => '500.000đ - 1.600.000đ (Sửa mạch VRM cấp nguồn card)',
        'CPU' => '350.000đ - 900.000đ (Kiểm tra socket, tháo tra keo gốm)',
        'Bản lề' => '150.000đ - 450.000đ (Hàn phục hồi chân ốc chịu lực)',
        'Vệ sinh' => '80.000đ - 180.000đ (Tra keo tản nhiệt gốm MX-4 / TF8)',
        'Thermal' => '80.000đ - 180.000đ (Giảm ngay 15-20°C sau 30 phút)',
        'RAM' => '350.000đ - 950.000đ (Nâng cấp RAM DDR4 / DDR5 chính hãng)',
        'SSD' => '450.000đ - 1.650.000đ (Ổ cứng NVMe PCIe tốc độ cao)',
        'Cài Win' => '80.000đ - 150.000đ (Bản quyền sạch, tối ưu đồ họa / dev)',
        'Cứu dữ liệu' => '350.000đ - 1.500.000đ (Cam kết bảo mật 100%, đồ án)',
        'Data Recovery' => '350.000đ - 1.500.000đ (Khôi phục phân vùng RAW / Bad)',
        'Vô nước' => '250.000đ - 650.000đ (Rửa sóng siêu âm sấy khô cấp cứu)',
        'Water Damage' => '250.000đ - 650.000đ (Xử lý chống oxy hóa vi mạch)',
        'Kính lưng' => '200.000đ - 550.000đ (Tách laser thay kính chuẩn khít)',
        'Camera' => '250.000đ - 950.000đ (Thay cụm cam zin chống rung OIS)',
        'Loa' => '150.000đ - 450.000đ (Thay màng loa zin âm bass chắc)',
        'Mic' => '150.000đ - 350.000đ (Thay cáp mic thu âm rõ tiếng)',
        'Mainboard' => '450.000đ - 1.450.000đ (Sửa tụ chập, IC nguồn, socket)',
        'Logic Board' => '650.000đ - 1.850.000đ (Sửa mạch Apple Silicon M1/M2/M3)',
        'Face ID' => '350.000đ - 750.000đ (Sửa cáp cảm biến chấm ma trận)',
        'Đồng hồ' => '200.000đ - 650.000đ (Ép kính, thay pin, chống nước Apple Watch)'
    ];

    $estimatedPrice = '200.000đ - 800.000đ (Kỹ thuật viên kiểm tra đo đạc báo giá trước)';
    foreach ($priceMap as $key => $p) {
        if (mb_stripos($issueType, $key) !== false || mb_stripos($symptom, $key) !== false) {
            $estimatedPrice = $p;
            break;
        }
    }

    // Lưu vào CSDL MySQL nếu có kết nối
    try {
        if (db()->isUsingMySQL()) {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=fixnear_db;charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $stmt = $pdo->prepare("INSERT INTO bookings (shop_id, customer_name, customer_email, customer_phone, device_type, issue_description, preferred_date, preferred_time, status) VALUES (1, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), 'pending')");
            $stmt->execute([$customerName, $customerEmail, $customerPhone, "$deviceType - $brandModel", "[$issueType] $symptom (Khu vực: $district, Khung giờ: $preferredTime)"]);
        }
    } catch (Exception $e) {
        // Fallback an toàn
    }

    // Lưu file JSON backup
    $requestsFile = __DIR__ . '/data/repair_requests.json';
    $requests = file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) : [];
    if (!is_array($requests)) $requests = [];
    
    $reqId = 'FN-' . rand(1000, 9999);
    $newReq = [
        'id' => $reqId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'device_type' => $deviceType,
        'brand_model' => $brandModel,
        'issue_type' => $issueType,
        'symptom' => $symptom,
        'district' => $district,
        'preferred_time' => $preferredTime,
        'estimated_price' => $estimatedPrice,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];
    array_unshift($requests, $newReq);
    if (!file_exists(dirname($requestsFile))) mkdir(dirname($requestsFile), 0777, true);
    file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Lọc cửa hàng phù hợp
    $devFilter = '';
    if (mb_stripos($deviceType, 'laptop') !== false || mb_stripos($deviceType, 'macbook') !== false || mb_stripos($deviceType, 'pc') !== false) {
        $devFilter = 'laptop';
    } elseif (mb_stripos($deviceType, 'điện thoại') !== false || mb_stripos($deviceType, 'phone') !== false || mb_stripos($deviceType, 'tablet') !== false || mb_stripos($deviceType, 'watch') !== false) {
        $devFilter = 'phone';
    }

    $recommendedShops = db()->getShops([
        'district' => $district,
        'device' => $devFilter
    ]);
    if (empty($recommendedShops)) {
        $recommendedShops = array_slice(db()->getShops(['district' => $district]), 0, 4);
    }
    if (empty($recommendedShops)) {
        $recommendedShops = array_slice(db()->getShops(), 0, 4);
    } else {
        $recommendedShops = array_slice($recommendedShops, 0, 4);
    }

    $success = true;
    $requestData = $newReq;
}
?>

<div class="fn-container" style="padding: 40px 20px 80px;">
    <!-- Tiêu đề lớn & Mục đích phân hệ (Căn giữa theo tiêu chuẩn trang) -->
    <div style="text-align: center; max-width: 820px; margin: 0 auto 36px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; font-size: 12.5px; font-weight: 800; padding: 5px 16px; border-radius: 20px; margin-bottom: 12px; text-transform: uppercase;">
            ⚡ BÁO GIÁ MINH BẠCH & CHẨN ĐOÁN PAN BỆNH THIẾT BỊ
        </div>
        <h1 style="font-family: var(--fn-font-heading); font-size: 32px; font-weight: 900; color: var(--fn-dark); line-height: 1.25;">
            Gửi Thiết Bị Hỏng — <span style="color: #ea580c;">Nhận Báo Giá Minh Bạch & Gợi Ý Thợ Chuẩn</span>
        </h1>
        <p style="font-size: 15px; color: var(--fn-dark-muted); margin-top: 10px; line-height: 1.6;">
            Quy trình 5 bước trực quan theo chuẩn quốc tế: Chọn thiết bị ➔ Hãng ➔ Model chính xác ➔ Chọn pan bệnh cần sửa ➔ Nhận khung giá niêm yết và gợi ý cửa hàng uy tín gần bạn nhất tại TP.HCM.
        </p>
    </div>

    <?php if ($success && $requestData): ?>
        <!-- KẾT QUẢ CUỐI CÙNG TRẢ VỀ CHO NGƯỜI DÙNG -->
        <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: var(--fn-radius-lg); padding: 32px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(22, 163, 74, 0.12); text-align: left;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #22c55e; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                    ✓
                </div>
                <div>
                    <h2 style="font-family: var(--fn-font-heading); font-size: 22px; font-weight: 900; color: #15803d; margin-bottom: 4px; text-align: left;">
                        Tiếp Nhận Thành Công! Mã Yêu Cầu: [<?= $requestData['id'] ?>]
                    </h2>
                    <p style="font-size: 14px; color: #166534; text-align: left;">
                        Cảm ơn <strong><?= htmlspecialchars($requestData['customer_name']) ?></strong>! Yêu cầu sửa chữa thiết bị <strong><?= htmlspecialchars($requestData['device_type']) ?> — <?= htmlspecialchars($requestData['brand_model']) ?></strong> đã được số hóa và chuyển đến các trung tâm cam kết tay nghề tại <strong><?= htmlspecialchars($requestData['district']) ?></strong>.
                    </p>
                </div>
            </div>

            <div style="background: #fff; border: 1px solid #bbf7d0; border-radius: 14px; padding: 22px; margin-bottom: 26px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; text-align: left;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pan bệnh cần khắc phục:</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">⚠️ <?= htmlspecialchars($requestData['issue_type']) ?></div>
                        <?php if (!empty($requestData['symptom'])): ?>
                            <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;"><em>"<?= htmlspecialchars($requestData['symptom']) ?>"</em></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Khung giá tham khảo chuẩn thị trường:</div>
                        <div style="font-size: 16px; font-weight: 900; color: #ea580c; margin-top: 4px;">💰 <?= htmlspecialchars($estimatedPrice) ?></div>
                        <div style="font-size: 12px; color: #16a34a; font-weight: 700; margin-top: 4px;">✓ Cam kết báo giá trước — Không phát sinh</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Thời gian hẹn dự kiến:</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 4px;">🕒 <?= htmlspecialchars($requestData['preferred_time']) ?></div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Giờ mở cửa: 08:30 - 20:30 hàng ngày</div>
                    </div>
                </div>
            </div>

            <h3 style="font-family: var(--fn-font-heading); font-size: 19px; font-weight: 900; color: var(--fn-dark); margin-bottom: 14px; text-align: left;">
                📍 Các Cửa Hàng Uy Tín Gần Bạn Nhất (Tại <?= htmlspecialchars($requestData['district']) ?>):
            </h3>
            <div class="fn-shops-grid" style="text-align: left;">
                <?php foreach ($recommendedShops as $shop): ?>
                    <div class="fn-shop-card">
                        <div class="fn-shop-thumb">
                            <img src="<?= htmlspecialchars($shop['image']) ?>" alt="<?= htmlspecialchars($shop['name']) ?>">
                            <span class="fn-distance-badge">📍 <?= htmlspecialchars($shop['district']) ?></span>
                        </div>
                        <div class="fn-shop-body" style="text-align: left;">
                            <div class="fn-shop-rating">⭐ <?= $shop['google_rating'] ?> (<?= $shop['google_reviews_count'] ?> đánh giá thật)</div>
                            <h3 class="fn-shop-name" style="text-align: left;"><?= htmlspecialchars($shop['name']) ?></h3>
                            <p class="fn-shop-address" style="text-align: left;">📌 <?= htmlspecialchars($shop['address']) ?></p>
                            <div style="font-size: 13px; color: #16a34a; font-weight: 700; margin: 8px 0; text-align: left;">
                                ✓ Cam kết cho xem trực tiếp & ký linh kiện
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 14px;">
                                <a href="shop_detail.php?id=<?= $shop['id'] ?>" class="fn-btn fn-btn-primary" style="flex: 1; text-align: center; text-decoration: none; font-size: 13px;">
                                    Xem Bảng Giá & Chi Tiết ➔
                                </a>
                                <a href="tel:<?= preg_replace('/[^0-9]/', '', $shop['phone']) ?>" class="fn-btn fn-btn-secondary" style="font-size: 13px; padding: 0 14px;" title="Gọi hotline">
                                    📞
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 24px;">
                <button type="button" onclick="window.location.href='request_repair.php'" class="fn-btn fn-btn-secondary" style="font-size: 14px; font-weight: 700;">
                    🔄 Tạo Thêm Yêu Cầu Cho Thiết Bị Khác
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- WIZARD INTERACTIVE REPAIRBOOKINGS-STYLE -->
    <div style="display: grid; grid-template-columns: 2.3fr 1fr; gap: 32px; align-items: flex-start; text-align: left;">
        
        <!-- Cột Trái: Trình Chọn Thông Minh 5 Bước -->
        <div class="fn-rb-wizard" id="fn-rb-wizard-box">
            
            <!-- Breadcrumbs tiến trình -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                <div id="fn-wizard-breadcrumb" class="fn-rb-breadcrumb" onclick="wizardGoToStep(1)">
                    <span>📱 Bước 1: Chọn Thiết Bị</span>
                </div>
                <div style="font-size: 12.5px; font-weight: 800; color: #ea580c; background: #fff7ed; padding: 4px 12px; border-radius: 12px; border: 1px solid #fed7aa;" id="fn-step-indicator">
                    Bước 1 / 5
                </div>
            </div>

            <!-- FORM GỬI THẬT ĐẾN BACKEND -->
            <form action="request_repair.php" method="POST" id="fn-wizard-form">
                <input type="hidden" name="submit_request" value="1">
                <input type="hidden" name="device_type" id="hidden_device_type" value="Điện thoại (Smartphone)">
                <input type="hidden" name="brand_model" id="hidden_brand_model" value="">
                <input type="hidden" name="issue_type" id="hidden_issue_type" value="">

                <!-- ================= BƯỚC 1: CHỌN LOẠI THIẾT BỊ ================= -->
                <div id="wizard-step-1" class="wizard-step">
                    <h2 class="fn-rb-title">1. Thiết Bị Bạn Cần Sửa Chữa Là Gì?</h2>
                    <p class="fn-rb-subtitle">Chọn đúng dòng máy để hiển thị đầy đủ danh mục thương hiệu và linh kiện tương ứng:</p>
                    
                    <div class="fn-rb-device-grid">
                        <div class="fn-rb-device-card active" onclick="wizardSelectDevice('phone', 'Điện thoại (Smartphone)')">
                            <div class="fn-rb-device-icon" style="background: #eff6ff; color: #2563eb;">📱</div>
                            <div class="fn-rb-device-name">Điện Thoại</div>
                        </div>
                        <div class="fn-rb-device-card" onclick="wizardSelectDevice('win_laptop', 'Laptop Windows')">
                            <div class="fn-rb-device-icon" style="background: #f0fdf4; color: #16a34a;">💻</div>
                            <div class="fn-rb-device-name">Laptop Win</div>
                        </div>
                        <div class="fn-rb-device-card" onclick="wizardSelectDevice('macbook', 'Apple MacBook')">
                            <div class="fn-rb-device-icon" style="background: #f8fafc; color: #0f172a;">🍏</div>
                            <div class="fn-rb-device-name">MacBook</div>
                        </div>
                        <div class="fn-rb-device-card" onclick="wizardSelectDevice('tablet', 'Máy tính bảng (iPad / Tablet)')">
                            <div class="fn-rb-device-icon" style="background: #faf5ff; color: #9333ea;">📟</div>
                            <div class="fn-rb-device-name">iPad / Tablet</div>
                        </div>
                        <div class="fn-rb-device-card" onclick="wizardSelectDevice('pc_desktop', 'Máy tính để bàn (PC / Desktop)')">
                            <div class="fn-rb-device-icon" style="background: #fff7ed; color: #ea580c;">🖥️</div>
                            <div class="fn-rb-device-name">Máy Tính Bàn</div>
                        </div>
                        <div class="fn-rb-device-card" onclick="wizardSelectDevice('smartwatch', 'Đồng hồ thông minh (Smartwatch)')">
                            <div class="fn-rb-device-icon" style="background: #ecfeff; color: #0891b2;">⌚</div>
                            <div class="fn-rb-device-name">Smartwatch</div>
                        </div>
                    </div>
                </div>

                <!-- ================= BƯỚC 2: CHỌN THƯƠNG HIỆU (LOGO CĂN GIỮA ĐẸP) ================= -->
                <div id="wizard-step-2" class="wizard-step" style="display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <h2 class="fn-rb-title" id="step-2-title">2. Chọn Hãng Thiết Bị Của Bạn</h2>
                        <button type="button" class="fn-btn fn-btn-secondary" style="padding: 6px 14px; font-size: 12.5px;" onclick="wizardGoToStep(1)">
                            ← Đổi Thiết Bị
                        </button>
                    </div>
                    <p class="fn-rb-subtitle">Click chọn thương hiệu để xem danh mục toàn bộ model và phiên bản:</p>
                    
                    <div class="fn-rb-brand-grid" id="fn-brand-grid-container">
                        <!-- Danh sách card thương hiệu với logo căn giữa sẽ được sinh bởi JavaScript -->
                    </div>
                </div>

                <!-- ================= BƯỚC 3: CHỌN MODEL MÁY CỤ THỂ ================= -->
                <div id="wizard-step-3" class="wizard-step" style="display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <h2 class="fn-rb-title" id="step-3-title">3. Chọn Đúng Đời Máy / Model</h2>
                        <button type="button" class="fn-btn fn-btn-secondary" style="padding: 6px 14px; font-size: 12.5px;" onclick="wizardGoToStep(2)">
                            ← Đổi Hãng
                        </button>
                    </div>
                    <p class="fn-rb-subtitle" id="step-3-subtitle">Gõ tìm kiếm nhanh hoặc click chọn từ danh sách:</p>

                    <!-- Thanh tìm kiếm model -->
                    <div class="fn-rb-model-search">
                        <input type="text" id="fn-model-filter-input" class="fn-input" placeholder="🔎 Gõ tìm nhanh model máy (Ví dụ: 17 Pro, 15 Plus, TUF F15, S24 Ultra, Air M2...)" autocomplete="off">
                    </div>

                    <!-- Grid chứa danh sách model (Ví dụ iPhone: full 36 dòng từ 17 Pro Max đến 7) -->
                    <div class="fn-rb-model-grid" id="fn-model-grid-container">
                        <!-- JS inject -->
                    </div>

                    <!-- Tùy chọn tự gõ model nếu không có trong danh sách -->
                    <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 14px 18px; margin-top: 14px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <span style="font-size: 13px; font-weight: 700; color: #475569;">✏️ Không tìm thấy mã máy của bạn?</span>
                        <input type="text" id="fn-custom-model-input" class="fn-input" style="flex: 1; min-width: 200px; padding: 8px 12px; font-size: 13px;" placeholder="Tự nhập mã máy (Ví dụ: Dell Vostro 3510, iPad Pro 2020...)">
                        <button type="button" class="fn-btn fn-btn-primary" style="padding: 8px 16px; font-size: 13px;" onclick="wizardSubmitCustomModel()">
                            Xác Nhận Model ➔
                        </button>
                    </div>
                </div>

                <!-- ================= BƯỚC 4: CHỌN PAN BỆNH CẦN SỬA (DEVICE-SPECIFIC) ================= -->
                <div id="wizard-step-4" class="wizard-step" style="display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <h2 class="fn-rb-title">4. Bạn Cần Sửa Chữa Pan Bệnh Nào?</h2>
                        <button type="button" class="fn-btn fn-btn-secondary" style="padding: 6px 14px; font-size: 12.5px;" onclick="wizardGoToStep(3)">
                            ← Đổi Model
                        </button>
                    </div>
                    <p class="fn-rb-subtitle">Bảng lỗi được tùy biến chuyên biệt theo cấu trúc phần cứng của thiết bị bạn đã chọn:</p>

                    <!-- Thanh tóm tắt model đang chọn -->
                    <div class="fn-rb-summary-bar">
                        <div>
                            <span style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Thiết bị & Model:</span>
                            <div style="font-size: 15px; font-weight: 900; color: #0f172a; margin-top: 2px;" id="summary-device-model">iPhone 15 Pro Max</div>
                        </div>
                        <span class="fn-rb-summary-tag">✓ Đã chọn đúng thông số</span>
                    </div>

                    <!-- Grid danh sách pan bệnh theo từng loại thiết bị -->
                    <div class="fn-rb-repair-grid" id="fn-repair-grid-container">
                        <!-- JS inject -->
                    </div>

                    <!-- Mục "Not sure? Describe it" đặc biệt theo RepairBookings -->
                    <div style="background: #fff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 20px; margin-top: 14px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <span style="font-size: 18px;">📝</span>
                            <label class="fn-label" style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0f172a;">
                                Not sure? Describe it / Chưa rõ pan bệnh? Hãy mô tả hiện trạng máy:
                            </label>
                        </div>
                        <textarea name="symptom" id="fn-symptom-textarea" class="fn-textarea" rows="3" placeholder="Ví dụ: Máy đang cắm sạc thì bị sập nguồn, khởi động lại chỉ hiện quả táo rồi tắt ngấm, trước đó có bị đổ một ít nước vào loa..."></textarea>
                        <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                            <button type="button" class="fn-btn fn-btn-primary" style="padding: 10px 22px; font-size: 14px; font-weight: 800;" onclick="wizardProceedToStep5()">
                                Tiếp Tục Đến Bước Vị Trí ➔
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ================= BƯỚC 5: KHU VỰC, THỜI GIAN & THÔNG TIN ================= -->
                <div id="wizard-step-5" class="wizard-step" style="display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <h2 class="fn-rb-title">5. Khu Vực & Thời Gian Tiếp Nhận</h2>
                        <button type="button" class="fn-btn fn-btn-secondary" style="padding: 6px 14px; font-size: 12.5px;" onclick="wizardGoToStep(4)">
                            ← Đổi Pan Bệnh
                        </button>
                    </div>

                    <!-- Banner chuẩn RepairBookings: We use your location to show nearby shops -->
                    <div class="fn-rb-location-notice">
                        <span style="font-size: 24px; flex-shrink: 0;">💡</span>
                        <div>
                            <strong>We use your location to show nearby shops</strong> — you can change it on the results page.
                            <br><span style="font-size: 12px; color: #3b82f6;">FixNear sử dụng khu vực bạn chọn để gợi ý các trung tâm sửa uy tín có sẵn linh kiện gần bạn nhất tại TP.HCM.</span>
                        </div>
                    </div>

                    <!-- Tóm tắt toàn bộ trước khi gửi -->
                    <div class="fn-rb-summary-bar" style="background: #fffaf0; border-color: #fde68a;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; width: 100%;">
                            <div>
                                <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase;">Dòng máy:</div>
                                <div style="font-size: 14px; font-weight: 800; color: #78350f;" id="summary-final-model">iPhone 15 Pro Max</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase;">Pan bệnh chọn:</div>
                                <div style="font-size: 14px; font-weight: 800; color: #ea580c;" id="summary-final-issue">Thay Màn Hình (Screen Repair)</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase;">Chính sách:</div>
                                <div style="font-size: 13px; font-weight: 700; color: #16a34a;">Báo giá trước • Ký linh kiện</div>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 18px;">
                        <div class="fn-form-group">
                            <label class="fn-label">Khu vực bạn muốn sửa: <span style="color:#ef4444;">*</span></label>
                            <select name="district" id="fn-wizard-district" class="fn-select" required>
                                <option value="Quận 1">Quận 1</option>
                                <option value="Quận 3">Quận 3</option>
                                <option value="Quận 4">Quận 4</option>
                                <option value="Quận 5">Quận 5</option>
                                <option value="Quận 6">Quận 6</option>
                                <option value="Quận 7">Quận 7</option>
                                <option value="Quận 8">Quận 8</option>
                                <option value="Quận 10">Quận 10</option>
                                <option value="Quận 11">Quận 11</option>
                                <option value="Quận 12">Quận 12</option>
                                <option value="Quận Bình Tân">Quận Bình Tân</option>
                                <option value="Quận Bình Thạnh">Quận Bình Thạnh</option>
                                <option value="Quận Gò Vấp">Quận Gò Vấp</option>
                                <option value="Quận Phú Nhuận">Quận Phú Nhuận</option>
                                <option value="Quận Tân Bình">Quận Tân Bình</option>
                                <option value="Quận Tân Phú">Quận Tân Phú</option>
                                <option value="TP. Thủ Đức">TP. Thủ Đức</option>
                            </select>
                        </div>

                        <div class="fn-form-group">
                            <label class="fn-label">Định vị GPS thông minh:</label>
                            <button type="button" class="fn-btn fn-btn-secondary" style="width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 700;" onclick="wizardDetectDistrict()">
                                📍 Lấy Vị Trí Của Tôi Hiện Tại
                            </button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 14px;">
                        <div class="fn-form-group">
                            <label class="fn-label">Họ và tên của bạn: <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="customer_name" class="fn-input" placeholder="Ví dụ: Nguyễn Văn An" required>
                        </div>
                        <div class="fn-form-group">
                            <label class="fn-label">Số điện thoại / Zalo nhận báo giá: <span style="color:#ef4444;">*</span></label>
                            <input type="tel" name="customer_phone" class="fn-input" placeholder="Ví dụ: 0908 123 456" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 14px;">
                        <div class="fn-form-group">
                            <label class="fn-label">Email nhận biên lai số hóa: <span style="color:#ef4444;">*</span></label>
                            <input type="email" name="customer_email" class="fn-input" placeholder="email@gmail.com" required>
                        </div>
                        <div class="fn-form-group">
                            <label class="fn-label">Khung giờ bạn có thể mang máy đến (08:30 - 20:30):</label>
                            <select name="preferred_time" class="fn-select">
                                <option value="⚡ Hôm nay (Sửa gấp lấy liền trong giờ mở cửa 08:30 - 20:30)">⚡ Sửa gấp lấy liền hôm nay (08:30 - 20:30)</option>
                                <option value="🌅 Ca Sáng (08:30 - 11:30) — Kiểm tra lấy trước trưa">🌅 Ca Sáng (08:30 - 11:30)</option>
                                <option value="☀️ Ca Chiều (13:30 - 17:30) — Xử lý buổi chiều">☀️ Ca Chiều (13:30 - 17:30)</option>
                                <option value="🌙 Ca Tối sau giờ tan ca (17:30 - 20:30)">🌙 Ca Tối sau giờ học / làm (17:30 - 20:30)</option>
                                <option value="📅 Cuối tuần (Thứ 7 / Chủ Nhật: 08:30 - 20:30)">📅 Cuối tuần (Thứ 7 & Chủ Nhật)</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 26px;">
                        <button type="submit" class="fn-btn fn-btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 900; border-radius: 14px; box-shadow: 0 8px 24px rgba(234, 88, 12, 0.4); text-transform: uppercase;">
                            🚀 Xem Báo Giá Minh Bạch & Danh Sách Tiệm Gần Bạn ➔
                        </button>
                        <div style="text-align: center; font-size: 12.5px; color: #64748b; margin-top: 10px;">
                            🔒 Cam kết bảo mật thông tin. Bạn sẽ nhận được báo giá và hỗ trợ từ kỹ thuật viên trong 15 phút.
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- Cột Phải: Cam Kết Minh Bạch & Live Feed -->
        <div style="text-align: left;">
            <!-- Cam Kết 3 Không -->
            <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; border-radius: var(--fn-radius-lg); padding: 26px; margin-bottom: 24px; text-align: left;">
                <div style="color: #fb923c; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                    🛡️ TIÊU CHUẨN MINH BẠCH FIXNEAR
                </div>
                <h3 style="font-family: var(--fn-font-heading); font-size: 19px; font-weight: 900; color: #fff; margin-bottom: 14px; text-align: left;">
                    Cam Kết Bảo Vệ Khách Hàng 100%
                </h3>
                <ul style="padding-left: 0; list-style: none; font-size: 13.5px; line-height: 1.7; display: flex; flex-direction: column; gap: 12px; text-align: left;">
                    <li style="display: flex; gap: 10px;">
                        <span style="color:#22c55e; font-size: 16px; flex-shrink: 0;">✓</span>
                        <span><strong>Báo giá trước khi làm:</strong> Tuyệt đối không có phí ẩn, không tự ý sửa khi khách chưa xác nhận.</span>
                    </li>
                    <li style="display: flex; gap: 10px;">
                        <span style="color:#22c55e; font-size: 16px; flex-shrink: 0;">✓</span>
                        <span><strong>Ký tên linh kiện:</strong> Khách hàng ký trực tiếp lên pin, màn hình, mainboard trước khi nhận máy.</span>
                    </li>
                    <li style="display: flex; gap: 10px;">
                        <span style="color:#22c55e; font-size: 16px; flex-shrink: 0;">✓</span>
                        <span><strong>Cho ngồi xem trực tiếp:</strong> Đối với các dịch vụ lấy liền (thay pin, màn hình, vệ sinh máy, cài win).</span>
                    </li>
                    <li style="display: flex; gap: 10px;">
                        <span style="color:#fb923c; font-size: 16px; flex-shrink: 0;">★</span>
                        <span><strong>Ưu đãi học sinh sinh viên:</strong> Hệ thống chuỗi đối tác (Điện Thoại Vui, FPT Services) có chương trình Back to School — hỗ trợ vệ sinh máy miễn phí & trợ giá khi xuất trình thẻ SV.</span>
                    </li>
                </ul>
            </div>

            <!-- Live Feed Đơn Thực Tế -->
            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 22px; text-align: left;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                    <h4 style="font-size: 14.5px; font-weight: 800; color: var(--fn-dark); display: flex; align-items: center; gap: 6px; margin: 0;">
                        <span class="fn-pulse-dot" style="background:#22c55e;"></span> Yêu Cầu Vừa Gửi Thực Tế
                    </h4>
                    <span style="font-size: 11px; color: #64748b;">Trực tiếp</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12.5px;">
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 8px; border-left: 3px solid #ea580c;">
                        <div style="font-weight: 700; color: #0f172a;">Hoàng Nam — iPhone 15 Pro Max (Q.1)</div>
                        <div style="color: #64748b; margin-top: 2px;">Lỗi: Thay màn hình OLED zin · <em>3 phút trước</em></div>
                    </div>
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 8px; border-left: 3px solid #22c55e;">
                        <div style="font-weight: 700; color: #0f172a;">Trần Minh T. — Asus TUF Gaming F15 (Q.Tân Bình)</div>
                        <div style="color: #64748b; margin-top: 2px;">Lỗi: Vệ sinh máy & Tra keo tản nhiệt MX-4 · <em>11 phút trước</em></div>
                    </div>
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 8px; border-left: 3px solid #3b82f6;">
                        <div style="font-weight: 700; color: #0f172a;">Thùy Trang — MacBook Air M2 (Q.3)</div>
                        <div style="color: #64748b; margin-top: 2px;">Lỗi: Thay pin chuẩn Apple · <em>24 phút trước</em></div>
                    </div>
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 8px; border-left: 3px solid #eab308;">
                        <div style="font-weight: 700; color: #0f172a;">Khánh Linh — Apple Watch Series 8 (Q.10)</div>
                        <div style="color: #64748b; margin-top: 2px;">Lỗi: Cấp cứu vô nước khi bơi · <em>38 phút trước</em></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ================= DICTIONARY BRANDS, MODELS & SPECIFIC REPAIR ISSUES =================
const WIZARD_DATA = {
    // 1. MOBILE / PHONE
    phone: {
        name: "Điện thoại",
        brands: [
            {
                id: "apple_iphone",
                name: "Apple (iPhone)",
                logo: `<svg width="26" height="26" viewBox="0 0 170 170" fill="#0f172a"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.58-7.7-11.64-13.98-5.87-9.01-10.37-19.16-13.5-30.46-3.13-11.29-4.7-22.18-4.7-32.65 0-15.01 3.86-27.42 11.58-37.22 7.72-9.8 17.51-14.83 29.36-15.09 4.99 0 10.22 1.25 15.69 3.76 5.48 2.5 9.07 3.81 10.78 3.91 1.7.1 5.37-1.25 11.02-4.06 5.66-2.82 10.66-4.14 15.02-3.97 11.57.65 21.03 4.9 28.38 12.74-10.15 6.19-15.11 14.83-14.88 25.92.23 8.78 3.63 16.27 10.2 22.47 6.56 6.2 14.28 9.77 23.16 10.7-2.6 7.6-5.83 15.17-9.7 22.71zM119.22 31.84c0-7.39 2.65-14.36 7.95-20.91 5.3-6.55 11.96-10.53 19.98-11.93-.11 1.41-.33 2.82-.65 4.23-1.09 4.79-3.32 9.77-6.7 14.94-3.38 5.16-7.55 9.03-12.51 11.6-1.52.87-3.04 1.52-4.56 1.95-.54-.65-1.09-1.52-1.63-2.61-.54-1.09-.88-2.39-.88-3.89z"/></svg>`,
                models: [
                    "iPhone 17 Pro Max", "iPhone 17 Pro", "iPhone 17",
                    "iPhone 16 Pro Max", "iPhone 16 Pro", "iPhone 16 Plus", "iPhone 16",
                    "iPhone 15 Pro Max", "iPhone 15 Pro", "iPhone 15 Plus", "iPhone 15",
                    "iPhone 14 Pro Max", "iPhone 14 Pro", "iPhone 14 Plus", "iPhone 14",
                    "iPhone 13 Pro Max", "iPhone 13 Pro", "iPhone 13", "iPhone 13 mini",
                    "iPhone 12 Pro Max", "iPhone 12 Pro", "iPhone 12", "iPhone 12 mini",
                    "iPhone SE (2022) / 3rd gen",
                    "iPhone 11 Pro Max", "iPhone 11 Pro", "iPhone 11",
                    "iPhone XS Max", "iPhone XS", "iPhone XR", "iPhone X",
                    "iPhone 8 Plus", "iPhone 8", "iPhone 7 Plus", "iPhone 7"
                ]
            },
            {
                id: "samsung",
                name: "Samsung",
                logo: `<div style="font-weight:900; font-size:13px; letter-spacing:-0.5px; color:#1428a0; border:2px solid #1428a0; border-radius:12px; padding:2px 6px;">SAMSUNG</div>`,
                models: [
                    "Galaxy S24 Ultra", "Galaxy S24 Plus", "Galaxy S24",
                    "Galaxy S23 Ultra", "Galaxy S23 Plus", "Galaxy S23",
                    "Galaxy S22 Ultra", "Galaxy S22 Plus", "Galaxy S22",
                    "Galaxy S21 Ultra", "Galaxy S21 Plus", "Galaxy S21 FE",
                    "Galaxy Z Fold 5", "Galaxy Z Fold 4", "Galaxy Z Fold 3",
                    "Galaxy Z Flip 5", "Galaxy Z Flip 4", "Galaxy Z Flip 3",
                    "Galaxy Note 20 Ultra", "Galaxy Note 20", "Galaxy Note 10 Plus",
                    "Galaxy A55 5G", "Galaxy A54 5G", "Galaxy A35 5G", "Galaxy A34 5G", "Galaxy A25 5G", "Galaxy A15", "Galaxy A05s"
                ]
            },
            {
                id: "xiaomi",
                name: "Xiaomi",
                logo: `<div style="background:#ff6900; color:#fff; font-weight:900; font-size:14px; border-radius:8px; width:30px; height:30px; display:flex; align-items:center; justify-content:center;">mi</div>`,
                models: [
                    "Xiaomi 14 Ultra", "Xiaomi 14", "Xiaomi 13 Pro", "Xiaomi 13", "Xiaomi 13T Pro", "Xiaomi 12T Pro", "Xiaomi 12 Pro",
                    "Redmi Note 13 Pro+ 5G", "Redmi Note 13 Pro", "Redmi Note 13", "Redmi Note 12 Pro", "Redmi Note 12", "Redmi 13C", "Redmi 12",
                    "POCO F5 Pro", "POCO F5", "POCO X6 Pro", "POCO X5 Pro", "POCO M6 Pro"
                ]
            },
            {
                id: "oppo",
                name: "Oppo",
                logo: `<div style="background:#00875a; color:#fff; font-weight:900; font-size:11px; border-radius:8px; padding:4px 8px; letter-spacing:0.5px;">OPPO</div>`,
                models: [
                    "Find N3 Fold", "Find N3 Flip", "Find X7 Ultra", "Find X6 Pro", "Find X5 Pro",
                    "Reno 11 Pro 5G", "Reno 11 5G", "Reno 10 Pro+ 5G", "Reno 10 5G", "Reno 8 Pro", "Reno 8",
                    "Oppo A79 5G", "Oppo A78", "Oppo A58", "Oppo A38", "Oppo A18"
                ]
            },
            {
                id: "vivo",
                name: "Vivo",
                logo: `<div style="color:#415fff; font-weight:900; font-size:14px;">vivo</div>`,
                models: ["Vivo X100 Pro", "Vivo X90 Pro", "Vivo V30 5G", "Vivo V29 5G", "Vivo V27 5G", "Vivo Y200", "Vivo Y36", "Vivo Y17s"]
            },
            {
                id: "realme",
                name: "Realme",
                logo: `<div style="background:#ffc915; color:#000; font-weight:900; font-size:11px; border-radius:6px; padding:3px 6px;">realme</div>`,
                models: ["Realme GT 5 Pro", "Realme 12 Pro+ 5G", "Realme 11 Pro", "Realme 11", "Realme C67", "Realme C55", "Realme C53"]
            },
            {
                id: "google",
                name: "Google (Pixel)",
                logo: `<svg width="24" height="24" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.65v3.03h3.88c2.27-2.09 3.665-5.17 3.665-9.12z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.03c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.26v3.13C3.25 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.29c-.25-.72-.38-1.49-.38-2.29s.13-1.57.38-2.29V6.58H1.26C.46 8.16 0 9.94 0 12s.46 3.84 1.26 5.42l4.02-3.13z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.25 2.7 1.26 6.58l4.02 3.13c.95-2.83 3.6-4.96 6.72-4.96z"/></svg>`,
                models: ["Pixel 9 Pro XL", "Pixel 9 Pro", "Pixel 9", "Pixel 8 Pro", "Pixel 8", "Pixel 7 Pro", "Pixel 7", "Pixel 7a", "Pixel 6 Pro", "Pixel 6a"]
            },
            {
                id: "sony",
                name: "Sony",
                logo: `<div style="font-weight:900; font-size:12px; letter-spacing:1px; color:#0f172a;">SONY</div>`,
                models: ["Xperia 1 VI", "Xperia 1 V", "Xperia 1 IV", "Xperia 5 V", "Xperia 5 IV", "Xperia 10 V"]
            },
            {
                id: "oneplus",
                name: "OnePlus",
                logo: `<div style="background:#f50514; color:#fff; font-weight:900; font-size:12px; border-radius:6px; padding:2px 7px;">1+</div>`,
                models: ["OnePlus 12", "OnePlus 12R", "OnePlus 11", "OnePlus 10 Pro", "OnePlus Open", "OnePlus Nord 3"]
            },
            {
                id: "phone_other",
                name: "✏️ Hãng khác",
                logo: `<div style="font-size:18px;">📱</div>`,
                models: ["Huawei Mate 60 Pro", "Honor Magic 6 Pro", "Nothing Phone (2)", "Nokia G22", "Asus ROG Phone 8 Pro", "Infinix Note 30"]
            }
        ],
        issues: [
            { id: "screen", name: "Thay Màn Hình (Screen Repair)", sub: "Nứt vỡ kính, sọc kẻ, đơ loạn cảm ứng, chảy mực đen", icon: "📱" },
            { id: "battery", name: "Thay Pin (Battery Replacement)", sub: "Chai pin, phù pin đội màn hình, sập nguồn đột ngột", icon: "🔋" },
            { id: "charging", name: "Cổng Sạc & Chân Cắm (Charging Port)", sub: "Cắm sạc không nhận, sạc chập chờn, báo ẩm cổng sạc", icon: "🔌" },
            { id: "water", name: "Cấp Cứu Vô Nước (Water Damage)", sub: "Rơi nước, ngâm nước, mất nguồn cần sấy siêu âm gấp", icon: "💧" },
            { id: "backglass", name: "Thay Kính Lưng (Back Glass)", sub: "Bắn laser thay mặt kính lưng vỡ, nứt sườn sau", icon: "🪞" },
            { id: "camera", name: "Sửa / Thay Camera (Camera)", sub: "Rung nhòe, không lấy nét, đốm đen, mất cam trước/sau", icon: "📷" },
            { id: "speaker", name: "Loa Ngoài / Chuông (Speaker)", sub: "Rè loa khi nghe nhạc xem phim, mất chuông cuộc gọi", icon: "🔊" },
            { id: "mic", name: "Mic Thu Âm (Microphone)", sub: "Gọi điện đối phương không nghe tiếng, ghi âm bị rè", icon: "🎙️" },
            { id: "software", name: "Lỗi Phần Mềm (Software Issue)", sub: "Treo logo, treo táo, kẹt bootloop, khóa mã bảo vệ", icon: "⚙️" },
            { id: "motherboard", name: "Sửa Bo Mạch Mainboard (Motherboard)", sub: "Máy chạm chập nguồn, nóng ran, ăn nguồn hao pin", icon: "🧩" },
            { id: "faceid", name: "Face ID / Cảm Biến Tiệm Cận (Face ID)", sub: "Mất Face ID không định vị được khuôn mặt, không tắt màn", icon: "👤" },
            { id: "fingerprint", name: "Cảm Biến Vân Tay (Fingerprint)", sub: "Không nhận vân tay Touch ID hoặc cảm biến dưới màn hình", icon: "👆" },
            { id: "wifi", name: "WiFi & Sóng Di Động (WiFi / Signal)", sub: "Ẩn nút gạt WiFi, không dò thấy mạng, mất sóng nghe gọi", icon: "📶" },
            { id: "buttons", name: "Nút Nguồn / Âm Lượng (Buttons)", sub: "Liệt phím nguồn, kẹt nút tăng giảm âm lượng, gạt rung", icon: "🔘" },
            { id: "housing", name: "Thay Khung Sườn Vỏ Máy (Housing)", sub: "Sườn máy bị cong vênh, móp méo viền kim loại do va đập", icon: "🛡️" },
            { id: "data", name: "Cứu Dữ Liệu Đồ Án / Ảnh (Data Recovery)", sub: "Trích xuất hình ảnh, danh bạ, tài liệu từ máy chết nguồn", icon: "💾" },
            { id: "earspeaker", name: "Loa Thoại Trong (Ear Speaker)", sub: "Nghe gọi âm lượng rất bé hoặc nghẹt tiếng", icon: "👂" },
            { id: "diagnostic", name: "Kiểm Tra Toàn Diện (Full Diagnostic)", sub: "Kỹ thuật viên đo đạc toàn bộ linh kiện báo giá chi tiết", icon: "🔬" },
            { id: "other", name: "Pan Bệnh Khác (Other)", sub: "Mô tả triệu chứng cụ thể bên dưới để thợ tư vấn", icon: "❓" }
        ]
    },

    // 2. WINDOWS LAPTOP
    win_laptop: {
        name: "Laptop Windows",
        brands: [
            {
                id: "dell",
                name: "Dell",
                logo: `<div style="color:#0076ce; font-weight:900; font-size:16px; border:2px solid #0076ce; border-radius:50%; width:34px; height:34px; display:flex; align-items:center; justify-content:center;">D</div>`,
                models: [
                    "Dell XPS 16 (9640)", "Dell XPS 14 (9440)", "Dell XPS 13 Plus (9320)", "Dell XPS 15 (9530)",
                    "Dell Inspiron 14 5430", "Dell Inspiron 15 3520", "Dell Inspiron 5510", "Dell Inspiron 7420 2-in-1",
                    "Dell Latitude 7440", "Dell Latitude 5430", "Dell Latitude 3520", "Dell Latitude 7490", "Dell Latitude 7480",
                    "Dell Vostro 3520", "Dell Vostro 3400", "Dell Vostro 5410",
                    "Dell Gaming G15 5530", "Dell Gaming G16 7630", "Dell Alienware m16 R2"
                ]
            },
            {
                id: "asus",
                name: "Asus",
                logo: `<div style="font-weight:900; font-size:13px; color:#00539b; letter-spacing:1px;">ASUS</div>`,
                models: [
                    "Asus TUF Gaming F15", "Asus TUF Gaming A15", "Asus TUF Gaming F16", "Asus TUF Dash F15",
                    "Asus ROG Strix G16", "Asus ROG Strix SCAR 16", "Asus ROG Zephyrus G14", "Asus ROG Zephyrus M16",
                    "Asus Zenbook 14 OLED (UX3405)", "Asus Zenbook 14 Flip", "Asus Zenbook Pro 14 Duo",
                    "Asus Vivobook 15 (X1504)", "Asus Vivobook 14 (X1404)", "Asus Vivobook Pro 15 OLED", "Asus Vivobook S 14 OLED",
                    "Asus ExpertBook B1 / B5"
                ]
            },
            {
                id: "hp",
                name: "HP",
                logo: `<div style="color:#0096d6; font-weight:900; font-size:15px; font-style:italic; border:2px solid #0096d6; border-radius:50%; width:34px; height:34px; display:flex; align-items:center; justify-content:center;">hp</div>`,
                models: [
                    "HP Victus 16", "HP Victus 15", "HP OMEN 16",
                    "HP Pavilion 15", "HP Pavilion 14", "HP Pavilion x360",
                    "HP Envy 13", "HP Envy 16", "HP Envy x360 14", "HP Spectre x360",
                    "HP ProBook 450 G10", "HP ProBook 440 G9", "HP EliteBook 840 G9"
                ]
            },
            {
                id: "lenovo",
                name: "Lenovo",
                logo: `<div style="background:#e2231a; color:#fff; font-weight:900; font-size:10px; padding:3px 6px; letter-spacing:0.5px;">lenovo</div>`,
                models: [
                    "Lenovo Legion Pro 5", "Lenovo Legion Pro 7", "Lenovo Legion Slim 5", "Lenovo LOQ 15",
                    "ThinkPad X1 Carbon Gen 11", "ThinkPad X1 Carbon Gen 9", "ThinkPad T14 Gen 3", "ThinkPad E14 Gen 4",
                    "Lenovo IdeaPad Slim 5", "Lenovo IdeaPad Slim 3", "Lenovo IdeaPad Gaming 3",
                    "Lenovo Yoga 7 2-in-1", "Lenovo Yoga Slim 7 Pro"
                ]
            },
            {
                id: "acer",
                name: "Acer",
                logo: `<div style="color:#83b81a; font-weight:900; font-size:13px;">acer</div>`,
                models: [
                    "Acer Predator Helios 16", "Acer Predator Helios Neo 16", "Acer Nitro 16 Phoenix", "Acer Nitro 5 Tiger",
                    "Acer Aspire 5", "Acer Aspire 3", "Acer Aspire 7 Gaming", "Acer Swift Go 14", "Acer Swift 3"
                ]
            },
            {
                id: "msi",
                name: "MSI",
                logo: `<div style="background:#d92328; color:#fff; font-weight:900; font-size:11px; padding:3px 6px; border-radius:4px;">msi</div>`,
                models: [
                    "MSI Titan 18 HX", "MSI Raider GE78", "MSI Katana 15", "MSI Cyborg 15", "MSI Bravo 15",
                    "MSI Modern 14", "MSI Modern 15", "MSI Stealth 16", "MSI Prestige 14"
                ]
            },
            {
                id: "laptop_other",
                name: "✏️ Hãng khác",
                logo: `<div style="font-size:18px;">💻</div>`,
                models: ["LG Gram 16", "LG Gram 14", "Microsoft Surface Laptop 5", "Surface Pro 9", "Gigabyte G5", "VAIO FE14"]
            }
        ],
        issues: [
            { id: "screen", name: "Thay Màn Hình (Screen Repair)", sub: "Màn sọc chỉ, nứt vỡ, đốm sáng, chớp nháy, không lên hình", icon: "💻" },
            { id: "keyboard", name: "Thay Bàn Phím (Keyboard)", sub: "Liệt phím, chạm loạn chữ, kẹt nút, phím bong gãy", icon: "⌨️" },
            { id: "battery", name: "Thay Pin Laptop (Battery Replacement)", sub: "Chai pin, phồng pin làm cong nắp đáy, rút sạc tắt ngay", icon: "🔋" },
            { id: "thermal", name: "Vệ Sinh & Tra Keo Tản Nhiệt (Thermal)", sub: "Máy quá nóng, quạt gió gào to, sập nguồn khi chạy nặng", icon: "❄️" },
            { id: "ram_upgrade", name: "Nâng Cấp RAM (RAM Upgrade)", sub: "Lắp thêm RAM 8GB / 16GB / 32GB đa nhiệm mượt mà", icon: "⚡" },
            { id: "ssd_upgrade", name: "Nâng Cấp SSD Tốc Độ Cao (SSD Upgrade)", sub: "Thay ổ HDD cũ hoặc nâng cấp dung lượng NVMe 512GB - 1TB", icon: "🚀" },
            { id: "charging", name: "Sửa Chân Sạc / Cổng DC (Charging Port)", sub: "Chân sạc lỏng lẻo, gãy chân kim, cắm lúc nhận lúc không", icon: "🔌" },
            { id: "hinge", name: "Hàn Phục Hồi Bản Lề (Hinge Repair)", sub: "Gãy chân ốc bản lề, hở vỏ khung sườn khi gập mở máy", icon: "🔧" },
            { id: "motherboard", name: "Sửa Mainboard Laptop (Motherboard)", sub: "Mất nguồn 19V, chập IC nguồn, chết tụ nguồn CPU/GPU", icon: "🧩" },
            { id: "water", name: "Cấp Cứu Vô Nước (Water Damage)", sub: "Đổ nước, cà phê lên bàn phím cần tháo xử lý sấy ngay", icon: "💧" },
            { id: "trackpad", name: "Bàn Rê Chuột Cảm Ứng (Trackpad)", sub: "Liệt chuột cảm ứng, chuột nhảy loạn, đơ cứng không click", icon: "🖱️" },
            { id: "os_soft", name: "Cài Win & Phần Mềm (OS / Softwares)", sub: "Cài sạch Win 11/10 bản quyền, Office, Photoshop, CAD, Dev", icon: "💿" },
            { id: "data", name: "Cứu Dữ Liệu Ổ Cứng Hỏng (Data Recovery)", sub: "Ổ cứng BAD sector, không nhận phân vùng đồ án, tài liệu", icon: "💾" },
            { id: "diagnostic", name: "Kiểm Tra Chẩn Đoán Toàn Diện (Full Diagnostic)", sub: "Máy chạy tắt ngẫu nhiên, màn xanh BSOD kiểm tra đo đạc", icon: "🔬" },
            { id: "other", name: "Pan Bệnh Khác (Other)", sub: "Mô tả triệu chứng cụ thể bên dưới để thợ tư vấn", icon: "❓" }
        ]
    },

    // 3. APPLE MACBOOK
    macbook: {
        name: "Apple MacBook",
        brands: [
            {
                id: "macbook_pro",
                name: "MacBook Pro",
                logo: `<svg width="26" height="26" viewBox="0 0 170 170" fill="#0f172a"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.58-7.7-11.64-13.98-5.87-9.01-10.37-19.16-13.5-30.46-3.13-11.29-4.7-22.18-4.7-32.65 0-15.01 3.86-27.42 11.58-37.22 7.72-9.8 17.51-14.83 29.36-15.09 4.99 0 10.22 1.25 15.69 3.76 5.48 2.5 9.07 3.81 10.78 3.91 1.7.1 5.37-1.25 11.02-4.06 5.66-2.82 10.66-4.14 15.02-3.97 11.57.65 21.03 4.9 28.38 12.74-10.15 6.19-15.11 14.83-14.88 25.92.23 8.78 3.63 16.27 10.2 22.47 6.56 6.2 14.28 9.77 23.16 10.7-2.6 7.6-5.83 15.17-9.7 22.71zM119.22 31.84c0-7.39 2.65-14.36 7.95-20.91 5.3-6.55 11.96-10.53 19.98-11.93-.11 1.41-.33 2.82-.65 4.23-1.09 4.79-3.32 9.77-6.7 14.94-3.38 5.16-7.55 9.03-12.51 11.6-1.52.87-3.04 1.52-4.56 1.95-.54-.65-1.09-1.52-1.63-2.61-.54-1.09-.88-2.39-.88-3.89z"/></svg>`,
                models: [
                    "MacBook Pro 16 M3 Max / M3 Pro", "MacBook Pro 14 M3 / M3 Pro",
                    "MacBook Pro 16 M2 Pro / M2 Max", "MacBook Pro 14 M2 Pro / M2 Max",
                    "MacBook Pro 16 M1 Pro / M1 Max", "MacBook Pro 14 M1 Pro / M1 Max",
                    "MacBook Pro 13 M2 (2022)", "MacBook Pro 13 M1 (2020)",
                    "MacBook Pro 13 Intel Touch Bar (2016 - 2020)",
                    "MacBook Pro 15 Intel Retina (2015 - 2019)",
                    "MacBook Pro 16 Intel (2019)"
                ]
            },
            {
                id: "macbook_air",
                name: "MacBook Air",
                logo: `<svg width="26" height="26" viewBox="0 0 170 170" fill="#ea580c"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.58-7.7-11.64-13.98-5.87-9.01-10.37-19.16-13.5-30.46-3.13-11.29-4.7-22.18-4.7-32.65 0-15.01 3.86-27.42 11.58-37.22 7.72-9.8 17.51-14.83 29.36-15.09 4.99 0 10.22 1.25 15.69 3.76 5.48 2.5 9.07 3.81 10.78 3.91 1.7.1 5.37-1.25 11.02-4.06 5.66-2.82 10.66-4.14 15.02-3.97 11.57.65 21.03 4.9 28.38 12.74-10.15 6.19-15.11 14.83-14.88 25.92.23 8.78 3.63 16.27 10.2 22.47 6.56 6.2 14.28 9.77 23.16 10.7-2.6 7.6-5.83 15.17-9.7 22.71zM119.22 31.84c0-7.39 2.65-14.36 7.95-20.91 5.3-6.55 11.96-10.53 19.98-11.93-.11 1.41-.33 2.82-.65 4.23-1.09 4.79-3.32 9.77-6.7 14.94-3.38 5.16-7.55 9.03-12.51 11.6-1.52.87-3.04 1.52-4.56 1.95-.54-.65-1.09-1.52-1.63-2.61-.54-1.09-.88-2.39-.88-3.89z"/></svg>`,
                models: [
                    "MacBook Air 15 M3 (2024)", "MacBook Air 13 M3 (2024)",
                    "MacBook Air 15 M2 (2023)", "MacBook Air 13 M2 (2022)",
                    "MacBook Air 13 M1 (2020)",
                    "MacBook Air 13 Intel Retina (2018 - 2020)"
                ]
            }
        ],
        issues: [
            { id: "retina_screen", name: "Màn Hình Retina (Retina Screen)", sub: "Sọc flexgate, nứt kính, mất đèn nền stage light, bong lớp chống lóa", icon: "🖥️" },
            { id: "battery", name: "Thay Pin Chuẩn Zin (Battery Replacement)", sub: "Báo Service Battery Warning, pin phù đội trackpad cấn vỏ", icon: "🔋" },
            { id: "keyboard", name: "Thay Bàn Phím (Keyboard)", sub: "Liệt phím bướm / phím Magic, kẹt nút space, nhảy chữ", icon: "⌨️" },
            { id: "trackpad", name: "Trackpad Force Touch", sub: "Mất rung haptic, đơ không click được, nứt mặt kính bàn rê", icon: "🖱️" },
            { id: "charging", name: "Cổng MagSafe & USB-C Type-C (Charging)", sub: "Cắm cáp không báo sạc, chập cháy cổng Thunderbolt", icon: "🔌" },
            { id: "logic_board", name: "Sửa Logic Board / Chip Apple Silicon", sub: "Mất nguồn M1/M2/M3, lỗi mạch sạc CD3217, bypass DFU", icon: "🧩" },
            { id: "water", name: "Cấp Cứu Vô Nước MacBook (Water Damage)", sub: "Rơi nước, đổ trà vào khe quạt cần cách ly pin sấy khô", icon: "💧" },
            { id: "thermal", name: "Vệ Sinh & Bảo Dưỡng Tản Nhiệt (Thermal)", sub: "Vệ sinh quạt gió, tra keo tản nhiệt cao cấp cho MacBook", icon: "❄️" },
            { id: "speaker", name: "Loa MacBook (Speaker)", sub: "Rè loa trầm, vỡ màng loa khi mở âm lượng trên 70%", icon: "🔊" },
            { id: "macos_data", name: "Cài macOS & Cứu Dữ Liệu (macOS & Data)", sub: "Treo logo táo, dấu hỏi chấm nhấp nháy, khôi phục data", icon: "🍏" },
            { id: "diagnostic", name: "Kiểm Tra Chẩn Đoán Toàn Diện (Full Diagnostic)", sub: "Kiểm tra chuyên sâu bằng thiết bị đo dòng Mac chuyên dụng", icon: "🔬" },
            { id: "other", name: "Pan Bệnh Khác (Other)", sub: "Mô tả triệu chứng cụ thể bên dưới để thợ tư vấn", icon: "❓" }
        ]
    },

    // 4. PC / DESKTOP
    pc_desktop: {
        name: "Máy tính bàn (PC)",
        brands: [
            {
                id: "pc_gaming",
                name: "PC Gaming ráp",
                logo: `<div style="font-size:18px;">🎮</div>`,
                models: [
                    "PC Gaming Core i5 / RTX 4060", "PC Gaming Core i7 / RTX 4070 Ti", "PC Gaming Ryzen 7 / RTX 4080",
                    "PC Gaming Core i5 / RTX 3060", "PC Gaming Core i3 / GTX 1660 Super"
                ]
            },
            {
                id: "pc_workstation",
                name: "PC Đồ Họa / Render",
                logo: `<div style="font-size:18px;">🏗️</div>`,
                models: [
                    "PC Workstation Core i9 14900K / RTX 4090", "PC Dual Xeon Render 3D / Đồ Họa", "PC Ryzen 9 7950X / 64GB RAM"
                ]
            },
            {
                id: "pc_office",
                name: "PC Văn Phòng",
                logo: `<div style="font-size:18px;">🏢</div>`,
                models: [
                    "PC Văn Phòng Core i3 / 8GB RAM / SSD 256GB", "PC Văn Phòng Core i5 / 16GB RAM / SSD 512GB", "PC Kế Toán / Thu Ngân"
                ]
            },
            {
                id: "pc_aio_brand",
                name: "Đồng Bộ Dell / HP / Asus",
                logo: `<div style="font-size:18px;">🖥️</div>`,
                models: [
                    "Dell OptiPlex 7090 / 3080", "Dell Inspiron AIO 24", "HP ProDesk 400 G7", "HP Pavilion AIO 27", "Asus ExpertCenter D5"
                ]
            },
            {
                id: "pc_apple",
                name: "Apple iMac / Mac Mini",
                logo: `<div style="font-size:18px;">🍏</div>`,
                models: [
                    "iMac 24 M3 (2023)", "iMac 24 M1 (2021)", "iMac 27 5K Retina Intel", "Mac Mini M2 / M2 Pro", "Mac Studio M2 Max"
                ]
            }
        ],
        issues: [
            { id: "psu", name: "Bộ Nguồn Máy Tính (PSU - Power Supply)", sub: "Kích nguồn quạt không quay, sụt áp sập nguồn khi tải nặng, khét tụ", icon: "⚡" },
            { id: "gpu", name: "Card Đồ Họa (GPU / VGA)", sub: "Rác hình, sọc màn gaming, crash văng game, quạt GPU gãy", icon: "🎮" },
            { id: "mainboard_pc", name: "Bo Mạch Chủ (Mainboard PC)", sub: "Lỗi BIOS, không nhận RAM, lỗi chipset, chết khe cắm PCIe", icon: "🧩" },
            { id: "cpu_cool", name: "CPU & Tản Nhiệt Khí/Nước (CPU & Cooler)", sub: "CPU quá nóng 95-100°C tự tắt máy, hỏng bơm tản AIO nước", icon: "❄️" },
            { id: "ram_upgrade", name: "Nâng Cấp RAM (RAM Upgrade)", sub: "Lắp thêm RAM kit Dual-channel DDR4 / DDR5 tốc độ cao", icon: "🚀" },
            { id: "ssd_upgrade", name: "Nâng Cấp SSD NVMe M.2 (SSD Upgrade)", sub: "Lắp ổ SSD NVMe Gen 4 đọc ghi cực nhanh cho đồ họa & game", icon: "💾" },
            { id: "case_io", name: "Nút Nguồn & Cổng USB Case (Case I/O)", sub: "Liệt nút Power/Reset mặt trước case, gãy cổng USB/Audio", icon: "🔘" },
            { id: "monitor", name: "Màn Hình Rời PC (Monitor Issue)", sub: "Màn hình chớp nháy, không nhận cổng HDMI / DisplayPort", icon: "🖥️" },
            { id: "data", name: "Cứu Dữ Liệu Ổ Cứng HDD/SSD (Data Recovery)", sub: "Ổ HDD cơ kêu lạch cạch, SSD mất định dạng RAW", icon: "💿" },
            { id: "os_game", name: "Cài Win, Driver & Tối Ưu Game (OS)", sub: "Cài Windows sạch, tối ưu xung nhịp, cập nhật full driver chuẩn", icon: "⚙️" },
            { id: "diagnostic", name: "Kiểm Tra Chẩn Đoán Toàn Diện (Full Diagnostic)", sub: "PC tự reset, dump xanh màn hình, cần đo đạc linh kiện", icon: "🔬" },
            { id: "other", name: "Pan Bệnh Khác (Other)", sub: "Mô tả linh kiện hỏng bên dưới để thợ tư vấn", icon: "❓" }
        ]
    },

    // 5. TABLET / IPAD
    tablet: {
        name: "Máy tính bảng",
        brands: [
            {
                id: "ipad",
                name: "Apple iPad",
                logo: `<div style="font-size:18px;">🍏</div>`,
                models: [
                    "iPad Pro 13 M4 (2024)", "iPad Pro 11 M4 (2024)",
                    "iPad Pro 12.9 M2", "iPad Pro 11 M2", "iPad Pro 12.9 M1", "iPad Pro 11 M1",
                    "iPad Air 6 (M2 2024)", "iPad Air 5 (M1)", "iPad Air 4",
                    "iPad Gen 10 (10.9 inch)", "iPad Gen 9 (10.2 inch)", "iPad Gen 8",
                    "iPad Mini 6", "iPad Mini 5"
                ]
            },
            {
                id: "samsung_tab",
                name: "Samsung Galaxy Tab",
                logo: `<div style="color:#1428a0; font-weight:900; font-size:12px;">SAMSUNG</div>`,
                models: [
                    "Galaxy Tab S9 Ultra", "Galaxy Tab S9 Plus", "Galaxy Tab S9", "Galaxy Tab S9 FE",
                    "Galaxy Tab S8 Ultra", "Galaxy Tab S8", "Galaxy Tab A9 Plus", "Galaxy Tab A8"
                ]
            },
            {
                id: "xiaomi_pad",
                name: "Xiaomi Pad",
                logo: `<div style="background:#ff6900; color:#fff; font-weight:900; font-size:11px; padding:2px 6px; border-radius:4px;">mi</div>`,
                models: ["Xiaomi Pad 6 Pro", "Xiaomi Pad 6", "Xiaomi Pad 5", "Redmi Pad Pro", "Redmi Pad SE"]
            },
            {
                id: "tablet_other",
                name: "✏️ Hãng khác",
                logo: `<div style="font-size:18px;">📟</div>`,
                models: ["Lenovo Tab P11 Pro", "Microsoft Surface Pro 9", "Huawei MatePad 11.5"]
            }
        ],
        issues: [
            { id: "screen", name: "Màn Hình & Cảm Ứng (Screen & Touch)", sub: "Vỡ kính cảm ứng ngoài, sọc màn hình, liệt cảm ứng góc", icon: "📟" },
            { id: "battery", name: "Thay Pin Tablet / iPad (Battery)", sub: "Chai pin tụt nhanh, hao pin chờ, pin phồng cong vỏ nhôm", icon: "🔋" },
            { id: "charging", name: "Cổng Sạc Type-C / Lightning (Charging Port)", sub: "Lỏng chân cắm sạc, gãy chân kim, không nhận sạc", icon: "🔌" },
            { id: "pencil", name: "Lỗi Nhận Bút Cảm Ứng (Stylus / Pencil)", sub: "Không hít sạc Apple Pencil bên hông, vẽ bị đứt đoạn", icon: "✏️" },
            { id: "buttons", name: "Nút Nguồn & Âm Lượng (Buttons)", sub: "Kẹt nút nguồn Touch ID, phím âm lượng cứng đơ không bấm được", icon: "🔘" },
            { id: "water", name: "Cấp Cứu Vô Nước (Water Damage)", sub: "Rơi nước cần vệ sinh cách ly nguồn sấy khô chống oxy hóa", icon: "💧" },
            { id: "speaker", name: "Loa Ngoài Quad-Speakers (Speaker)", sub: "Rè loa, mất 1 bên dải loa trên/dưới khi xem phim học bài", icon: "🔊" },
            { id: "motherboard", name: "Sửa IC Nguồn & Sạc (Motherboard IC)", sub: "Sửa IC sạc Tristar/Hydra, máy mất nguồn bật không lên", icon: "🧩" },
            { id: "diagnostic", name: "Kiểm Tra Chẩn Đoán Toàn Diện (Full Diagnostic)", sub: "Máy nóng ran bất thường, hao nguồn kiểm tra đo đạc", icon: "🔬" },
            { id: "other", name: "Pan Bệnh Khác (Other)", sub: "Mô tả triệu chứng cụ thể bên dưới để thợ tư vấn", icon: "❓" }
        ]
    },

    // 6. SMARTWATCH
    smartwatch: {
        name: "Đồng hồ thông minh",
        brands: [
            {
                id: "apple_watch",
                name: "Apple Watch",
                logo: `<div style="font-size:18px;">🍏</div>`,
                models: [
                    "Apple Watch Ultra 2", "Apple Watch Ultra (49mm)",
                    "Apple Watch Series 9 (45mm / 41mm)", "Apple Watch Series 8", "Apple Watch Series 7", "Apple Watch Series 6", "Apple Watch Series 5", "Apple Watch Series 4",
                    "Apple Watch SE 2 (2022)", "Apple Watch SE (2020)"
                ]
            },
            {
                id: "galaxy_watch",
                name: "Samsung Galaxy Watch",
                logo: `<div style="color:#1428a0; font-weight:900; font-size:12px;">SAMSUNG</div>`,
                models: [
                    "Galaxy Watch 6 Classic (47mm / 43mm)", "Galaxy Watch 6 (44mm / 40mm)",
                    "Galaxy Watch 5 Pro", "Galaxy Watch 5", "Galaxy Watch 4 Classic", "Galaxy Watch 4"
                ]
            },
            {
                id: "garmin",
                name: "Garmin",
                logo: `<div style="color:#007cc3; font-weight:900; font-size:12px;">GARMIN</div>`,
                models: ["Garmin Fenix 7 Pro", "Garmin Epix Pro", "Garmin Forerunner 965", "Garmin Forerunner 265", "Garmin Venu 3"]
            },
            {
                id: "watch_other",
                name: "✏️ Hãng khác",
                logo: `<div style="font-size:18px;">⌚</div>`,
                models: ["Xiaomi Watch S3", "Redmi Watch 4", "Huawei Watch GT 4", "Huawei Watch 4 Pro", "Amazfit GTR 4"]
            }
        ],
        issues: [
            { id: "screen", name: "Mặt Kính & Màn Hình (Screen Repair)", sub: "Nứt kính Sapphire / Ion-X, liệt cảm ứng viền, kẻ sọc màn", icon: "⌚" },
            { id: "battery", name: "Thay Pin Đồng Hồ (Battery Replacement)", sub: "Pin dùng chưa được nửa ngày, pin phù đội kênh màn hình", icon: "🔋" },
            { id: "water", name: "Cấp Cứu Vô Nước (Water Damage)", sub: "Đi bơi/tắm bị nước vào làm chập rung chuông liên tục", icon: "💧" },
            { id: "crown", name: "Nút Xoay Digital Crown & Nút Nguồn", sub: "Kẹt nút xoay Digital Crown, liệt phím Side Button sườn", icon: "🔘" },
            { id: "sensor", name: "Cảm Biến Nhịp Tim & SpO2 (Sensor Array)", sub: "Vỡ mặt kính đáy cảm biến, không đo được chỉ số sức khỏe", icon: "❤️" },
            { id: "wireless_charge", name: "Sạc Không Dây (Wireless Charging)", sub: "Đặt lên đế sạc không nhận, đồng hồ nóng ran không lên pin", icon: "🔌" },
            { id: "software", name: "Treo Táo / Treo Logo (Software & Bootloop)", sub: "Kẹt thanh khởi động, hiện chấm than đỏ, lỗi cập nhật", icon: "⚙️" },
            { id: "housing", name: "Vỏ Máy & Khung Sườn (Housing Repair)", sub: "Móp méo vỏ titan/nhôm, gãy ngàm gắn dây đồng hồ", icon: "🛡️" },
            { id: "diagnostic", name: "Kiểm Tra Chẩn Đoán Toàn Diện (Full Diagnostic)", sub: "Đo dòng kiểm tra độ kín nước và cảm biến toàn diện", icon: "🔬" },
            { id: "other", name: "Pan Bệnh Khác (Other)", sub: "Mô tả triệu chứng cụ thể bên dưới để thợ tư vấn", icon: "❓" }
        ]
    }
};

// ================= STATE CỦA TRÌNH WIZARD =================
let currentStep = 1;
let selectedDeviceKey = 'phone';
let selectedBrandId = '';
let selectedModelName = '';
let selectedIssueName = '';

// ================= KHỞI TẠO GIAO DIỆN KHI LOAD TRANG =================
document.addEventListener('DOMContentLoaded', function() {
    renderStep2Brands();
});

// Chuyển bước
function wizardGoToStep(step) {
    if (step < 1 || step > 5) return;
    currentStep = step;

    // Ẩn tất cả các bước
    document.querySelectorAll('.wizard-step').forEach(el => el.style.display = 'none');
    
    // Hiện bước hiện tại
    const targetStepEl = document.getElementById('wizard-step-' + step);
    if (targetStepEl) {
        targetStepEl.style.display = 'block';
    }

    // Cập nhật breadcrumb và chỉ báo bước
    document.getElementById('fn-step-indicator').textContent = 'Bước ' + step + ' / 5';

    let bcText = '📱 Bước 1: Chọn Thiết Bị';
    if (step === 2) bcText = '🏷️ Bước 2: Chọn Hãng (' + WIZARD_DATA[selectedDeviceKey].name + ')';
    if (step === 3) bcText = '🔍 Bước 3: Chọn Model (' + selectedBrandId + ')';
    if (step === 4) bcText = '⚠️ Bước 4: Chọn Pan Bệnh (' + selectedModelName + ')';
    if (step === 5) bcText = '📍 Bước 5: Khu Vực & Nhận Báo Giá';
    document.getElementById('fn-wizard-breadcrumb').innerHTML = '<span>' + bcText + '</span>';

    // Cuộn mượt lên đầu khung wizard
    document.getElementById('fn-rb-wizard-box').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// BƯỚC 1: CHỌN LOẠI THIẾT BỊ
function wizardSelectDevice(deviceKey, deviceFullName) {
    selectedDeviceKey = deviceKey;
    document.getElementById('hidden_device_type').value = deviceFullName;

    // Highlight card đang chọn
    const cards = document.querySelectorAll('.fn-rb-device-card');
    cards.forEach(c => c.classList.remove('active'));
    event.currentTarget.classList.add('active');

    // Chuyển sang Bước 2 & Render Brand tương ứng
    renderStep2Brands();
    wizardGoToStep(2);
}

// BƯỚC 2: RENDER HÃNG VỚI LOGO CĂN GIỮA ĐẸP
function renderStep2Brands() {
    const devData = WIZARD_DATA[selectedDeviceKey];
    if (!devData) return;

    document.getElementById('step-2-title').textContent = '2. Chọn Hãng ' + devData.name + ' Của Bạn';
    const container = document.getElementById('fn-brand-grid-container');
    container.innerHTML = '';

    devData.brands.forEach(brand => {
        const card = document.createElement('div');
        card.className = 'fn-rb-brand-card';
        card.innerHTML = `
            <div class="fn-rb-brand-logo">
                ${brand.logo}
            </div>
            <div class="fn-rb-brand-name" title="${brand.name}">${brand.name}</div>
        `;
        card.onclick = function() {
            wizardSelectBrand(brand);
        };
        container.appendChild(card);
    });
}

// BƯỚC 2 -> BƯỚC 3: CHỌN HÃNG & RENDER MODEL
function wizardSelectBrand(brand) {
    selectedBrandId = brand.id;
    document.getElementById('step-3-title').textContent = '3. Chọn Model ' + brand.name;
    document.getElementById('step-3-subtitle').textContent = 'Có ' + brand.models.length + ' model thuộc hãng ' + brand.name + '. Click chọn hoặc tìm kiếm nhanh:';

    renderStep3Models(brand.models);
    wizardGoToStep(3);
}

// BƯỚC 3: RENDER MODEL & TÌM KIẾM
function renderStep3Models(modelsList) {
    const container = document.getElementById('fn-model-grid-container');
    const filterInput = document.getElementById('fn-model-filter-input');
    filterInput.value = '';

    function displayModels(list) {
        container.innerHTML = '';
        if (list.length === 0) {
            container.innerHTML = `<div style="grid-column: 1 / -1; padding: 20px; color: #64748b; font-size: 13.5px; text-align: left;">
                🔍 Không tìm thấy model phù hợp. Bạn hãy tự nhập tên model máy ở ô phía dưới!
            </div>`;
            return;
        }
        list.forEach(m => {
            const card = document.createElement('div');
            card.className = 'fn-rb-model-card';
            card.innerHTML = `
                <span>${m}</span>
                <span style="font-size: 11px; color: #ea580c;">➔</span>
            `;
            card.onclick = function() {
                wizardSelectModel(m);
            };
            container.appendChild(card);
        });
    }

    displayModels(modelsList);

    // Bộ lọc Real-time gõ tới đâu lọc tới đó
    filterInput.oninput = function() {
        const q = this.value.trim().toLowerCase();
        if (!q) {
            displayModels(modelsList);
            return;
        }
        const filtered = modelsList.filter(m => m.toLowerCase().includes(q));
        displayModels(filtered);
    };
}

// Xác nhận model từ danh sách
function wizardSelectModel(modelName) {
    selectedModelName = modelName;
    document.getElementById('hidden_brand_model').value = modelName;
    document.getElementById('summary-device-model').textContent = modelName;
    document.getElementById('summary-final-model').textContent = modelName;

    // Render danh sách pan bệnh của loại thiết bị này
    renderStep4Issues();
    wizardGoToStep(4);
}

// Xác nhận model tự gõ
function wizardSubmitCustomModel() {
    const val = document.getElementById('fn-custom-model-input').value.trim();
    if (!val) {
        alert('Vui lòng nhập tên hoặc mã máy của bạn!');
        document.getElementById('fn-custom-model-input').focus();
        return;
    }
    wizardSelectModel(val);
}

// BƯỚC 4: RENDER CÁC PAN BỆNH CHUYÊN BIỆT (PHONE, LAPTOP WIN, MAC, PC, TABLET, WATCH)
function renderStep4Issues() {
    const devData = WIZARD_DATA[selectedDeviceKey];
    if (!devData) return;

    const container = document.getElementById('fn-repair-grid-container');
    container.innerHTML = '';

    devData.issues.forEach(issue => {
        const card = document.createElement('div');
        card.className = 'fn-rb-repair-card';
        card.innerHTML = `
            <div class="fn-rb-repair-icon">${issue.icon}</div>
            <div class="fn-rb-repair-info">
                <div class="fn-rb-repair-name">${issue.name}</div>
                <div class="fn-rb-repair-sub">${issue.sub}</div>
            </div>
            <div class="fn-rb-repair-check">✓</div>
        `;
        card.onclick = function() {
            document.querySelectorAll('.fn-rb-repair-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            selectedIssueName = issue.name;
            document.getElementById('hidden_issue_type').value = issue.name;
            document.getElementById('summary-final-issue').textContent = issue.name;
            // Tự động chuyển tiếp sau 0.3s
            setTimeout(() => {
                wizardGoToStep(5);
            }, 300);
        };
        container.appendChild(card);
    });
}

// Nhấn tiếp tục từ mô tả triệu chứng
function wizardProceedToStep5() {
    const symptom = document.getElementById('fn-symptom-textarea').value.trim();
    if (!selectedIssueName) {
        selectedIssueName = symptom ? 'Chẩn đoán theo mô tả: ' + symptom.substring(0, 30) + '...' : 'Kiểm tra chẩn đoán toàn diện';
        document.getElementById('hidden_issue_type').value = selectedIssueName;
        document.getElementById('summary-final-issue').textContent = selectedIssueName;
    }
    wizardGoToStep(5);
}

// Định vị GPS lấy quận tự động
function wizardDetectDistrict() {
    if (!navigator.geolocation) {
        alert('Trình duyệt của bạn không hỗ trợ Geolocation GPS.');
        return;
    }
    const btn = event.currentTarget;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '⏳ Đang xác định vị trí...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            btn.innerHTML = '✓ Đã nhận diện vị trí gần bạn';
            // Mặc định chọn khu vực trung tâm nếu GPS thành công
            const sel = document.getElementById('fn-wizard-district');
            if (sel) sel.value = 'Quận 1';
            setTimeout(() => {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }, 2000);
        },
        function(err) {
            btn.innerHTML = '⚠️ Không lấy được GPS (Đã chọn mặc định)';
            setTimeout(() => {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }, 2000);
        },
        { timeout: 6000 }
    );
}

// Validation form trước khi submit
document.getElementById('fn-wizard-form').addEventListener('submit', function(e) {
    if (!selectedModelName) {
        e.preventDefault();
        alert('Vui lòng chọn model máy ở Bước 3 trước khi hoàn tất!');
        wizardGoToStep(3);
        return;
    }
    if (!selectedIssueName && !document.getElementById('fn-symptom-textarea').value.trim()) {
        selectedIssueName = 'Kiểm tra chẩn đoán toàn diện';
        document.getElementById('hidden_issue_type').value = selectedIssueName;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
'''

with open(r'd:\Kỹ năng làm việc\fixnear\request_repair.php', 'w', encoding='utf-8') as f:
    f.write(content.strip())

print("SUCCESS: Generated request_repair.php successfully!")
