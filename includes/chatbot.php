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
        <div style="display:flex;align-items:center;gap:6px;">
            <button type="button" class="fn-ai-chat-clear-btn" onclick="clearFixnearChatHistory()" title="Bắt đầu hội thoại mới (xóa lịch sử chat)" aria-label="Bắt đầu hội thoại mới" style="background:transparent;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:4px 6px;border-radius:4px;transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">🔄</button>
            <button type="button" class="fn-ai-chat-close-btn" onclick="toggleFixnearAIChat()" title="Thu nhỏ hướng dẫn" aria-label="Đóng trợ lý hướng dẫn">&times;</button>
        </div>
    </div>

    <!-- Khu vực tin nhắn -->
    <div class="fn-ai-chat-body" id="fn-ai-chat-messages">
        <div class="fn-ai-msg bot">
            <img src="<?= $assetPrefix ?>assets/images/ai_avatar.png" alt="" width="1024" height="1024" class="fn-ai-msg-avatar">
            <div class="fn-ai-msg-content">
                Xin chào bạn! 👋 Mình là Trợ lý Kỹ thuật FixNear. Bạn đang gặp vấn đề gì với điện thoại hay máy tính, hãy nhắn cho mình (kèm tên máy hoặc khu vực nếu có) để mình hỗ trợ chẩn đoán và báo giá tham khảo ngay nhé!
                <div class="fn-ai-msg-time">Vừa xong</div>
            </div>
        </div>
    </div>

    <!-- Gợi ý câu hỏi nhanh (Quick Chips) -->
    <div class="fn-ai-quick-chips">
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('iPhone 13 bị bể màn hình thay hết bao nhiêu?')">
            📱 iPhone 13 bể màn
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Thay pin Laptop Dell Inspiron ở Quận 10')">
            🔋 Thay pin Dell Inspiron Q.10
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Máy vô nước mở không lên cấp cứu thế nào?')">
            💧 Cấp cứu máy vô nước
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Làm sao để tránh bị tráo linh kiện (luộc đồ)?')">
            🛡️ Tránh luộc đồ
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Tôi muốn tra cứu tiến độ yêu cầu sửa chữa')">
            📋 Tra cứu yêu cầu sửa
        </button>
        <button type="button" class="fn-ai-chip" onclick="sendQuickPrompt('Tôi muốn khiếu nại chất lượng của cửa hàng sửa chữa')">
            ✉️ Khiếu nại cửa hàng
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
