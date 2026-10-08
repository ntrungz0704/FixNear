<?php
/** Exact provider listings, kept separate from FixNear's model estimates. */
class FixNearPriceEvidence {
    public static function all(): array {
        $path = __DIR__ . '/../data/pricing/source-quotes.json';
        $items = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        if (!is_array($items)) return [];
        return array_values(array_filter($items, static fn($item) => is_array($item)
            && !empty($item['modelId']) && !empty($item['faultId'])
            && !empty($item['componentName']) && !empty($item['componentBrand'])
            && !empty($item['sourceUrl']) && !empty($item['checkedAt'])
            && isset($item['priceVnd']) && is_numeric($item['priceVnd'])));
    }

    public static function forModel(string $modelId): array {
        $result = [];
        foreach (self::all() as $item) {
            if ($item['modelId'] !== $modelId) continue;
            $result[$item['faultId']][] = $item;
        }
        return $result;
    }
}
