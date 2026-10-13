(() => {
    const status = document.getElementById('fn-admin-live-status');
    if (!status) return;
    const bell = document.getElementById('fn-admin-notif-btn');
    const badge = document.getElementById('fn-admin-notif-count');
    const panel = document.getElementById('fn-admin-notif-panel');
    if (bell && panel) bell.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        bell.setAttribute('aria-expanded', String(!panel.hidden));
    });
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
            const counts = [data.pending_requests, data.pending_reviews, data.pending_reports, data.pending_contacts].map(value => Number(value) || 0);
            const summary = `Chờ xử lý: ${counts[0]} yêu cầu · ${counts[1]} đánh giá · ${counts[2]} báo cáo · ${counts[3]} liên hệ`;
            status.textContent = summary;
            const total = counts.reduce((sum, value) => sum + value, 0);
            if (badge) {
                badge.hidden = total === 0;
                badge.style.display = total === 0 ? 'none' : 'flex';
                badge.textContent = total > 99 ? '99+' : String(total);
            }
            if (panel) {
                panel.replaceChildren();
                const heading = document.createElement('strong');
                heading.textContent = total ? `${total} việc cần xử lý` : 'Không có việc cần xử lý';
                panel.append(heading);
                [['Yêu cầu sửa chữa', 'requests.php'], ['Đánh giá', 'reviews.php'], ['Báo cáo sai sót', 'reports.php'], ['Liên hệ', 'contacts.php']].forEach(([label, href], index) => {
                    if (!counts[index]) return;
                    const link = document.createElement('a');
                    link.href = href;
                    link.textContent = `${label}: ${counts[index]}`;
                    link.style.cssText = 'display:block;padding:8px 0;color:#c2410c;text-decoration:none';
                    panel.append(link);
                });
            }
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
