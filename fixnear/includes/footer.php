</div><!-- /#fn-main-content -->
<?php $footerStats = db()->getStats(); ?>

<!-- Modal Báo cáo thông tin sai -->
<div class="fn-modal-overlay" id="fn-report-modal">
    <div class="fn-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-family: var(--fn-font-heading); font-size: 18px; font-weight: 800; color: var(--fn-dark);">
                🚩 Báo Cáo Thông Tin Sai
            </h3>
            <button type="button" class="fn-close-modal" style="background:none; border:none; font-size:20px; cursor:pointer; color:#9ca3af;">&times;</button>
        </div>
        <p style="font-size: 13px; color: var(--fn-dark-muted); margin-bottom: 16px;">
            Cửa hàng: <strong id="fn-report-shop-name" style="color: var(--fn-primary);">...</strong>
        </p>

        <form action="<?= $assetPrefix ?>api/add_report.php" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="shop_id" id="fn-report-shop-id">
            <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">

            <div class="fn-form-group">
                <label class="fn-label">Lý do báo cáo:</label>
                <select name="reason" class="fn-select" required>
                    <option value="Sai khoảng giá thực tế">Sai khoảng giá thực tế</option>
                    <option value="Sai địa chỉ / Đã chuyển địa điểm">Sai địa chỉ / Đã chuyển địa điểm</option>
                    <option value="Sai giờ mở cửa / Ngày làm việc">Sai giờ mở cửa / Ngày làm việc</option>
                    <option value="Số điện thoại không liên lạc được">Số điện thoại không liên lạc được</option>
                    <option value="Cửa hàng đã ngừng hoạt động">Cửa hàng đã ngừng hoạt động</option>
                    <option value="Khác">Lý do khác</option>
                </select>
            </div>

            <div class="fn-form-group">
                <label class="fn-label">Chi tiết phản ánh (giúp nhóm cập nhật chính xác):</label>
                <textarea name="details" class="fn-textarea" rows="3" placeholder="Ví dụ: Giá ép kính thực tế ở tiệm là 350k, tiệm đóng cửa chủ nhật..." required></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="fn-btn fn-btn-secondary fn-close-modal">Hủy bỏ</button>
                <button type="submit" class="fn-btn fn-btn-primary">Gửi báo cáo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Banner Khuyến Mãi 3s (Promo Popup) -->
<div class="fn-modal-overlay" id="fn-promo-modal" style="z-index: 2500;">
    <div class="fn-modal-card" style="max-width: 620px; width: 100%; padding: 0; overflow: hidden; border-radius: 16px; border: 2px solid #ea580c; box-shadow: 0 25px 60px rgba(0,0,0,0.4); position: relative; background: #0f172a;">
        <!-- Nút đóng nhanh căn giữa hoàn hảo -->
        <button type="button" class="fn-close-modal" style="position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.7); color: #fff; border: 2px solid rgba(255,255,255,0.4); padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; transition: background-color 0.2s, transform 0.2s;" title="Đóng banner" aria-label="Đóng banner">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: block; margin: auto;">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        
        <!-- Ảnh Banner sắc nét -->
        <div style="position: relative; width: 100%; height: 290px; overflow: hidden; background: #0b1329;">
            <img src="<?= $assetPrefix ?>assets/images/promo_banner.jpg" alt="Ưu Đãi FixNear" width="620" height="290" style="width: 100%; height: 100%; object-fit: cover; display: block;">
            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 80px; background: linear-gradient(to top, #0f172a, transparent);"></div>
        </div>

        <!-- Nội dung khuyến mãi -->
        <div style="padding: 20px 24px 24px; color: #fff; text-align: center;">
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(234, 88, 12, 0.2); border: 1px solid #ea580c; color: #fb923c; font-size: 11.5px; font-weight: 800; padding: 4px 12px; border-radius: 20px; margin-bottom: 8px; text-transform: uppercase;">
                🔥 Chiến Dịch Đồng Hành Sửa Chữa
            </div>
            <h3 style="font-family: var(--fn-font-heading); font-size: 21px; font-weight: 900; color: #fff; margin-bottom: 8px; line-height: 1.3;">
                KIỂM TRA ƯU ĐÃI HỌC SINH – SINH VIÊN TRƯỚC KHI ĐẾN
            </h3>
            <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5; margin-bottom: 20px;">
                FixNear chưa xác nhận ưu đãi riêng cho từng chi nhánh trong <?= (int)$footerStats['total_shops'] ?> bản ghi thuộc <?= (int)$footerStats['total_districts'] ?> khu vực. Hãy xem nguồn chính thức và gọi cửa hàng để xác nhận điều kiện áp dụng.
            </p>

            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="request_repair.php" class="fn-btn fn-btn-primary" style="padding: 12px 24px; font-size: 14.5px; font-weight: 800; border-radius: 10px; text-decoration: none; flex: 1; min-width: 200px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    ⚡ GỬI THIẾT BỊ TÌM THỢ NGAY
                </a>
                <a href="shops.php" class="fn-btn fn-btn-secondary" style="padding: 12px 20px; font-size: 14px; font-weight: 700; border-radius: 10px; text-decoration: none; background: #1e293b; color: #f8fafc; border: 1px solid #475569; flex: 1; min-width: 180px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    ⭐ Xem <?= (int)$footerStats['total_shops'] ?> Cửa Hàng (<?= (int)$footerStats['total_districts'] ?> Khu Vực)
                </a>
            </div>
            <div style="margin-top: 12px; text-align: center;">
                <button type="button" onclick="dismissPromoToday()" style="background: none; border: none; color: #94a3b8; font-size: 12px; cursor: pointer; text-decoration: underline; padding: 6px 12px;">Không hiển thị hôm nay</button>
            </div>
        </div>
    </div>
</div>
<script>
function dismissPromoToday() {
    const today = new Date().toISOString().slice(0, 10);
    localStorage.setItem('fixnear_promo_dismissed_date', today);
    const modal = document.getElementById('fn-promo-modal');
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}
</script>

<!-- Modal Chọn Vị Trí / Khu Vực Của Bạn -->
<div class="fn-modal-overlay" id="fn-location-modal">
    <div class="fn-modal-card" style="max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 28px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
            <div style="flex: 1; text-align: center;">
                <div class="fn-gps-radar-wrap">
                    <div class="fn-gps-radar-ring"></div>
                    <div class="fn-gps-radar-ring delay"></div>
                    <div class="fn-gps-radar-center">📍</div>
                </div>
                <h3 style="font-family: var(--fn-font-heading); font-size: 20px; font-weight: 900; color: var(--fn-dark);">
                    Chọn Khu Vực / Định Vị Cửa Hàng
                </h3>
                <p style="font-size: 13px; color: var(--fn-dark-muted); margin-top: 6px; line-height: 1.5; max-width: 480px; margin-left: auto; margin-right: auto;">
                    Bấm bật GPS để FixNear tự động tính khoảng cách km hoặc bấm chọn nhanh 1 khu vực bạn đang ở bên dưới.
                </p>
            </div>
            <button type="button" class="fn-close-modal" style="background:none; border:none; font-size:24px; cursor:pointer; color:#9ca3af; line-height: 1;" title="Đóng">&times;</button>
        </div>

        <!-- Nút GPS thiết bị tự động -->
        <div style="margin: 16px 0 20px;">
            <button type="button" class="fn-btn fn-btn-primary" id="fn-main-gps-btn" onclick="triggerDeviceGPS(this)" style="width: 100%; padding: 14px 20px; font-size: 15px; font-weight: 800; border-radius: 12px; box-shadow: 0 6px 18px rgba(234, 88, 12, 0.4); display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span style="font-size: 18px;">🛰️</span> BẬT ĐỊNH VỊ TỰ ĐỘNG (GPS THIẾT BỊ)
            </button>
            <div style="font-size: 11.5px; color: var(--fn-text-light); text-align: center; margin-top: 6px;">
                Trình duyệt sẽ hỏi quyền truy cập vị trí, hãy nhấn <strong>"Cho phép" (Allow)</strong>
            </div>
        </div>

        <!-- Phân cách chọn quận thủ công -->
        <div style="display: flex; align-items: center; gap: 10px; margin: 18px 0 12px;">
            <div style="flex: 1; height: 1px; background: var(--fn-border);"></div>
            <span style="font-size: 11px; font-weight: 800; color: var(--fn-text-light); text-transform: uppercase; letter-spacing: 0.5px;">
                HOẶC CHỌN NHANH KHU VỰC BẠN ĐANG Ở
            </span>
            <div style="flex: 1; height: 1px; background: var(--fn-border);"></div>
        </div>

        <!-- Danh sách chọn quận nhanh 17 Quận Nội Thành TP.HCM -->
        <div style="max-height: 260px; overflow-y: auto; padding-right: 4px;">
            <div class="fn-district-group-title">🏙️ TP.HCM — Khu Vực Trung Tâm</div>
            <div class="fn-district-quick-grid">
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7769, 106.7009, 'Quận 1')">Quận 1</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7844, 106.6844, 'Quận 3')">Quận 3</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7716, 106.6675, 'Quận 10')">Quận 10</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7554, 106.6669, 'Quận 5')">Quận 5</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7992, 106.6803, 'Quận Phú Nhuận')">Phú Nhuận</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7578, 106.7013, 'Quận 4')">Quận 4</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7473, 106.6352, 'Quận 6')">Quận 6</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7241, 106.6286, 'Quận 8')">Quận 8</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7629, 106.6504, 'Quận 11')">Quận 11</button>
            </div>

            <div class="fn-district-group-title" style="margin-top: 14px;">🏡 TP.HCM — Khu Vực Phía Bắc & Tây</div>
            <div class="fn-district-quick-grid">
                <button type="button" class="fn-district-pill-btn" style="border-color: #ea580c; color: #ea580c; background: #fff7ed;" onclick="selectCustomLocation(10.8530, 106.6275, 'Quận 12 (Tô Ký)')">★ Quận 12</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.8387, 106.6653, 'Quận Gò Vấp')">Quận Gò Vấp</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.8015, 106.6558, 'Quận Tân Bình')">Quận Tân Bình</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7901, 106.6283, 'Quận Tân Phú')">Quận Tân Phú</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7654, 106.6039, 'Quận Bình Tân')">Quận Bình Tân</button>
            </div>

            <div class="fn-district-group-title" style="margin-top: 14px;">🌴 TP.HCM — Khu Vực Phía Đông & Nam</div>
            <div class="fn-district-quick-grid">
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.8494, 106.7715, 'TP. Thủ Đức')">TP. Thủ Đức</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.8106, 106.6983, 'Quận Bình Thạnh')">Bình Thạnh</button>
                <button type="button" class="fn-district-pill-btn" onclick="selectCustomLocation(10.7340, 106.7218, 'Quận 7')">Quận 7</button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--fn-border);">
            <button type="button" class="fn-btn fn-btn-secondary fn-close-modal" style="padding: 8px 24px; font-size: 13px; font-weight: 700; border-radius: 8px; cursor: pointer;">
                ✕ Đóng lại & Tiếp tục duyệt web
            </button>
        </div>
    </div>
</div>

<!-- Modal Minh Bạch 3 Không & Sàng Lọc FixNear -->
<div class="fn-modal-overlay" id="fn-verification-modal">
    <div class="fn-modal-card" style="max-width: 620px; max-height: 90vh; overflow-y: auto; padding: 30px 26px; text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: #ecfdf5; border: 1.5px solid #a7f3d0; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    🛡️
                </div>
                <div>
                    <h3 style="font-family: var(--fn-font-heading); font-size: 19px; font-weight: 900; color: var(--fn-dark); margin: 0; text-align: left;">
                        Tiêu Chuẩn "3 Không" & Sàng Lọc
                    </h3>
                    <span style="font-size: 12.5px; color: #059669; font-weight: 700;">Danh sách câu hỏi an toàn người dùng nên xác nhận</span>
                </div>
            </div>
            <button type="button" onclick="fnCloseVerificationModal()" style="background:none; border:none; font-size:26px; cursor:pointer; color:#9ca3af; line-height: 1; padding: 4px;" title="Đóng">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;">
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 16px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span style="background: #ea580c; color: #fff; font-size: 11px; font-weight: 900; padding: 2px 8px; border-radius: 12px;">TIÊU CHUẨN 1</span>
                    <strong style="font-size: 14.5px; color: #0f172a;">Không Tráo Đổi Linh Kiện</strong>
                </div>
                <p style="font-size: 13px; color: #475569; line-height: 1.6; margin: 0; text-align: left;">
                    Trước khi giao máy, hãy hỏi cửa hàng có cho quan sát hoặc ký tên lên linh kiện hay không và ghi nhận tình trạng máy bằng ảnh.
                </p>
            </div>

            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 16px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span style="background: #2563eb; color: #fff; font-size: 11px; font-weight: 900; padding: 2px 8px; border-radius: 12px;">TIÊU CHUẨN 2</span>
                    <strong style="font-size: 14.5px; color: #0f172a;">Không Vẽ Thêm Bệnh — Không Khống Giá</strong>
                </div>
                <p style="font-size: 13px; color: #475569; line-height: 1.6; margin: 0; text-align: left;">
                    Yêu cầu báo giá chi tiết linh kiện, công thợ và mọi khoản phí trước khi sửa; chỉ xác nhận sau khi bạn hiểu và đồng ý.
                </p>
            </div>

            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 16px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span style="background: #16a34a; color: #fff; font-size: 11px; font-weight: 900; padding: 2px 8px; border-radius: 12px;">TIÊU CHUẨN 3</span>
                    <strong style="font-size: 14.5px; color: #0f172a;">Không Đùn Đẩy Trách Nhiệm Bảo Hành</strong>
                </div>
                <p style="font-size: 13px; color: #475569; line-height: 1.6; margin: 0; text-align: left;">
                    Yêu cầu phiếu bảo hành ghi rõ thời hạn, phạm vi, điều kiện từ chối và phương án xử lý khi linh kiện lỗi.
                </p>
            </div>
        </div>

        <div style="background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 14px; padding: 16px; margin-bottom: 20px; text-align: left;">
            <h4 style="font-size: 13.5px; font-weight: 800; color: #c2410c; margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px; text-align: left;">
                🔍 FixNear Sàng Lọc & Xác Minh Như Thế Nào?
            </h4>
            <p style="font-size: 12.5px; color: #9a3412; line-height: 1.5; margin: 0; text-align: left;">
                Bộ dữ liệu hiện lưu thông tin dịch vụ và liên hệ để người dùng đối chiếu. Chỉ nội dung có nguồn và ngày đối soát mới được coi là đã xác minh; hãy kiểm tra lại giờ mở cửa, giá và chính sách bảo hành trực tiếp với cửa hàng.
            </p>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" onclick="fnCloseVerificationModal()" class="fn-btn fn-btn-secondary" style="padding: 10px 18px; font-size: 13px; cursor: pointer;">
                Đóng
            </button>
            <a href="shops.php" class="fn-btn fn-btn-primary" style="padding: 10px 20px; font-size: 13px; text-decoration: none; font-weight: 800;">
                Xem <?= (int)$footerStats['total_shops'] ?> Bản Ghi Cửa Hàng &rarr;
            </a>
        </div>
    </div>
</div>

<footer class="fn-footer" style="text-align: left; background: #0f172a; color: #cbd5e1; padding: 60px 0 30px; border-top: 1px solid #1e293b;">
    <div class="fn-container" style="max-width: 1240px; margin: 0 auto; padding: 0 20px; text-align: left;">
        <div style="display: grid; grid-template-columns: 1.4fr 1fr 1fr 1.6fr; gap: 36px; text-align: left;">
            <!-- Cột 1: Thông tin thương hiệu FixNear -->
            <div style="text-align: left;">
                <div class="fn-logo" style="color: #fff; margin-bottom: 14px; text-align: left; display: inline-flex; align-items: center; gap: 10px;">
                    <img src="<?= $assetPrefix ?>assets/images/fixnear_logo_icon.svg" alt="FixNear" width="32" height="32" style="flex-shrink:0; display:block;">
                    <div class="fn-logo-text" style="font-size: 24px; font-weight: 900; letter-spacing: -0.5px;">
                        <span style="color:#fff;">Fix</span><span style="color:#ea580c;">Near</span>
                    </div>
                </div>
                <p style="font-size: 13.5px; line-height: 1.6; color: #94a3b8; margin: 0 0 16px 0; text-align: left;">
                    Nền tảng thử nghiệm giúp tra cứu <?= (int)$footerStats['total_shops'] ?> bản ghi cửa hàng và nhóm dịch vụ sửa chữa tại TP.HCM. Người dùng cần xác nhận thông tin trực tiếp trước khi sử dụng dịch vụ.
                </p>
                <div style="font-size: 13px; line-height: 1.7; color: #cbd5e1; display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <div>📍 <strong>Địa chỉ vận hành:</strong> Chưa công bố</div>
                    <div>📞 <strong>Hotline / Zalo:</strong> Chưa cấu hình</div>
                    <div>✉️ <strong>Email:</strong> Chưa cấu hình</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">🎓 <em>Phiên bản thử nghiệm phục vụ mục đích học tập; không hàm ý tổ chức nào bảo trợ dữ liệu.</em></div>
                </div>
            </div>

            <!-- Cột 2: Dịch Vụ Sửa Chữa -->
            <div style="text-align: left;">
                <h4 style="font-size: 16px; font-weight: 900; color: #fff; margin: 0 0 16px 0; text-align: left; text-transform: uppercase; letter-spacing: 0.5px;">
                    Dịch Vụ Sửa Chữa
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; font-size: 13.5px; text-align: left;">
                    <li><a href="request_repair.php?device=phone" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">📱 Sửa Điện Thoại (iPhone, Samsung...)</a></li>
                    <li><a href="request_repair.php?device=laptop_win" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">💻 Sửa Laptop Windows (Dell, Asus, HP...)</a></li>
                    <li><a href="request_repair.php?device=macbook" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">🍏 Sửa MacBook & iMac Chuyên Sâu</a></li>
                    <li><a href="request_repair.php?device=tablet" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">📟 Sửa Máy Tính Bảng (iPad, Tab)</a></li>
                    <li><a href="request_repair.php?device=pc" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">🖥️ Sửa Máy Tính Bàn PC / Máy Gaming</a></li>
                    <li><a href="request_repair.php?device=smartwatch" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">⌚ Sửa Đồng Hồ Apple Watch / Smartwatch</a></li>
                    <li><a href="request_repair.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">💾 Cứu Dữ Liệu Ổ Cứng Hỏng</a></li>
                    <li><a href="request_repair.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">❄️ Vệ Sinh & Tra Keo Tản Nhiệt</a></li>
                </ul>
            </div>

            <!-- Cột 3: Cam Kết & Hỗ Trợ -->
            <div style="text-align: left;">
                <h4 style="font-size: 16px; font-weight: 900; color: #fff; margin: 0 0 16px 0; text-align: left; text-transform: uppercase; letter-spacing: 0.5px;">
                    Hướng Dẫn & Liên Hệ
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; font-size: 13.5px; text-align: left;">
                    <li><a href="javascript:void(0)" onclick="fnOpenVerificationModal()" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">🛡️ Bộ Câu Hỏi An Toàn "3 Không"</a></li>
                    <li><a href="javascript:void(0)" onclick="fnOpenVerificationModal()" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">🔍 Cách Đối Chiếu Dữ Liệu</a></li>
                    <li><a href="request_repair.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">⚡ Gửi Yêu Cầu Báo Giá Nhanh</a></li>
                    <li><a href="shops.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">⭐ Danh Sách <?= (int)$footerStats['total_shops'] ?> Cửa Hàng</a></li>
                    <li><a href="track_request.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">🔎 Theo Dõi Yêu Cầu</a></li>
                    <li><a href="contact.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">📞 Liên Hệ Ban Điều Hành Dự Án</a></li>
                    <li><a href="contact.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">⚠️ Góp Ý & Khiếu Nại Chất Lượng</a></li>
                    <li><a href="contact.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#cbd5e1'">🤝 Góp Ý Thông Tin Cửa Hàng</a></li>
                </ul>
            </div>

            <!-- Cột 4: các khu vực đang có dữ liệu -->
            <div style="text-align: left;">
                <h4 style="font-size: 16px; font-weight: 900; color: #fff; margin: 0 0 16px 0; text-align: left; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?= (int)$footerStats['total_districts'] ?> Khu Vực Có Dữ Liệu (A – Z)
                </h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px 14px; font-size: 12.5px; text-align: left;">
                    <a href="shops.php?district=Quận 1" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 1</a>
                    <a href="shops.php?district=Quận 3" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 3</a>
                    <a href="shops.php?district=Quận 4" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 4</a>
                    <a href="shops.php?district=Quận 5" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 5</a>
                    <a href="shops.php?district=Quận 6" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 6</a>
                    <a href="shops.php?district=Quận 7" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 7</a>
                    <a href="shops.php?district=Quận 8" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 8</a>
                    <a href="shops.php?district=Quận 10" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 10</a>
                    <a href="shops.php?district=Quận 11" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 11</a>
                    <a href="shops.php?district=Quận 12" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Quận 12</a>
                    <a href="shops.php?district=Quận Bình Tân" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Bình Tân</a>
                    <a href="shops.php?district=Quận Bình Thạnh" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Bình Thạnh</a>
                    <a href="shops.php?district=Quận Gò Vấp" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Gò Vấp</a>
                    <a href="shops.php?district=Quận Phú Nhuận" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Phú Nhuận</a>
                    <a href="shops.php?district=Quận Tân Bình" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Tân Bình</a>
                    <a href="shops.php?district=Quận Tân Phú" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• Tân Phú</a>
                    <a href="shops.php?district=TP. Thủ Đức" style="color: #94a3b8; text-decoration: none; grid-column: span 2;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">• TP. Thủ Đức (Q.2, Q.9, Thủ Đức)</a>
                </div>
                <div style="margin-top: 14px; font-size: 12px; color: #64748b; line-height: 1.5;">
                    💡 Bấm vào từng khu vực để lọc nhanh các bản ghi cửa hàng đang có trong dữ liệu.
                </div>
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 13px; color: #64748b; text-align: left;">
            <div>
                &copy; <?= date('Y') ?> <strong>FixNear</strong> — Dự án thử nghiệm tra cứu thông tin sửa chữa thiết bị tại TP.HCM.
            </div>
            <div style="font-size: 12px; color: #475569;">
                Phiên bản học tập/demo — dữ liệu chưa xác minh toàn bộ
            </div>
        </div>
    </div>
</footer>

<?php if ($assetPrefix !== ''): ?>
<script>
// Footer/modals are shared by public and /admin pages. Normalize their public
// links when rendered one directory deeper.
document.querySelectorAll('footer a[href], #fn-promo-modal a[href], #fn-verification-modal a[href]').forEach((link) => {
    const href = link.getAttribute('href') || '';
    if (href && !/^(?:[a-z][a-z0-9+.-]*:|\/|#|\.\.\/)/i.test(href)) {
        link.setAttribute('href', <?= json_encode($assetPrefix) ?> + href);
    }
});
</script>
<?php endif; ?>

<!-- Tích hợp Trợ lý Kỹ thuật AI Chatbot -->
<?php require_once __DIR__ . '/chatbot.php'; ?>

<!-- Leaflet JS for Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Project Scripts -->
<script src="<?= $assetPrefix ?>assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
<script src="<?= $assetPrefix ?>assets/js/map.js?v=<?= filemtime(__DIR__ . '/../assets/js/map.js') ?>"></script>
<script src="<?= $assetPrefix ?>assets/js/chatbot.js?v=<?= filemtime(__DIR__ . '/../assets/js/chatbot.js') ?>"></script>
<script>
function fnOpenVerificationModal() {
    const modal = document.getElementById('fn-verification-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}
function fnCloseVerificationModal() {
    const modal = document.getElementById('fn-verification-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}
// Đóng khi click ngoài overlay
document.addEventListener('click', function(e) {
    const modal = document.getElementById('fn-verification-modal');
    if (modal && e.target === modal) {
        fnCloseVerificationModal();
    }
});

// ================= HAMBURGER MOBILE MENU =================
function fnToggleMobileMenu() {
    const navLinks = document.getElementById('fn-nav-links');
    const hamburger = document.getElementById('fn-hamburger-btn');
    const backdrop = document.getElementById('fn-mobile-backdrop');
    
    if (!navLinks) return;
    
    const isOpen = navLinks.classList.contains('active');
    
    if (isOpen) {
        // Đóng menu
        navLinks.classList.remove('active');
        if (hamburger) hamburger.classList.remove('active');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    } else {
        // Mở menu
        navLinks.classList.add('active');
        if (hamburger) hamburger.classList.add('active');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
        if (backdrop) backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Đóng mobile menu khi nhấn ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const navLinks = document.getElementById('fn-nav-links');
        if (navLinks && navLinks.classList.contains('active')) {
            fnToggleMobileMenu();
        }
    }
});

// Tự động đóng mobile menu khi resize lên desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 900) {
        const navLinks = document.getElementById('fn-nav-links');
        const hamburger = document.getElementById('fn-hamburger-btn');
        const backdrop = document.getElementById('fn-mobile-backdrop');
        if (navLinks) navLinks.classList.remove('active');
        if (hamburger) hamburger.classList.remove('active');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }
});
</script>
</body>
</html>
