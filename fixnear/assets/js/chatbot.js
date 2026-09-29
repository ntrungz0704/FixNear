/**
 * Trợ lý hướng dẫn FixNear chạy hoàn toàn ở trình duyệt.
 * Không tự nhận là AI, không chẩn đoán và không phát hành giá chưa có nguồn.
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
    appendGuideMessage(buildGuideResponse(message), false, true);
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

function buildGuideResponse(query) {
    const safeQuery = escapeHTML(query);
    const encodedQuery = encodeURIComponent(query.slice(0, 120));
    return `
        🔎 <strong>Đã nhận nội dung:</strong> “${safeQuery}”<br><br>
        FixNear chưa có đủ nguồn để chẩn đoán kỹ thuật hoặc đưa ra giá, thời gian và bảo hành chính xác. Nếu pin phồng/nóng bất thường hoặc máy vừa vào nước, hãy tắt nguồn và ngừng sạc; sau đó yêu cầu kỹ thuật viên kiểm tra trực tiếp.<br><br>
        <a href="search.php?keyword=${encodedQuery}" style="color:#ea580c;font-weight:700;text-decoration:underline;">Tìm bản ghi cửa hàng phù hợp</a><br>
        <small>Hãy xác nhận báo giá, linh kiện, thời gian và bảo hành bằng văn bản trước khi sửa.</small>
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
