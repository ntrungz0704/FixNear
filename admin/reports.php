<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$db = FixNearDB::getInstance();

// 1. XỬ LÝ ACTION: LƯU GHI CHÚ NỘI BỘ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_note') {
    requireValidCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $note = trim($_POST['admin_note'] ?? '');
    if ($id > 0) {
        db()->updateReportNote($id, $note);
        header("Location: reports.php?detail=" . $id . "&msg=note_saved");
        exit;
    }
}

// 2. XỬ LÝ ACTION: ĐỔI MỨC ĐỘ ƯU TIÊN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_priority') {
    requireValidCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $priority = in_array($_POST['priority'] ?? '', ['high', 'medium', 'low'], true) ? $_POST['priority'] : 'medium';
    if ($id > 0) {
        db()->updateReportPriority($id, $priority);
        header("Location: reports.php?detail=" . $id . "&msg=priority_saved");
        exit;
    }
}

// 3. XỬ LÝ ACTION: XÓA BÁO CÁO (SPAM / TRÙNG LẶP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_report') {
    requireValidCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->deleteReport($id);
        header("Location: reports.php?msg=deleted");
        exit;
    }
}

// 4. XỬ LÝ ACTION: CẬP NHẬT TRẠNG THÁI (Hỗ trợ POST status và id truyền thống + status mới)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    requireValidCsrf();
    $id = (int)$_POST['id'];
    $allowed = ['pending', 'verifying', 'resolved'];
    $st = in_array($_POST['status'], $allowed, true) ? $_POST['status'] : 'pending';
    db()->updateReportStatus($id, $st);
    header("Location: reports.php?detail=" . $id . "&msg=" . $st);
    exit;
}

// Lấy danh sách toàn bộ báo cáo từ hệ thống
$rawReports = db()->getReports();

// Helper: Phân loại trường bị báo cáo từ lý do
function fnGetFieldCategory($reason) {
    $r = mb_strtolower($reason ?? '', 'UTF-8');
    if (strpos($r, 'giá') !== false) {
        return 'Giá dịch vụ';
    }
    if (strpos($r, 'địa chỉ') !== false || strpos($r, 'chuyển') !== false) {
        return 'Thông tin cửa hàng';
    }
    if (strpos($r, 'giờ') !== false || strpos($r, 'thời gian') !== false) {
        return 'Giờ mở cửa';
    }
    if (strpos($r, 'số điện thoại') !== false || strpos($r, 'điện thoại') !== false || strpos($r, 'liên lạc') !== false) {
        return 'Số điện thoại';
    }
    if (strpos($r, 'ngừng hoạt động') !== false || strpos($r, 'đóng cửa') !== false) {
        return 'Trạng thái hoạt động';
    }
    if (strpos($r, 'hình ảnh') !== false || strpos($r, 'ảnh') !== false) {
        return 'Hình ảnh dịch vụ';
    }
    return 'Mô tả dịch vụ';
}

// Helper: Định dạng mã báo cáo chuyên nghiệp (#BR-YYYYMMDD-001)
function fnFormatReportCode($id, $createdAt) {
    $datePart = date('Ymd', strtotime($createdAt ?: 'now'));
    return '#BR-' . $datePart . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

// Helper: Trích xuất link đính kèm từ nội dung phản ánh
function fnExtractEvidenceLink($details) {
    if (preg_match('/(https?:\/\/[^\s]+)/i', $details ?? '', $matches)) {
        return $matches[1];
    }
    return null;
}

// THỐNG KÊ TOÀN BỘ
$totalPending = 0;
$totalVerifying = 0;
$totalResolved = 0;

foreach ($rawReports as $rp) {
    $st = $rp['status'] ?? 'pending';
    if ($st === 'resolved') {
        $totalResolved++;
    } elseif ($st === 'verifying') {
        $totalVerifying++;
    } else {
        $totalPending++;
    }
}
$totalAll = count($rawReports);

// BỘ LỌC DỮ LIỆU
$filterField = trim($_GET['field'] ?? 'all');
$filterStatus = trim($_GET['status'] ?? ($_GET['filter'] ?? 'all'));
$filterPriority = trim($_GET['priority'] ?? 'all');
$searchKeyword = trim($_GET['q'] ?? '');
$filterDate = trim($_GET['date_range'] ?? 'all');

$filteredReports = array_filter($rawReports, function($rp) use ($filterField, $filterStatus, $filterPriority, $searchKeyword, $filterDate) {
    // 1. Lọc theo trạng thái
    $st = $rp['status'] ?? 'pending';
    if ($filterStatus === 'pending' && $st !== 'pending') return false;
    if ($filterStatus === 'verifying' && $st !== 'verifying') return false;
    if ($filterStatus === 'resolved' && $st !== 'resolved') return false;

    // 2. Lọc theo lĩnh vực
    if ($filterField !== 'all') {
        $category = fnGetFieldCategory($rp['reason'] ?? '');
        if ($category !== $filterField) return false;
    }

    // 3. Lọc theo mức độ ưu tiên
    if ($filterPriority !== 'all') {
        $p = $rp['priority'] ?? 'medium';
        if ($p !== $filterPriority) return false;
    }

    // 4. Lọc theo mốc thời gian
    if ($filterDate !== 'all') {
        $ts = strtotime($rp['created_at'] ?? 'now');
        $now = time();
        if ($filterDate === 'today' && date('Y-m-d', $ts) !== date('Y-m-d', $now)) return false;
        if ($filterDate === '7days' && $ts < ($now - 7 * 86400)) return false;
        if ($filterDate === '30days' && $ts < ($now - 30 * 86400)) return false;
    }

    // 5. Tìm kiếm từ khóa
    if ($searchKeyword !== '') {
        $q = mb_strtolower($searchKeyword, 'UTF-8');
        $idStr = (string)($rp['id'] ?? '');
        $code = mb_strtolower(fnFormatReportCode($rp['id'], $rp['created_at'] ?? ''), 'UTF-8');
        $shop = mb_strtolower($rp['shop_name'] ?? ($rp['current_shop_name'] ?? ''), 'UTF-8');
        $user = mb_strtolower($rp['user_name'] ?? '', 'UTF-8');
        $phone = mb_strtolower(($rp['reporter_phone'] ?? '') . ($rp['registered_user_phone'] ?? ''), 'UTF-8');
        $reason = mb_strtolower($rp['reason'] ?? '', 'UTF-8');
        $details = mb_strtolower($rp['details'] ?? '', 'UTF-8');

        if (
            strpos($idStr, $q) === false &&
            strpos($code, $q) === false &&
            strpos($shop, $q) === false &&
            strpos($user, $q) === false &&
            strpos($phone, $q) === false &&
            strpos($reason, $q) === false &&
            strpos($details, $q) === false
        ) {
            return false;
        }
    }

    return true;
});

// Chuyển mảng thành dạng index liên tục
$filteredReports = array_values($filteredReports);
$totalFiltered = count($filteredReports);

// PHÂN TRANG (PAGINATION) - 8 báo cáo mỗi trang như mockup
$perPage = 8;
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['p'] ?? 1)));
$offset = ($page - 1) * $perPage;
$paginatedReports = array_slice($filteredReports, $offset, $perPage);

// XÁC ĐỊNH BÁO CÁO ĐANG ĐƯỢC CHỌN (DETAIL PANEL)
$detailId = (int)($_GET['detail'] ?? 0);
$currentReport = null;

if ($detailId > 0) {
    foreach ($rawReports as $rp) {
        if ((int)$rp['id'] === $detailId) {
            $currentReport = $rp;
            break;
        }
    }
}

// Nếu không chỉ định hoặc không tìm thấy, mặc định chọn báo cáo đầu tiên trong danh sách hiển thị
if (!$currentReport && !empty($paginatedReports)) {
    $currentReport = $paginatedReports[0];
}

$pageTitle = "Quản Lý Báo Cáo Sai Sót — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
/* ================= CSS GIAO DIỆN BÁO CÁO SAI SÓT FIXNEAR ADMIN ================= */
.fn-rp-container {
    padding: 0;
}

.fn-rp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 22px;
    flex-wrap: wrap;
    gap: 16px;
}
.fn-rp-header h1 {
    font-family: var(--fn-font-heading);
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 6px 0;
}
.fn-rp-header p {
    font-size: 13.5px;
    color: #64748b;
    margin: 0;
}

/* THẺ THỐNG KÊ (STATS CARDS - 3 CỘT) */
.fn-rp-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
.fn-rp-stat-card {
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
.fn-rp-stat-card:hover {
    transform: translateY(-2px);
    border-color: #ea580c;
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.08);
}
.fn-rp-stat-card.active {
    border-color: #ea580c;
    background: #fffaf7;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.1);
}
.fn-rp-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.fn-rp-stat-icon.orange { background: #ffedd5; color: #ea580c; }
.fn-rp-stat-icon.blue   { background: #e0f2fe; color: #0284c7; }
.fn-rp-stat-icon.green  { background: #dcfce7; color: #16a34a; }
.fn-rp-stat-info {
    display: flex;
    flex-direction: column;
}
.fn-rp-stat-title {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 2px;
}
.fn-rp-stat-count {
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.1;
}
.fn-rp-stat-desc {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
}

/* THANH BỘ LỌC & TÌM KIẾM (TOOLBAR) */
.fn-rp-toolbar {
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
.fn-rp-select {
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
    min-width: 150px;
}
.fn-rp-select:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}
.fn-rp-search-box {
    flex: 1;
    min-width: 200px;
    position: relative;
    display: flex;
    align-items: center;
}
.fn-rp-search-box input {
    width: 100%;
    padding: 8px 14px 8px 36px;
    font-size: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
    transition: all 0.15s;
}
.fn-rp-search-box input:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}
.fn-rp-search-icon {
    position: absolute;
    left: 12px;
    color: #94a3b8;
    pointer-events: none;
    font-size: 14px;
}

/* BỐ CỤC MASTER-DETAIL 2 CỘT */
.fn-rp-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 440px;
    gap: 20px;
    align-items: start;
}

/* MASTER TABLE (CỘT TRÁI) */
.fn-rp-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.fn-rp-table-wrap {
    overflow-x: auto;
    width: 100%;
}
.fn-rp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
.fn-rp-table th {
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
.fn-rp-table td {
    padding: 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #1e293b;
    transition: background-color 0.15s;
}
.fn-rp-table tbody tr {
    cursor: pointer;
    transition: all 0.15s;
}
.fn-rp-table tbody tr:hover td {
    background: #f8fafc;
}
.fn-rp-table tbody tr.active-row td {
    background: #fff7ed;
    color: #0f172a;
}
.fn-rp-table tbody tr.active-row td:first-child {
    border-left: 3px solid #ea580c;
    font-weight: 900;
    color: #ea580c;
}

/* BADGES MỨC ĐỘ ƯU TIÊN */
.fn-badge-pri {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    text-align: center;
    white-space: nowrap;
}
.fn-badge-pri.high   { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.fn-badge-pri.medium { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.fn-badge-pri.low    { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }

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
.fn-badge-st.pending   { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
.fn-badge-st.verifying { background: #ffedd5; color: #ea580c; border: 1px solid #fed7aa; }
.fn-badge-st.resolved  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

/* PHÂN TRANG (PAGINATION) */
.fn-rp-pagination {
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
.fn-rp-page-btns {
    display: flex;
    gap: 4px;
}
.fn-rp-page-btn {
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
.fn-rp-page-btn:hover {
    border-color: #ea580c;
    color: #ea580c;
}
.fn-rp-page-btn.active {
    background: #ea580c;
    border-color: #ea580c;
    color: #ffffff;
}

/* DETAIL PANEL (CỘT PHẢI) */
.fn-rp-detail-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    position: sticky;
    top: 85px;
}
.fn-rp-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
}
.fn-rp-detail-code {
    font-family: monospace;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
}
.fn-rp-detail-title {
    font-size: 17px;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 16px 0;
    letter-spacing: -0.01em;
}

/* BẢNG META CHI TIẾT (2 CỘT KEY-VALUE) */
.fn-rp-meta-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 18px;
    font-size: 13px;
}
.fn-rp-meta-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 8px;
    align-items: baseline;
}
.fn-rp-meta-label {
    color: #64748b;
    font-size: 12.5px;
}
.fn-rp-meta-val {
    color: #0f172a;
    font-weight: 700;
    word-break: break-word;
}

/* KHỐI NỘI DUNG NGƯỜI DÙNG BÁO CÁO (NỀN HỒNG/ĐỎ NHẠT) */
.fn-rp-user-content {
    background: #fef2f2;
    border: 1px solid #fee2e2;
    border-radius: 8px;
    padding: 14px;
    color: #991b1b;
    font-size: 13.5px;
    line-height: 1.5;
    margin-bottom: 18px;
}
.fn-rp-user-content-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #b91c1c;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* KHỐI GIÁ TRỊ HIỆN TẠI TRÊN HỆ THỐNG */
.fn-rp-system-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px;
    font-size: 13px;
    color: #334155;
    line-height: 1.55;
    margin-bottom: 18px;
}
.fn-rp-system-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.fn-rp-system-item {
    margin-bottom: 6px;
}
.fn-rp-system-item strong {
    color: #0f172a;
}
.fn-rp-quick-links {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #cbd5e1;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.fn-rp-quick-btn {
    font-size: 11.5px;
    padding: 4px 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #1e293b;
    text-decoration: none;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
}
.fn-rp-quick-btn:hover {
    border-color: #ea580c;
    color: #ea580c;
    background: #fffaf7;
}

/* KHỐI BẰNG CHỨNG ĐÍNH KÈM */
.fn-rp-evidence-box {
    margin-bottom: 18px;
    font-size: 13px;
}
.fn-rp-evidence-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 6px;
}
.fn-rp-evidence-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: #0284c7;
    text-decoration: none;
    word-break: break-all;
    font-weight: 600;
}
.fn-rp-evidence-link:hover {
    text-decoration: underline;
}

/* KHỐI GHI CHÚ NỘI BỘ (ADMIN NOTE) */
.fn-rp-note-box {
    margin-bottom: 20px;
}
.fn-rp-note-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
}
.fn-rp-note-textarea {
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
.fn-rp-note-textarea:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
}
.fn-rp-note-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 6px;
    font-size: 11px;
    color: #94a3b8;
}

/* CÁC NÚT HÀNH ĐỘNG DƯỚI CÙNG */
.fn-rp-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.fn-rp-btn-main {
    width: 100%;
    padding: 11px 16px;
    background: #ea580c;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.fn-rp-btn-main:hover {
    background: #c2410c;
}
.fn-rp-btn-sec {
    width: 100%;
    padding: 9px 14px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.fn-rp-btn-sec:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}
.fn-rp-btn-danger {
    color: #dc2626;
    border-color: #fecaca;
}
.fn-rp-btn-danger:hover {
    background: #fef2f2;
    border-color: #dc2626;
}

/* TOAST / FLASH ALERTS */
.fn-rp-alert {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.fn-rp-alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.fn-rp-alert.info    { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
.fn-rp-alert.warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

/* RESPONSIVE TRÊN TABLET VÀ MOBILE */
@media (max-width: 1024px) {
    .fn-rp-grid {
        grid-template-columns: 1fr;
    }
    .fn-rp-detail-card {
        position: static;
        margin-top: 10px;
    }
    .fn-rp-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content fn-rp-container">
        
        <!-- HEADER TRANG -->
        <div class="fn-rp-header">
            <div>
                <h1>Báo cáo sai sót</h1>
                <p>Tiếp nhận và xử lý các báo cáo về thông tin không chính xác trên FixNear</p>
            </div>

            <!-- Nút chọn khung thời gian như trong mockup -->
            <form method="GET" action="reports.php" style="margin: 0; display: inline-flex; align-items: center;">
                <input type="hidden" name="field" value="<?= htmlspecialchars($filterField) ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <input type="hidden" name="priority" value="<?= htmlspecialchars($filterPriority) ?>">
                <?php if ($searchKeyword !== ''): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($searchKeyword) ?>">
                <?php endif; ?>
                <select name="date_range" class="fn-rp-select" style="min-width: 190px; background: #ffffff;" onchange="this.form.submit()">
                    <option value="all" <?= $filterDate === 'all' ? 'selected' : '' ?>>📅 Tất cả thời gian</option>
                    <option value="today" <?= $filterDate === 'today' ? 'selected' : '' ?>>📅 Hôm nay</option>
                    <option value="7days" <?= $filterDate === '7days' ? 'selected' : '' ?>>📅 7 ngày qua</option>
                    <option value="30days" <?= $filterDate === '30days' ? 'selected' : '' ?>>📅 30 ngày qua</option>
                </select>
            </form>
        </div>

        <!-- THÔNG BÁO FLASH MESSAGE -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'resolved'): ?>
                <div class="fn-rp-alert success">
                    ✓ Đã chuyển trạng thái báo cáo sang "Đã xử lý"!
                </div>
            <?php elseif ($_GET['msg'] === 'verifying'): ?>
                <div class="fn-rp-alert warning">
                    🔍 Đã chuyển báo cáo sang trạng thái "Đang kiểm tra / xác minh"!
                </div>
            <?php elseif ($_GET['msg'] === 'pending'): ?>
                <div class="fn-rp-alert info">
                    ⏳ Đã mở lại báo cáo sang trạng thái "Mới / Chờ kiểm tra"!
                </div>
            <?php elseif ($_GET['msg'] === 'note_saved'): ?>
                <div class="fn-rp-alert success">
                    💾 Đã lưu ghi chú nội bộ thành công!
                </div>
            <?php elseif ($_GET['msg'] === 'priority_saved'): ?>
                <div class="fn-rp-alert success">
                    ⚡ Đã cập nhật mức độ ưu tiên của báo cáo!
                </div>
            <?php elseif ($_GET['msg'] === 'deleted'): ?>
                <div class="fn-rp-alert warning">
                    🗑️ Đã xóa báo cáo sai sót khỏi hệ thống!
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 3 THẺ THỐNG KÊ (STATS CARDS) -->
        <div class="fn-rp-stats">
            <!-- THẺ 1: MỚI / CHỜ KIỂM TRA -->
            <a href="reports.php?status=pending" class="fn-rp-stat-card <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                <div class="fn-rp-stat-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <div class="fn-rp-stat-info">
                    <span class="fn-rp-stat-title">Mới</span>
                    <span class="fn-rp-stat-count"><?= $totalPending ?></span>
                    <span class="fn-rp-stat-desc">Cần kiểm tra</span>
                </div>
            </a>

            <!-- THẺ 2: ĐANG KIỂM TRA -->
            <a href="reports.php?status=verifying" class="fn-rp-stat-card <?= $filterStatus === 'verifying' ? 'active' : '' ?>">
                <div class="fn-rp-stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <div class="fn-rp-stat-info">
                    <span class="fn-rp-stat-title">Đang kiểm tra</span>
                    <span class="fn-rp-stat-count"><?= $totalVerifying ?></span>
                    <span class="fn-rp-stat-desc">Đang xác minh</span>
                </div>
            </a>

            <!-- THẺ 3: ĐÃ XỬ LÝ -->
            <a href="reports.php?status=resolved" class="fn-rp-stat-card <?= $filterStatus === 'resolved' ? 'active' : '' ?>">
                <div class="fn-rp-stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="fn-rp-stat-info">
                    <span class="fn-rp-stat-title">Đã xử lý</span>
                    <span class="fn-rp-stat-count"><?= $totalResolved ?></span>
                    <span class="fn-rp-stat-desc">Đã cập nhật hệ thống</span>
                </div>
            </a>
        </div>

        <!-- THANH CÔNG CỤ BỘ LỌC VÀ TÌM KIẾM (TOOLBAR) -->
        <form method="GET" action="reports.php" class="fn-rp-toolbar">
            <input type="hidden" name="date_range" value="<?= htmlspecialchars($filterDate) ?>">
            
            <!-- Lọc lĩnh vực / trường bị báo cáo -->
            <select name="field" class="fn-rp-select" onchange="this.form.submit()">
                <option value="all" <?= $filterField === 'all' ? 'selected' : '' ?>>Tất cả lĩnh vực</option>
                <option value="Giá dịch vụ" <?= $filterField === 'Giá dịch vụ' ? 'selected' : '' ?>>Giá dịch vụ</option>
                <option value="Thông tin cửa hàng" <?= $filterField === 'Thông tin cửa hàng' ? 'selected' : '' ?>>Thông tin cửa hàng</option>
                <option value="Giờ mở cửa" <?= $filterField === 'Giờ mở cửa' ? 'selected' : '' ?>>Giờ mở cửa</option>
                <option value="Số điện thoại" <?= $filterField === 'Số điện thoại' ? 'selected' : '' ?>>Số điện thoại</option>
                <option value="Trạng thái hoạt động" <?= $filterField === 'Trạng thái hoạt động' ? 'selected' : '' ?>>Trạng thái hoạt động</option>
                <option value="Mô tả dịch vụ" <?= $filterField === 'Mô tả dịch vụ' ? 'selected' : '' ?>>Mô tả dịch vụ</option>
            </select>

            <!-- Lọc trạng thái -->
            <select name="status" class="fn-rp-select" onchange="this.form.submit()">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Mới (Cần kiểm tra)</option>
                <option value="verifying" <?= $filterStatus === 'verifying' ? 'selected' : '' ?>>Đang kiểm tra</option>
                <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
            </select>

            <!-- Lọc mức độ ưu tiên -->
            <select name="priority" class="fn-rp-select" onchange="this.form.submit()">
                <option value="all" <?= $filterPriority === 'all' ? 'selected' : '' ?>>Tất cả mức độ ưu tiên</option>
                <option value="high" <?= $filterPriority === 'high' ? 'selected' : '' ?>>Cao</option>
                <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Trung bình</option>
                <option value="low" <?= $filterPriority === 'low' ? 'selected' : '' ?>>Thấp</option>
            </select>

            <!-- Ô tìm kiếm từ khóa -->
            <div class="fn-rp-search-box">
                <span class="fn-rp-search-icon">🔍</span>
                <input type="text" name="q" placeholder="Tìm theo từ khóa, tên tiệm, người gửi..." value="<?= htmlspecialchars($searchKeyword) ?>">
            </div>

            <button type="submit" class="fn-btn fn-btn-sm fn-btn-primary" style="padding: 8px 14px; font-size: 13px;">Lọc</button>
            <?php if ($filterField !== 'all' || $filterStatus !== 'all' || $filterPriority !== 'all' || $searchKeyword !== '' || $filterDate !== 'all'): ?>
                <a href="reports.php" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 8px 12px; font-size: 13px;" title="Xóa tất cả bộ lọc">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <!-- BỐ CỤC MASTER-DETAIL (DANH SÁCH BÊN TRÁI - CHI TIẾT BÊN PHẢI) -->
        <div class="fn-rp-grid">
            
            <!-- MASTER TABLE (CỘT TRÁI) -->
            <div class="fn-rp-table-card">
                <div class="fn-rp-table-wrap">
                    <table class="fn-rp-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Trường bị báo cáo</th>
                                <th>Cửa hàng / Dòng máy</th>
                                <th>Người báo cáo</th>
                                <th>Ngày gửi</th>
                                <th style="text-align: center;">Ưu tiên</th>
                                <th style="text-align: center;">Trạng thái</th>
                                <th style="width: 30px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paginatedReports)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding: 48px 20px; color: #94a3b8;">
                                        <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                                        <strong style="color: #475569; font-size: 14px;">Không tìm thấy báo cáo sai sót nào</strong>
                                        <p style="margin: 4px 0 0 0; font-size: 12.5px;">Thử đổi điều kiện tìm kiếm hoặc xóa bộ lọc để xem toàn bộ dữ liệu.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paginatedReports as $idx => $rp): 
                                    $category = fnGetFieldCategory($rp['reason'] ?? '');
                                    $st = $rp['status'] ?? 'pending';
                                    $pri = $rp['priority'] ?? 'medium';
                                    $isActive = $currentReport && ((int)$currentReport['id'] === (int)$rp['id']);
                                    $rowNum = $offset + $idx + 1;
                                    
                                    // Build query string duy trì các filter khi click chọn item
                                    $rowParams = http_build_query([
                                        'field' => $filterField,
                                        'status' => $filterStatus,
                                        'priority' => $filterPriority,
                                        'date_range' => $filterDate,
                                        'q' => $searchKeyword,
                                        'p' => $page,
                                        'detail' => $rp['id']
                                    ]);
                                ?>
                                    <tr class="<?= $isActive ? 'active-row' : '' ?>" onclick="window.location.href='reports.php?<?= $rowParams ?>'">
                                        <td style="font-weight: 700; color: #64748b;"><?= $rowNum ?></td>
                                        
                                        <!-- Trường bị báo cáo -->
                                        <td style="font-weight: 700; color: #0f172a; white-space: nowrap;">
                                            <?= htmlspecialchars($category) ?>
                                        </td>
                                        
                                        <!-- Cửa hàng / Dòng máy -->
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a; line-height: 1.3;">
                                                <?= htmlspecialchars($rp['shop_name'] ?? ($rp['current_shop_name'] ?? 'Tiệm #' . $rp['shop_id'])) ?>
                                            </div>
                                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                                <?= htmlspecialchars($rp['shop_district'] ? $rp['shop_district'] . ' • ' : '') ?><?= htmlspecialchars(mb_substr($rp['reason'] ?? '', 0, 40)) ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Người báo cáo -->
                                        <td style="white-space: nowrap;">
                                            <div style="font-weight: 700; color: #334155;">
                                                <?= htmlspecialchars($rp['user_name'] ?? 'Khách vãng lai') ?>
                                            </div>
                                            <?php if (!empty($rp['reporter_phone']) || !empty($rp['registered_user_phone'])): ?>
                                                <div style="font-size: 11px; color: #94a3b8;">
                                                    <?= htmlspecialchars($rp['reporter_phone'] ?: $rp['registered_user_phone']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Ngày gửi -->
                                        <td style="white-space: nowrap; font-size: 12px; color: #475569;">
                                            <div><?= date('d/m/Y', strtotime($rp['created_at'])) ?></div>
                                            <div style="font-size: 11px; color: #94a3b8;"><?= date('H:i', strtotime($rp['created_at'])) ?></div>
                                        </td>
                                        
                                        <!-- Mức độ ưu tiên -->
                                        <td style="text-align: center;">
                                            <?php if ($pri === 'high'): ?>
                                                <span class="fn-badge-pri high">Cao</span>
                                            <?php elseif ($pri === 'low'): ?>
                                                <span class="fn-badge-pri low">Thấp</span>
                                            <?php else: ?>
                                                <span class="fn-badge-pri medium">Trung bình</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Trạng thái -->
                                        <td style="text-align: center;">
                                            <?php if ($st === 'resolved'): ?>
                                                <span class="fn-badge-st resolved">Đã xử lý</span>
                                            <?php elseif ($st === 'verifying'): ?>
                                                <span class="fn-badge-st verifying">Đang kiểm tra</span>
                                            <?php else: ?>
                                                <span class="fn-badge-st pending">Mới</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Mũi tên xem chi tiết -->
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
                <div class="fn-rp-pagination">
                    <div>
                        Hiển thị <strong><?= $totalFiltered > 0 ? ($offset + 1) : 0 ?> - <?= min($offset + $perPage, $totalFiltered) ?></strong> của <strong><?= $totalFiltered ?></strong> báo cáo
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="fn-rp-page-btns">
                            <?php
                            $baseParams = [
                                'field' => $filterField,
                                'status' => $filterStatus,
                                'priority' => $filterPriority,
                                'date_range' => $filterDate,
                                'q' => $searchKeyword
                            ];
                            ?>

                            <!-- Trang trước -->
                            <?php if ($page > 1): ?>
                                <a href="reports.php?<?= http_build_query(array_merge($baseParams, ['p' => $page - 1])) ?>" class="fn-rp-page-btn">‹</a>
                            <?php endif; ?>

                            <!-- Các số trang -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="reports.php?<?= http_build_query(array_merge($baseParams, ['p' => $i])) ?>" class="fn-rp-page-btn <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Trang sau -->
                            <?php if ($page < $totalPages): ?>
                                <a href="reports.php?<?= http_build_query(array_merge($baseParams, ['p' => $page + 1])) ?>" class="fn-rp-page-btn">›</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DETAIL PANEL (CỘT PHẢI) -->
            <?php if ($currentReport): 
                $curSt = $currentReport['status'] ?? 'pending';
                $curPri = $currentReport['priority'] ?? 'medium';
                $curCat = fnGetFieldCategory($currentReport['reason'] ?? '');
                $curCode = fnFormatReportCode($currentReport['id'], $currentReport['created_at']);
                $evidenceLink = fnExtractEvidenceLink($currentReport['details']);
                $shopId = (int)($currentReport['shop_id'] ?? 0);
            ?>
                <div class="fn-rp-detail-card">
                    <!-- HEADER CỦA PANEL CHI TIẾT -->
                    <div class="fn-rp-detail-header">
                        <div>
                            <?php if ($curSt === 'resolved'): ?>
                                <span class="fn-badge-st resolved">Đã xử lý</span>
                            <?php elseif ($curSt === 'verifying'): ?>
                                <span class="fn-badge-st verifying">Đang kiểm tra</span>
                            <?php else: ?>
                                <span class="fn-badge-st pending">Mới</span>
                            <?php endif; ?>
                        </div>
                        <div class="fn-rp-detail-code">
                            <?= htmlspecialchars($curCode) ?>
                        </div>
                    </div>

                    <h2 class="fn-rp-detail-title">Chi tiết báo cáo</h2>

                    <!-- THÔNG TIN TỔNG QUAN (KEY-VALUE META) -->
                    <div class="fn-rp-meta-list">
                        <div class="fn-rp-meta-row">
                            <span class="fn-rp-meta-label">Trường bị báo cáo:</span>
                            <span class="fn-rp-meta-val"><?= htmlspecialchars($curCat) ?></span>
                        </div>
                        <div class="fn-rp-meta-row">
                            <span class="fn-rp-meta-label">Cửa hàng / Dòng máy:</span>
                            <span class="fn-rp-meta-val">
                                <?php if ($shopId > 0): ?>
                                    <a href="../shop_detail.php?id=<?= $shopId ?>" target="_blank" style="color: #ea580c; text-decoration: none;" title="Xem cửa hàng">
                                        <?= htmlspecialchars($currentReport['shop_name'] ?? ($currentReport['current_shop_name'] ?? 'Tiệm #' . $shopId)) ?> ↗
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($currentReport['shop_name'] ?? 'Cửa hàng') ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="fn-rp-meta-row">
                            <span class="fn-rp-meta-label">Người báo cáo:</span>
                            <span class="fn-rp-meta-val">
                                <?= htmlspecialchars($currentReport['user_name'] ?? 'Khách vãng lai') ?>
                                <?php if (!empty($currentReport['reporter_phone']) || !empty($currentReport['registered_user_phone'])): ?>
                                    <span style="font-weight: 500; color: #64748b; font-size: 12px;">
                                        (<?= htmlspecialchars($currentReport['reporter_phone'] ?: $currentReport['registered_user_phone']) ?>)
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="fn-rp-meta-row">
                            <span class="fn-rp-meta-label">Ngày gửi:</span>
                            <span class="fn-rp-meta-val"><?= date('d/m/Y H:i', strtotime($currentReport['created_at'])) ?></span>
                        </div>
                        <div class="fn-rp-meta-row">
                            <span class="fn-rp-meta-label">Mức độ ưu tiên:</span>
                            <span class="fn-rp-meta-val">
                                <form method="POST" action="reports.php" style="display: inline-flex; align-items: center; gap: 6px;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="set_priority">
                                    <input type="hidden" name="id" value="<?= $currentReport['id'] ?>">
                                    <select name="priority" onchange="this.form.submit()" style="font-size: 11.5px; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-weight: 700; cursor: pointer; background: #ffffff;">
                                        <option value="high" <?= $curPri === 'high' ? 'selected' : '' ?>>🔴 Cao</option>
                                        <option value="medium" <?= $curPri === 'medium' ? 'selected' : '' ?>>🟡 Trung bình</option>
                                        <option value="low" <?= $curPri === 'low' ? 'selected' : '' ?>>🟢 Thấp</option>
                                    </select>
                                </form>
                            </span>
                        </div>
                    </div>

                    <!-- KHỐI 1: NỘI DUNG NGƯỜI DÙNG BÁO CÁO -->
                    <div class="fn-rp-user-content">
                        <div class="fn-rp-user-content-title">
                            ⚠️ Nội dung người dùng báo cáo
                        </div>
                        <div style="white-space: pre-line; word-break: break-word;">
                            <?= htmlspecialchars($currentReport['details']) ?>
                        </div>
                    </div>

                    <!-- KHỐI 2: GIÁ TRỊ HIỆN TẠI TRÊN HỆ THỐNG -->
                    <div class="fn-rp-system-box">
                        <div class="fn-rp-system-title">
                            🏢 Giá trị hiện tại trên hệ thống
                        </div>
                        <div class="fn-rp-system-item">
                            <strong>Địa chỉ:</strong> <?= htmlspecialchars($currentReport['shop_address'] ?: 'Chưa cập nhật địa chỉ cụ thể') ?>
                        </div>
                        <div class="fn-rp-system-item">
                            <strong>Số điện thoại:</strong> 
                            <?php if (!empty($currentReport['shop_phone'])): ?>
                                <a href="tel:<?= preg_replace('/\s+/', '', $currentReport['shop_phone']) ?>" style="color: #0284c7; text-decoration: none; font-weight: 700;">
                                    <?= htmlspecialchars($currentReport['shop_phone']) ?> 📞
                                </a>
                            <?php else: ?>
                                <span style="color: #94a3b8;">Chưa có hotline</span>
                            <?php endif; ?>
                        </div>
                        <div class="fn-rp-system-item">
                            <strong>Giờ mở cửa:</strong> <?= htmlspecialchars($currentReport['shop_opening_hours'] ?: '08:00 - 20:00 (T2 - CN)') ?>
                        </div>

                        <!-- CÁC NÚT PHÍM TẮT SỬA NHANH THÔNG TIN -->
                        <?php if ($shopId > 0): ?>
                            <div class="fn-rp-quick-links">
                                <a href="shop_edit.php?id=<?= $shopId ?>" class="fn-rp-quick-btn" title="Chỉnh sửa giờ mở cửa, địa chỉ, số điện thoại">
                                    ⚙️ Sửa thông tin tiệm
                                </a>
                                <a href="services.php?shop_id=<?= $shopId ?>" class="fn-rp-quick-btn" title="Cập nhật bảng giá dịch vụ cho tiệm này">
                                    🏷️ Sửa bảng giá tiệm
                                </a>
                                <a href="../shop_detail.php?id=<?= $shopId ?>" target="_blank" class="fn-rp-quick-btn" title="Mở trang xem công khai">
                                    🌐 Xem trên web
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- KHỐI 3: BẰNG CHỨNG ĐÍNH KÈM -->
                    <div class="fn-rp-evidence-box">
                        <div class="fn-rp-evidence-title">Bằng chứng đính kèm</div>
                        <?php if ($evidenceLink): ?>
                            <a href="<?= htmlspecialchars($evidenceLink) ?>" target="_blank" rel="noopener noreferrer" class="fn-rp-evidence-link">
                                🔗 <?= htmlspecialchars($evidenceLink) ?> ↗
                            </a>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Không có tài liệu đính kèm</span>
                        <?php endif; ?>
                    </div>

                    <!-- KHỐI 4: GHI CHÚ NỘI BỘ (ADMIN NOTE) -->
                    <div class="fn-rp-note-box">
                        <form method="POST" action="reports.php" id="form-admin-note">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_note">
                            <input type="hidden" name="id" value="<?= $currentReport['id'] ?>">

                            <div class="fn-rp-note-title">
                                <span>Ghi chú nội bộ</span>
                                <span id="note-counter" style="color: #94a3b8; font-size: 11px; font-weight: normal;">
                                    <?= mb_strlen($currentReport['admin_note'] ?? '') ?>/500
                                </span>
                            </div>

                            <textarea name="admin_note" id="admin_note_input" class="fn-rp-note-textarea" rows="3" maxlength="500" placeholder="Đã liên hệ cửa hàng, xác nhận thông tin..."><?= htmlspecialchars($currentReport['admin_note'] ?? '') ?></textarea>

                            <div class="fn-rp-note-footer">
                                <span>Chỉ Admin mới nhìn thấy ghi chú này</span>
                                <button type="submit" class="fn-btn fn-btn-sm fn-btn-secondary" style="padding: 3px 8px; font-size: 11px;">
                                    💾 Lưu ghi chú
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- KHỐI 5: NÚT THAO TÁC HÀNH ĐỘNG DƯỚI CÙNG -->
                    <div class="fn-rp-actions">
                        <?php if ($curSt !== 'resolved'): ?>
                            <!-- Nút to màu cam: Đánh dấu đã xử lý -->
                            <form method="POST" action="reports.php" style="width: 100%; margin: 0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= $currentReport['id'] ?>">
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit" class="fn-rp-btn-main">
                                    ✓ Đánh dấu đã xử lý
                                </button>
                            </form>

                            <?php if ($curSt !== 'verifying'): ?>
                                <!-- Nút chuyển sang Đang kiểm tra -->
                                <form method="POST" action="reports.php" style="width: 100%; margin: 0;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $currentReport['id'] ?>">
                                    <input type="hidden" name="status" value="verifying">
                                    <button type="submit" class="fn-rp-btn-sec">
                                        🔍 Chuyển sang "Đang kiểm tra"
                                    </button>
                                </form>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- Nút mở lại báo cáo -->
                            <form method="POST" action="reports.php" style="width: 100%; margin: 0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= $currentReport['id'] ?>">
                                <input type="hidden" name="status" value="pending">
                                <button type="submit" class="fn-rp-btn-sec" style="color: #d97706; border-color: #fde68a; background: #fffbeb;">
                                    ⏳ Mở lại báo cáo (Chờ kiểm tra)
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Nút Xóa báo cáo (Spam / Troll) -->
                        <form method="POST" action="reports.php" style="width: 100%; margin: 0;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn báo cáo sai sót này không? Thao tác không thể khôi phục.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_report">
                            <input type="hidden" name="id" value="<?= $currentReport['id'] ?>">
                            <button type="submit" class="fn-rp-btn-sec fn-rp-btn-danger">
                                🗑️ Xóa báo cáo này
                            </button>
                        </form>
                    </div>

                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<script>
// Đếm số ký tự cho ô ghi chú nội bộ
document.addEventListener('DOMContentLoaded', function() {
    const noteInput = document.getElementById('admin_note_input');
    const noteCounter = document.getElementById('note-counter');
    if (noteInput && noteCounter) {
        noteInput.addEventListener('input', function() {
            noteCounter.textContent = this.value.length + '/500';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
