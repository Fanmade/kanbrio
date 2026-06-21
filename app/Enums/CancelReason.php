<?php

namespace App\Enums;

/**
 * Why a task was canceled. Captured alongside the terminal {@see Status::Canceled}
 * state so an abandoned task stays on the record with an explanation, instead of
 * being deleted or silently closed.
 */
enum CancelReason: string
{
    case WontFix = 'wont_fix';
    case Duplicate = 'duplicate';
    case Deprecated = 'deprecated';

    /**
     * The human-readable, translatable label for the reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::WontFix => __('Won\'t fix'),
            self::Duplicate => __('Duplicate'),
            self::Deprecated => __('Deprecated'),
        };
    }

    /**
     * The Flux badge/accent color for this reason.
     */
    public function color(): string
    {
        return match ($this) {
            self::WontFix => 'zinc',
            self::Duplicate => 'sky',
            self::Deprecated => 'amber',
        };
    }

    /**
     * The Heroicon name representing this reason.
     */
    public function icon(): string
    {
        return match ($this) {
            self::WontFix => 'no-symbol',
            self::Duplicate => 'document-duplicate',
            self::Deprecated => 'archive-box-x-mark',
        };
    }

    /**
     * The case names, e.g. for API/MCP input validation and schemas.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_map(static fn (self $reason): string => $reason->name, self::cases());
    }

    /**
     * Resolve a reason from its case name (e.g. "WontFix"), or null if unknown.
     */
    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
