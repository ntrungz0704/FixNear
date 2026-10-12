<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/contacts.php");
    exit;
}

// XỬ LÝ ACTIONS (POST CÓ CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireValidCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // 1. Phản hồi thư liên hệ
    if ($action === 'reply_contact' && $id > 0) {
        $replyText = trim($_POST['reply_text'] ?? '');
        if (!empty($replyText)) {
            db()->replyContactMessage($id, $replyText);
            header("Location: contacts.php?detail=" . $id . "&msg=replied");
            exit;
        }
    }
    // 2. Cập nhật trạng thái
    elseif ($action === 'update_status' && $id > 0) {
        $st = in_array($_POST['status'] ?? '', ['pending', 'read', 'replied'], true) ? $_POST['status'] : 'pending';
        db()->updateContactMessageStatus($id, $st);
        header("Location: contacts.php?detail=" . $id . "&msg=updated");
        exit;
    }
    // 3. Xóa thư liên hệ
    elseif ($action === 'delete' && $id > 0) {
        db()->deleteContactMessage($id);
        header("Location: contacts.php?msg=deleted");
        exit;
    }
}

// Lấy danh sách toàn bộ thư liên hệ
$allContacts = db()->getContactMessages();

// Helpers phân loại và giao diện
function fnGetContactInitials($name) {
    $name = trim($name ?? '');
    if (empty($name)) return 'KH';
    $words = preg_split('/\s+/u', $name);
    if (count($words) >= 2) {
        $first = mb_substr($words[count($words) - 2], 0, 1, 'UTF-8');
        $second = mb_substr($words[count($words) - 1], 0, 1, 'UTF-8');
        return mb_strtoupper($first . $second, 'UTF-8');
    }
    return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
}

function fnFormatContactCode($id, $createdAt) {
    $datePart = date('Ymd', strtotime($createdAt ?: 'now'));
    return '#CT-' . $datePart . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

function fnGetSubjectMeta($subject) {
    $s = mb_strtolower($subject ?? '', 'UTF-8');
    if (strpos($s, 'khiếu nại') !== false || strpos($s, 'góp ý chất lượng') !== false) {
        return [
            'label' => 'Khiếu nại chất lượng tiệm',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
            'border' => '#fecaca',
            'icon' => '🔴'
        ];
    }
    if (strpos($s, 'cửa hàng') !== false || strpos($s, 'tham gia') !== false || strpos($s, 'mạng lưới') !== false) {
        return [
            'label' => 'Đăng ký tham gia mạng lưới',
            'color' => '#0284c7',
            'bg' => '#e0f2fe',
            'border' => '#bae6fd',
            'icon' => '🔵'
        ];
    }
    if (strpos($s, 'cải tiến') !== false || strpos($s, 'tính năng') !== false) {
        return [
            'label' => 'Góp ý tính năng website',
            'color' => '#16a34a',
            'bg' => '#dcfce7',
            'border' => '#bbf7d0',
            'icon' => '🟢'
        ];
    }
    if (strpos($s, 'hợp tác') !== false || strpos($s, 'học thuật') !== false || strpos($s, 'truyền thông') !== false) {
        return [
            'label' => 'Hợp tác truyền thông / Nghiên cứu',
            'color' => '#9333ea',
            'bg' => '#f3e8ff',
            'border' => '#e9d5ff',
            'icon' => '🟣'
        ];
    }
    return [
        'label' => $subject ?: 'Chưa phân loại',
        'color' => '#475569',
        'bg' => '#f1f5f9',
        'border' => '#e2e8f0',
        'icon' => '⚪'
    ];
}

// THỐNG KÊ TOÀN BỘ
$totalPending = 0;
$totalRead = 0;
$totalReplied = 0;

foreach ($allContacts as $c) {
    $st = $c['status'] ?? 'pending';
    if ($st === 'replied') {
        $totalReplied++;
    } elseif ($st === 'read') {
        $totalRead++;
    } else {
        $totalPending++;
    }
}
$totalAll = count($allContacts);

// BỘ LỌC VÀ TÌM KIẾM
$filterSubject = trim($_GET['subject'] ?? 'all');
$filterStatus = trim($_GET['status'] ?? 'all');
$searchKeyword = trim($_GET['q'] ?? '');

$filteredContacts = array_filter($allContacts, function($c) use ($filterSubject, $filterStatus, $searchKeyword) {
    // 1. Lọc theo trạng thái
    $st = $c['status'] ?? 'pending';
    if ($filterStatus !== 'all' && $st !== $filterStatus) return false;

    // 2. Lọc theo phân loại chủ đề
    if ($filterSubject !== 'all') {
        $subj = $c['subject'] ?? ($c['type'] ?? '');
        $meta = fnGetSubjectMeta($subj);
        if ($meta['label'] !== $filterSubject) return false;
    }

    // 3. Tìm kiếm từ khóa
    if ($searchKeyword !== '') {
        $q = mb_strtolower($searchKeyword, 'UTF-8');
        $idStr = (string)($c['id'] ?? '');
        $code = mb_strtolower(fnFormatContactCode($c['id'], $c['created_at'] ?? ''), 'UTF-8');
        $name = mb_strtolower($c['name'] ?? '', 'UTF-8');
        $phone = mb_strtolower($c['phone'] ?? '', 'UTF-8');
        $email = mb_strtolower($c['email'] ?? '', 'UTF-8');
        $subj = mb_strtolower($c['subject'] ?? ($c['type'] ?? ''), 'UTF-8');
        $msg = mb_strtolower($c['message'] ?? '', 'UTF-8');
        $reply = mb_strtolower($c['admin_reply'] ?? '', 'UTF-8');

        if (
            strpos($idStr, $q) === false &&
            strpos($code, $q) === false &&
            strpos($name, $q) === false &&
            strpos($phone, $q) === false &&
            strpos($email, $q) === false &&
            strpos($subj, $q) === false &&
            strpos($msg, $q) === false &&
            strpos($reply, $q) === false
        ) {
            return false;
        }
    }

    return true;
});

$filteredContacts = array_values($filteredContacts);
$totalFiltered = count($filteredContacts);

// PHÂN TRANG (PAGINATION) - 8 thư mỗi trang
$perPage = 8;
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['p'] ?? 1)));
$offset = ($page - 1) * $perPage;
$paginatedContacts = array_slice($filteredContacts, $offset, $perPage);

// XÁC ĐỊNH THƯ ĐANG ĐƯỢC CHỌN (DETAIL PANEL)
$detailId = (int)($_GET['detail'] ?? 0);
$currentContact = null;

if ($detailId > 0) {
    foreach ($allContacts as $c) {
        if ((int)$c['id'] === $detailId) {
            $currentContact = $c;
            break;
        }
    }
}

// Nếu không chỉ định hoặc không tìm thấy, chọn thư đầu tiên trong danh sách hiển thị
if (!$currentContact && !empty($paginatedContacts)) {
    $currentContact = $paginatedContacts[0];
}

$pageTitle = "Quản Lý Hộp Thư Liên Hệ & Hợp Tác — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
/* ================= CSS GIAO DIỆN HỘP THƯ LIÊN HỆ FIXNEAR ADMIN ================= */
.fn-ct-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 22px;
    flex-wrap: wrap;
    gap: 16px;
}
.fn-ct-header h1 {
    font-family: var(--fn-font-heading);
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 6px 0;
}
.fn-ct-header p {
    font-size: 13.5px;
    color: #64748b;
    margin: 0;
}

/* THẺ THỐNG KÊ (STATS CARDS - 3 CỘT) */
.fn-ct-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
.fn-ct-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}
.fn-ct-stat-card:hover {
    transform: translateY(-2px);
    border-color: #ea580c;
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.08);
}
.fn-ct-stat-card.active {
    border-color: #ea580c;
    background: #fffaf7;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.1);
}
.fn-ct-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.fn-ct-stat-icon.orange { background: #ffedd5; color: #ea580c; }
.fn-ct-stat-icon.blue   { background: #e0f2fe; color: #0284c7; }
.fn-ct-stat-icon.green  { background: #dcfce7; color: #16a34a; }
.fn-ct-stat-info {
    display: flex;
    flex-direction: column;
}
.fn-ct-stat-title {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 2px;
}
.fn-ct-stat-count {
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.1;
}
.fn-ct-stat-desc {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
}

/* THANH BỘ LỌC & TÌM KIẾM (TOOLBAR) */
.fn-ct-toolbar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.fn-ct-select {
    padding: 8px 12px;
    font-size: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #1e293b;
    font-weight: 600;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s;
    min-width: 170px;
}
.fn-ct-select:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}
.fn-ct-search-box {
    flex: 1;
    min-width: 200px;
    position: relative;
    display: flex;
    align-items: center;
}
.fn-ct-search-box input {
    width: 100%;
    padding: 8px 14px 8px 36px;
    font-size: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
    transition: all 0.15s;
}
.fn-ct-search-box input:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}
.fn-ct-search-icon {
    position: absolute;
    left: 12px;
    color: #94a3b8;
    pointer-events: none;
    font-size: 14px;
}

/* BỐ CỤC 2 CỘT MASTER-DETAIL */
.fn-ct-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 450px;
    gap: 20px;
    align-items: start;
}

/* MASTER TABLE CARD (CỘT TRÁI) */
.fn-ct-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.fn-ct-table-wrap {
    overflow-x: auto;
    width: 100%;
}
.fn-ct-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
.fn-ct-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.fn-ct-table td {
    padding: 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #1e293b;
    transition: background-color 0.15s;
}
.fn-ct-table tbody tr {
    cursor: pointer;
    transition: all 0.15s;
}
.fn-ct-table tbody tr:hover td {
    background: #f8fafc;
}
.fn-ct-table tbody tr.active-row td {
    background: #fff7ed;
    color: #0f172a;
}
.fn-ct-table tbody tr.active-row td:first-child {
    border-left: 3px solid #ea580c;
    font-weight: 900;
    color: #ea580c;
}

/* AVATAR KHÁCH HÀNG */
.fn-ct-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 12px;
    flex-shrink: 0;
    border: 1px solid #cbd5e1;
}

/* BADGES TRẠNG THÁI */
.fn-badge-st {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    text-align: center;
    white-space: nowrap;
}
.fn-badge-st.pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.fn-badge-st.read    { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.fn-badge-st.replied { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

/* PHÂN TRANG (PAGINATION) */
.fn-ct-pagination {
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    border-top: 1px solid #f1f5f9;
    font-size: 12.5px;
    color: #64748b;
    background: #ffffff;
}
.fn-ct-page-btns {
    display: flex;
    gap: 4px;
}
.fn-ct-page-btn {
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    color: #334155;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.15s;
    padding: 0 6px;
}
.fn-ct-page-btn:hover {
    border-color: #ea580c;
    color: #ea580c;
}
.fn-ct-page-btn.active {
    background: #ea580c;
    border-color: #ea580c;
    color: #ffffff;
}

/* DETAIL PANEL (CỘT PHẢI) */
.fn-ct-detail-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    position: sticky;
    top: 85px;
}
.fn-ct-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
}
.fn-ct-detail-code {
    font-family: monospace;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
}
.fn-ct-detail-title {
    font-size: 17px;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 16px 0;
    letter-spacing: -0.01em;
}

/* THẺ THÔNG TIN NGƯỜI GỬI */
.fn-ct-sender-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 18px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.fn-ct-sender-info {
    flex: 1;
}
.fn-ct-sender-name {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 4px;
}
.fn-ct-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.fn-ct-action-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 10px;
    border-radius: 6px;
    text-decoration: none;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #1e293b;
    transition: all 0.15s;
}
.fn-ct-action-pill:hover {
    border-color: #ea580c;
    color: #ea580c;
    background: #fffaf7;
}

/* KHỐI NỘI DUNG THƯ */
.fn-ct-msg-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.fn-ct-msg-title {
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
}
.fn-ct-msg-text {
    font-size: 13.5px;
    line-height: 1.6;
    color: #0f172a;
    white-space: pre-wrap;
    word-break: break-word;
}

/* KHỐI PHẢN HỒI CỦA ADMIN */
.fn-ct-reply-box {
    margin-bottom: 20px;
}
.fn-ct-reply-done {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 14px;
}
.fn-ct-reply-done-title {
    font-size: 12px;
    font-weight: 800;
    color: #166534;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
}
.fn-ct-reply-done-text {
    font-size: 13px;
    color: #14532d;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
}

/* FORM SOẠN PHẢN HỒI */
.fn-ct-reply-form textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12.5px;
    color: #1e293b;
    resize: vertical;
    outline: none;
    background: #ffffff;
    font-family: inherit;
    transition: border-color 0.15s;
}
.fn-ct-reply-form textarea:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
}

/* NÚT THAO TÁC */
.fn-ct-btn-main {
    width: 100%;
    padding: 11px 16px;
    background: #ea580c;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-weight: 800;
    font-size: 13.5px;
    cursor: pointer;
    transition: background 0.15s;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.fn-ct-btn-main:hover {
    background: #c2410c;
}
.fn-ct-actions-row {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}
.fn-ct-btn-sec {
    flex: 1;
    padding: 8px 12px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
}
.fn-ct-btn-sec:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}
.fn-ct-btn-danger {
    color: #dc2626;
    border-color: #fecaca;
}
.fn-ct-btn-danger:hover {
    background: #fef2f2;
    border-color: #dc2626;
}

/* TOAST / FLASH ALERTS */
.fn-ct-alert {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.fn-ct-alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.fn-ct-alert.warning { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .fn-ct-grid {
        grid-template-columns: 1fr;
    }
    .fn-ct-detail-card {
        position: static;
        margin-top: 10px;
    }
    .fn-ct-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content">

        <!-- HEADER TRANG -->
        <div class="fn-ct-header">
            <div>
                <h1>Hộp Thư Liên Hệ & Hợp Tác</h1>
                <p>Tổng hợp các góp ý, khiếu nại chất lượng tiệm sửa, hoặc đề xuất tham gia mạng lưới của các cửa hàng.</p>
            </div>
            <div>
                <a href="../contact.php" target="_blank" class="fn-btn fn-btn-secondary fn-btn-sm" style="font-size: 13px; padding: 7px 14px;">
                    ↗️ Mở trang form liên hệ
                </a>
            </div>
        </div>

        <!-- THÔNG BÁO FLASH MESSAGE -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'replied'): ?>
                <div class="fn-ct-alert success">
                    ✅ Đã lưu phản hồi của Ban Quản Trị và đánh dấu "Đã phản hồi"!
                </div>
            <?php elseif ($_GET['msg'] === 'updated'): ?>
                <div class="fn-ct-alert success">
                    ✅ Đã cập nhật trạng thái tin nhắn thành công!
                </div>
            <?php elseif ($_GET['msg'] === 'deleted'): ?>
                <div class="fn-ct-alert warning">
                    🗑️ Đã xóa tin nhắn liên hệ khỏi hệ thống!
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 3 THẺ THỐNG KÊ (STATS CARDS) -->
        <div class="fn-ct-stats">
            <!-- THẺ 1: CHỜ XỬ LÝ -->
            <a href="contacts.php?status=pending" class="fn-ct-stat-card <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                <div class="fn-ct-stat-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="fn-ct-stat-info">
                    <span class="fn-ct-stat-title">Chờ xử lý</span>
                    <span class="fn-ct-stat-count"><?= $totalPending ?></span>
                    <span class="fn-ct-stat-desc">Cần liên hệ phản hồi</span>
                </div>
            </a>

            <!-- THẺ 2: ĐÃ XEM -->
            <a href="contacts.php?status=read" class="fn-ct-stat-card <?= $filterStatus === 'read' ? 'active' : '' ?>">
                <div class="fn-ct-stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <div class="fn-ct-stat-info">
                    <span class="fn-ct-stat-title">Đã xem</span>
                    <span class="fn-ct-stat-count"><?= $totalRead ?></span>
                    <span class="fn-ct-stat-desc">Đã tiếp nhận thông tin</span>
                </div>
            </a>

            <!-- THẺ 3: ĐÃ PHẢN HỒI -->
            <a href="contacts.php?status=replied" class="fn-ct-stat-card <?= $filterStatus === 'replied' ? 'active' : '' ?>">
                <div class="fn-ct-stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="fn-ct-stat-info">
                    <span class="fn-ct-stat-title">Đã phản hồi</span>
                    <span class="fn-ct-stat-count"><?= $totalReplied ?></span>
                    <span class="fn-ct-stat-desc">Đã giải quyết xong</span>
                </div>
            </a>
        </div>

        <!-- THANH BỘ LỌC VÀ TÌM KIẾM (TOOLBAR) -->
        <form method="GET" action="contacts.php" class="fn-ct-toolbar">
            <!-- Lọc theo chủ đề -->
            <select name="subject" class="fn-ct-select" onchange="this.form.submit()">
                <option value="all" <?= $filterSubject === 'all' ? 'selected' : '' ?>>Tất cả chủ đề</option>
                <option value="Khiếu nại chất lượng tiệm" <?= $filterSubject === 'Khiếu nại chất lượng tiệm' ? 'selected' : '' ?>>🔴 Khiếu nại chất lượng tiệm</option>
                <option value="Đăng ký tham gia mạng lưới" <?= $filterSubject === 'Đăng ký tham gia mạng lưới' ? 'selected' : '' ?>>🔵 Đăng ký tham gia mạng lưới</option>
                <option value="Góp ý tính năng website" <?= $filterSubject === 'Góp ý tính năng website' ? 'selected' : '' ?>>🟢 Góp ý tính năng website</option>
                <option value="Hợp tác truyền thông / Nghiên cứu" <?= $filterSubject === 'Hợp tác truyền thông / Nghiên cứu' ? 'selected' : '' ?>>🟣 Hợp tác truyền thông / Nghiên cứu</option>
            </select>

            <!-- Lọc theo trạng thái -->
            <select name="status" class="fn-ct-select" onchange="this.form.submit()">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>⏳ Chờ xử lý</option>
                <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>👁️ Đã xem</option>
                <option value="replied" <?= $filterStatus === 'replied' ? 'selected' : '' ?>>✅ Đã phản hồi</option>
            </select>

            <!-- Ô tìm kiếm từ khóa -->
            <div class="fn-ct-search-box">
                <span class="fn-ct-search-icon">🔍</span>
                <input type="text" name="q" placeholder="Tìm theo tên người gửi, số điện thoại, email, từ khóa..." value="<?= htmlspecialchars($searchKeyword) ?>">
            </div>

            <button type="submit" class="fn-btn fn-btn-sm fn-btn-primary" style="padding: 8px 14px; font-size: 13px;">Lọc</button>
            <?php if ($filterSubject !== 'all' || $filterStatus !== 'all' || $searchKeyword !== ''): ?>
                <a href="contacts.php" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 8px 12px; font-size: 13px;" title="Xóa tất cả bộ lọc">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <!-- BỐ CỤC 2 CỘT MASTER-DETAIL -->
        <div class="fn-ct-grid">

            <!-- MASTER TABLE (CỘT TRÁI) -->
            <div class="fn-ct-table-card">
                <div class="fn-ct-table-wrap">
                    <table class="fn-ct-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Người gửi</th>
                                <th>Chủ đề / Phân loại</th>
                                <th>Nội dung thư</th>
                                <th>Thời gian</th>
                                <th style="text-align: center;">Trạng thái</th>
                                <th style="width: 30px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paginatedContacts)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 48px 20px; color: #94a3b8;">
                                        <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                                        <strong style="color: #475569; font-size: 14px;">Không có tin nhắn nào trong danh mục này</strong>
                                        <p style="margin: 4px 0 0 0; font-size: 12.5px;">Thử đổi từ khóa hoặc điều kiện lọc để xem thêm dữ liệu.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paginatedContacts as $idx => $c):
                                    $cStatus = $c['status'] ?? 'pending';
                                    $subj = $c['subject'] ?? ($c['type'] ?? '');
                                    $meta = fnGetSubjectMeta($subj);
                                    $isActive = $currentContact && ((int)$currentContact['id'] === (int)$c['id']);
                                    $rowNum = $offset + $idx + 1;

                                    $rowParams = http_build_query([
                                        'subject' => $filterSubject,
                                        'status' => $filterStatus,
                                        'q' => $searchKeyword,
                                        'p' => $page,
                                        'detail' => $c['id']
                                    ]);
                                ?>
                                    <tr class="<?= $isActive ? 'active-row' : '' ?>" onclick="window.location.href='contacts.php?<?= $rowParams ?>'">
                                        <td style="font-weight: 700; color: #64748b;"><?= $rowNum ?></td>

                                        <!-- Người gửi -->
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="fn-ct-avatar">
                                                    <?= fnGetContactInitials($c['name'] ?? '') ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 800; color: #0f172a; line-height: 1.2;">
                                                        <?= htmlspecialchars($c['name'] ?? 'Khách ẩn danh') ?>
                                                    </div>
                                                    <?php if (!empty($c['phone'])): ?>
                                                        <div style="font-size: 11.5px; color: #ea580c; font-weight: 600; margin-top: 2px;">
                                                            📞 <?= htmlspecialchars($c['phone']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Chủ đề / Phân loại -->
                                        <td>
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; color: <?= $meta['color'] ?>; background: <?= $meta['bg'] ?>; border: 1px solid <?= $meta['border'] ?>; white-space: nowrap;">
                                                <?= $meta['icon'] ?> <?= htmlspecialchars($meta['label']) ?>
                                            </span>
                                        </td>

                                        <!-- Tóm tắt nội dung -->
                                        <td style="max-width: 220px;">
                                            <div style="font-size: 12.5px; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($c['message'] ?? '') ?>
                                            </div>
                                        </td>

                                        <!-- Thời gian -->
                                        <td style="white-space: nowrap; font-size: 12px; color: #64748b;">
                                            <?= date('d/m/Y', strtotime($c['created_at'] ?? 'now')) ?>
                                            <div style="font-size: 11px; color: #94a3b8;"><?= date('H:i', strtotime($c['created_at'] ?? 'now')) ?></div>
                                        </td>

                                        <!-- Trạng thái -->
                                        <td style="text-align: center;">
                                            <?php if ($cStatus === 'replied'): ?>
                                                <span class="fn-badge-st replied">✅ Đã phản hồi</span>
                                            <?php elseif ($cStatus === 'read'): ?>
                                                <span class="fn-badge-st read">👁️ Đã xem</span>
                                            <?php else: ?>
                                                <span class="fn-badge-st pending">⏳ Chờ xử lý</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Mũi tên -->
                                        <td style="color: #cbd5e1; font-weight: 900; font-size: 16px;">
                                            ›
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- THANH PHÂN TRANG -->
                <div class="fn-ct-pagination">
                    <div>
                        Hiển thị <strong><?= $totalFiltered > 0 ? ($offset + 1) : 0 ?> - <?= min($offset + $perPage, $totalFiltered) ?></strong> của <strong><?= $totalFiltered ?></strong> tin nhắn
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="fn-ct-page-btns">
                            <?php
                            $baseParams = [
                                'subject' => $filterSubject,
                                'status' => $filterStatus,
                                'q' => $searchKeyword
                            ];
                            ?>

                            <?php if ($page > 1): ?>
                                <a href="contacts.php?<?= http_build_query(array_merge($baseParams, ['p' => $page - 1])) ?>" class="fn-ct-page-btn">‹</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="contacts.php?<?= http_build_query(array_merge($baseParams, ['p' => $i])) ?>" class="fn-ct-page-btn <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="contacts.php?<?= http_build_query(array_merge($baseParams, ['p' => $page + 1])) ?>" class="fn-ct-page-btn">›</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DETAIL PANEL (CỘT PHẢI) -->
            <?php if ($currentContact):
                $curSt = $currentContact['status'] ?? 'pending';
                $curSubj = $currentContact['subject'] ?? ($currentContact['type'] ?? '');
                $curMeta = fnGetSubjectMeta($curSubj);
                $curCode = fnFormatContactCode($currentContact['id'], $currentContact['created_at']);
                $cleanPhone = preg_replace('/\D+/', '', $currentContact['phone'] ?? '');
            ?>
                <div class="fn-ct-detail-card">
                    <!-- HEADER CỦA PANEL CHI TIẾT -->
                    <div class="fn-ct-detail-header">
                        <div>
                            <?php if ($curSt === 'replied'): ?>
                                <span class="fn-badge-st replied">✅ Đã phản hồi</span>
                            <?php elseif ($curSt === 'read'): ?>
                                <span class="fn-badge-st read">👁️ Đã xem</span>
                            <?php else: ?>
                                <span class="fn-badge-st pending">⏳ Chờ xử lý</span>
                            <?php endif; ?>
                        </div>
                        <div class="fn-ct-detail-code">
                            <?= htmlspecialchars($curCode) ?>
                        </div>
                    </div>

                    <h2 class="fn-ct-detail-title">Chi tiết thư liên hệ</h2>

                    <!-- THẺ THÔNG TIN NGƯỜI GỬI (CONTACT CARD) -->
                    <div class="fn-ct-sender-box">
                        <div class="fn-ct-avatar" style="width: 44px; height: 44px; font-size: 14px;">
                            <?= fnGetContactInitials($currentContact['name'] ?? '') ?>
                        </div>
                        <div class="fn-ct-sender-info">
                            <div class="fn-ct-sender-name">
                                <?= htmlspecialchars($currentContact['name'] ?? 'Khách ẩn danh') ?>
                            </div>
                            <div style="font-size: 12px; color: #64748b;">
                                Gửi lúc: <strong><?= date('d/m/Y H:i', strtotime($currentContact['created_at'] ?? 'now')) ?></strong>
                            </div>

                            <!-- CÁC NÚT LIÊN HỆ NHANH (GỌI, ZALO, EMAIL) -->
                            <div class="fn-ct-quick-actions">
                                <?php if (!empty($cleanPhone)): ?>
                                    <a href="tel:<?= $cleanPhone ?>" class="fn-ct-action-pill" title="Gọi điện thoại trực tiếp">
                                        📞 <?= htmlspecialchars($currentContact['phone']) ?>
                                    </a>
                                    <a href="https://zalo.me/<?= $cleanPhone ?>" target="_blank" class="fn-ct-action-pill" style="color: #0284c7; border-color: #bae6fd;" title="Mở chat Zalo">
                                        💬 Chat Zalo
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($currentContact['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($currentContact['email']) ?>?subject=Re:%20Phan%20hoi%20tu%20FixNear" class="fn-ct-action-pill" style="color: #7c3aed; border-color: #ddd6fe;" title="Gửi thư điện tử">
                                        ✉️ <?= htmlspecialchars($currentContact['email']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CHỦ ĐỀ LIÊN HỆ -->
                    <div style="margin-bottom: 14px;">
                        <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; color: <?= $curMeta['color'] ?>; background: <?= $curMeta['bg'] ?>; border: 1px solid <?= $curMeta['border'] ?>;">
                            <?= $curMeta['icon'] ?> <?= htmlspecialchars($curMeta['label']) ?>
                        </span>
                    </div>

                    <!-- KHỐI NỘI DUNG THƯ ĐẦY ĐỦ -->
                    <div class="fn-ct-msg-box">
                        <div class="fn-ct-msg-title">
                            <span>Nội dung gửi đến</span>
                            <span>#<?= (int)$currentContact['id'] ?></span>
                        </div>
                        <div class="fn-ct-msg-text">
                            <?= htmlspecialchars($currentContact['message'] ?? '') ?>
                        </div>
                    </div>

                    <!-- KHỐI PHẢN HỒI CỦA ADMIN -->
                    <div class="fn-ct-reply-box">
                        <?php if (!empty($currentContact['admin_reply'])): ?>
                            <div class="fn-ct-reply-done">
                                <div class="fn-ct-reply-done-title">
                                    <span>💬 Phản hồi của Ban Quản Trị</span>
                                    <?php if (!empty($currentContact['replied_at'])): ?>
                                        <span style="font-weight: 500; font-size: 11px;">
                                            <?= date('d/m/Y H:i', strtotime($currentContact['replied_at'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="fn-ct-reply-done-text">
                                    <?= htmlspecialchars($currentContact['admin_reply']) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- FORM SOẠN PHẢN HỒI / LƯU GHI CHÚ TRAO ĐỔI -->
                        <form method="POST" action="contacts.php" class="fn-ct-reply-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reply_contact">
                            <input type="hidden" name="id" value="<?= (int)$currentContact['id'] ?>">

                            <label style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 6px;">
                                <?= !empty($currentContact['admin_reply']) ? 'Cập nhật nội dung phản hồi / kết quả:' : 'Soạn phản hồi / ghi nhận kết quả xử lý:' ?>
                            </label>

                            <textarea name="reply_text" rows="3" placeholder="Ví dụ: Đã liên hệ qua Zalo hướng dẫn chủ tiệm bổ sung giấy tờ xác minh..." required><?= htmlspecialchars($currentContact['admin_reply'] ?? '') ?></textarea>

                            <div style="margin-top: 8px;">
                                <button type="submit" class="fn-ct-btn-main">
                                    💬 <?= !empty($currentContact['admin_reply']) ? 'Cập nhật phản hồi' : 'Lưu phản hồi & Đánh dấu đã giải quyết' ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- KHỐI CÁC NÚT ĐỔI TRẠNG THÁI & XÓA -->
                    <div class="fn-ct-actions-row">
                        <?php if ($curSt === 'pending'): ?>
                            <form method="POST" action="contacts.php" style="flex: 1; margin: 0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= (int)$currentContact['id'] ?>">
                                <input type="hidden" name="status" value="read">
                                <button type="submit" class="fn-ct-btn-sec" style="width: 100%;">
                                    👁️ Đánh dấu Đã xem
                                </button>
                            </form>
                        <?php elseif ($curSt === 'read'): ?>
                            <form method="POST" action="contacts.php" style="flex: 1; margin: 0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= (int)$currentContact['id'] ?>">
                                <input type="hidden" name="status" value="pending">
                                <button type="submit" class="fn-ct-btn-sec" style="width: 100%;">
                                    ⏳ Chuyển lại Chờ xử lý
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Nút Xóa thư -->
                        <form method="POST" action="contacts.php" style="margin: 0;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này khỏi hệ thống? Thao tác không thể khôi phục.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$currentContact['id'] ?>">
                            <button type="submit" class="fn-ct-btn-sec fn-ct-btn-danger" title="Xóa tin nhắn này">
                                🗑️ Xóa thư
                            </button>
                        </form>
                    </div>

                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
