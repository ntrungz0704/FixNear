<?php
require_once __DIR__ . '/../includes/pricing_engine.php';
require_once __DIR__ . '/../includes/price_evidence.php';

$pageTitle = 'Bảng giá sửa chữa theo model | FixNear';
$pageStyles = ['assets/css/prices.css'];
$allModels = RepairAtlasPricing::getAllModels();
$devices = RepairAtlasPricing::getAllDevices();
$faults = RepairAtlasPricing::getAllFaults();
$quotes = FixNearPriceEvidence::all();
$brandsByDevice = [];
foreach ($allModels as $catalogModel) {
    $brandsByDevice[$catalogModel['deviceType']][$catalogModel['brand']] = true;
}
$brandLabels = [
    'apple' => 'Apple', 'apple_desktop' => 'Apple', 'hp' => 'HP',
    'msi' => 'MSI', 'lg' => 'LG', 'oem_brand' => 'Máy bộ theo hãng',
    'gaming' => 'PC Gaming', 'office' => 'PC văn phòng',
    'workstation' => 'Workstation', 'others' => 'Hãng khác',
];
$sourceModels = [];
$sourceFaults = [];
$sourceBrands = [];
$sourceProviders = [];
foreach ($quotes as $quote) {
    $sourceModels[$quote['modelId']] = true;
    $sourceFaults[$quote['modelId'] . ':' . $quote['faultId']] = true;
    $sourceBrands[$quote['componentBrand']] = true;
    $sourceProviders[$quote['provider']] = true;
}

$device = trim((string) ($_GET['device'] ?? ''));
$brand = trim((string) ($_GET['brand'] ?? ''));
$modelId = trim((string) ($_GET['model'] ?? ''));
$faultId = trim((string) ($_GET['fault'] ?? ''));
$partBrand = trim((string) ($_GET['part_brand'] ?? ''));
$provider = trim((string) ($_GET['provider'] ?? ''));
$query = trim((string) ($_GET['q'] ?? ''));
$mode = (string) ($_GET['mode'] ?? '');
if (!isset($devices[$device])) $device = '';
if (!isset($allModels[$modelId])) $modelId = '';
if ($modelId !== '') {
    $device = $allModels[$modelId]['deviceType'];
    $brand = $allModels[$modelId]['brand'];
}
if ($device === '' || !isset($brandsByDevice[$device][$brand])) $brand = '';
if ($modelId !== '' && $device !== '' && $allModels[$modelId]['deviceType'] !== $device) $modelId = '';
if (!isset($faults[$faultId])) $faultId = '';
if (!isset($sourceBrands[$partBrand])) $partBrand = '';
if (!isset($sourceProviders[$provider])) $provider = '';
if (!in_array($mode, ['estimate', 'source'], true)) {
    $mode = $modelId !== '' && isset($sourceModels[$modelId]) ? 'source' : 'estimate';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 24;
$h = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => number_format((int) $value, 0, ',', '.') . 'đ';
$matches = static function (string $haystack, string $query): bool {
    if ($query === '') return true;
    $tokens = preg_split('/\s+/u', mb_strtolower($query, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($tokens as $token) {
        if (mb_stripos($haystack, $token, 0, 'UTF-8') === false) return false;
    }
    return true;
};

$modelOptions = $allModels;
uasort($modelOptions, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
$displayRows = [];
if ($mode === 'source') {
    foreach ($quotes as $quote) {
        $model = $allModels[$quote['modelId']] ?? null;
        if (!$model) continue;
        if ($device !== '' && $model['deviceType'] !== $device) continue;
        if ($brand !== '' && $model['brand'] !== $brand) continue;
        if ($modelId !== '' && $quote['modelId'] !== $modelId) continue;
        if ($faultId !== '' && $quote['faultId'] !== $faultId) continue;
        if ($partBrand !== '' && $quote['componentBrand'] !== $partBrand) continue;
        if ($provider !== '' && $quote['provider'] !== $provider) continue;
        $haystack = implode(' ', [$model['name'], $model['brand'], $quote['componentName'], $quote['componentBrand'], $quote['provider'], $faults[$quote['faultId']]['name'] ?? '']);
        if (!$matches($haystack, $query)) continue;
        $displayRows[] = ['model' => $model, 'quote' => $quote, 'faultId' => $quote['faultId']];
    }
} else {
    foreach ($allModels as $model) {
        if ($device !== '' && $model['deviceType'] !== $device) continue;
        if ($brand !== '' && $model['brand'] !== $brand) continue;
        if ($modelId !== '' && $model['id'] !== $modelId) continue;
        foreach ($model['supportedFaults'] ?? [] as $supportedFaultId) {
            if (!isset($faults[$supportedFaultId])) continue;
            if ($faultId !== '' && $supportedFaultId !== $faultId) continue;
            $haystack = implode(' ', [$model['name'], $model['brand'], $faults[$supportedFaultId]['name']]);
            if (!$matches($haystack, $query)) continue;
            $displayRows[] = ['model' => $model, 'faultId' => $supportedFaultId];
        }
    }
}
usort($displayRows, static function ($a, $b) use ($faults) {
    return strnatcasecmp($a['model']['name'], $b['model']['name'])
        ?: strnatcasecmp($faults[$a['faultId']]['name'] ?? '', $faults[$b['faultId']]['name'] ?? '')
        ?: (($a['quote']['priceVnd'] ?? 0) <=> ($b['quote']['priceVnd'] ?? 0));
});
$totalRows = count($displayRows);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$displayRows = array_slice($displayRows, ($page - 1) * $perPage, $perPage);
$params = array_filter(['mode' => $mode, 'device' => $device, 'brand' => $brand, 'model' => $modelId, 'fault' => $faultId, 'part_brand' => $partBrand, 'provider' => $provider, 'q' => $query], static fn($v) => $v !== '');
$pageUrl = static function (int $number) use ($params): string {
    return 'prices.php?' . http_build_query($params + ['page' => $number]);
};

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="fn-prices-page fn-container">
    <header class="fn-price-hero">
        <div>
            <span class="fn-price-kicker">BẢNG GIÁ FIXNEAR</span>
            <h1>Tra giá theo đúng dòng máy</h1>
            <p>Chọn nhóm thiết bị, hãng rồi mã máy để xem giá theo từng hạng mục.</p>
        </div>
        <div class="fn-price-stats" aria-label="Phạm vi dữ liệu">
            <span><strong><?= count($allModels) ?></strong> model</span>
            <span><strong><?= count($quotes) ?></strong> giá có nguồn</span>
        </div>
    </header>

    <nav class="fn-price-tabs" aria-label="Loại dữ liệu giá">
        <?php $tabParams = $params; unset($tabParams['part_brand'], $tabParams['provider']); ?>
        <a class="<?= $mode === 'estimate' ? 'is-active' : '' ?>" href="prices.php?<?= $h(http_build_query(array_merge($tabParams, ['mode' => 'estimate']))) ?>" <?= $mode === 'estimate' ? 'aria-current="page"' : '' ?>>Ước tính · <?= count($allModels) ?> model</a>
        <a class="<?= $mode === 'source' ? 'is-active' : '' ?>" href="prices.php?<?= $h(http_build_query(array_merge($tabParams, ['mode' => 'source']))) ?>" <?= $mode === 'source' ? 'aria-current="page"' : '' ?>>Giá có nguồn · <?= count($quotes) ?> lựa chọn</a>
    </nav>

    <section class="fn-price-panel" aria-labelledby="price-list-heading">
        <div class="fn-price-heading">
            <div>
                <h2 id="price-list-heading"><?= $mode === 'estimate' ? 'Mức chi phí tham khảo' : 'Giá niêm yết đã đối chiếu' ?></h2>
                <p><?= $mode === 'estimate'
                    ? 'Ba khoảng tính từ ma trận FixNear; tên hãng linh kiện, thời gian và tổng tiền do cửa hàng xác nhận.'
                    : 'Mỗi giá đi kèm đúng model, tên linh kiện, đơn vị niêm yết và liên kết nguồn.' ?></p>
            </div>
            <span class="fn-price-count" role="status"><?= number_format($totalRows, 0, ',', '.') ?> kết quả</span>
        </div>

        <form class="fn-price-filters" method="get" action="prices.php">
            <input type="hidden" name="mode" value="<?= $h($mode) ?>">
            <label><span>1. Nhóm thiết bị</span><select name="device" id="price-device"><option value="">Chọn nhóm thiết bị</option><?php foreach ($devices as $id => $item): ?><option value="<?= $h($id) ?>" <?= $device === $id ? 'selected' : '' ?>><?= $h($item['name']) ?></option><?php endforeach; ?></select></label>
            <label><span>2. Hãng / phân loại</span><select name="brand" id="price-brand" data-selected="<?= $h($brand) ?>" <?= $device === '' ? 'disabled' : '' ?>><option value="">Chọn nhóm thiết bị trước</option></select></label>
            <label><span>3. Mã máy</span><select name="model" id="price-model" data-selected="<?= $h($modelId) ?>" <?= $brand === '' ? 'disabled' : '' ?>><option value="">Chọn hãng trước</option></select></label>
            <label><span>Hạng mục</span><select name="fault"><option value="">Tất cả hạng mục</option><?php foreach ($faults as $id => $item): ?><option value="<?= $h($id) ?>" <?= $faultId === $id ? 'selected' : '' ?>><?= $h($item['name']) ?></option><?php endforeach; ?></select></label>
            <label class="fn-price-query"><span>Tìm nhanh</span><input type="search" name="q" value="<?= $h($query) ?>" placeholder="Tên máy hoặc lỗi"></label>
            <?php if ($mode === 'source'): ?><label><span>Hãng linh kiện</span><select name="part_brand"><option value="">Tất cả hãng</option><?php foreach (array_keys($sourceBrands) as $brand): ?><option value="<?= $h($brand) ?>" <?= $partBrand === $brand ? 'selected' : '' ?>><?= $h($brand) ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <?php if ($mode === 'source'): ?><label><span>Đơn vị niêm yết</span><select name="provider"><option value="">Tất cả đơn vị</option><?php foreach (array_keys($sourceProviders) as $name): ?><option value="<?= $h($name) ?>" <?= $provider === $name ? 'selected' : '' ?>><?= $h($name) ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <div class="fn-price-filter-actions"><button type="submit">Lọc giá</button><a href="prices.php?mode=<?= $h($mode) ?>">Xóa lọc</a></div>
        </form>

        <?php if ($totalRows === 0): ?>
            <div class="fn-price-empty">
                <strong>Chưa có kết quả phù hợp.</strong>
                <p><?= $mode === 'source' ? 'Model này có thể chưa có giá niêm yết kèm nguồn. Xem mức ước tính để tham khảo.' : 'Thử đổi model, hạng mục hoặc từ khóa.' ?></p>
                <a href="prices.php?<?= $h(http_build_query(['mode' => 'estimate', 'model' => $modelId, 'device' => $device])) ?>">Xem mức ước tính →</a>
            </div>
        <?php else: ?>
            <div class="fn-price-table-wrap">
                <table class="fn-price-table">
                    <thead><tr><th scope="col">Model</th><th scope="col">Hạng mục<?= $mode === 'source' ? ' / linh kiện' : '' ?></th><th scope="col"><?= $mode === 'source' ? 'Giá niêm yết' : 'Thấp · trung bình · cao' ?></th><th scope="col"><?= $mode === 'source' ? 'Đơn vị & nguồn' : 'Tiếp theo' ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($displayRows as $row):
                        $item = $row['model'];
                        $fault = $faults[$row['faultId']];
                        $isSource = isset($row['quote']);
                        $quote = $row['quote'] ?? null;
                        $brandSlug = strtolower((string) $item['brand']);
                        $deviceLogo = fixnearBrandLogoPath($brandSlug);
                        $partName = $isSource ? preg_replace('/^' . preg_quote($quote['componentBrand'], '/') . '\\s*/iu', '', (string) $quote['componentName']) : '';
                        if ($isSource && $partName === '') $partName = $quote['componentName'];
                        $grades = $isSource ? [] : RepairAtlasPricing::getModelPrices($item['deviceType'], $item['tier'], $item['brand'], [$row['faultId']], $item['id'])[$row['faultId']] ?? [];
                    ?>
                        <tr>
                            <td data-label="Model"><div class="fn-price-model-cell"><?php if ($deviceLogo): ?><img src="<?= $h($deviceLogo) ?>" width="28" height="28" alt="Logo <?= $h($item['brand']) ?>"><?php endif; ?><div><a class="fn-price-model-link" href="model_detail.php?id=<?= rawurlencode($item['id']) ?>#bang-gia"><?= $h($item['name']) ?></a><small><?= $h($devices[$item['deviceType']]['name'] ?? $item['deviceType']) ?></small></div></div></td>
                            <td data-label="Hạng mục"><strong><?= $h($fault['name']) ?></strong><?php if ($isSource): ?><small><span class="fn-price-part-brand"><?= $h($quote['componentBrand']) ?></span> <?= $h($partName) ?></small><?php endif; ?></td>
                            <td data-label="Giá"><?php if ($isSource): ?><strong class="fn-price-amount"><?= $money($quote['priceVnd']) ?></strong><?php else: ?><div class="fn-price-range"><?php foreach (['standard' => 'Thấp', 'oem' => 'TB', 'genuine' => 'Cao'] as $gradeId => $label): $price = $grades[$gradeId] ?? null; ?><span><b><?= $label ?></b><strong><?= $price ? $money($price['min']) . '–' . $money($price['max']) : 'Cần báo giá' ?></strong></span><?php endforeach; ?></div><?php endif; ?></td>
                            <td data-label="<?= $isSource ? 'Nguồn' : 'Tiếp theo' ?>"><?php if ($isSource): ?><strong><?= $h($quote['provider']) ?></strong><small>Đối chiếu <?= $h(date('d/m/Y', strtotime($quote['checkedAt']))) ?> · BH <?= (int) $quote['warrantyMonths'] ?> tháng</small><a href="<?= $h($quote['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer">Xem nguồn ↗</a><?php else: ?><a class="fn-price-row-action" href="search.php?<?= $h(http_build_query(['device' => $item['deviceType'], 'model' => $item['id'], 'service_id' => $fault['serviceId'] ?? ''])) ?>">Tìm cửa hàng →</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?><nav class="fn-price-pagination" aria-label="Phân trang bảng giá"><?php if ($page > 1): ?><a href="<?= $h($pageUrl($page - 1)) ?>">← Trước</a><?php endif; ?><span>Trang <?= $page ?> / <?= $totalPages ?></span><?php if ($page < $totalPages): ?><a href="<?= $h($pageUrl($page + 1)) ?>">Sau →</a><?php endif; ?></nav><?php endif; ?>
        <?php endif; ?>
    </section>
    <p class="fn-price-footnote">Giá niêm yết có thể đổi theo chi nhánh và khuyến mãi. Các khoảng ước tính không xác nhận hãng hoặc chất lượng linh kiện. Hãy yêu cầu báo giá trọn gói và bảo hành trước khi sửa.</p>
</main>
<script>
const priceDevice = document.getElementById('price-device');
const priceBrand = document.getElementById('price-brand');
const priceModel = document.getElementById('price-model');
const priceCatalog = <?= json_encode(array_values(array_map(static fn($item) => [
    'id' => $item['id'], 'name' => $item['name'], 'device' => $item['deviceType'], 'brand' => $item['brand'],
], $modelOptions)), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const priceBrandLabels = <?= json_encode($brandLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
if (priceDevice && priceBrand && priceModel) {
    const addOption = (select, value, label) => select.add(new Option(label, value));
    const updateModels = (selected = '') => {
        priceModel.replaceChildren();
        if (!priceBrand.value) {
            addOption(priceModel, '', 'Chọn hãng trước');
            priceModel.disabled = true;
            return;
        }
        const items = priceCatalog.filter(item => item.device === priceDevice.value && item.brand === priceBrand.value);
        addOption(priceModel, '', `Tất cả ${items.length} mã máy`);
        items.forEach(item => addOption(priceModel, item.id, item.name));
        priceModel.disabled = false;
        priceModel.value = selected;
        if (!priceModel.value) priceModel.value = '';
    };
    const updateBrands = (selected = '') => {
        priceBrand.replaceChildren();
        if (!priceDevice.value) {
            addOption(priceBrand, '', 'Chọn nhóm thiết bị trước');
            priceBrand.disabled = true;
            updateModels();
            return;
        }
        const brands = [...new Set(priceCatalog.filter(item => item.device === priceDevice.value).map(item => item.brand))]
            .sort((a, b) => (priceBrandLabels[a] || a).localeCompare(priceBrandLabels[b] || b, 'vi'));
        addOption(priceBrand, '', 'Tất cả hãng / phân loại');
        brands.forEach(value => addOption(priceBrand, value, priceBrandLabels[value] || value.charAt(0).toUpperCase() + value.slice(1)));
        priceBrand.disabled = false;
        priceBrand.value = selected;
        if (!priceBrand.value) priceBrand.value = '';
        updateModels(priceModel.dataset.selected || '');
    };
    priceDevice.addEventListener('change', () => { priceModel.dataset.selected = ''; updateBrands(); });
    priceBrand.addEventListener('change', () => { priceModel.dataset.selected = ''; updateModels(); });
    updateBrands(priceBrand.dataset.selected || '');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
