<?php

namespace App\Enums;

/**
 * A user's choice for how the Done/Cancel parent→children status cascade behaves
 * when a parent task is closed with open subtasks still open:
 *
 * - Ask: prompt every time (Done leaves children by default, Cancel cascades).
 * - Always: cascade to the open subtasks without prompting.
 * - Never: only change the parent, never the children.
 *
 * The silent child→parent "in progress" bump is unaffected — it is always silent
 * with an undo.
 */
enum CascadePreference: string
{
    case Ask = 'ask';
    case Always = 'always';
    case Never = 'never';

    /**
     * The human-readable, translatable label for the preference.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ask => __('Ask each time'),
            self::Always => __('Always apply to subtasks'),
            self::Never => __('Never apply to subtasks'),
        };
    }
}
