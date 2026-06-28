<?php

use App\Support\IconCatalog;

it('exposes the configured icon set as a list of strings', function () {
    expect(IconCatalog::available())
        ->toBe(config('kanvigo.icons'))
        ->toContain('tag');
});

it('keeps a known icon and rejects an unknown or null one', function () {
    expect(IconCatalog::validOrNull('tag'))->toBe('tag')
        ->and(IconCatalog::validOrNull('not-a-real-icon'))->toBeNull()
        ->and(IconCatalog::validOrNull(null))->toBeNull();
});
