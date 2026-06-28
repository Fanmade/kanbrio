<?php

namespace App\Support;

/**
 * Typed access to the curated Heroicon set offered in the icon pickers (task
 * types and tags). The list itself lives in config/kanvigo.php; this exposes it
 * as a re-indexed list of strings for validation rules and the picker views.
 */
class IconCatalog
{
    /**
     * The icons a task type or tag may carry.
     *
     * @return list<string>
     */
    public static function available(): array
    {
        /** @var array<array-key, mixed> $icons */
        $icons = (array) config('kanvigo.icons', []);

        return array_values(array_map(static fn (mixed $icon): string => (string) $icon, $icons));
    }

    /**
     * Return the icon only when it belongs to the curated set, otherwise null —
     * so a preview/badge never tries to render a blank or stale icon value.
     */
    public static function validOrNull(?string $icon): ?string
    {
        return $icon !== null && in_array($icon, self::available(), true) ? $icon : null;
    }
}
