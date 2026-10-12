<?php
declare(strict_types=1);

/** Keep the first render and live search in the same order. */
function fixnearSortSearchShops(array &$shops, string $sort, bool $hasLocation): void
{
    $nameOrder = static fn(array $a, array $b): int => strnatcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    if ($sort === 'distance' && $hasLocation) {
        usort($shops, static function (array $a, array $b) use ($nameOrder): int {
            return ((float)($a['distance_km'] ?? INF) <=> (float)($b['distance_km'] ?? INF)) ?: $nameOrder($a, $b);
        });
    } elseif ($sort === 'rating') {
        usort($shops, static function (array $a, array $b) use ($nameOrder): int {
            $aRating = !empty($a['google_rating_verified']) ? (float)($a['google_rating'] ?? 0) : 0.0;
            $bRating = !empty($b['google_rating_verified']) ? (float)($b['google_rating'] ?? 0) : 0.0;
            $aCount = !empty($a['google_rating_verified']) ? (int)($a['google_reviews_count'] ?? 0) : 0;
            $bCount = !empty($b['google_rating_verified']) ? (int)($b['google_reviews_count'] ?? 0) : 0;
            return ($bRating <=> $aRating) ?: (($bCount <=> $aCount) ?: $nameOrder($a, $b));
        });
    } else {
        usort($shops, $nameOrder);
    }
}
