<?php

namespace App\Repository;

/**
 * Helpers for safe LIKE searches. Values are always bound as parameters
 * (no SQL concatenation); LIKE wildcards typed by the user are escaped.
 */
final class SearchHelper
{
    public static function likeContains(string $term): string
    {
        return '%'.addcslashes(trim($term), '%_\\').'%';
    }
}
