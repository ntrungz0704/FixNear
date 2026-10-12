<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/../config/app.php';

const PRIVATE_FILES = [
    'users.json', 'repair_requests.json', 'contact_messages.json',
    'reviews.json', 'favorites.json', 'reports.json',
];
const SNAPSHOT_AAD = 'FixNear private snapshot v1';

function failSnapshot(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function snapshotKey(): string
{
    $path = (string)getenv('FIXNEAR_SNAPSHOT_KEY_FILE');
    if ($path === '' || !is_file($path)) {
        failSnapshot('Thiếu FIXNEAR_SNAPSHOT_KEY_FILE hoặc tệp khóa không tồn tại.');
    }
    $key = base64_decode(trim((string)file_get_contents($path)), true);
    if ($key === false || strlen($key) !== 32) {
        failSnapshot('Khóa sao lưu không hợp lệ.');
    }
    return $key;
}

function validatePrivateFiles(array $files): void
{
    if (array_keys($files) !== PRIVATE_FILES) {
        failSnapshot('Bản sao lưu thiếu hoặc thừa tệp dữ liệu.');
    }
    foreach ($files as $name => $raw) {
        if (!is_string($raw) || strlen($raw) > 20_000_000) {
            failSnapshot("Kích thước dữ liệu không hợp lệ: {$name}");
        }
        try {
            $records = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            failSnapshot("JSON không hợp lệ: {$name}");
        }
        if (!is_array($records) || !array_is_list($records)) {
            failSnapshot("Dữ liệu phải là danh sách: {$name}");
        }
    }
    $users = json_decode($files['users.json'], true, 512, JSON_THROW_ON_ERROR);
    $admins = array_filter($users, static fn($user) => is_array($user) && ($user['role'] ?? '') === 'admin');
    if (count($admins) !== 1) {
        failSnapshot('Bản sao lưu phải chứa đúng một tài khoản admin.');
    }
}

$action = $argv[1] ?? '';
$snapshotPath = (string)getenv('FIXNEAR_SNAPSHOT_FILE') ?: dirname(__DIR__) . '/data/private-snapshot.enc';
$keyPath = (string)getenv('FIXNEAR_SNAPSHOT_KEY_FILE');

if ($action === 'keygen') {
    if ($keyPath === '' || file_exists($keyPath)) {
        failSnapshot('Đặt FIXNEAR_SNAPSHOT_KEY_FILE tới đường dẫn mới, chưa tồn tại.');
    }
    $directory = dirname($keyPath);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        failSnapshot('Không tạo được thư mục khóa.');
    }
    $handle = @fopen($keyPath, 'x');
    if ($handle === false) {
        failSnapshot('Không tạo được tệp khóa.');
    }
    fwrite($handle, base64_encode(random_bytes(32)) . PHP_EOL);
    fclose($handle);
    @chmod($keyPath, 0600);
    fwrite(STDOUT, "Đã tạo khóa riêng tại {$keyPath}. Giữ khóa ngoài GitHub.\n");
    exit(0);
}

if (!in_array($action, ['export', 'verify', 'restore'], true)) {
    failSnapshot('Cách dùng: php scripts/private_snapshot.php keygen|export|verify|restore');
}

$key = snapshotKey();
if ($action === 'export') {
    if (!is_dir(FIXNEAR_DATA_DIR)) {
        failSnapshot('Thiếu thư mục dữ liệu.');
    }
    $files = [];
    foreach (PRIVATE_FILES as $name) {
        $path = FIXNEAR_DATA_DIR . $name;
        $files[$name] = is_file($path) ? (string)file_get_contents($path) : '[]';
    }
    validatePrivateFiles($files);
    $payload = json_encode(['version' => 1, 'exported_at' => gmdate('c'), 'files' => $files], JSON_THROW_ON_ERROR);
    $nonce = random_bytes(12);
    $ciphertext = openssl_encrypt($payload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, SNAPSHOT_AAD);
    if ($ciphertext === false) {
        failSnapshot('Không mã hóa được bản sao lưu.');
    }
    $envelope = json_encode([
        'format' => 'fixnear-aes-256-gcm-v1',
        'nonce' => base64_encode($nonce),
        'tag' => base64_encode($tag),
        'ciphertext' => base64_encode($ciphertext),
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    if (file_put_contents($snapshotPath, $envelope . PHP_EOL, LOCK_EX) === false) {
        failSnapshot('Không ghi được bản sao lưu mã hóa.');
    }
    fwrite(STDOUT, "Đã xuất bản sao lưu mã hóa gồm 6 tệp.\n");
    exit(0);
}

if (!is_file($snapshotPath)) {
    failSnapshot('Không tìm thấy bản sao lưu mã hóa.');
}
try {
    $envelope = json_decode((string)file_get_contents($snapshotPath), true, 512, JSON_THROW_ON_ERROR);
    if (($envelope['format'] ?? '') !== 'fixnear-aes-256-gcm-v1') {
        failSnapshot('Định dạng bản sao lưu không hợp lệ.');
    }
    $nonce = base64_decode((string)($envelope['nonce'] ?? ''), true);
    $tag = base64_decode((string)($envelope['tag'] ?? ''), true);
    $ciphertext = base64_decode((string)($envelope['ciphertext'] ?? ''), true);
    if ($nonce === false || strlen($nonce) !== 12 || $tag === false || strlen($tag) !== 16 || $ciphertext === false) {
        failSnapshot('Bản sao lưu bị hỏng.');
    }
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, SNAPSHOT_AAD);
    if ($plaintext === false) {
        failSnapshot('Không giải mã được; kiểm tra khóa sao lưu.');
    }
    $payload = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
    if (($payload['version'] ?? null) !== 1 || !is_array($payload['files'] ?? null)) {
        failSnapshot('Nội dung bản sao lưu không hợp lệ.');
    }
    $files = $payload['files'];
    validatePrivateFiles($files);
} catch (JsonException $exception) {
    failSnapshot('Bản sao lưu không phải JSON hợp lệ.');
}

if ($action === 'verify') {
    fwrite(STDOUT, "Bản sao lưu giải mã hợp lệ, có đúng một admin.\n");
    exit(0);
}

if (!is_dir(FIXNEAR_DATA_DIR) && !mkdir(FIXNEAR_DATA_DIR, 0750, true) && !is_dir(FIXNEAR_DATA_DIR)) {
    failSnapshot('Không tạo được thư mục dữ liệu đích.');
}
foreach (PRIVATE_FILES as $name) {
    if (file_exists(FIXNEAR_DATA_DIR . $name)) {
        failSnapshot("Đích đã có {$name}; không ghi đè dữ liệu hiện tại.");
    }
}
foreach ($files as $name => $raw) {
    if (file_put_contents(FIXNEAR_DATA_DIR . $name, $raw, LOCK_EX) === false) {
        failSnapshot("Không khôi phục được {$name}.");
    }
    @chmod(FIXNEAR_DATA_DIR . $name, 0640);
}
fwrite(STDOUT, "Đã khôi phục đúng 6 tệp dữ liệu riêng và một admin.\n");
