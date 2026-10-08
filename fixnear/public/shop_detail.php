<?php
require_once __DIR__ . '/../config/db.php';

$shopId = (int)($_GET['id'] ?? 0);
$userLoc = getUserLocation();
$user_lat = $userLoc['lat'] ?? null;
$user_lng = $userLoc['lng'] ?? null;
$shop = db()->getShopById($shopId, $user_lat, $user_lng);

if (!$shop) {
    header("Location: shops.php");
    exit;
}

$pageTitle = $shop['name'] . " — Thông Tin Dịch Vụ & Phản Hồi | FixNear";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$services = db()->getServicesByShop($shopId);
$reviews = db()->getReviewsByShop($shopId);

$currentUserId = isLoggedIn() ? (int)($_SESSION['user']['id'] ?? 0) : 0;
$isFav = $currentUserId > 0 ? db()->isFavorite($currentUserId, $shopId) : false;

// Thông báo thành công nếu vừa gửi review hoặc report
$msg = $_GET['msg'] ?? '';
?>

<div class="fn-container" style="padding-top: 20px;">
    <!-- Nút trở lại & Breadcrumbs -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <a href="shops.php" style="display: inline-flex; align-items: center; gap: 8px; color: #1e293b; font-size: 13.5px; font-weight: 800; text-decoration: none; padding: 8px 14px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 8px; transition: color 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.2s, opacity 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <span style="font-size: 16px; color: #ea580c; font-weight: 900;">←</span>
            <span>Trở lại danh sách cửa hàng</span>
        </a>
        <div style="font-size: 13px; color: var(--fn-text-light);">
            <a href="index.php" style="color: var(--fn-dark-muted);">Trang chủ</a> &rsaquo;
            <a href="shops.php" style="color: var(--fn-dark-muted);">Cửa hàng</a> &rsaquo;
            <span style="color: var(--fn-dark); font-weight: 700;"><?= htmlspecialchars($shop['name']) ?></span>
        </div>
    </div>

    <?php if ($msg === 'review_added'): ?>
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; color: #92400e; font-size: 14px; margin-bottom: 20px;">
            ✅ Cảm ơn bạn! Đánh giá của bạn đã được ghi nhận và đang chờ Ban Quản Trị FixNear duyệt trước khi hiển thị công khai.
        </div>
    <?php elseif ($msg === 'report_added'): ?>
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; color: #92400e; font-size: 14px; margin-bottom: 20px;">
            ✅ Nhóm quản trị viên FixNear đã ghi nhận thông tin phản ánh và sẽ tiến hành kiểm tra cập nhật sớm nhất!
        </div>
    <?php elseif ($msg === 'reply_added'): ?>
        <div style="background: #dcfce7; border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 4px; color: #166534; font-size: 14px; margin-bottom: 20px;">
            🛡️ Đã đăng phản hồi chính thức từ Ban Quản Trị FixNear thành công!
        </div>
    <?php endif; ?>

    <!-- Header Thông Tin Cửa Hàng -->
    <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); overflow: hidden; box-shadow: var(--fn-shadow-sm); margin-bottom: 30px;">
        <div class="fn-shop-header-grid">
            <div style="height: 100%; min-height: 240px; position: relative;">
                <?php if (!empty($shop['image'])): ?><img class="<?= $shop['image_kind'] === 'website_snapshot' ? 'fn-shop-website-shot' : 'fn-shop-logo-img' ?>" src="<?= htmlspecialchars($shop['image']) ?>" alt="Hình từ website hệ thống <?= htmlspecialchars($shop['name']) ?>" width="600" height="400"><?php else: ?><div class="fn-shop-no-photo">Chưa có ảnh chính thức của chi nhánh</div><?php endif; ?>
                <span class="fn-shop-media-caption"><?= $shop['image_kind'] === 'website_snapshot' ? 'Ảnh website hệ thống' : (!empty($shop['image']) ? 'Logo từ website hệ thống' : 'Ảnh đang chờ đối soát') ?></span>
                <?php if (isset($shop['distance_km'])): ?>
                    <span class="fn-distance-badge" style="background: #0f172a; color: #38bdf8; font-weight: 800; border: 1px solid #0284c7;">
                        📍 <?= $shop['distance_km'] < 1 ? 'Cách vị trí đã chọn ~' . round($shop['distance_km'] * 1000) . 'm' : 'Cách vị trí đã chọn ~' . $shop['distance_km'] . ' km' ?>
                    </span>
                <?php else: ?>
                    <span class="fn-distance-badge" style="background:#0f172a;color:#e2e8f0;">📍 Chưa có vị trí để tính khoảng cách</span>
                <?php endif; ?>
            </div>

            <div style="padding: 24px 24px 24px 0; display: flex; flex-direction: column; justify-content: center;">
                <div class="fn-shop-detail-title-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
                    <h1 style="font-family: var(--fn-font-heading); font-size: 24px; font-weight: 900; color: var(--fn-dark);">
                        <?= htmlspecialchars($shop['name']) ?>
                    </h1>
                    <?php if (!empty($shop['google_rating_verified'])): ?>
                        <div class="fn-shop-rating" style="font-size: 14px; padding: 4px 10px;">⭐ <?= htmlspecialchars($shop['google_rating']) ?> (<?= number_format($shop['google_reviews_count']) ?> đánh giá Google)</div>
                    <?php endif; ?>
                </div>

                <div style="font-size: 14px; color: var(--fn-dark-muted); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                    <span>🏢</span>
                    <span><?= htmlspecialchars($shop['address']) ?></span>
                </div>
                <p style="margin:0 0 12px;font-size:12px;color:#9a3412;">
                    <?php if (!empty($shop['address_verified'])): ?>
                        Địa chỉ đường đã đối chiếu <?= htmlspecialchars(date('d/m/Y', strtotime($shop['address_verified_at']))) ?> · <a href="<?= htmlspecialchars($shop['address_source_url']) ?>" target="_blank" rel="noopener noreferrer" style="color:#c2410c;font-weight:800;">Xem website cửa hàng ↗</a>
                    <?php else: ?>
                        Địa chỉ chi nhánh chưa có nguồn đối soát; vui lòng xác nhận trước khi đến.
                    <?php endif; ?>
                </p>

                <div style="font-size: 13.5px; color: var(--fn-text-light); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <span>⏰ Giờ tham khảo: <strong><?= htmlspecialchars($shop['opening_hours']) ?></strong></span>
                    <span>•</span>
                    <span>📞 Hotline: <strong><?= htmlspecialchars($shop['phone']) ?></strong></span>
                </div>

                <p style="font-size: 13.5px; color: var(--fn-dark-muted); line-height: 1.6; margin-bottom: 16px;">
                    <?= !empty($shop['source_verified']) ? htmlspecialchars($shop['description']) : 'Mô tả cửa hàng trong dữ liệu dự án chưa được đối soát với chi nhánh. Hãy xác nhận trực tiếp trước khi sửa.' ?>
                </p>

                <div class="fn-features-pills" style="margin-bottom: 18px;">
                    <?php if (!empty($shop['service_policy_verified']) && !empty($shop['allows_onsite_watch'])): ?>
                        <span class="fn-pill fn-pill-highlight">✓ Xem kỹ thuật sửa trực tiếp</span>
                    <?php endif; ?>
                    <?php if (!empty($shop['service_policy_verified']) && !empty($shop['requires_component_signing'])): ?>
                        <span class="fn-pill">✓ Cho ký tên lên linh kiện</span>
                    <?php endif; ?>
                    <?php if (!empty($shop['student_discount_verified'])): ?>
                        <span class="fn-pill" style="background:#fef3c7; color:#92400e; border-color:#fde68a;">
                            🎁 <?= htmlspecialchars($shop['student_discount']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px;">
                    <a href="map.php?shop_id=<?= $shop['id'] ?>" class="fn-btn fn-btn-primary" style="display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 14px rgba(234, 88, 12, 0.3);">
                        🗺️ Xem trên bản đồ FixNear ➔
                    </a>
                    <a href="<?= htmlspecialchars($shop['map_url'] ?: ('https://www.google.com/maps/search/?api=1&query=' . urlencode($shop['name'] . ', ' . $shop['address']))) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                        ⭐ Mở Google Maps ↗
                    </a>
                    <?php if (!empty($shop['website'])): ?>
                        <a href="<?= htmlspecialchars($shop['website']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary" style="background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; font-weight: 700;">
                            🌐 Website Chính Thức ↗
                        </a>
                    <?php endif; ?>
                    <a href="tel:<?= preg_replace('/[^0-9]/', '', $shop['phone']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary" style="background: #f0fdf4; color: #15803d; border-color: #bbf7d0; font-weight: 700;">
                        📞 Hotline: <?= htmlspecialchars($shop['phone']) ?>
                    </a>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($shop['name'] . ', ' . $shop['address']) ?>" target="_blank" rel="noopener noreferrer" class="fn-btn fn-btn-secondary">
                        🗺️ Chỉ Đường
                    </a>
                    <button type="button" id="btn-fav-detail" class="fn-btn fn-btn-secondary fn-fav-btn" data-shop-id="<?= $shopId ?>" data-favorited="<?= $isFav ? '1' : '0' ?>" onclick="toggleFavorite(event, <?= $shopId ?>)" style="background: <?= $isFav ? '#fef2f2' : '#ffffff' ?>; color: <?= $isFav ? '#dc2626' : '#1e293b' ?>; border-color: <?= $isFav ? '#fca5a5' : 'var(--fn-border)' ?>; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <span id="fav-icon-<?= $shopId ?>"><?= $isFav ? '❤️' : '🤍' ?></span>
                        <span id="fav-text-<?= $shopId ?>"><?= $isFav ? 'Đã lưu yêu thích' : 'Lưu yêu thích' ?></span>
                    </button>
                    <button type="button" class="fn-btn fn-btn-secondary fn-open-report-btn" data-shop-id="<?= $shop['id'] ?>" data-shop-name="<?= htmlspecialchars($shop['name']) ?>">
                        🚩 Báo sai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Khối 1: BẢNG GIÁ THAM KHẢO DỊCH VỤ -->
    <div style="margin-bottom: 40px;">
        <h2 class="fn-section-title" style="margin-bottom: 8px;">Danh Mục Dịch Vụ Trong Dữ Liệu</h2>
        <p class="fn-section-desc" style="margin-bottom: 16px;">
            Chỉ hiển thị giá khi có URL nguồn và ngày đối soát; các mục còn lại yêu cầu cửa hàng xác nhận.
        </p>

        <!-- Khung lưu ý miễn trừ trách nhiệm -->
        <div class="fn-price-disclaimer">
            📌 <strong>Lưu ý từ FixNear:</strong> Danh mục dịch vụ là dữ liệu tham khảo. Giá, bảo hành, thời gian và việc cửa hàng có nhận sửa thiết bị cụ thể phải được xác nhận trực tiếp. Chỉ số giá kèm URL nguồn và ngày đối soát mới được hiển thị.
        </div>

        <div class="fn-table-wrapper">
            <table class="fn-price-table">
                <thead>
                    <tr>
                        <th>Tên Dịch Vụ / Lỗi</th>
                        <th>Thiết Bị</th>
                        <th>Khoảng Giá Tham Khảo</th>
                        <th>Bảo Hành</th>
                        <th>Thời Gian Sửa</th>
                        <th>Ghi Chú Kỹ Thuật</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--fn-text-light); padding: 30px;">
                                Cửa hàng đang cập nhật danh mục dịch vụ. Vui lòng liên hệ trực tiếp để kiểm tra tình trạng máy và nhận báo giá.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $srv): $priceVerified = !empty($srv['source_url']) && !empty($srv['verified_at']); ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--fn-dark); font-size: 14.5px;">
                                        <?= htmlspecialchars($srv['service_name']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--fn-text-light); margin-top: 2px;">
                                        <?= htmlspecialchars($srv['description'] ?? $srv['service_desc'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: <?= $srv['device_type'] === 'laptop' ? '#e0f2fe; color:#0369a1;' : '#fce7f3; color:#be185d;' ?>">
                                        <?= $srv['device_type'] === 'laptop' ? '💻 Laptop' : '📱 Điện thoại' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($priceVerified && !empty($srv['min_price']) && !empty($srv['max_price'])): ?>
                                        <span class="fn-price-range"><?= formatPrice($srv['min_price']) ?> – <?= formatPrice($srv['max_price']) ?></span>
                                        <br><a href="<?= htmlspecialchars($srv['source_url']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px;">Nguồn · <?= htmlspecialchars($srv['verified_at']) ?></a>
                                    <?php else: ?>
                                        <span style="font-size:13px; color: var(--fn-primary); font-weight:700;">Chưa có giá đối soát</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; font-size: 13px; color: var(--fn-dark);">
                                        <?= $priceVerified ? htmlspecialchars($srv['warranty_text'] ?? $srv['warranty'] ?? 'Cần xác nhận') : 'Cần xác nhận' ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 13px; color: var(--fn-dark-muted);">
                                        <?= $priceVerified ? '⏱️ ' . htmlspecialchars($srv['turnaround_text'] ?? $srv['turnaround'] ?? 'Cần xác nhận') : 'Cần xác nhận' ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 12.5px; color: var(--fn-text-light);">
                                        <?= $priceVerified ? htmlspecialchars($srv['note'] ?? '') : 'Chưa đối soát linh kiện và quy trình' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Khối 2: ĐÁNH GIÁ & TRẢI NGHIỆM TỪ THÀNH VIÊN -->
    <div class="fn-shop-reviews-grid">
        <!-- Cột danh sách đánh giá -->
            <!-- Khối Đánh Giá Google Maps Tiêu Biểu -->
            <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 16px; padding: 22px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(234, 88, 12, 0.06);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 24px;">🌐</span>
                            <h3 style="font-size: 17px; font-weight: 800; color: var(--fn-dark); margin: 0;">
                                Kiểm Chứng Đánh Giá Trên Google Maps
                            </h3>
                        </div>
                        <div style="font-size: 13px; color: var(--fn-dark-muted); margin-top: 4px;">
                            Điểm Google thay đổi theo thời gian. FixNear chưa công bố lại con số trong bộ dữ liệu cho đến khi có Place ID và thời điểm đối soát; hãy mở Google Maps ở trên để xem trực tiếp.
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($shop['map_url']) ?>" target="_blank" class="fn-btn fn-btn-primary fn-btn-sm" style="font-size: 12.5px; padding: 8px 16px;">
                        Mở Google Maps Kiểm Chứng ↗
                    </a>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;font-size:13px;color:#475569;line-height:1.6;">
                    FixNear không sao chép hoặc tự dựng nhận xét Google. Hãy dùng nút “Mở Google Maps Kiểm Chứng” để đọc nội dung, ngày đăng và phản hồi của cửa hàng trực tiếp từ nguồn.
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 class="fn-section-title" style="font-size: 20px;">
                    Đánh Giá Từ Thành Viên FixNear (<?= count($reviews) ?>)
                </h2>
                <span style="font-size: 13px; color: var(--fn-text-light);">Phản hồi do tài khoản gửi; FixNear chưa xác minh giao dịch sửa chữa</span>
            </div>

            <?php if (empty($reviews)): ?>
                <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius); padding: 24px; text-align: center; color: var(--fn-text-light); font-size: 13.5px;">
                    Chưa có phản hồi từ thành viên FixNear. Bạn có thể là người đầu tiên đánh giá bên cạnh!
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="fn-review-card">
                        <div class="fn-review-header">
                            <div class="fn-reviewer-name">
                                <?= htmlspecialchars($rev['user_name']) ?>
                            </div>
                            <div class="fn-review-date">
                                <?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?>
                            </div>
                        </div>

                        <div class="fn-review-stars">
                            <?= str_repeat('⭐', (int)$rev['rating']) ?>
                            <span style="font-size: 12px; color: var(--fn-text-light); margin-left: 4px;">(<?= $rev['rating'] ?>/5)</span>
                        </div>

                        <?php if (!empty($rev['device_name'])): ?>
                            <div class="fn-review-device-tag">
                                🔧 Thiết bị sửa: <?= htmlspecialchars($rev['device_name']) ?>
                                <?php if (!empty($rev['service_repaired'])): ?>
                                    • <?= htmlspecialchars($rev['service_repaired']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <p class="fn-review-comment">
                            <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                        </p>

                        <?php if (!empty($rev['admin_reply'])): ?>
                            <!-- Phản hồi chính thức từ Ban Quản Trị FixNear -->
                            <div style="margin-top: 14px; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 8px; padding: 12px 14px; text-align: left;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; flex-wrap: wrap; gap: 6px;">
                                    <span style="font-weight: 800; font-size: 12.5px; color: #15803d; display: flex; align-items: center; gap: 6px;">
                                        <span>🛡️</span> Phản Hồi Từ Ban Quản Trị FixNear
                                    </span>
                                    <span style="font-size: 11px; color: #64748b;">
                                        <?= !empty($rev['admin_reply_at']) ? date('d/m/Y H:i', strtotime($rev['admin_reply_at'])) : '' ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: #1e293b; line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($rev['admin_reply'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                            <!-- Form phản hồi dành riêng cho Quản trị viên -->
                            <details style="margin-top: 10px;">
                                <summary style="font-size: 12px; color: #2563eb; cursor: pointer; font-weight: 700;">
                                    🛡️ <?= !empty($rev['admin_reply']) ? 'Chỉnh sửa phản hồi của BQT' : 'Trả lời nhận xét này (Admin)' ?>
                                </summary>
                                <form action="api/reply_review.php" method="POST" style="margin-top: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: left;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <input type="hidden" name="shop_id" value="<?= $shop['id'] ?>">
                                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Nội dung phản hồi chính thức:</label>
                                    <textarea name="reply_text" rows="2" class="fn-textarea" style="font-size: 12.5px; margin-bottom: 8px;" placeholder="Ghi nhận phản ánh và thông tin giải quyết từ ban điều hành..." required><?= htmlspecialchars($rev['admin_reply'] ?? '') ?></textarea>
                                    <button type="submit" class="fn-btn fn-btn-primary fn-btn-sm" style="font-size: 12px; padding: 6px 14px;">
                                        Đăng phản hồi công khai
                                    </button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Cột form gửi đánh giá -->
        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 24px; box-shadow: var(--fn-shadow-sm);">
            <h3 style="font-family: var(--fn-font-heading); font-size: 18px; font-weight: 800; color: var(--fn-dark); margin-bottom: 8px;">
                ✍️ Viết Đánh Giá Trải Nghiệm
            </h3>
            <p style="font-size: 13px; color: var(--fn-dark-muted); margin-bottom: 16px;">
                Đánh giá của bạn sẽ giúp cộng đồng người dùng đưa ra quyết định đúng đắn.
            </p>

            <?php if (isLoggedIn()): ?>
                <form action="api/add_review.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="shop_id" value="<?= $shop['id'] ?>">
                    
                    <div class="fn-form-group">
                        <label class="fn-label">Bạn chấm cửa hàng này mấy sao?</label>
                        <select name="rating" class="fn-select" required>
                            <option value="5">⭐⭐⭐⭐⭐ 5 Sao - Rất hài lòng</option>
                            <option value="4">⭐⭐⭐⭐ 4 Sao - Hài lòng, dịch vụ ổn</option>
                            <option value="3">⭐⭐⭐ 3 Sao - Bình thường</option>
                            <option value="2">⭐⭐ 2 Sao - Chưa hài lòng</option>
                            <option value="1">⭐ 1 Sao - Thất vọng</option>
                        </select>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Thiết bị bạn đã sửa:</label>
                        <input type="text" name="device_name" class="fn-input" placeholder="Ví dụ: Laptop Asus TUF, iPhone 11..." required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Lỗi hoặc dịch vụ thực hiện:</label>
                        <input type="text" name="service_repaired" class="fn-input" placeholder="Ví dụ: Thay pin, Vệ sinh máy, Ép kính..." required>
                    </div>

                    <div class="fn-form-group">
                        <label class="fn-label">Nhận xét chi tiết (Thái độ, thời gian, tính tiền...):</label>
                        <textarea name="comment" class="fn-textarea" rows="4" placeholder="Chia sẻ cảm nhận chân thật của bạn..." required></textarea>
                    </div>

                    <button type="submit" class="fn-btn fn-btn-primary" style="width: 100%;">
                        Gửi đánh giá công khai
                    </button>
                </form>
            <?php else: ?>
                <div style="background: var(--fn-bg); border: 1px dashed var(--fn-border); border-radius: var(--fn-radius); padding: 24px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 10px;">🔒</div>
                    <p style="font-size: 13.5px; color: var(--fn-dark); font-weight: 700; margin-bottom: 6px;">
                        Đăng nhập để viết đánh giá
                    </p>
                    <p style="font-size: 12.5px; color: var(--fn-dark-muted); margin-bottom: 16px;">
                        Vui lòng đăng nhập để gửi phản hồi. Việc đăng nhập không xác minh giao dịch sửa chữa.
                    </p>
                    <a href="login.php?redirect=<?= urlencode('shop_detail.php?id=' . $shop['id']) ?>" class="fn-btn fn-btn-primary fn-btn-sm" style="width: 100%;">
                        Đăng nhập ngay
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
