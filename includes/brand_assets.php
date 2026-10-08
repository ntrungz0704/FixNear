<?php
/** Audited brand assets, shared by PHP cards and the browser selectors. */
function fixnearBrandAssetMap(): array
{
    static $files = null;
    if ($files !== null) return $files;
    $manifestPath = dirname(__DIR__) . '/data/brand-logo-sources.json';
    $data = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
    $files = [];
    foreach (($data['logos'] ?? []) as $id => $item) {
        $file = $item['file'] ?? '';
        if (preg_match('/^[a-z0-9_-]+$/', (string)$id)
            && preg_match('/^[a-z0-9_-]+\.(?:svg|png)$/', (string)$file)
            && is_file(dirname(__DIR__) . '/public/assets/images/brands/' . $file)) {
            $files[$id] = $file;
        }
    }
    return $files;
}

function fixnearBrandLogoPath(string $brand): ?string
{
    $file = fixnearBrandAssetMap()[strtolower($brand)] ?? null;
    return $file ? 'assets/images/brands/' . $file : null;
}
