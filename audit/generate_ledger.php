<?php
declare(strict_types=1);

/**
 * Tạo baseline ledger từ data/shops.json. Script này không xác minh Internet,
 * không thay đổi dữ liệu runtime, và không suy diễn website seed là nguồn thật.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

$root = dirname(__DIR__);
$auditDir = __DIR__;
$shops = json_decode((string) file_get_contents($root . '/data/shops.json'), true, 512, JSON_THROW_ON_ERROR);
$auditDate = date('Y-m-d');
$columns = [
    'shop_id', 'name', 'address', 'district', 'lat', 'lng', 'official_url',
    'google_place_id', 'google_maps_url', 'google_rating_observed',
    'google_reviews_observed', 'observed_at', 'phone_source', 'hours_source',
    'price_source_url', 'price_effective_date', 'student_discount_source_url',
    'student_discount_verified_at', 'policy_source_url', 'policy_verified_at',
    'status', 'evidence_note', 'verified_by', 'needs_human_action'
];

$entries = [];
foreach ($shops as $shop) {
    $entries[] = [
        'shop_id' => (int) ($shop['id'] ?? 0),
        'name' => (string) ($shop['name'] ?? ''),
        'address' => (string) ($shop['address'] ?? ''),
        'district' => (string) ($shop['district'] ?? ''),
        'lat' => $shop['latitude'] ?? null,
        'lng' => $shop['longitude'] ?? null,
        // Candidate only: the seed website has not met the verification standard.
        'official_url' => (string) ($shop['website'] ?? ''),
        'google_place_id' => '',
        'google_maps_url' => (string) ($shop['map_url'] ?? ''),
        'google_rating_observed' => null,
        'google_reviews_observed' => null,
        'observed_at' => '',
        'phone_source' => '',
        'hours_source' => '',
        'price_source_url' => '',
        'price_effective_date' => '',
        'student_discount_source_url' => '',
        'student_discount_verified_at' => '',
        'policy_source_url' => '',
        'policy_verified_at' => '',
        'status' => 'UNVERIFIED',
        'evidence_note' => 'Baseline seed record only. No source URL plus verification date was present for shop, Google, price, student discount, or service policy at audit time.',
        'verified_by' => '',
        'needs_human_action' => 'Confirm this exact branch using official contact or Place Details; record a source URL, access date, and concise evidence before publishing any claim.',
        'field_status' => [
            'shop_identity' => 'UNVERIFIED', 'google' => 'UNVERIFIED',
            'phone_hours' => 'UNVERIFIED', 'price' => 'UNVERIFIED',
            'student_discount' => 'UNVERIFIED', 'service_policy' => 'UNVERIFIED',
        ],
    ];
}

$json = [
    'generated_at' => $auditDate,
    'method' => 'Local baseline audit only; no web scraping or external verification performed.',
    'verification_standard' => 'VERIFIED requires a specific URL, an access date, and concise evidence.',
    'entries' => $entries,
];
file_put_contents(
    $auditDir . '/verification_ledger.json',
    json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL,
    LOCK_EX
);

$csv = fopen($auditDir . '/verification_ledger.csv', 'wb');
if ($csv === false) {
    throw new RuntimeException('Cannot create verification_ledger.csv');
}
fputcsv($csv, $columns, ',', '"', '');
foreach ($entries as $entry) {
    $row = [];
    foreach ($columns as $column) {
        $row[] = $entry[$column] ?? '';
    }
    fputcsv($csv, $row, ',', '"', '');
}
fclose($csv);

printf("Generated %d UNVERIFIED baseline entries for %s.\n", count($entries), $auditDate);
