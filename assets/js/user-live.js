(() => {
    const badge = document.getElementById('fn-user-live-badge');
    if (!badge) return;
    const key = `fixnear-user-requests-${badge.dataset.userId}`;
    const poll = async () => {
        try {
            const response = await fetch(`${badge.dataset.prefix}api/user_live.php`, {credentials: 'same-origin', cache: 'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            const previous = JSON.parse(localStorage.getItem(key) || '{}');
            const current = {};
            for (const request of data.requests || []) current[request.id] = request.status;
            const changed = Object.keys(current).filter(id => previous[id] && previous[id] !== current[id]).length;
            if (changed) {
                badge.textContent = `${changed} yêu cầu cập nhật`;
                badge.hidden = false;
            }
            localStorage.setItem(key, JSON.stringify(current));
        } catch { /* Tạm mất kết nối: thử lại vào lần kiểm tra kế tiếp. */ }
    };
    poll();
    setInterval(() => { if (!document.hidden) poll(); }, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
