<?php
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header("Location: ../login.php?redirect=admin/contacts.php");
    exit;
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

// XỬ LÝ ACTIONS (POST CÓ CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireValidCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'reply_contact' && $id > 0) {
        $replyText = trim($_POST['reply_text'] ?? '');
        if ($replyText !== '') {
            db()->replyContactMessage($id, $replyText);
            header("Location: contacts.php?msg=replied");
            exit;
        }
    } elseif ($action === 'update_status' && $id > 0) {
        $st = in_array($_POST['status'] ?? '', ['pending', 'read', 'replied'], true) ? $_POST['status'] : 'pending';
        db()->updateContactMessageStatus($id, $st);
        header("Location: contacts.php?msg=updated");
        exit;
    } elseif ($action === 'delete' && $id > 0) {
        db()->deleteContactMessage($id);
        header("Location: contacts.php?msg=deleted");
        exit;
    }
}

$allContacts = db()->getContactMessages();

function fnGetContactInitials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'KH';
    $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) >= 2) {
        return mb_strtoupper(
            mb_substr($words[count($words) - 2], 0, 1, 'UTF-8') .
            mb_substr($words[count($words) - 1], 0, 1, 'UTF-8'),
            'UTF-8'
        );
    }
    return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
}

function fnFormatContactCode($id, $createdAt): string {
    $datePart = date('Ymd', strtotime((string)($createdAt ?: 'now')));
    return '#CT-' . $datePart . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

function fnGetSubjectMeta(string $subject): array {
    $s = mb_strtolower($subject, 'UTF-8');
    if (str_contains($s, 'khiếu nại') || str_contains($s, 'góp ý chất lượng')) {
        return [
            'label' => 'Khiếu nại chất lượng tiệm',
            'badge_class' => 'bg-red-50 text-red-700 border-red-200',
            'icon' => '🔴',
        ];
    }
    if (str_contains($s, 'cửa hàng') || str_contains($s, 'tham gia') || str_contains($s, 'mạng lưới')) {
        return [
            'label' => 'Đăng ký tham gia mạng lưới',
            'badge_class' => 'bg-blue-50 text-blue-700 border-blue-200',
            'icon' => '🔵',
        ];
    }
    if (str_contains($s, 'cải tiến') || str_contains($s, 'tính năng')) {
        return [
            'label' => 'Góp ý tính năng website',
            'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'icon' => '🟢',
        ];
    }
    if (str_contains($s, 'hợp tác') || str_contains($s, 'học thuật') || str_contains($s, 'truyền thông')) {
        return [
            'label' => 'Hợp tác truyền thông / Nghiên cứu',
            'badge_class' => 'bg-purple-50 text-purple-700 border-purple-200',
            'icon' => '🟣',
        ];
    }
    return [
        'label' => $subject !== '' ? $subject : 'Liên hệ chung',
        'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
        'icon' => '💬',
    ];
}

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

$filterSubject = trim((string)($_GET['subject'] ?? 'all'));
$filterStatus = trim((string)($_GET['status'] ?? 'all'));
$searchKeyword = trim((string)($_GET['q'] ?? ''));

$filteredContacts = array_values(array_filter($allContacts, static function ($c) use ($filterSubject, $filterStatus, $searchKeyword) {
    $st = $c['status'] ?? 'pending';
    if ($filterStatus !== 'all' && $filterStatus !== '' && $st !== $filterStatus) {
        return false;
    }

    if ($filterSubject !== 'all' && $filterSubject !== '') {
        $subj = (string)($c['subject'] ?? ($c['type'] ?? ''));
        $meta = fnGetSubjectMeta($subj);
        if ($meta['label'] !== $filterSubject) {
            return false;
        }
    }

    if ($searchKeyword !== '') {
        $haystack = implode(' ', [
            $c['id'] ?? '',
            fnFormatContactCode($c['id'] ?? 0, $c['created_at'] ?? ''),
            $c['name'] ?? '',
            $c['phone'] ?? '',
            $c['email'] ?? '',
            $c['subject'] ?? ($c['type'] ?? ''),
            $c['message'] ?? '',
            $c['admin_reply'] ?? '',
        ]);
        if (mb_stripos($haystack, $searchKeyword, 0, 'UTF-8') === false) {
            return false;
        }
    }

    return true;
}));

$totalFiltered = count($filteredContacts);
$perPage = 8;
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['p'] ?? ($_GET['page'] ?? 1))));
$offset = ($page - 1) * $perPage;
$paginatedContacts = array_slice($filteredContacts, $offset, $perPage);

function fnBuildContactPageUrl(array $overrides = []): string {
    $params = [
        'subject' => $_GET['subject'] ?? 'all',
        'status' => $_GET['status'] ?? 'all',
        'q' => $_GET['q'] ?? '',
        'p' => $_GET['p'] ?? 1,
    ];
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    $clean = [];
    foreach ($params as $k => $v) {
        $vStr = trim((string)$v);
        if ($vStr === '' || $vStr === 'all' || ($k === 'p' && (int)$vStr <= 1)) {
            continue;
        }
        $clean[$k] = $vStr;
    }
    $qs = http_build_query($clean);
    return 'contacts.php' . ($qs !== '' ? '?' . $qs : '');
}

$hasActiveFilters = (($filterSubject !== 'all' && $filterSubject !== '') || ($filterStatus !== 'all' && $filterStatus !== '') || $searchKeyword !== '');

$pageTitle = "Hộp thư Liên hệ & Hợp tác — FixNear Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="fn-admin-content !p-6 !bg-[#fffaf5]">
        <!-- HEADER & LIVE CLOCK -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Hộp thư Liên hệ & Hợp tác</h1>
                <p class="text-sm text-slate-500 mt-0.5">Tổng hợp các góp ý, khiếu nại chất lượng tiệm sửa, hoặc đề xuất tham gia mạng lưới của các cửa hàng</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="../contact.php" target="_blank" class="bg-white hover:bg-orange-50 border border-orange-200 rounded-xl px-3.5 py-2 text-xs font-bold text-orange-700 flex items-center gap-1.5 transition shadow-xs">
                    <span>↗️</span>
                    <span>Mở trang form liên hệ</span>
                </a>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 text-xs font-bold text-emerald-800 flex items-center gap-2.5 shadow-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Trực tuyến</span>
                    <span class="text-emerald-300">|</span>
                    <span id="fn-live-clock" class="font-mono font-black text-emerald-950 text-[13px] tracking-wider"><?= date('H:i:s — d/m/Y') ?></span>
                </div>
            </div>
        </div>

        <!-- FLASH MESSAGES -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'replied'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-xl mb-6 flex items-center justify-between">
                    <span>✓ Đã lưu phản hồi của Ban Quản Trị và đánh dấu "Đã phản hồi"!</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold text-base cursor-pointer">&times;</button>
                </div>
            <?php elseif ($_GET['msg'] === 'updated'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-xl mb-6 flex items-center justify-between">
                    <span>✓ Đã cập nhật trạng thái tin nhắn liên hệ thành công!</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold text-base cursor-pointer">&times;</button>
                </div>
            <?php elseif ($_GET['msg'] === 'deleted'): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold px-4 py-3 rounded-xl mb-6 flex items-center justify-between">
                    <span>🗑️ Đã xóa tin nhắn liên hệ khỏi hệ thống!</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800 font-bold text-base cursor-pointer">&times;</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 4 KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <a href="contacts.php" class="bg-white rounded-xl border <?= $filterStatus === 'all' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-orange-100' ?> p-5 shadow-sm hover:border-orange-200 transition block">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-900 leading-none"><?= $totalAll ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Tổng thư liên hệ</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center text-xl">
                        📬
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Toàn bộ tin nhắn từ form liên hệ
                </div>
            </a>

            <a href="contacts.php?status=pending" class="bg-white rounded-xl border <?= $filterStatus === 'pending' ? 'border-orange-400 ring-1 ring-orange-200' : 'border-orange-100' ?> p-5 shadow-sm hover:border-orange-200 transition block">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-orange-600 leading-none"><?= $totalPending ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Chờ xử lý</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl">
                        ⏳
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Cần liên hệ xác minh & phản hồi
                </div>
            </a>

            <a href="contacts.php?status=read" class="bg-white rounded-xl border <?= $filterStatus === 'read' ? 'border-blue-400 ring-1 ring-blue-200' : 'border-orange-100' ?> p-5 shadow-sm hover:border-orange-200 transition block">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-blue-600 leading-none"><?= $totalRead ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Đã xem</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xl">
                        👁️
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Đã tiếp nhận thông tin từ người gửi
                </div>
            </a>

            <a href="contacts.php?status=replied" class="bg-white rounded-xl border <?= $filterStatus === 'replied' ? 'border-emerald-400 ring-1 ring-emerald-200' : 'border-orange-100' ?> p-5 shadow-sm hover:border-orange-200 transition block">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-emerald-600 leading-none"><?= $totalReplied ?></div>
                        <div class="text-xs font-semibold text-slate-500 mt-2">Đã phản hồi</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
                        ✅
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-orange-50 text-xs text-slate-500">
                    Đã giải quyết & lưu kết quả xử lý
                </div>
            </a>
        </div>

        <!-- BỘ LỌC TÌM KIẾM THƯ LIÊN HỆ -->
        <form method="GET" action="contacts.php" class="bg-white rounded-xl border border-orange-100 p-4 mb-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Chủ đề / Phân loại</label>
                    <select name="subject" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="all" <?= $filterSubject === 'all' ? 'selected' : '' ?>>-- Tất cả chủ đề --</option>
                        <option value="Khiếu nại chất lượng tiệm" <?= $filterSubject === 'Khiếu nại chất lượng tiệm' ? 'selected' : '' ?>>🔴 Khiếu nại chất lượng tiệm</option>
                        <option value="Đăng ký tham gia mạng lưới" <?= $filterSubject === 'Đăng ký tham gia mạng lưới' ? 'selected' : '' ?>>🔵 Đăng ký tham gia mạng lưới</option>
                        <option value="Góp ý tính năng website" <?= $filterSubject === 'Góp ý tính năng website' ? 'selected' : '' ?>>🟢 Góp ý tính năng website</option>
                        <option value="Hợp tác truyền thông / Nghiên cứu" <?= $filterSubject === 'Hợp tác truyền thông / Nghiên cứu' ? 'selected' : '' ?>>🟣 Hợp tác truyền thông / Nghiên cứu</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Trạng thái xử lý</label>
                    <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>-- Tất cả trạng thái --</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>⏳ Chờ xử lý</option>
                        <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>👁️ Đã xem</option>
                        <option value="replied" <?= $filterStatus === 'replied' ? 'selected' : '' ?>>✅ Đã phản hồi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tìm kiếm người gửi / nội dung</label>
                    <div class="relative">
                        <input
                            type="text"
                            name="q"
                            value="<?= htmlspecialchars($searchKeyword) ?>"
                            placeholder="Tên người gửi, SĐT, email, nội dung..."
                            class="w-full rounded-lg border border-slate-200 pl-8 pr-3 py-2 text-xs text-slate-700 focus:border-orange-500 focus:outline-none"
                        >
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"></path></svg>
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs py-2 px-4 rounded-lg transition cursor-pointer">
                        Tìm kiếm & Lọc
                    </button>
                    <?php if ($hasActiveFilters): ?>
                        <a href="contacts.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs py-2 px-3 rounded-lg transition whitespace-nowrap">
                            Xóa lọc
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- BẢNG DANH SÁCH THƯ LIÊN HỆ FULL-WIDTH -->
        <div class="bg-white rounded-xl border border-orange-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">💬 Danh sách Thư Liên hệ & Đề xuất Hợp tác</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <?php if ($totalFiltered > 0): ?>
                            Hiển thị <strong><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalFiltered) ?></strong> trên tổng số <strong><?= $totalFiltered ?></strong> tin nhắn
                        <?php else: ?>
                            Không có tin nhắn nào khớp với bộ lọc hiện tại
                        <?php endif; ?>
                    </p>
                </div>
                <span class="text-xs font-semibold text-orange-700 bg-orange-50 border border-orange-200 rounded-md px-3 py-1">
                    Trang <?= $page ?> / <?= $totalPages ?> • Bấm "Xem chi tiết" để đọc & phản hồi
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-[#fff7ed] border-b border-orange-200 text-[11px] font-bold text-orange-950 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Mã thư & Thời gian</th>
                            <th class="py-3 px-4">Người gửi</th>
                            <th class="py-3 px-4">Chủ đề / Phân loại</th>
                            <th class="py-3 px-4">Nội dung thư</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50 text-slate-700 text-xs">
                        <?php if (empty($paginatedContacts)): ?>
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    Không có tin nhắn liên hệ nào phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginatedContacts as $idx => $c):
                                $rowNum = $offset + $idx + 1;
                                $cStatus = $c['status'] ?? 'pending';
                                $subj = (string)($c['subject'] ?? ($c['type'] ?? ''));
                                $meta = fnGetSubjectMeta($subj);
                                $createdTs = strtotime((string)($c['created_at'] ?? 'now'));
                                $codeStr = fnFormatContactCode($c['id'] ?? 0, $c['created_at'] ?? '');
                                $cleanPhone = preg_replace('/\D+/', '', (string)($c['phone'] ?? ''));

                                $modalPayload = [
                                    'id' => (int)($c['id'] ?? 0),
                                    'code' => $codeStr,
                                    'name' => $c['name'] ?? 'Khách ẩn danh',
                                    'email' => $c['email'] ?? '',
                                    'phone' => $c['phone'] ?? '',
                                    'clean_phone' => $cleanPhone,
                                    'subject_label' => $meta['label'],
                                    'subject_icon' => $meta['icon'],
                                    'subject_badge_class' => $meta['badge_class'],
                                    'message' => $c['message'] ?? '',
                                    'status' => $cStatus,
                                    'created_at' => $createdTs ? date('d/m/Y lúc H:i', $createdTs) : '',
                                    'admin_reply' => $c['admin_reply'] ?? '',
                                    'replied_at' => !empty($c['replied_at']) ? date('d/m/Y H:i', strtotime((string)$c['replied_at'])) : '',
                                ];
                            ?>
                                <tr class="hover:bg-[#fffaf3] transition">
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400"><?= $rowNum ?></td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-mono font-bold text-slate-900"><?= htmlspecialchars($codeStr) ?></div>
                                        <div class="text-[11px] text-orange-600 font-semibold mt-0.5">
                                            ⏰ <?= $createdTs ? date('d/m/Y H:i', $createdTs) : '' ?>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-[11px] flex items-center justify-center flex-shrink-0">
                                                <?= htmlspecialchars(fnGetContactInitials((string)($c['name'] ?? ''))) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900"><?= htmlspecialchars($c['name'] ?? 'Khách ẩn danh') ?></div>
                                                <div class="text-[11px] text-slate-500">
                                                    <?= htmlspecialchars(($c['phone'] ?? '') !== '' ? ('📞 ' . $c['phone']) : ($c['email'] ?? '')) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold border <?= $meta['badge_class'] ?>">
                                            <?= $meta['icon'] ?> <?= htmlspecialchars($meta['label']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-md">
                                        <div class="text-slate-700 font-medium truncate" title="<?= htmlspecialchars($c['message'] ?? '') ?>">
                                            <?= htmlspecialchars($c['message'] ?? '') ?>
                                        </div>
                                        <?php if (!empty($c['email'])): ?>
                                            <div class="text-[11px] text-slate-400 mt-0.5 truncate">✉️ <?= htmlspecialchars($c['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <?php if ($cStatus === 'replied'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✅ Đã phản hồi
                                            </span>
                                        <?php elseif ($cStatus === 'read'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                👁️ Đã xem
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                ⏳ Chờ xử lý
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            onclick='fnOpenContactModal(<?= htmlspecialchars(json_encode($modalPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>)'
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-600 text-orange-700 hover:text-white border border-orange-200 hover:border-orange-600 font-bold text-xs transition cursor-pointer shadow-xs"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Xem chi tiết
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($totalPages > 1): ?>
                <div class="px-5 py-3.5 bg-[#fffaf5] border-t border-orange-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">
                        Hiển thị <strong class="text-slate-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalFiltered) ?></strong> / <strong class="text-slate-800"><?= $totalFiltered ?></strong> tin nhắn
                    </div>
                    <div class="flex items-center gap-1.5">
                        <?php if ($page > 1): ?>
                            <a href="<?= htmlspecialchars(fnBuildContactPageUrl(['p' => $page - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white hover:bg-orange-50 text-xs font-bold text-slate-700 transition">
                                « Trước
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 cursor-not-allowed">
                                « Trước
                            </span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a
                                href="<?= htmlspecialchars(fnBuildContactPageUrl(['p' => $i])) ?>"
                                class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center transition <?= $i === $page ? 'bg-orange-600 text-white shadow-xs' : 'bg-white border border-orange-200 text-slate-700 hover:bg-orange-50' ?>"
                            >
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= htmlspecialchars(fnBuildContactPageUrl(['p' => $page + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white hover:bg-orange-50 text-xs font-bold text-slate-700 transition">
                                Sau »
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 cursor-not-allowed">
                                Sau »
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL CHI TIẾT & PHẢN HỒI THƯ LIÊN HỆ -->
<div id="fn-contact-modal" class="fixed inset-0 z-[3000] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-2.5 sm:p-4" style="z-index: 3000;">
    <div class="bg-white rounded-2xl border border-orange-200 shadow-2xl max-w-xl w-full max-h-[88dvh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-[#fff7ed] border-b border-orange-200 flex items-start sm:items-center justify-between gap-2.5 sticky top-0 z-10">
            <div class="flex items-start sm:items-center gap-3 min-w-0">
                <div id="fn-ct-md-avatar" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-orange-600 text-white font-black text-sm flex items-center justify-center shadow-xs shrink-0">
                    KH
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <h3 id="fn-ct-md-name" class="text-sm sm:text-base font-black text-slate-900 break-words">Người gửi</h3>
                        <span id="fn-ct-md-code" class="font-mono text-xs font-bold text-slate-500">#CT-001</span>
                    </div>
                    <p id="fn-ct-md-time" class="text-xs text-slate-500 mt-0.5">Gửi lúc: —</p>
                </div>
            </div>
            <button type="button" onclick="fnCloseContactModal()" class="w-8 h-8 rounded-lg bg-white hover:bg-orange-100 text-slate-500 hover:text-orange-700 border border-orange-200 flex items-center justify-center text-lg font-bold cursor-pointer transition shrink-0">
                &times;
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-4 sm:p-6 space-y-4">
            <!-- Quick contact links -->
            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <div id="fn-ct-md-links" class="flex flex-wrap items-center gap-2"></div>
                <span id="fn-ct-md-subject" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold border"></span>
            </div>

            <!-- Message Box -->
            <div class="p-4 rounded-xl border border-orange-200 bg-[#fffaf5]">
                <div class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider mb-1.5">Nội dung thư gửi đến</div>
                <div id="fn-ct-md-message" class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap"></div>
            </div>

            <!-- Existing Reply -->
            <div id="fn-ct-md-reply-box" class="hidden p-4 rounded-xl border border-emerald-200 bg-emerald-50/70">
                <div class="flex items-center justify-between text-xs font-extrabold text-emerald-800 mb-1">
                    <span>💬 Phản hồi của Ban Quản Trị</span>
                    <span id="fn-ct-md-reply-time" class="font-medium text-[11px]"></span>
                </div>
                <div id="fn-ct-md-reply-text" class="text-xs text-emerald-900 leading-relaxed whitespace-pre-wrap"></div>
            </div>

            <!-- Form Phản hồi -->
            <form method="POST" action="contacts.php" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reply_contact">
                <input type="hidden" name="id" id="fn-ct-md-id-reply" value="">

                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Soạn phản hồi / Ghi nhận kết quả xử lý
                    </label>
                    <textarea
                        name="reply_text"
                        id="fn-ct-md-textarea"
                        rows="3"
                        required
                        placeholder="Ví dụ: Đã liên hệ qua Zalo hướng dẫn chủ tiệm bổ sung giấy tờ xác minh..."
                        class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-800 focus:border-orange-500 focus:outline-none"
                    ></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs transition cursor-pointer">
                    💬 Lưu phản hồi & Đánh dấu đã giải quyết
                </button>
            </form>

            <!-- Đổi trạng thái & Xóa -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-between gap-2">
                <form method="POST" action="contacts.php" class="flex-1">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="fn-ct-md-id-status" value="">
                    <input type="hidden" name="status" id="fn-ct-md-next-status" value="read">
                    <button type="submit" id="fn-ct-md-status-btn" class="w-full py-2 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer">
                        👁️ Đánh dấu Đã xem
                    </button>
                </form>

                <form method="POST" action="contacts.php" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin nhắn liên hệ này?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="fn-ct-md-id-delete" value="">
                    <button type="submit" class="py-2 px-3.5 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs transition cursor-pointer">
                        🗑️ Xóa thư
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function initLiveClock() {
    const clockEl = document.getElementById('fn-live-clock');
    if (!clockEl) return;
    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }
    function tick() {
        const now = new Date();
        clockEl.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()) + ' — ' + pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear();
    }
    tick();
    setInterval(tick, 1000);
})();

function fnOpenContactModal(data) {
    const modal = document.getElementById('fn-contact-modal');
    if (!modal || !data) return;

    const name = data.name || 'Khách ẩn danh';
    const words = name.trim().split(/\s+/);
    const initials = words.length >= 2
        ? (words[words.length - 2][0] + words[words.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();

    document.getElementById('fn-ct-md-avatar').textContent = initials;
    document.getElementById('fn-ct-md-name').textContent = name;
    document.getElementById('fn-ct-md-code').textContent = data.code || '';
    document.getElementById('fn-ct-md-time').textContent = 'Gửi lúc: ' + (data.created_at || '');
    document.getElementById('fn-ct-md-message').textContent = data.message || '';

    const subjEl = document.getElementById('fn-ct-md-subject');
    subjEl.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold border ' + (data.subject_badge_class || 'bg-slate-100 text-slate-700 border-slate-200');
    subjEl.textContent = (data.subject_icon || '💬') + ' ' + (data.subject_label || '');

    const linksEl = document.getElementById('fn-ct-md-links');
    linksEl.innerHTML = '';
    if (data.clean_phone) {
        const tel = document.createElement('a');
        tel.href = 'tel:' + data.clean_phone;
        tel.className = 'px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-800 hover:border-orange-400';
        tel.textContent = '📞 ' + data.phone;
        linksEl.appendChild(tel);

        const zalo = document.createElement('a');
        zalo.href = 'https://zalo.me/' + data.clean_phone;
        zalo.target = '_blank';
        zalo.className = 'px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 hover:bg-blue-100';
        zalo.textContent = '💬 Chat Zalo';
        linksEl.appendChild(zalo);
    }
    if (data.email) {
        const mail = document.createElement('a');
        mail.href = 'mailto:' + data.email;
        mail.className = 'px-2.5 py-1 rounded-lg bg-purple-50 border border-purple-200 text-xs font-bold text-purple-700 hover:bg-purple-100';
        mail.textContent = '✉️ ' + data.email;
        linksEl.appendChild(mail);
    }

    const replyBox = document.getElementById('fn-ct-md-reply-box');
    if (data.admin_reply) {
        replyBox.classList.remove('hidden');
        document.getElementById('fn-ct-md-reply-text').textContent = data.admin_reply;
        document.getElementById('fn-ct-md-reply-time').textContent = data.replied_at || '';
    } else {
        replyBox.classList.add('hidden');
    }

    document.getElementById('fn-ct-md-textarea').value = data.admin_reply || '';
    document.getElementById('fn-ct-md-id-reply').value = data.id;
    document.getElementById('fn-ct-md-id-status').value = data.id;
    document.getElementById('fn-ct-md-id-delete').value = data.id;

    const nextStatusInput = document.getElementById('fn-ct-md-next-status');
    const statusBtn = document.getElementById('fn-ct-md-status-btn');
    if (data.status === 'pending') {
        nextStatusInput.value = 'read';
        statusBtn.textContent = '👁️ Đánh dấu Đã xem';
    } else {
        nextStatusInput.value = 'pending';
        statusBtn.textContent = '⏳ Chuyển về Chờ xử lý';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fnCloseContactModal() {
    const modal = document.getElementById('fn-contact-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('fn-contact-modal');
    if (modal && e.target === modal) {
        fnCloseContactModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fnCloseContactModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
