<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

$directory = sys_get_temp_dir() . '/fixnear-admin-' . bin2hex(random_bytes(8));
mkdir($directory, 0700, true);
putenv('FIXNEAR_DATA_DIR=' . $directory);
putenv('FIXNEAR_DISABLE_MYSQL=1');
require_once __DIR__ . '/../config/db.php';

try {
    $hash = password_hash('temporary-test-password', PASSWORD_DEFAULT);
    assert(db()->createOrUpdateAdmin('admin@example.invalid', 'Admin', $hash));
    assert(!db()->createOrUpdateAdmin('second@example.invalid', 'Other', $hash));
    assert(db()->createOrUpdateAdmin('ADMIN@example.invalid', 'Updated', $hash));
    $user = db()->createUser([
        'name' => 'Member', 'email' => 'member@example.invalid',
        'password' => $hash, 'role' => 'admin',
    ]);
    assert(is_array($user) && ($user['role'] ?? '') === 'user');
    $users = json_decode((string)file_get_contents($directory . '/users.json'), true, 512, JSON_THROW_ON_ERROR);
    assert(count(array_filter($users, static fn($row) => ($row['role'] ?? '') === 'admin')) === 1);
    echo "Single-admin and registration role checks passed.\n";
} finally {
    foreach (glob($directory . '/*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($directory);
}
