(() => {
    const status = document.getElementById('fn-admin-live-status');
    if (!status) return;
    let previousRevision = '';
    let hasUnseenChanges = false;
    let requestInFlight = false;
    const poll = async () => {
        if (requestInFlight) return;
        requestInFlight = true;
        try {
            const response = await fetch('../api/admin_live.php', {credentials: 'same-origin', cache: 'no-store'});
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            const changed = previousRevision !== '' && previousRevision !== data.revision;
            previousRevision = data.revision;
            if (changed) hasUnseenChanges = true;
            const summary = `Chờ xử lý: ${data.pending_requests} yêu cầu · ${data.pending_reviews} đánh giá`;
            status.textContent = summary;
            if (hasUnseenChanges) {
                const reload = document.createElement('button');
                reload.type = 'button';
                reload.textContent = 'Có cập nhật mới · Tải lại';
                reload.addEventListener('click', () => location.reload());
                status.append(' · ', reload);
            }
        } catch {
            status.textContent = 'Chưa kết nối được dữ liệu quản trị';
        } finally {
            requestInFlight = false;
        }
    };
    poll();
    setInterval(() => { if (!document.hidden) poll(); }, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
