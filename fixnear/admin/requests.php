<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/requests.php");
    exit;
}

// Xử lý cập nhật trạng thái yêu cầu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    requireValidCsrf();
    $reqId = trim($_POST['id']);
    $newStatus = trim($_POST['status']);
    $updated = db()->updateRepairRequestStatus($reqId, $newStatus);
    header('Location: requests.php?msg=' . ($updated ? 'updated' : 'invalid_status'));
    exit;
}

$requests = db()->getRepairRequests();

$stats = db()->getStats();

// Thống kê nhanh
$totalReqs = count($requests);
$pendingReqs = 0;
$contactedReqs = 0;
$completedReqs = 0;
foreach ($requests as $r) {
    $st = $r['status'] ?? 'pending';
    if ($st === 'pending') $pendingReqs++;
    elseif ($st === 'contacted') $contactedReqs++;
    elseif ($st === 'completed') $completedReqs++;
}

$pageTitle = "Quản Lý Yêu Cầu Báo Giá & Cam Kết SLA 24h — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <!-- Sidebar Quản Trị -->
    <div class="fn-admin-sidebar">
        <div style="font-size: 11px; font-weight: 800; color: var(--fn-text-light); text-transform: uppercase; padding: 0 12px 8px;">
            Quản Trị Hệ Thống
        </div>
        <a href="index.php" class="fn-admin-menu-item">
            📊 Bảng thống kê
        </a>
        <a href="requests.php" class="fn-admin-menu-item active">
            📋 Yêu cầu báo giá (<?= $totalReqs ?>)
        </a>
        <a href="shops.php" class="fn-admin-menu-item">
            🏪 Quản lý cửa hàng (<?= $stats['total_shops'] ?>)
        </a>
        <a href="services.php" class="fn-admin-menu-item">
            🏷️ Dịch vụ & Bảng giá
        </a>
        <a href="reviews.php" class="fn-admin-menu-item">
            ⭐ Quản lý đánh giá (<?= $stats['total_reviews'] ?>)
        </a>
        <a href="reports.php" class="fn-admin-menu-item">
            🚩 Báo cáo sai sót (<?= $stats['pending_reports'] ?>)
        </a>
        <a href="contacts.php" class="fn-admin-menu-item">
            💬 Tin nhắn liên hệ (<?= $stats['total_contacts'] ?? 0 ?>)
        </a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--fn-border);">
            <a href="../index.php" class="fn-btn fn-btn-secondary fn-btn-sm" style="width: 100%;">
                &larr; Xem giao diện web
            </a>
        </div>
    </div>

    <!-- Nội dung chính -->
    <div class="fn-admin-content">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark); line-height: 1.3;">
                    Quản Lý Yêu Cầu Báo Giá & Cam Kết Phản Hồi SLA 24h
                </h1>
                <p style="font-size: 13.5px; color: var(--fn-dark-muted); margin-top: 4px;">
                    Quy chuẩn dịch vụ: Tiếp nhận pan bệnh, ước tính giá và liên hệ hỗ trợ khách hàng trong vòng 24 giờ.
                </p>
            </div>
            <div style="display: flex; gap: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 800;">
                    🛡️ SLA Cam Kết: Trong vòng 24 giờ
                </span>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 16px; border-radius: 8px; font-size: 13.5px; margin-bottom: 20px; border: 1px solid #86efac;">
                ✓ Đã cập nhật trạng thái xử lý yêu cầu thành công!
            </div>
        <?php endif; ?>

        <!-- 3 Thẻ KPI thống kê -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px;">
            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: 12px; padding: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--fn-text-light); text-transform: uppercase;">Tổng Yêu Cầu Nhận Được</div>
                <div style="font-size: 26px; font-weight: 900; color: var(--fn-dark); margin-top: 4px;"><?= $totalReqs ?></div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Khách gửi từ trang Gửi Thiết Bị</div>
            </div>

            <div style="background: var(--fn-surface); border: 1px solid #fed7aa; border-radius: 12px; padding: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #ea580c; text-transform: uppercase;">Chờ Phản Hồi (Cần xử lý)</div>
                <div style="font-size: 26px; font-weight: 900; color: #ea580c; margin-top: 4px;"><?= $pendingReqs ?></div>
                <div style="font-size: 11.5px; color: #c2410c; margin-top: 4px;">Đang trong khung kiểm soát SLA 24h</div>
            </div>

            <div style="background: var(--fn-surface); border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #16a34a; text-transform: uppercase;">Đã Hoàn Tất Hỗ Trợ</div>
                <div style="font-size: 26px; font-weight: 900; color: #16a34a; margin-top: 4px;"><?= $contactedReqs + $completedReqs ?></div>
                <div style="font-size: 11.5px; color: #15803d; margin-top: 4px;">Đã gọi Zalo / Gửi báo giá qua Gmail</div>
            </div>
        </div>

        <!-- Bảng danh sách yêu cầu -->
        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); overflow-x: auto; box-shadow: var(--fn-shadow-sm);">
            <table class="fn-price-table" style="min-width: 980px;">
                <thead>
                    <tr>
                        <th style="width: 100px;">Mã & Ngày</th>
                        <th style="width: 180px;">Khách Hàng</th>
                        <th style="width: 220px;">Thiết Bị & Pan Bệnh</th>
                        <th style="width: 170px;">Khung Giá & Hẹn</th>
                        <th style="width: 160px;">Tiến Độ SLA 24h</th>
                        <th style="text-align: right; width: 150px;">Phản Hồi Nhanh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--fn-text-light); padding: 40px;">
                                Hiện chưa có yêu cầu báo giá nào từ khách hàng.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): 
                            $status = $req['status'] ?? 'pending';
                            $createdAt = !empty($req['created_at']) ? strtotime($req['created_at']) : time();
                            $elapsedHours = (time() - $createdAt) / 3600;
                            $hoursLeft = 24 - $elapsedHours;
                            $cleanPhone = preg_replace('/[^0-9]/', '', $req['customer_phone'] ?? '');
                            $cleanZalo = preg_replace('/[^0-9]/', '', $req['customer_zalo'] ?? ($req['customer_phone'] ?? ''));

                            // Soạn sẵn nội dung Gmail
                            $emailSubject = rawurlencode("[FixNear] Báo giá & Hướng dẫn sửa chữa thiết bị " . ($req['brand_model'] ?? ''));
                            $emailBody = rawurlencode("Kính chào " . ($req['customer_name'] ?? '') . ",\n\nFixNear đã tiếp nhận yêu cầu tham khảo cho thiết bị " . ($req['brand_model'] ?? '') . " của bạn.\n- Lỗi thiết bị: " . ($req['issue_type'] ?? '') . "\n- Triệu chứng: " . ($req['symptom'] ?? 'Chờ kiểm tra trực tiếp') . "\n- Khoảng giá do FixNear ước tính: " . ($req['estimated_price'] ?? '') . "\n- Khu vực bạn chọn: " . ($req['district'] ?? 'TP.HCM') . "\n- Thời gian dự kiến: " . ($req['preferred_time'] ?? '') . "\n\nFixNear chưa xác nhận thay cửa hàng về giá cuối, linh kiện, bảo hành hoặc quy trình sửa. Trước khi giao máy, vui lòng yêu cầu cửa hàng:\n1. Kiểm tra và báo lỗi trực tiếp\n2. Xác nhận loại linh kiện, giá trọn gói và thời gian bảo hành bằng văn bản\n3. Ghi nhận tình trạng máy và hỏi về quyền quan sát/ký linh kiện nếu bạn cần.\n\nTrân trọng,\nFixNear");
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #ea580c; font-size: 13.5px;">
                                        <?= htmlspecialchars($req['id']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        <?= date('d/m/Y', $createdAt) ?><br>
                                        <?= date('H:i', $createdAt) ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 800; color: var(--fn-dark); font-size: 14px;">
                                        <?= htmlspecialchars($req['customer_name']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: #2563eb; font-weight: 700; margin-top: 2px;">
                                        📞 <?= htmlspecialchars($req['customer_phone']) ?>
                                    </div>
                                    <?php if (!empty($req['customer_zalo'])): ?>
                                        <div style="font-size: 11.5px; margin-top: 2px;"><a href="https://zalo.me/<?= htmlspecialchars(preg_replace('/\D+/', '', $req['customer_zalo'])) ?>" target="_blank" rel="noopener noreferrer" style="color:#c2410c;font-weight:700;">Zalo: <?= htmlspecialchars($req['customer_zalo']) ?> ↗</a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($req['customer_email'])): ?>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px; word-break: break-all;">
                                            ✉️ <?= htmlspecialchars($req['customer_email']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-size: 11.5px; color: #475569; margin-top: 2px;">
                                        📍 <?= htmlspecialchars($req['district'] ?? 'TP.HCM') ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 13.5px;">
                                        <?= htmlspecialchars($req['brand_model'] ?? $req['device_type']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: #ea580c; font-weight: 700; margin-top: 2px;">
                                        ⚠️ <?= htmlspecialchars($req['issue_type']) ?>
                                    </div>
                                    <?php if (!empty($req['symptom'])): ?>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.4; max-width: 280px;">
                                            <em>"<?= htmlspecialchars($req['symptom']) ?>"</em>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="font-weight: 800; color: #16a34a; font-size: 13px;">
                                        💰 <?= htmlspecialchars($req['estimated_price'] ?? 'Khảo sát báo giá') ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: #475569; margin-top: 4px;">
                                        🕒 <?= htmlspecialchars($req['preferred_time'] ?? '') ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if ($status === 'completed'): ?>
                                        <span style="display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px;">
                                            ✓ ĐÃ HOÀN TẤT
                                        </span>
                                    <?php elseif ($status === 'contacted'): ?>
                                        <span style="display: inline-block; background: #ede9fe; color: #6d28d9; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px;">
                                            💬 ĐÃ TƯ VẤN KHÁCH
                                        </span>
                                    <?php elseif ($hoursLeft > 0): ?>
                                        <div style="display: inline-flex; align-items: center; gap: 4px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-size: 11.5px; font-weight: 800; padding: 4px 8px; border-radius: 6px;">
                                            <span>⏳</span> Còn <?= round($hoursLeft, 1) ?>h phản hồi
                                        </div>
                                        <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                                            Cam kết SLA 24h
                                        </div>
                                    <?php else: ?>
                                        <div style="display: inline-flex; align-items: center; gap: 4px; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; font-size: 11.5px; font-weight: 800; padding: 4px 8px; border-radius: 6px;">
                                            <span>⚠️</span> Quá hạn 24h!
                                        </div>
                                        <div style="font-size: 10.5px; color: #ef4444; margin-top: 2px; font-weight: 700;">
                                            Cần phản hồi ngay
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align: right;">
                                    <div style="display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                                        <!-- Nút Gửi Gmail -->
                                        <?php if (!empty($req['customer_email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($req['customer_email']) ?>?subject=<?= $emailSubject ?>&body=<?= $emailBody ?>" target="_blank" class="fn-btn fn-btn-sm" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11.5px; padding: 4px 8px; text-decoration: none; width: 120px; text-align: center;" title="Mở ứng dụng email để phản hồi">
                                                ✉️ Gửi email
                                            </a>
                                        <?php endif; ?>

                                        <!-- Nút Nhắn Tin Zalo -->
                                        <?php if (!empty($cleanZalo)): ?>
                                            <a href="https://zalo.me/<?= $cleanZalo ?>" target="_blank" rel="noopener" class="fn-btn fn-btn-sm" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 11.5px; padding: 4px 8px; text-decoration: none; width: 120px; text-align: center;" title="Mở Zalo nhắn tin cho khách">
                                                💬 Chat Zalo
                                            </a>
                                        <?php endif; ?>

                                        <!-- Chuyển trạng thái -->
                                        <?php if ($status !== 'completed'): ?>
                                            <form method="post" action="requests.php">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($req['id']) ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="fn-btn fn-btn-sm" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 11px; padding: 3px 8px; width: 120px; text-align: center;">✓ Đã xong</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="requests.php">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($req['id']) ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" style="background:none;border:0;font-size:11px;color:#64748b;text-decoration:underline;cursor:pointer;">Đặt lại chờ</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
