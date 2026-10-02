<?php
$pageTitle = "Liên Hệ & Hỗ Trợ Dự Án — FixNear";
require_once __DIR__ . '/config/db.php';
$sentSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    requireValidCsrf();
    if (!enforceRateLimit('contact', 5, 3600)) {
        http_response_code(429);
        $contactError = 'Bạn đã gửi quá nhiều tin nhắn. Vui lòng thử lại sau.';
    }
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $type = trim($_POST['type'] ?? 'Khách hàng góp ý');
    $message = trim($_POST['message'] ?? '');

    $phoneDigits = preg_replace('/\D+/', '', $phone);
    $allowedTypes = [
        'Khách hàng khiếu nại / Góp ý chất lượng tiệm sửa',
        'Cửa hàng / Thợ kỹ thuật muốn đăng ký tham gia mạng lưới',
        'Đóng góp ý kiến cải tiến tính năng website',
        'Hợp tác truyền thông / Nghiên cứu học thuật',
        'Khác'
    ];
    if (($contactError ?? '') === '' && ($name === '' || mb_strlen($name) > 100 || strlen($phoneDigits) < 9 || strlen($phoneDigits) > 12 || mb_strlen($message) < 10 || mb_strlen($message) > 3000 || !in_array($type, $allowedTypes, true))) {
        $contactError = 'Vui lòng kiểm tra họ tên, số điện thoại và nội dung (10–3.000 ký tự).';
    } elseif (($contactError ?? '') === '' && $email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)) {
        $contactError = 'Email liên hệ chưa đúng định dạng.';
    }

    if (($contactError ?? '') === '') {
        $subject = $type;
        $saved = db()->addContactMessage(compact('name', 'phone', 'email', 'subject', 'type', 'message'));
        $sentSuccess = $saved !== false;
        if (!$sentSuccess) $contactError = 'Không thể lưu tin nhắn lúc này. Vui lòng thử lại.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="fn-container" style="padding: 24px 20px 80px;">
    <!-- Nút Trở Lại Trang Chủ -->
    <div style="margin-bottom: 24px;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #1e293b; font-size: 14px; font-weight: 800; text-decoration: none; padding: 9px 16px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; transition: color 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.2s, opacity 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <span style="font-size: 18px; color: #ea580c; font-weight: 900; line-height: 1;">←</span>
            <span>Trở lại Trang Chủ</span>
        </a>
    </div>
    <!-- Tiêu đề trang -->
    <div style="text-align: center; max-width: 760px; margin: 0 auto 36px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; font-size: 12.5px; font-weight: 800; padding: 5px 16px; border-radius: 20px; margin-bottom: 12px; text-transform: uppercase;">
            📞 LIÊN HỆ & ĐỒNG HÀNH CÙNG DỰ ÁN
        </div>
        <h1 style="font-family: var(--fn-font-heading); font-size: 32px; font-weight: 900; color: var(--fn-dark); line-height: 1.25;">
            Kết Nối Với Ban Điều Hành <span style="color: #ea580c;">Dự Án FixNear</span>
        </h1>
        <p style="font-size: 15px; color: var(--fn-dark-muted); margin-top: 10px; line-height: 1.6;">
            Đây là kênh góp ý cho dự án tra cứu FixNear. FixNear không phải bên cung cấp dịch vụ sửa chữa và chưa đại diện giải quyết khiếu nại thay cho cửa hàng.
        </p>
    </div>

    <?php if ($sentSuccess): ?>
        <div style="background: #dcfce7; border: 2px solid #86efac; border-radius: var(--fn-radius); padding: 20px; color: #166534; font-size: 15px; margin-bottom: 30px; text-align: center;">
            🎉 <strong>Cảm ơn bạn!</strong> Tin nhắn đã được gửi thành công đến ban điều hành FixNear. Chúng tôi sẽ phản hồi qua Số điện thoại / Zalo của bạn trong thời gian sớm nhất!
        </div>
    <?php endif; ?>
    <?php if (!empty($contactError)): ?>
        <div role="alert" style="background:#fee2e2;border-left:4px solid #ef4444;padding:14px 16px;border-radius:6px;color:#991b1b;margin-bottom:24px;">
            <?= htmlspecialchars($contactError) ?>
        </div>
    <?php endif; ?>

    <div class="fn-contact-grid">
        <!-- Thông tin liên hệ -->
        <div style="text-align: left;">
            <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; border-radius: var(--fn-radius-lg); padding: 32px; margin-bottom: 24px; box-shadow: var(--fn-shadow); text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; text-align: left;">
                    <div style="width: 46px; height: 46px; border-radius: 10px; background: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                        🔧
                    </div>
                    <div style="text-align: left;">
                        <h3 style="font-family: var(--fn-font-heading); font-size: 20px; font-weight: 900; color: #fff; margin: 0; text-align: left;">
                            Dự Án FixNear
                        </h3>
                        <span style="font-size: 12px; color: #cbd5e1; text-align: left; display: block;">Dự án tra cứu và so sánh thông tin sửa chữa</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px; line-height: 1.6; text-align: left;">
                    <div style="display: flex; gap: 12px; text-align: left;">
                        <span style="font-size: 18px; flex-shrink: 0;">📍</span>
                        <div style="text-align: left;">
                            <strong style="color: #fff;">Địa chỉ vận hành:</strong><br>
                            <span style="color: #cbd5e1;">Chưa công bố — cần cập nhật thông tin pháp lý trước khi triển khai chính thức.</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; text-align: left;">
                        <span style="font-size: 18px; flex-shrink: 0;">📞</span>
                        <div style="text-align: left;">
                            <strong style="color: #fff;">Hotline / Zalo hỗ trợ:</strong><br>
                            <span style="color: #cbd5e1;">Chưa cấu hình</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; text-align: left;">
                        <span style="font-size: 18px; flex-shrink: 0;">✉️</span>
                        <div style="text-align: left;">
                            <strong style="color: #fff;">Email chính thức:</strong><br>
                            <span style="color: #cbd5e1;">Chưa cấu hình</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; text-align: left;">
                        <span style="font-size: 18px; flex-shrink: 0;">🎓</span>
                        <div style="text-align: left;">
                            <strong style="color: #fff;">Bối cảnh dự án:</strong><br>
                            <span style="color: #cbd5e1;">Sản phẩm học tập; không hàm ý nhà trường bảo trợ hoặc xác nhận dữ liệu.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Câu hỏi thường gặp -->
            <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 24px; text-align: left;">
                <h4 style="font-family: var(--fn-font-heading); font-size: 16px; font-weight: 900; color: var(--fn-dark); margin-bottom: 12px; text-align: left;">
                    ❓ Câu Hỏi Thường Gặp
                </h4>
                <div style="font-size: 13px; color: var(--fn-dark-muted); display: flex; flex-direction: column; gap: 12px; line-height: 1.5; text-align: left;">
                    <div style="text-align: left;">
                        <strong style="color: var(--fn-dark);">1. Giá trên FixNear có bị phụ thu thêm không?</strong><br>
                        FixNear không tự đưa ra báo giá. Khi mang máy đến, hãy yêu cầu kỹ thuật viên kiểm tra và xác nhận giá trọn gói trước khi sửa.
                    </div>
                    <div style="text-align: left;">
                        <strong style="color: var(--fn-dark);">2. Tiệm sửa có thực sự cho ngồi xem trực tiếp?</strong><br>
                        FixNear chỉ hiển thị nhãn này khi có URL nguồn và ngày đối soát. Nếu chưa có, bạn cần xác nhận trực tiếp với cửa hàng trước khi đến.
                    </div>
                </div>
            </div>
        </div>

        <!-- Form gửi tin nhắn liên hệ -->
        <div style="background: var(--fn-surface); border: 1px solid var(--fn-border); border-radius: var(--fn-radius-lg); padding: 32px; box-shadow: var(--fn-shadow); text-align: left;">
            <h2 style="font-family: var(--fn-font-heading); font-size: 20px; font-weight: 900; color: var(--fn-dark); margin-bottom: 20px; text-align: left;">
                ✉️ Gửi Tin Nhắn / Góp Ý Đến Dự Án
            </h2>

            <form action="contact.php" method="POST" style="text-align: left;">
                <?= csrfField() ?>
                <input type="hidden" name="send_contact" value="1">

                <div class="fn-form-group" style="text-align: left;">
                    <label class="fn-label" for="contact-type" style="text-align: left; display: block;">Mục đích liên hệ: <span style="color:#ef4444;">*</span></label>
                    <select id="contact-type" name="type" class="fn-select" required style="text-align: left;">
                        <option value="Khách hàng khiếu nại / Góp ý chất lượng tiệm sửa">Khách hàng khiếu nại / Góp ý chất lượng tiệm sửa</option>
                        <option value="Cửa hàng / Thợ kỹ thuật muốn đăng ký tham gia mạng lưới">Cửa hàng / Thợ kỹ thuật muốn đăng ký vào FixNear</option>
                        <option value="Đóng góp ý kiến cải tiến tính năng website">Đóng góp ý kiến cải tiến tính năng website</option>
                        <option value="Hợp tác truyền thông / Nghiên cứu học thuật">Hợp tác truyền thông / Nghiên cứu học thuật</option>
                        <option value="Khác">Lý do khác</option>
                    </select>
                </div>

                <div class="fn-form-row" style="margin-top: 12px; text-align: left;">
                    <div class="fn-form-group" style="text-align: left;">
                        <label class="fn-label" for="contact-name" style="text-align: left; display: block;">Họ và tên của bạn: <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="contact-name" name="name" class="fn-input" placeholder="Nguyễn Văn B" autocomplete="name" maxlength="100" required style="text-align: left;">
                    </div>

                    <div class="fn-form-group" style="text-align: left;">
                        <label class="fn-label" for="contact-phone" style="text-align: left; display: block;">Số điện thoại / Zalo: <span style="color:#ef4444;">*</span></label>
                        <input type="tel" id="contact-phone" name="phone" class="fn-input" placeholder="0912 345 678" autocomplete="tel" inputmode="tel" maxlength="20" required style="text-align: left;">
                    </div>
                </div>

                <div class="fn-form-group" style="margin-top: 12px; text-align: left;">
                    <label class="fn-label" for="contact-email" style="text-align: left; display: block;">Email liên hệ (nếu có):</label>
                    <input type="email" id="contact-email" name="email" class="fn-input" placeholder="email@example.com" autocomplete="email" spellcheck="false" maxlength="150" style="text-align: left;">
                </div>

                <div class="fn-form-group" style="margin-top: 12px; text-align: left;">
                    <label class="fn-label" for="contact-message" style="text-align: left; display: block;">Nội dung chi tiết: <span style="color:#ef4444;">*</span></label>
                    <textarea id="contact-message" name="message" class="fn-textarea" rows="4" placeholder="Nhập chi tiết nội dung bạn muốn phản ánh hoặc trao đổi với nhóm dự án FixNear..." minlength="10" maxlength="3000" required style="text-align: left;"></textarea>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="fn-btn fn-btn-primary" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 800; border-radius: 10px; text-align: center;">
                        📤 GỬI TIN NHẮN ĐẾN BAN ĐIỀU HÀNH FIXNEAR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
