<?php
require_once __DIR__ . '/../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php?redirect=admin/approvals.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $id = trim((string)($_POST['id'] ?? ''));
    $decision = (string)($_POST['decision'] ?? '');
    $ok = db()->decidePendingRepairRequest($id, $decision);
    header('Location: approvals.php?result=' . ($ok ? 'saved' : 'stale'));
    exit;
}

$pending = array_values(array_filter(db()->getRepairRequests(), static fn($request) => ($request['status'] ?? 'pending') === 'pending'));
$pageTitle = 'Duyệt yêu cầu — FixNear Admin';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fn-admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <main class="fn-admin-content !p-6 !bg-[#fffaf5]">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Duyệt yêu cầu sửa chữa</h1>
                <p class="text-sm text-slate-600 mt-1">Chỉ hiển thị yêu cầu đang chờ từ dữ liệu thật. Duyệt để tiếp nhận, từ chối để đóng yêu cầu.</p>
            </div>
            <a class="text-sm font-bold text-orange-700 hover:underline" href="requests.php">Xem tất cả yêu cầu →</a>
        </div>
        <?php if (($_GET['result'] ?? '') === 'saved'): ?>
            <p role="status" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-5 text-emerald-800">Đã lưu quyết định. Trạng thái của người gửi được cập nhật.</p>
        <?php elseif (($_GET['result'] ?? '') === 'stale'): ?>
            <p role="alert" class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-5 text-amber-800">Yêu cầu đã thay đổi hoặc không tồn tại. Danh sách đã được tải lại.</p>
        <?php endif; ?>
        <p class="text-sm font-semibold text-slate-700 mb-4">Đang chờ: <?= count($pending) ?> yêu cầu</p>
        <?php if (!$pending): ?>
            <div class="bg-white border border-orange-100 rounded-xl p-10 text-center text-slate-600">Hiện chưa có yêu cầu nào cần duyệt.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <?php foreach ($pending as $request): ?>
                    <article class="bg-white border border-orange-100 rounded-xl p-5 shadow-sm">
                        <div class="flex flex-wrap justify-between gap-2"><h2 class="font-black text-slate-900"><?= htmlspecialchars((string)($request['id'] ?? '')) ?></h2><span class="text-xs text-slate-500"><?= htmlspecialchars((string)($request['created_at'] ?? 'Chưa có thời gian gửi')) ?></span></div>
                        <p class="font-bold mt-3"><?= htmlspecialchars((string)($request['brand_model'] ?? $request['device_type'] ?? 'Thiết bị')) ?> · <?= htmlspecialchars((string)($request['issue_type'] ?? 'Chưa rõ lỗi')) ?></p>
                        <p class="text-sm text-slate-600 mt-2"><?= nl2br(htmlspecialchars((string)($request['symptom'] ?? 'Chưa có mô tả'))) ?></p>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4 text-sm">
                            <div><dt class="text-slate-500">Người gửi</dt><dd class="font-semibold"><?= htmlspecialchars((string)($request['customer_name'] ?? 'Không rõ')) ?></dd></div>
                            <div><dt class="text-slate-500">Điện thoại</dt><dd class="font-semibold"><?= htmlspecialchars((string)($request['customer_phone'] ?? 'Không có')) ?></dd></div>
                            <div><dt class="text-slate-500">Khu vực</dt><dd><?= htmlspecialchars((string)($request['district'] ?? 'Chưa chọn')) ?></dd></div>
                            <div><dt class="text-slate-500">Thời gian mong muốn</dt><dd><?= htmlspecialchars((string)($request['preferred_time'] ?? 'Chưa chọn')) ?></dd></div>
                        </dl>
                        <div class="flex flex-wrap gap-2 mt-5">
                            <form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= htmlspecialchars((string)($request['id'] ?? ''), ENT_QUOTES) ?>"><button name="decision" value="approve" class="bg-orange-600 text-white font-bold px-4 py-2 rounded-lg">Tiếp nhận yêu cầu</button></form>
                            <form method="post" onsubmit="return confirm('Từ chối yêu cầu này?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= htmlspecialchars((string)($request['id'] ?? ''), ENT_QUOTES) ?>"><button name="decision" value="reject" class="border border-slate-300 text-slate-700 font-bold px-4 py-2 rounded-lg">Từ chối</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
