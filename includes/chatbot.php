<!-- Trợ lý hướng dẫn chạy cục bộ, không phải công cụ chẩn đoán -->
<button type="button" id="fn-ai-chat-bubble" onclick="toggleFixnearAIChat()" title="Mở hướng dẫn FixNear" aria-label="Mở trợ lý hướng dẫn FixNear" aria-controls="fn-ai-chat-window" aria-expanded="false">
    <div class="fn-ai-bubble-badge">Hướng dẫn</div>
    <img src="<?= $assetPrefix ?>assets/images/ai_avatar.png" alt="" width="1024" height="1024">
    <div class="fn-ai-pulse-ring"></div>
</button>

<div id="fn-ai-chat-window" role="dialog" aria-label="Trợ lý hướng dẫn FixNear">
    <div class="fn-ai-chat-header">
        <div class="fn-ai-chat-header-info">
            <img src="<?= $assetPrefix ?>assets/images/ai_avatar.png" alt="" width="1024" height="1024" class="fn-ai-chat-header-avatar">
            <div>
                <div class="fn-ai-chat-header-title">
                    Trợ lý Kỹ Thuật FixNear <span>Chẩn đoán & Hướng dẫn</span>
                </div>
                <div class="fn-ai-chat-header-sub">
                    Chẩn đoán pan bệnh & cảnh báo an toàn tức thì
                </div>
            </div>
        </div>
        <button type="button" class="fn-ai-chat-close-btn" onclick="toggleFixnearAIChat()" title="Thu nhỏ hướng dẫn" aria-label="Đóng trợ lý hướng dẫn">&times;</button>
    </div>

    <!-- Banner giới thiệu 4 năng lực cốt lõi -->
    <div class="fn-ai-capabilities-card">
        🩺 <strong>Trợ lý chẩn đoán & tư vấn kỹ thuật:</strong><br>
        • Nhận diện triệu chứng pan bệnh (sập nguồn, sọc màn, chai pin, vô nước...).<br>
        • Cảnh báo an toàn và hướng dẫn sơ cứu thiết bị khẩn cấp.<br>
        • Ước tính khoảng giá thị trường chuẩn sinh viên và liên kết tiệm uy tín.<br>
        • Khuyến nghị kiểm tra trực tiếp tại tiệm trước khi chốt phương án sửa.
    </div>

    <!-- Khu vực tin nhắn -->
    <div class="fn-ai-chat-body" id="fn-ai-chat-messages">
        <div class="fn-ai-msg bot">
            <img src="<?= $assetPrefix ?>assets/images/ai_avatar.png" alt="" width="1024" height="1024" class="fn-ai-msg-avatar">
            <div class="fn-ai-msg-content">
                Xin chào! 👋 Hãy mô tả thiết bị và triệu chứng. Trợ lý sẽ giúp bạn tìm bản ghi phù hợp và nhắc những thông tin cần hỏi cửa hàng trước khi sửa.
                <div class="fn-ai-msg-time">Vừa xong</div>
            </div>
        </div>
    </div>

    <!-- Gợi ý câu hỏi nhanh (Quick Chips) -->
    <div class="fn-ai-quick-chips">
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('iPhone 13 Pro Max bị sọc màn hình thay hết bao nhiêu?')">
            📱 iPhone 13 Pro Max sọc màn
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Thay pin Laptop Dell Inspiron ở Quận 10')">
            🔋 Thay pin Dell Inspiron Q.10
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Máy vô nước mở không lên cấp cứu thế nào?')">
            💧 Cấp cứu máy vô nước
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Máy sập nguồn mở không lên phải làm sao?')">
            ⚡ Máy sập nguồn
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Làm sao để tránh bị tráo linh kiện (luộc đồ)?')">
            🛡️ Tránh luộc đồ
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Tìm tiệm sửa chữa uy tín gần Quận 12')">
            📍 Tiệm uy tín Quận 12
        </button>
    </div>

    <!-- Khung nhập câu hỏi -->
    <form class="fn-ai-chat-input-bar" id="fn-ai-chat-form" onsubmit="handleAIChatSubmit(event)">
        <input type="text" id="fn-ai-chat-input" class="fn-ai-chat-input" placeholder="Mô tả thiết bị và triệu chứng..." aria-label="Mô tả thiết bị và triệu chứng" autocomplete="off" maxlength="500">
        <button type="submit" class="fn-ai-chat-send-btn" title="Gửi nội dung" aria-label="Gửi nội dung">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </button>
    </form>
</div>
