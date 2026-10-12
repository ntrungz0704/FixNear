<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$db = FixNearDB::getInstance();
$pdo = $db->isUsingMySQL() ? $db->getPdo() : null;

// Xử lý admin phản hồi review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_review') {
    requireValidCsrf();
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $replyText = trim($_POST['reply_text'] ?? '');
    if ($reviewId > 0 && !empty($replyText)) {
        db()->replyReview($reviewId, $replyText);
        header("Location: reviews.php?detail=" . $reviewId . "&msg=replied");
        exit;
    }
}

// Ẩn hoặc xóa đánh giá bằng POST có CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['toggle_review', 'delete_review'], true)) {
    requireValidCsrf();
    $reviewId = (int)($_POST['review_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'toggle_review') {
        db()->toggleReviewVisibility($reviewId);
        header("Location: reviews.php?detail=" . $reviewId . "&msg=toggled");
        exit;
    } else {
        db()->deleteReview($reviewId);
        header("Location: reviews.php?msg=deleted");
        exit;
    }
}

// Hàm lấy 2 chữ cái viết tắt đại diện cho Avatar khách hàng
function fnGetInitials($name) {
    $name = trim($name);
    if (empty($name)) return 'KH';
    $words = preg_split('/\s+/u', $name);
    if (count($words) >= 2) {
        $first = mb_substr($words[count($words) - 2], 0, 1, 'UTF-8');
        $second = mb_substr($words[count($words) - 1], 0, 1, 'UTF-8');
        return mb_strtoupper($first . $second, 'UTF-8');
    }
    return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
}

// Hàm hiển thị số sao vàng
function fnRenderStars($rating) {
    $r = max(1, min(5, (int)$rating));
    $filled = str_repeat('★', $r);
    $empty = str_repeat('☆', 5 - $r);
    return '<span style="color:#f59e0b; font-size:14px; letter-spacing:1.5px;">' . $filled . '</span><span style="color:#cbd5e1; font-size:14px; letter-spacing:1.5px;">' . $empty . '</span>';
}

// Đọc danh sách cửa hàng để hỗ trợ lọc
$shops = db()->getShops(['include_unverified' => true]);

// Truy vấn toàn bộ đánh giá kèm thông tin cửa hàng và người dùng
$allReviews = [];
if ($pdo) {
    $stmt = $pdo->query("
        SELECT r.*,
               s.name AS shop_name, s.address AS shop_address, s.district AS shop_district, s.phone AS shop_phone, s.map_url AS shop_map_url,
               u.name AS user_full_name, u.email AS user_email, u.phone AS user_phone
        FROM reviews r
        LEFT JOIN shops s ON r.shop_id = s.id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $raw = db()->getAllReviews();
    $users = json_decode(@file_get_contents(FIXNEAR_DATA_DIR . 'users.json'), true) ?: [];
    $userMap = [];
    foreach ($users as $u) $userMap[$u['id']] = $u;
    foreach ($raw as $r) {
        $uInfo = $userMap[$r['user_id'] ?? 0] ?? [];
        $r['user_email'] = $uInfo['email'] ?? '';
        $r['user_phone'] = $uInfo['phone'] ?? '';
        $r['shop_address'] = '';
        $r['shop_district'] = '';
        $allReviews[] = $r;
    }
}

// Tính số liệu thống kê toàn hệ thống
$totalPending = 0;
$totalApproved = 0;
$totalReplied = 0;
$ratingSum = 0;
$totalReviews = count($allReviews);

foreach ($allReviews as $r) {
    if (!empty($r['is_hidden'])) {
        $totalPending++;
    } else {
        $totalApproved++;
    }
    if (!empty($r['admin_reply'])) {
        $totalReplied++;
    }
    $ratingSum += (int)($r['rating'] ?? 5);
}
$avgRating = $totalReviews > 0 ? number_format($ratingSum / $totalReviews, 1) : '5.0';

// Đọc tham số lọc & tìm kiếm
$filterStatus = trim($_GET['status'] ?? $_GET['filter'] ?? 'all'); // 'all', 'pending', 'approved'
$filterRating = max(0, (int)($_GET['rating'] ?? 0));
$filterShop = max(0, (int)($_GET['shop_id'] ?? 0));
$searchQuery = trim($_GET['q'] ?? '');
$selectedReviewId = (int)($_GET['detail'] ?? 0);

// Lọc dữ liệu danh sách
$filteredReviews = array_filter($allReviews, function($r) use ($filterStatus, $filterRating, $filterShop, $searchQuery) {
    // Trạng thái
    if ($filterStatus === 'pending' && empty($r['is_hidden'])) return false;
    if ($filterStatus === 'approved' && !empty($r['is_hidden'])) return false;

    // Số sao
    if ($filterRating > 0 && (int)$r['rating'] !== $filterRating) return false;

    // Cửa hàng
    if ($filterShop > 0 && (int)$r['shop_id'] !== $filterShop) return false;

    // Tìm kiếm từ khóa
    if ($searchQuery !== '') {
        $kw = mb_strtolower($searchQuery, 'UTF-8');
        $uName = mb_strtolower($r['user_name'] ?? '', 'UTF-8');
        $sName = mb_strtolower($r['shop_name'] ?? '', 'UTF-8');
        $comment = mb_strtolower($r['comment'] ?? '', 'UTF-8');
        $dev = mb_strtolower($r['device_name'] ?? '', 'UTF-8');
        $srv = mb_strtolower($r['service_repaired'] ?? '', 'UTF-8');
        if (!str_contains($uName, $kw) && !str_contains($sName, $kw) && !str_contains($comment, $kw) && !str_contains($dev, $kw) && !str_contains($srv, $kw)) {
            return false;
        }
    }

    return true;
});
$filteredReviews = array_values($filteredReviews);

// Cấu hình phân trang cho danh sách đánh giá
$rvPage = max(1, (int)($_GET['page'] ?? 1));
$rvPageSize = 8; // 8 đánh giá trên 1 trang cân đối tuyệt đẹp với panel chi tiết
$rvTotalCount = count($filteredReviews);
$rvPageCount = max(1, (int)ceil($rvTotalCount / $rvPageSize));
$rvPage = min($rvPageCount, max(1, $rvPage));
$rvOffset = ($rvPage - 1) * $rvPageSize;
$pagedReviews = array_slice($filteredReviews, $rvOffset, $rvPageSize);

// Tham số lọc cơ bản để giữ nguyên ngữ cảnh khi bấm chuyển trang
$baseFilterParams = array_filter([
    'status' => $filterStatus !== 'all' ? $filterStatus : '',
    'rating' => $filterRating > 0 ? $filterRating : '',
    'shop_id' => $filterShop > 0 ? $filterShop : '',
    'q' => $searchQuery,
    'detail' => $selectedReviewId > 0 ? $selectedReviewId : ''
], fn($v) => $v !== '');

// Xác định mục đánh giá được chọn để hiển thị chi tiết ở cột phải
$selectedReview = null;
if ($selectedReviewId > 0) {
    foreach ($allReviews as $r) {
        if ((int)$r['id'] === $selectedReviewId) {
            $selectedReview = $r;
            break;
        }
    }
}
if (!$selectedReview && !empty($pagedReviews)) {
    $selectedReview = $pagedReviews[0];
    $selectedReviewId = (int)$selectedReview['id'];
}

$pageTitle = "Kiểm Duyệt & Quản Lý Đánh Giá — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
/* ====== GIAO DIỆN KIỂM DUYỆT ĐÁNH GIÁ (MASTER-DETAIL LAYOUT) ====== */
.rv-container {
    max-width: 1380px;
    margin: 0 auto;
}
.rv-page-head {
    margin-bottom: 22px;
}
.rv-page-head h1 {
    font-family: var(--fn-font-heading);
    font-size: 26px;
    font-weight: 900;
    color: var(--fn-dark);
    margin: 0 0 6px;
    letter-spacing: -0.02em;
}
.rv-page-head p {
    font-size: 13.5px;
    color: var(--fn-dark-muted);
    margin: 0;
}

/* THẺ THỐNG KÊ (STATS CARDS) */
.rv-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.rv-stat-card {
    background: var(--fn-surface);
    border: 1px solid var(--fn-border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    color: inherit;
    box-shadow: var(--fn-shadow-sm);
    transition: transform .15s, border-color .15s, box-shadow .15s;
}
.rv-stat-card:hover {
    transform: translateY(-2px);
    border-color: #ea580c;
    box-shadow: 0 6px 18px rgba(234, 88, 12, .08);
}
.rv-stat-card.active {
    border-color: #ea580c;
    background: #fffaf7;
}
.rv-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.rv-stat-icon.yellow { background: #fef3c7; color: #b45309; }
.rv-stat-icon.green  { background: #dcfce7; color: #15803d; }
.rv-stat-icon.blue   { background: #eff6ff; color: #2563eb; }
.rv-stat-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 2px;
}
.rv-stat-value {
    font-size: 26px;
    font-weight: 900;
    color: var(--fn-dark);
    line-height: 1.1;
}

/* THÔNG BÁO FLASH MESSAGE */
.rv-alert-toast {
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.rv-alert-toast.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.rv-alert-toast.danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* BỐ CỤC 2 CỘT CHUYÊN NGHIỆP: MASTER LIST TRÁI - DETAIL PHẢI */
.rv-layout-2col {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 460px;
    gap: 22px;
    align-items: start;
}

/* CỘT TRÁI: MASTER LIST */
.rv-master-panel {
    background: var(--fn-surface);
    border: 1px solid var(--fn-border);
    border-radius: 14px;
    box-shadow: var(--fn-shadow-sm);
    overflow: hidden;
    min-width: 0;
}

/* THANH TÌM KIẾM VÀ BỘ LỌC */
.rv-filter-bar {
    padding: 14px 16px;
    background: #fafaf9;
    border-bottom: 1px solid var(--fn-border);
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.rv-filter-inputs-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 140px 130px;
    gap: 8px;
}
.rv-search-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.rv-search-input-wrap svg {
    position: absolute;
    left: 11px;
    width: 16px;
    height: 16px;
    color: #94a3b8;
    pointer-events: none;
}
.rv-search-input {
    width: 100%;
    height: 38px;
    padding: 0 12px 0 34px;
    border: 1px solid var(--fn-border);
    border-radius: 8px;
    font-size: 12.5px;
    background: #fff;
    color: var(--fn-dark);
    box-sizing: border-box;
    outline: none;
    transition: border-color .15s;
}
.rv-search-input:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 2px rgba(234, 88, 12, .12);
}
.rv-select {
    height: 38px;
    padding: 0 26px 0 10px;
    border: 1px solid var(--fn-border);
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--fn-dark);
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 8px center / 12px;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s;
}
.rv-select:focus {
    border-color: #ea580c;
}
.rv-filter-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.rv-filter-footer strong {
    color: var(--fn-dark);
    font-weight: 800;
}
.rv-btn-reset-clean {
    color: #ea580c;
    text-decoration: none;
    font-size: 11.5px;
    font-weight: 700;
    background: #fff7ed;
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid #fed7aa;
}

/* DANH SÁCH MỤC ĐÁNH GIÁ (LIST ITEMS) */
.rv-list-container {
    max-height: 820px;
    overflow-y: auto;
}
.rv-item-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 15px 18px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background .12s, border-left .12s;
    user-select: none;
    position: relative;
}
.rv-item-card:hover {
    background: #fafaf9;
}
.rv-item-card.active {
    background: #fffaf7;
    border-left: 4px solid #ea580c;
}
.rv-item-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #475569;
    font-size: 13.5px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}
.rv-item-card.active .rv-item-avatar {
    background: #ea580c;
    color: #fff;
    border-color: #ea580c;
}
.rv-item-main {
    flex: 1;
    min-width: 0;
}
.rv-item-user-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 2px;
}
.rv-item-user-name {
    font-size: 14px;
    font-weight: 800;
    color: var(--fn-dark);
}
.rv-item-shop-name {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.rv-item-rating-row {
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.rv-item-comment-snippet {
    font-size: 12.5px;
    color: #475569;
    line-height: 1.45;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rv-item-side {
    text-align: right;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}
.rv-item-date {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 700;
}
.rv-item-status-badge {
    font-size: 10.5px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    white-space: nowrap;
}
.rv-item-status-badge.pending  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
/* PHÂN TRANG DANH SÁCH ĐÁNH GIÁ */
.rv-pagination {
    padding: 12px 16px;
    background: #fafaf9;
    border-top: 1px solid var(--fn-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.rv-page-info {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.rv-page-info strong {
    color: var(--fn-dark);
    font-weight: 800;
}
.rv-page-nav-btns {
    display: flex;
    align-items: center;
    gap: 4px;
}
.rv-page-btn {
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    border: 1px solid var(--fn-border);
    border-radius: 7px;
    background: #fff;
    color: var(--fn-dark);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all .12s;
}
.rv-page-btn:hover {
    border-color: #ea580c;
    color: #ea580c;
    background: #fff7ed;
}
.rv-page-btn.active {
    background: #ea580c;
    border-color: #ea580c;
    color: #fff;
}

/* CỘT PHẢI: DETAIL PANEL */
.rv-detail-panel {
    min-width: 0;
}
.rv-detail-card {
    background: var(--fn-surface);
    border: 1px solid var(--fn-border);
    border-radius: 14px;
    box-shadow: var(--fn-shadow-sm);
    padding: 22px;
    position: sticky;
    top: 76px;
}

/* HEADER CỦA DETAIL CARD */
.rv-detail-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}
.rv-detail-head-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.rv-detail-avatar-lg {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #ffedd5;
    color: #c2410c;
    font-size: 16px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #fed7aa;
}
.rv-detail-user-name {
    font-size: 16px;
    font-weight: 900;
    color: var(--fn-dark);
    line-height: 1.25;
}
.rv-detail-shop-link a {
    font-size: 12.5px;
    font-weight: 700;
    color: #0284c7;
    text-decoration: none;
}
.rv-detail-shop-link a:hover {
    text-decoration: underline;
}
.rv-detail-service-tag {
    margin-top: 4px;
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 7px;
    border-radius: 5px;
}
.rv-detail-head-right {
    text-align: right;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}
.rv-detail-date {
    font-size: 11.5px;
    color: #94a3b8;
    font-weight: 700;
}
.rv-detail-badge {
    font-size: 11px;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: 6px;
}
.rv-detail-badge.pending  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.rv-detail-badge.approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

.rv-detail-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 18px 0;
}

/* KHỐI NỘI DUNG ĐÁNH GIÁ */
.rv-section-title {
    font-size: 12px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.rv-detail-stars-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.rv-rating-score {
    font-size: 13px;
    font-weight: 800;
    color: #f59e0b;
}
.rv-detail-comment-box {
    background: #fafaf9;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: #334155;
    line-height: 1.6;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal !important;
}

/* 2 CỘT THÔNG TIN KHÁCH HÀNG & CỬA HÀNG */
.rv-info-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.rv-info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
}
.rv-info-box-title {
    font-size: 11.5px;
    font-weight: 800;
    color: #475569;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.rv-info-item {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    font-size: 12px;
    color: #334155;
    margin-bottom: 6px;
    line-height: 1.4;
    overflow-wrap: break-word;
}
.rv-info-item:last-child { margin-bottom: 0; }
.rv-info-icon { font-size: 13px; flex-shrink: 0; }
.rv-info-text a {
    color: #0284c7;
    text-decoration: none;
    font-weight: 700;
}
.rv-info-text a:hover { text-decoration: underline; }

/* KHỐI PHẢN HỒI BAN QUẢN TRỊ FIXNEAR */
.rv-reply-box {
    margin-top: 4px;
}
.rv-reply-existing-box {
    background: #f0fdf4;
    border-left: 3px solid #16a34a;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 12px;
    font-size: 12.5px;
    color: #166534;
}
.rv-reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}
.rv-reply-time {
    font-size: 11px;
    color: #64748b;
}
.rv-reply-text {
    line-height: 1.45;
    overflow-wrap: break-word;
}
.rv-reply-textarea {
    width: 100%;
    border: 1px solid var(--fn-border);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 12.5px;
    color: var(--fn-dark);
    box-sizing: border-box;
    outline: none;
    resize: vertical;
    font-family: inherit;
    transition: border-color .15s;
}
.rv-reply-textarea:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 2px rgba(234, 88, 12, .12);
}
.rv-btn-reply-submit {
    margin-top: 6px;
    height: 32px;
    padding: 0 12px;
    background: #0284c7;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background .12s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.rv-btn-reply-submit:hover { background: #0369a1; }

/* CỤM NÚT HÀNH ĐỘNG DƯỚI CÙNG */
.rv-detail-actions {
    display: flex;
    gap: 10px;
}
.rv-btn-action {
    flex: 1;
    height: 40px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all .12s;
    border: none;
}
.rv-btn-action.approve {
    background: #ea580c;
    color: #fff;
}
.rv-btn-action.approve:hover { background: #c2410c; }
.rv-btn-action.hide {
    background: #fff;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.rv-btn-action.hide:hover { background: #f1f5f9; }
.rv-btn-action.delete {
    flex: 0 0 90px;
    background: #fff;
    color: #dc2626;
    border: 1px solid #fca5a5;
}
.rv-btn-action.delete:hover { background: #fee2e2; border-color: #ef4444; }

@media (max-width: 1050px) {
    .rv-layout-2col {
        grid-template-columns: 1fr;
    }
    .rv-detail-card {
        position: static;
        margin-top: 20px;
    }
    .rv-stats-row {
        grid-template-columns: 1fr;
    }
    .rv-filter-inputs-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content rv-container">

        <!-- TIÊU ĐỀ TRANG -->
        <div class="rv-page-head">
            <h1>Kiểm duyệt đánh giá</h1>
            <p>Xem và quản lý các đánh giá từ khách hàng về cửa hàng và dịch vụ trên FixNear.</p>
        </div>

        <!-- THÔNG BÁO FLASH MESSAGE -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'toggled'): ?>
            <div class="rv-alert-toast success">✓ Đã cập nhật trạng thái hiển thị của đánh giá thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="rv-alert-toast danger">✓ Đã xóa vĩnh viễn đánh giá khỏi hệ thống!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'replied'): ?>
            <div class="rv-alert-toast success">🛡️ Đã cập nhật phản hồi chính thức từ Ban Quản Trị thành công!</div>
        <?php endif; ?>

        <!-- 3 CARDS THỐNG KÊ NHƯ HÌNH MẪU -->
        <div class="rv-stats-row">
            <!-- Card 1: Chờ duyệt -->
            <a href="reviews.php?status=pending" class="rv-stat-card<?= $filterStatus === 'pending' ? ' active' : '' ?>">
                <div class="rv-stat-icon yellow">🕒</div>
                <div>
                    <div class="rv-stat-label">Chờ duyệt</div>
                    <div class="rv-stat-value"><?= number_format($totalPending) ?></div>
                </div>
            </a>

            <!-- Card 2: Hiển thị -->
            <a href="reviews.php?status=approved" class="rv-stat-card<?= $filterStatus === 'approved' ? ' active' : '' ?>">
                <div class="rv-stat-icon green">✓</div>
                <div>
                    <div class="rv-stat-label">Hiển thị</div>
                    <div class="rv-stat-value"><?= number_format($totalApproved) ?></div>
                </div>
            </a>

            <!-- Card 3: Điểm đánh giá trung bình -->
            <a href="reviews.php?status=all" class="rv-stat-card<?= $filterStatus === 'all' ? ' active' : '' ?>">
                <div class="rv-stat-icon blue">⭐</div>
                <div>
                    <div class="rv-stat-label">Điểm trung bình</div>
                    <div class="rv-stat-value"><?= $avgRating ?>★ <span style="font-size:13px; font-weight:700; color:#64748b;">(<?= $totalReviews ?> lượt)</span></div>
                </div>
            </a>
        </div>

        <!-- BỐ CỤC 2 CỘT MASTER-DETAIL -->
        <div class="rv-layout-2col">

            <!-- CỘT TRÁI: MASTER LIST -->
            <div class="rv-master-panel">

                <!-- THANH TÌM KIẾM VÀ BỘ LỌC -->
                <form method="GET" action="reviews.php" class="rv-filter-bar">
                    <div class="rv-filter-inputs-row">
                        <!-- Ô tìm kiếm -->
                        <div class="rv-search-input-wrap">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/>
                            </svg>
                            <input type="text" name="q" class="rv-search-input"
                                   placeholder="Tìm theo tên, nội dung hoặc cửa hàng..."
                                   value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
                        </div>

                        <!-- Lọc trạng thái -->
                        <select name="status" class="rv-select" onchange="this.form.submit()">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Hiển thị</option>
                        </select>

                        <!-- Lọc số sao -->
                        <select name="rating" class="rv-select" onchange="this.form.submit()">
                            <option value="0" <?= $filterRating === 0 ? 'selected' : '' ?>>Tất cả số sao</option>
                            <option value="5" <?= $filterRating === 5 ? 'selected' : '' ?>>5 ⭐⭐⭐⭐⭐</option>
                            <option value="4" <?= $filterRating === 4 ? 'selected' : '' ?>>4 ⭐⭐⭐⭐</option>
                            <option value="3" <?= $filterRating === 3 ? 'selected' : '' ?>>3 ⭐⭐⭐</option>
                            <option value="2" <?= $filterRating === 2 ? 'selected' : '' ?>>2 ⭐⭐</option>
                            <option value="1" <?= $filterRating === 1 ? 'selected' : '' ?>>1 ⭐</option>
                        </select>
                    </div>

                    <div class="rv-filter-footer">
                        <div>
                            Tìm thấy <strong><?= number_format($rvTotalCount) ?></strong> đánh giá
                            <?php if ($filterStatus === 'pending'): ?>(đang chờ duyệt)<?php endif; ?>
                            <?php if ($rvPageCount > 1): ?>&nbsp;·&nbsp;Trang <?= $rvPage ?>/<?= $rvPageCount ?><?php endif; ?>
                        </div>
                        <?php if ($searchQuery !== '' || $filterStatus !== 'all' || $filterRating > 0 || $filterShop > 0): ?>
                            <a href="reviews.php" class="rv-btn-reset-clean">✕ Bỏ lọc</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- DANH SÁCH MỤC ĐÁNH GIÁ (CLICK SẼ CẬP NHẬT PANEL PHẢI) -->
                <div class="rv-list-container">
                    <?php if (empty($pagedReviews)): ?>
                        <div style="padding: 50px 20px; text-align: center; color: var(--fn-dark-muted);">
                            <div style="font-size: 38px; margin-bottom: 8px;">🔍</div>
                            <div style="font-size: 14px; font-weight: 800; color: var(--fn-dark);">Không tìm thấy đánh giá nào</div>
                            <div style="font-size: 12.5px; margin-top: 4px;">Vui lòng thử từ khóa khác hoặc bấm nút "Bỏ lọc".</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pagedReviews as $idx => $r):
                            $isActive = ($selectedReview && (int)$r['id'] === (int)$selectedReview['id']);
                            $initials = fnGetInitials($r['user_name']);
                            $dateStr = date('d/m/Y', strtotime($r['created_at']));
                            $statusText = !empty($r['is_hidden']) ? 'Chờ duyệt' : 'Hiển thị';
                            $statusClass = !empty($r['is_hidden']) ? 'pending' : 'approved';
                        ?>
                            <div class="rv-item-card<?= $isActive ? ' active' : '' ?>"
                                 data-review-id="<?= $r['id'] ?>"
                                 onclick="selectReview(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>, this)">
                                <!-- Avatar viết tắt 2 chữ cái -->
                                <div class="rv-item-avatar"><?= $initials ?></div>

                                <!-- Cột giữa: Tên khách, tên tiệm, số sao & nội dung -->
                                <div class="rv-item-main">
                                    <div class="rv-item-user-row">
                                        <span class="rv-item-user-name"><?= htmlspecialchars($r['user_name']) ?></span>
                                        <span class="rv-item-shop-name">· <?= htmlspecialchars($r['shop_name']) ?></span>
                                    </div>
                                    <div class="rv-item-rating-row">
                                        <?= fnRenderStars($r['rating']) ?>
                                    </div>
                                    <div class="rv-item-comment-snippet" title="<?= htmlspecialchars($r['comment']) ?>">
                                        <?= htmlspecialchars($r['comment']) ?>
                                    </div>
                                </div>

                                <!-- Cột phải: Ngày tháng & Badge trạng thái -->
                                <div class="rv-item-side">
                                    <span class="rv-item-date"><?= $dateStr ?></span>
                                    <span class="rv-item-status-badge <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- THANH PHÂN TRANG KHI CÓ NHIỀU ĐÁNH GIÁ -->
                <?php if ($rvPageCount > 1): ?>
                    <div class="rv-pagination">
                        <div class="rv-page-info">
                            Trang <strong><?= $rvPage ?></strong> / <?= $rvPageCount ?> &nbsp;·&nbsp; <?= number_format($rvTotalCount) ?> đánh giá
                        </div>
                        <div class="rv-page-nav-btns">
                            <?php if ($rvPage > 1): ?>
                                <a class="rv-page-btn" href="reviews.php?<?= htmlspecialchars(http_build_query(array_merge($baseFilterParams, ['page' => $rvPage - 1]))) ?>">
                                    ← Trước
                                </a>
                            <?php endif; ?>

                            <?php
                            $startP = max(1, $rvPage - 2);
                            $endP = min($rvPageCount, $rvPage + 2);
                            for ($p = $startP; $p <= $endP; $p++):
                            ?>
                                <a class="rv-page-btn<?= $p === $rvPage ? ' active' : '' ?>"
                                   href="reviews.php?<?= htmlspecialchars(http_build_query(array_merge($baseFilterParams, ['page' => $p]))) ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($rvPage < $rvPageCount): ?>
                                <a class="rv-page-btn" href="reviews.php?<?= htmlspecialchars(http_build_query(array_merge($baseFilterParams, ['page' => $rvPage + 1]))) ?>">
                                    Sau →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- CỘT PHẢI: DETAIL PANEL (CẬP NHẬT TỨC THÌ KHI CLICK) -->
            <div class="rv-detail-panel">
                <?php if ($selectedReview):
                    $selInitials = fnGetInitials($selectedReview['user_name']);
                    $selDate = date('d/m/Y H:i', strtotime($selectedReview['created_at']));
                    $selStatusText = !empty($selectedReview['is_hidden']) ? 'Chờ duyệt' : 'Hiển thị';
                    $selStatusClass = !empty($selectedReview['is_hidden']) ? 'pending' : 'approved';
                    $serviceLabel = !empty($selectedReview['service_repaired'])
                        ? ('Đã sửa: ' . $selectedReview['service_repaired'])
                        : (!empty($selectedReview['device_name']) ? ('Thiết bị: ' . $selectedReview['device_name']) : 'Sửa chữa thiết bị');
                ?>
                    <div class="rv-detail-card" id="rv-detail-card">

                        <!-- Header thông tin chính -->
                        <div class="rv-detail-head">
                            <div class="rv-detail-head-left">
                                <div class="rv-detail-avatar-lg" id="d-avatar"><?= $selInitials ?></div>
                                <div style="min-width:0;">
                                    <div class="rv-detail-user-name" id="d-user-name"><?= htmlspecialchars($selectedReview['user_name']) ?></div>
                                    <div class="rv-detail-shop-link">
                                        <a href="../shop_detail.php?id=<?= (int)$selectedReview['shop_id'] ?>" target="_blank" id="d-shop-link">
                                            <span id="d-shop-name"><?= htmlspecialchars($selectedReview['shop_name']) ?></span> ↗
                                        </a>
                                    </div>
                                    <div class="rv-detail-service-tag" id="d-service-tag">
                                        🛠️ <span id="d-service-text"><?= htmlspecialchars($serviceLabel) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="rv-detail-head-right">
                                <div class="rv-detail-date" id="d-date"><?= $selDate ?></div>
                                <div class="rv-detail-badge <?= $selStatusClass ?>" id="d-badge">
                                    <?= $selStatusText ?>
                                </div>
                            </div>
                        </div>

                        <div class="rv-detail-divider"></div>

                        <!-- Khối Đánh giá: Sao to + Nhận xét đầy đủ (Không bao giờ bị đè chữ!) -->
                        <div class="rv-detail-section">
                            <div class="rv-section-title">Đánh giá</div>
                            <div class="rv-detail-stars-wrap">
                                <div id="d-stars"><?= fnRenderStars($selectedReview['rating']) ?></div>
                                <span class="rv-rating-score" id="d-score"><?= (int)$selectedReview['rating'] ?>.0 / 5.0</span>
                            </div>
                            <div class="rv-detail-comment-box" id="d-comment">
                                <?= nl2br(htmlspecialchars($selectedReview['comment'])) ?>
                            </div>
                        </div>

                        <div class="rv-detail-divider"></div>

                        <!-- 2 Cột: Thông tin khách hàng & Thông tin cửa hàng -->
                        <div class="rv-info-2col">
                            <!-- Khách hàng -->
                            <div class="rv-info-box">
                                <div class="rv-info-box-title">Thông tin khách hàng</div>
                                <div class="rv-info-item">
                                    <span class="rv-info-icon">👤</span>
                                    <span class="rv-info-text" id="d-cust-name"><?= htmlspecialchars($selectedReview['user_name']) ?></span>
                                </div>
                                <div class="rv-info-item">
                                    <span class="rv-info-icon">📞</span>
                                    <span class="rv-info-text" id="d-cust-phone">
                                        <?php if (!empty($selectedReview['user_phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars($selectedReview['user_phone']) ?>"><?= htmlspecialchars($selectedReview['user_phone']) ?></a>
                                        <?php else: ?>
                                            <em>Chưa có SĐT</em>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="rv-info-item">
                                    <span class="rv-info-icon">✉️</span>
                                    <span class="rv-info-text" id="d-cust-email">
                                        <?php if (!empty($selectedReview['user_email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($selectedReview['user_email']) ?>"><?= htmlspecialchars($selectedReview['user_email']) ?></a>
                                        <?php else: ?>
                                            <em>Chưa có email</em>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Cửa hàng -->
                            <div class="rv-info-box">
                                <div class="rv-info-box-title">Thông tin cửa hàng</div>
                                <div class="rv-info-item">
                                    <span class="rv-info-icon">🏪</span>
                                    <span class="rv-info-text" id="d-shop-info-name"><strong><?= htmlspecialchars($selectedReview['shop_name']) ?></strong></span>
                                </div>
                                <div class="rv-info-item">
                                    <span class="rv-info-icon">📍</span>
                                    <span class="rv-info-text" id="d-shop-address"><?= htmlspecialchars($selectedReview['shop_district'] ?: ($selectedReview['shop_address'] ?: 'TP.HCM')) ?></span>
                                </div>
                                <div class="rv-info-item">
                                    <span class="rv-info-icon">📱</span>
                                    <span class="rv-info-text" id="d-shop-device"><?= htmlspecialchars($selectedReview['device_name'] ?: 'Thiết bị điện tử') ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="rv-detail-divider"></div>

                        <!-- Khối Phản hồi Ban Quản Trị FixNear -->
                        <div class="rv-reply-box">
                            <div class="rv-section-title">Phản hồi Ban Quản Trị FixNear</div>

                            <!-- Hộp phản hồi đã có -->
                            <div id="d-reply-display" class="rv-reply-existing-box" style="<?= empty($selectedReview['admin_reply']) ? 'display:none;' : '' ?>">
                                <div class="rv-reply-header">
                                    <strong>🛡️ Phản hồi công khai từ BQT:</strong>
                                    <span class="rv-reply-time" id="d-reply-time">
                                        <?= !empty($selectedReview['admin_reply_at']) ? date('d/m/Y H:i', strtotime($selectedReview['admin_reply_at'])) : '' ?>
                                    </span>
                                </div>
                                <div class="rv-reply-text" id="d-reply-text">
                                    <?= htmlspecialchars($selectedReview['admin_reply'] ?? '') ?>
                                </div>
                            </div>

                            <!-- Form gửi / cập nhật phản hồi -->
                            <form action="reviews.php" method="POST" id="d-reply-form" class="rv-reply-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="reply_review">
                                <input type="hidden" name="review_id" id="d-form-reply-id" value="<?= $selectedReview['id'] ?>">
                                <textarea name="reply_text" id="d-reply-input" class="rv-reply-textarea" rows="2"
                                          placeholder="Nhập câu trả lời chính thức của Ban Quản Trị FixNear..." required><?= htmlspecialchars($selectedReview['admin_reply'] ?? '') ?></textarea>
                                <button type="submit" id="d-btn-reply" class="rv-btn-reply-submit">
                                    💬 <?= !empty($selectedReview['admin_reply']) ? 'Cập nhật phản hồi BQT' : 'Gửi phản hồi BQT công khai' ?>
                                </button>
                            </form>
                        </div>

                        <div class="rv-detail-divider"></div>

                        <!-- Cụm nút hành động dưới cùng -->
                        <div class="rv-detail-actions">
                            <!-- Nút Duyệt / Ẩn -->
                            <form action="reviews.php" method="POST" style="margin:0; flex:1;" id="d-toggle-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_review">
                                <input type="hidden" name="review_id" id="d-form-toggle-id" value="<?= $selectedReview['id'] ?>">
                                <button type="submit" id="d-btn-toggle" class="rv-btn-action <?= !empty($selectedReview['is_hidden']) ? 'approve' : 'hide' ?>" style="width:100%;">
                                    <?= !empty($selectedReview['is_hidden']) ? '✓ Duyệt hiển thị' : '👁️ Ẩn đánh giá' ?>
                                </button>
                            </form>

                            <!-- Nút Xóa vĩnh viễn -->
                            <form action="reviews.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đánh giá này?');" style="margin:0;" id="d-delete-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" id="d-form-delete-id" value="<?= $selectedReview['id'] ?>">
                                <button type="submit" class="rv-btn-action delete">
                                    🗑️ Xóa
                                </button>
                            </form>
                        </div>

                    </div>
                <?php else: ?>
                    <div style="background:#fff; border:1px solid var(--fn-border); border-radius:14px; padding:40px; text-align:center; color:#64748b;">
                        <div style="font-size:38px; margin-bottom:10px;">⭐</div>
                        <div style="font-size:15px; font-weight:800; color:var(--fn-dark);">Chưa chọn đánh giá nào</div>
                        <div style="font-size:13px; margin-top:4px;">Bấm vào một đánh giá ở danh sách bên trái để xem chi tiết và kiểm duyệt.</div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<script>
// Hàm sinh 2 chữ cái viết tắt của tên
function jsGetInitials(name) {
    if (!name) return 'KH';
    const words = name.trim().split(/\s+/);
    if (words.length >= 2) {
        return (words[words.length - 2].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
}

// Hàm sinh HTML sao
function jsRenderStars(rating) {
    const r = Math.max(1, Math.min(5, parseInt(rating, 10) || 5));
    const filled = '★'.repeat(r);
    const empty = '☆'.repeat(5 - r);
    return `<span style="color:#f59e0b; font-size:14px; letter-spacing:1.5px;">${filled}</span><span style="color:#cbd5e1; font-size:14px; letter-spacing:1.5px;">${empty}</span>`;
}

// Định dạng ngày giờ dạng dd/mm/yyyy hh:mm
function jsFormatDateTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${mins}`;
}

// Xử lý khi Admin bấm chọn 1 review ở cột trái (Cập nhật tức thì)
function selectReview(data, cardEl) {
    if (!data) return;

    // 1. Cập nhật trạng thái active ở cột trái
    document.querySelectorAll('.rv-item-card').forEach(c => c.classList.remove('active'));
    if (cardEl) cardEl.classList.add('active');

    // 2. Cập nhật thông tin Header
    const dAvatar = document.getElementById('d-avatar');
    if (dAvatar) dAvatar.textContent = jsGetInitials(data.user_name);

    const dUserName = document.getElementById('d-user-name');
    if (dUserName) dUserName.textContent = data.user_name || 'Khách vãng lai';

    const dShopName = document.getElementById('d-shop-name');
    if (dShopName) dShopName.textContent = data.shop_name || 'Cửa hàng';

    const dShopLink = document.getElementById('d-shop-link');
    if (dShopLink) dShopLink.href = '../shop_detail.php?id=' + (data.shop_id || 0);

    const dServiceText = document.getElementById('d-service-text');
    if (dServiceText) {
        if (data.service_repaired) {
            dServiceText.textContent = 'Đã sửa: ' + data.service_repaired;
        } else if (data.device_name) {
            dServiceText.textContent = 'Thiết bị: ' + data.device_name;
        } else {
            dServiceText.textContent = 'Sửa chữa thiết bị';
        }
    }

    const dDate = document.getElementById('d-date');
    if (dDate) dDate.textContent = jsFormatDateTime(data.created_at);

    const isHidden = (parseInt(data.is_hidden, 10) === 1);
    const dBadge = document.getElementById('d-badge');
    if (dBadge) {
        dBadge.className = 'rv-detail-badge ' + (isHidden ? 'pending' : 'approved');
        dBadge.textContent = isHidden ? 'Chờ duyệt' : 'Hiển thị';
    }

    // 3. Cập nhật Đánh giá & Nhận xét (Khắc phục triệt để lỗi đè chữ)
    const dStars = document.getElementById('d-stars');
    if (dStars) dStars.innerHTML = jsRenderStars(data.rating);

    const dScore = document.getElementById('d-score');
    if (dScore) dScore.textContent = (data.rating || 5) + '.0 / 5.0';

    const dComment = document.getElementById('d-comment');
    if (dComment) dComment.textContent = data.comment || '';

    // 4. Cập nhật 2 Cột thông tin
    const dCustName = document.getElementById('d-cust-name');
    if (dCustName) dCustName.textContent = data.user_name || 'Khách vãng lai';

    const dCustPhone = document.getElementById('d-cust-phone');
    if (dCustPhone) {
        if (data.user_phone) {
            dCustPhone.innerHTML = `<a href="tel:${data.user_phone}">${data.user_phone}</a>`;
        } else {
            dCustPhone.innerHTML = '<em>Chưa có SĐT</em>';
        }
    }

    const dCustEmail = document.getElementById('d-cust-email');
    if (dCustEmail) {
        if (data.user_email) {
            dCustEmail.innerHTML = `<a href="mailto:${data.user_email}">${data.user_email}</a>`;
        } else {
            dCustEmail.innerHTML = '<em>Chưa có email</em>';
        }
    }

    const dShopInfoName = document.getElementById('d-shop-info-name');
    if (dShopInfoName) dShopInfoName.innerHTML = `<strong>${data.shop_name || 'Cửa hàng'}</strong>`;

    const dShopAddress = document.getElementById('d-shop-address');
    if (dShopAddress) dShopAddress.textContent = data.shop_district || data.shop_address || 'TP.HCM';

    const dShopDevice = document.getElementById('d-shop-device');
    if (dShopDevice) dShopDevice.textContent = data.device_name || 'Thiết bị điện tử';

    // 5. Cập nhật Khối phản hồi BQT
    const dReplyDisplay = document.getElementById('d-reply-display');
    const dReplyText = document.getElementById('d-reply-text');
    const dReplyTime = document.getElementById('d-reply-time');
    const dReplyInput = document.getElementById('d-reply-input');
    const dBtnReply = document.getElementById('d-btn-reply');

    if (data.admin_reply) {
        if (dReplyDisplay) dReplyDisplay.style.display = 'block';
        if (dReplyText) dReplyText.textContent = data.admin_reply;
        if (dReplyTime) dReplyTime.textContent = jsFormatDateTime(data.admin_reply_at);
        if (dReplyInput) dReplyInput.value = data.admin_reply;
        if (dBtnReply) dBtnReply.innerHTML = '💬 Cập nhật phản hồi BQT';
    } else {
        if (dReplyDisplay) dReplyDisplay.style.display = 'none';
        if (dReplyInput) dReplyInput.value = '';
        if (dBtnReply) dBtnReply.innerHTML = '💬 Gửi phản hồi BQT công khai';
    }

    // 6. Cập nhật Form Action IDs & Nút bấm
    const dFormReplyId = document.getElementById('d-form-reply-id');
    if (dFormReplyId) dFormReplyId.value = data.id;

    const dFormToggleId = document.getElementById('d-form-toggle-id');
    if (dFormToggleId) dFormToggleId.value = data.id;

    const dFormDeleteId = document.getElementById('d-form-delete-id');
    if (dFormDeleteId) dFormDeleteId.value = data.id;

    const dBtnToggle = document.getElementById('d-btn-toggle');
    if (dBtnToggle) {
        if (isHidden) {
            dBtnToggle.className = 'rv-btn-action approve';
            dBtnToggle.innerHTML = '✓ Duyệt hiển thị';
        } else {
            dBtnToggle.className = 'rv-btn-action hide';
            dBtnToggle.innerHTML = '👁️ Ẩn đánh giá';
        }
    }

    // Cập nhật URL trình duyệt không cần reload
    try {
        const url = new URL(window.location);
        url.searchParams.set('detail', data.id);
        window.history.replaceState({}, '', url);
    } catch(e) {}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
