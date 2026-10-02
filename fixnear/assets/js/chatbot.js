/**
 * Trợ lý Chẩn đoán & Hướng dẫn Kỹ thuật FixNear (Client-side Rule Engine).
 * Hệ thống tự động phân tích triệu chứng hư hỏng thiết bị từ dữ liệu nhập của người dùng,
 * đưa ra cảnh báo an toàn khẩn cấp, mức độ nghiêm trọng, khoảng giá thị trường và liên kết trực tiếp
 * đến danh mục cửa hàng uy tín phù hợp nhất.
 */

function toggleFixnearAIChat() {
    const windowEl = document.getElementById('fn-ai-chat-window');
    const bubble = document.getElementById('fn-ai-chat-bubble');
    if (!windowEl) return;
    windowEl.classList.toggle('active');
    if (bubble) bubble.setAttribute('aria-expanded', windowEl.classList.contains('active') ? 'true' : 'false');
    if (windowEl.classList.contains('active')) {
        const input = document.getElementById('fn-ai-chat-input');
        if (input) setTimeout(() => input.focus(), 100);
        scrollGuideToBottom();
    }
}

function sendQuickPrompt(promptText) {
    const input = document.getElementById('fn-ai-chat-input');
    if (!input) return;
    input.value = promptText;
    handleAIChatSubmit();
}

function handleAIChatSubmit(event) {
    if (event && event.preventDefault) event.preventDefault();
    const input = document.getElementById('fn-ai-chat-input');
    if (!input) return;
    const message = input.value.trim();
    if (!message) return;
    input.value = '';
    appendGuideMessage(message, true);
    
    // Giả lập phản hồi phân tích sau 250ms cho trải nghiệm tự nhiên
    setTimeout(() => {
        appendGuideMessage(buildDiagnosticResponse(message), false, true);
    }, 250);
}

function appendGuideMessage(content, isUser, isHtml = false) {
    const container = document.getElementById('fn-ai-chat-messages');
    if (!container) return;
    const row = document.createElement('div');
    row.className = `fn-ai-msg ${isUser ? 'user' : 'bot'}`;
    if (!isUser) {
        const avatar = document.createElement('img');
        avatar.src = 'assets/images/ai_avatar.png';
        avatar.alt = '';
        avatar.width = 1024;
        avatar.height = 1024;
        avatar.className = 'fn-ai-msg-avatar';
        row.appendChild(avatar);
    }
    const body = document.createElement('div');
    body.className = 'fn-ai-msg-content';
    if (isHtml) body.innerHTML = content;
    else body.textContent = content;
    const time = document.createElement('div');
    time.className = 'fn-ai-msg-time';
    time.textContent = new Date().toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
    body.appendChild(time);
    row.appendChild(body);
    container.appendChild(row);
    scrollGuideToBottom();
}

/**
 * Chuẩn hóa chuỗi tiếng Việt để so khớp từ khóa không dấu
 */
function normalizeVi(str) {
    if (!str) return '';
    return str.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'd')
        .trim();
}

/**
 * Động cơ phân tích chẩn đoán dựa trên tập quy tắc chuyên gia (Rule-based Diagnostic Engine)
 */
function buildDiagnosticResponse(query) {
    const safeQuery = escapeHTML(query);
    const norm = normalizeVi(query);

    // 1. Triệu chứng: Máy vào nước / Rớt nước / Ẩm mạch
    if (norm.includes('nuoc') || norm.includes('rot nuoc') || norm.includes('vao nuoc') || norm.includes('vo nuoc') || norm.includes('ngam nuoc') || norm.includes('di mua') || norm.includes('am uot')) {
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    🔴 MỨC ĐỘ: TỐI KHẨN CẤP
                </span>
            </div>
            <strong>💧 Chẩn đoán: Sự cố chất lỏng xâm nhập bo mạch</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
                • <strong>Nguyên nhân:</strong> Nước gây chập đường nguồn VDD và oxy hóa ăn mòn chân IC, socket chỉ sau vài giờ.<br>
                • <strong>⚠️ SƠ CỨU AN TOÀN BẮT BUỘC:</strong><br>
                1. <strong>TẮT NGUỒN NGAY</strong> — Tuyệt đối không bấm nút mở màn hình kiểm tra.<br>
                2. <strong>KHÔNG CẮM SẠC</strong> (Cắm sạc khi còn ẩm sẽ làm cháy nổ CPU & IC nguồn).<br>
                3. <strong>KHÔNG DÙNG MÁY SẤY TÓC NÓNG</strong> (Áp lực gió thổi nước sâu vào kẽ màn hình).<br>
                4. <strong>KHÔNG BỎ THÙNG GẠO</strong> (Bụi cám gạo làm kẹt cổng sạc và loa).<br>
                • <strong>Giá sấy siêu âm & vệ sinh mạch:</strong> Khoảng <strong>150.000đ – 450.000đ</strong>.
            </div>
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <a href="search.php?service_id=5" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    🚑 Tìm Tiệm Cấp Cứu Vô Nước Gần Nhất
                </a>
                <a href="request_repair.php" style="display:inline-block;padding:6px 12px;background:#fff;color:#ea580c;border:1px solid #ea580c;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    📋 Yêu Cầu Hỗ Trợ
                </a>
            </div>
        `;
    }

    // 2. Triệu chứng: Pin chai / Pin phồng / Nóng máy
    if (norm.includes('pin') || norm.includes('chai pin') || norm.includes('phong pin') || norm.includes('pin phong') || norm.includes('nong may') || norm.includes('tut pin') || norm.includes('battery')) {
        const isSwollen = norm.includes('phong') || norm.includes('bung') || norm.includes('ho vien');
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:${isSwollen ? '#fee2e2' : '#fef3c7'};color:${isSwollen ? '#991b1b' : '#92400e'};border:1px solid ${isSwollen ? '#fca5a5' : '#fde68a'};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    ${isSwollen ? '🔴 NGUY HIỂM CHÁY NỔ (PIN PHỒNG)' : '🟡 MỨC ĐỘ: TRUNG BÌNH (CHAI PIN)'}
                </span>
            </div>
            <strong>🔋 Chẩn đoán: Suy giảm dung lượng hoặc phồng cell pin Lithium</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
                • <strong>Tình trạng:</strong> Chu kỳ sạc đã vượt quá ngưỡng an toàn (>500 lần) hoặc khí giải phóng làm phồng vỏ túi pin.<br>
                • <strong>⚠️ Lưu ý an toàn:</strong> ${isSwollen ? '<strong>NGỪNG CẮM SẠC NGAY LẬP TỨC.</strong> Không đè ép nắp lưng hay màn hình. Để máy nơi thông thoáng và thay pin trong ngày để tránh nứt vỡ màn hình!' : 'Hạn chế vừa sạc vừa sử dụng tác vụ nặng khiến nhiệt độ pin tăng cao.'}<br>
                • <strong>Khoảng giá tham khảo:</strong><br>
                - Điện thoại (iPhone, Android): <strong>250.000đ – 750.000đ</strong> (Pin dung lượng cao BH 12 tháng).<br>
                - Laptop (Dell, Asus, HP, MacBook): <strong>450.000đ – 1.450.000đ</strong> (Bảo hành 6 – 12 tháng).
            </div>
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <a href="search.php?service_id=3" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    🔋 Xem Tiệm Thay Pin Có Bảo Hành 12T
                </a>
                <a href="request_repair.php" style="display:inline-block;padding:6px 12px;background:#fff;color:#ea580c;border:1px solid #ea580c;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    📋 Báo Giá Theo Model
                </a>
            </div>
        `;
    }

    // 3. Triệu chứng: Màn hình sọc / Chảy mực / Ép kính / Liệt cảm ứng
    if (norm.includes('man hinh') || norm.includes('soc chi') || norm.includes('soc') || norm.includes('chay muc') || norm.includes('ep kinh') || norm.includes('kinh') || norm.includes('cam ung') || norm.includes('toi den') || norm.includes('am man')) {
        const askGlass = norm.includes('ep kinh') || norm.includes('soc chi co ep kinh') || norm.includes('co ep kinh duoc');
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    🟡 MỨC ĐỘ: TRUNG BÌNH — CẦN PHÂN BIỆT RÕ
                </span>
            </div>
            <strong>🖥️ Chẩn đoán: Lỗi Hiển Thị / Mặt Kính Cảm Ứng</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
                • <strong>${askGlass ? 'Giải đáp thắc mắc ép kính:' : 'Phân biệt thay màn hình và ép kính:'}</strong><br>
                - <strong>Màn hình bị sọc chỉ, chảy mực, tối đen:</strong> Bắt buộc phải <strong>thay nguyên bộ cụm màn hình</strong>. Không thể chỉ ép kính.<br>
                - <strong>Màn hình hiển thị còn đẹp, cảm ứng bình thường</strong>, chỉ nứt vỡ lớp kính ngoài: Có thể <strong>ép mặt kính</strong> để tiết kiệm 70% chi phí.<br>
                • <strong>Khoảng giá tham khảo:</strong><br>
                - Ép mặt kính: <strong>250.000đ – 550.000đ</strong>.<br>
                - Thay màn hình nguyên cụm (Tiêu chuẩn / OEM): <strong>650.000đ – 2.850.000đ</strong> (BH 6 tháng).
            </div>
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <a href="search.php?service_id=1" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    🖥️ Tiệm Thay Màn Hình
                </a>
                <a href="search.php?service_id=2" style="display:inline-block;padding:6px 12px;background:#fff;color:#ea580c;border:1px solid #ea580c;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    💎 Tiệm Ép Kính Uy Tín
                </a>
            </div>
        `;
    }

    // 4. Triệu chứng: Sập nguồn / Mất nguồn / Mở không lên / Treo logo
    if (norm.includes('sap nguon') || norm.includes('mat nguon') || norm.includes('khong len') || norm.includes('ko len') || norm.includes('mo khong len') || norm.includes('treo logo') || norm.includes('chet main') || norm.includes('khoi dong') || norm.includes('tat ngom')) {
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    🔴 MỨC ĐỘ: CAO — NGUY CƠ LỖI BO MẠCH
                </span>
            </div>
            <strong>⚡ Chẩn đoán: Mất nguồn do chập Mainboard hoặc hỏng IC nguồn</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
                • <strong>Nguyên nhân khả dĩ:</strong> Chết IC nguồn (PMIC), chập tụ đường áp cấp sau, quá nhiệt làm hở chân chip hoặc pin tụt áp kiệt không thể kích nguồn.<br>
                • <strong>⚠️ Lưu ý an toàn:</strong> Không cắm sạc liên tục qua đêm hoặc cố bấm giữ nút nguồn nhiều lần vì có thể làm chập lan sang CPU.<br>
                • <strong>Giá sửa chữa tham khảo:</strong> <strong>350.000đ – 1.200.000đ</strong> tùy dòng máy (sửa chip IC mainboard thay vì thay cả cụm bo mạch đắt đỏ).
            </div>
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <a href="search.php?service_id=6" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    🔧 Tìm Tiệm Sửa Chữa Mainboard
                </a>
                <a href="request_repair.php" style="display:inline-block;padding:6px 12px;background:#fff;color:#ea580c;border:1px solid #ea580c;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    📋 Gửi Yêu Cầu Chẩn Đoán
                </a>
            </div>
        `;
    }

    // 5. Triệu chứng: Chân sạc / Không vào pin / Sạc chập chờn
    if (norm.includes('chan sac') || norm.includes('cong sac') || norm.includes('khong vao pin') || norm.includes('khong nhan sac') || norm.includes('long sac') || norm.includes('type c') || norm.includes('lightning') || norm.includes('sac')) {
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    🟡 MỨC ĐỘ: TRUNG BÌNH
                </span>
            </div>
            <strong>⚡ Chẩn đoán: Hỏng Cổng Sạc / Tiếp Xúc Chân Sạc</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
                • <strong>Nguyên nhân thường gặp:</strong> Bụi xơ vải nhồi chặt vào đáy cổng sạc, gãy chân kim tiếp xúc hoặc cháy chập mạch cụm cáp sạc.<br>
                • <strong>⚠️ Lưu ý:</strong> Dùng đèn soi cổng sạc và dùng tăm tre gẩy nhẹ bụi. Tuyệt đối không dùng kim kim loại chọc vào kẻo gây đoản mạch chập chân VBUS!<br>
                • <strong>Giá tham khảo:</strong> Vệ sinh miễn phí / Thay cụm cáp sạc: <strong>150.000đ – 450.000đ</strong>.
            </div>
            <div style="margin-top:10px;">
                <a href="search.php?service_id=4" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    ⚡ Tiệm Sửa Chân Sạc Lấy Liền
                </a>
            </div>
        `;
    }

    // 6. Kinh nghiệm tránh luộc đồ / Tráo linh kiện
    if (norm.includes('luoc') || norm.includes('trao linh kien') || norm.includes('luoc do') || norm.includes('bi luoc') || norm.includes('an toan') || norm.includes('kinh nghiem')) {
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:#dcfce7;color:#166534;border:1px solid #86efac;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    🛡️ CẨM NANG BẢO VỆ QUYỀN LỢI SINH VIÊN
                </span>
            </div>
            <strong>🛡️ 5 Quy Tắc Vàng Tránh Bị Tráo Linh Kiện (Luộc Đồ):</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.6;">
                1. <strong>Ký tên trực tiếp:</strong> Ký lên màn hình, pin, camera, mainboard, ổ cứng trước khi để máy lại tiệm.<br>
                2. <strong>Biên nhận rõ ràng:</strong> Giữ phiếu tiếp nhận có ghi số IMEI/Serial, cấu hình và tình trạng ngoại quan.<br>
                3. <strong>Hỏi giá trọn gói:</strong> Xác nhận giá đã gồm công thợ và bảo hành hay chưa.<br>
                4. <strong>Ưu tiên ngồi xem sửa trực tiếp:</strong> Các lỗi pin, màn hình, chân sạc nên ngồi xem lấy sau 30-45 phút.<br>
                5. <strong>Thu hồi đồ cũ:</strong> Yêu cầu thợ trả lại linh kiện cũ đã thay thế.
            </div>
            <div style="margin-top:10px;">
                <a href="shops.php" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    🏪 Xem Danh Sách Tiệm Đã Xác Minh Minh Bạch
                </a>
            </div>
        `;
    }

    // 7. Tìm tiệm gần tôi / Quận huyện
    if (norm.includes('gan toi') || norm.includes('dia chi') || norm.includes('o dau') || norm.includes('quan 10') || norm.includes('quan 12') || norm.includes('quan') || norm.includes('tim tiem') || norm.includes('gan nhat')) {
        return `
            <div style="margin-bottom:8px;">
                <span style="display:inline-block;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                    📍 BẢN ĐỒ ĐỊNH VỊ KHU VỰC
                </span>
            </div>
            <strong>📍 Mạng Lưới Sửa Chữa FixNear Quanh Các Trường Đại Học:</strong><br>
            <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
                • Hệ thống hiện có hơn <strong>20 cửa hàng xác minh</strong> tại Quận 12 (Công viên Phần mềm Quang Trung / Tô Ký), Quận 10 (Lý Thường Kiệt), Gò Vấp (Quang Trung), Bình Thạnh, Thủ Đức...<br>
                • Bấm nút bên dưới để xem khoảng cách km và lọc tiệm theo quận của bạn.
            </div>
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <a href="shops.php?district=Quận 12" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    📍 Tiệm Quận 12 (Quanh FPT Poly)
                </a>
                <a href="shops.php?district=Quận 10" style="display:inline-block;padding:6px 12px;background:#fff;color:#ea580c;border:1px solid #ea580c;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    📍 Tiệm Quận 10
                </a>
                <a href="shops.php" style="display:inline-block;padding:6px 12px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                    🗺️ Xem Toàn Bộ Bản Đồ
                </a>
            </div>
        `;
    }

    // 8. Trường hợp mặc định: Phân tích tổng quát & Hướng dẫn
    const encodedQuery = encodeURIComponent(query.slice(0, 100));
    return `
        <div style="margin-bottom:8px;">
            <span style="display:inline-block;background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:800;">
                💡 TƯ VẤN & HƯỚNG DẪN KỸ THUẬT
            </span>
        </div>
        <strong>Đã ghi nhận yêu cầu:</strong> “<em>${safeQuery}</em>”<br>
        <div style="margin:6px 0;font-size:12.5px;color:#334155;line-height:1.5;">
            Để nhận được tư vấn và báo giá chính xác nhất từ các cửa hàng uy tín, bạn nên:<br>
            1. Mô tả cụ thể dòng máy (ví dụ: iPhone 13, Laptop Dell Inspiron, ThinkPad...).<br>
            2. Nêu rõ dấu hiệu (máy có nóng không, có phát ra âm thanh lạ không).<br>
            3. Luôn sao lưu dữ liệu quan trọng trước khi đem sửa.
        </div>
        <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
            <a href="search.php?keyword=${encodedQuery}" style="display:inline-block;padding:6px 12px;background:#ea580c;color:#fff;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                🔍 Tìm Kiếm Cửa Hàng Phù Hợp
            </a>
            <a href="request_repair.php" style="display:inline-block;padding:6px 12px;background:#fff;color:#ea580c;border:1px solid #ea580c;font-size:12px;font-weight:700;border-radius:6px;text-decoration:none;">
                📋 Gửi Yêu Cầu Báo Giá Nhanh
            </a>
        </div>
    `;
}

function scrollGuideToBottom() {
    const container = document.getElementById('fn-ai-chat-messages');
    if (container) container.scrollTop = container.scrollHeight;
}

function escapeHTML(value) {
    return String(value).replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[char]));
}
