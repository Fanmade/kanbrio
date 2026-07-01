<?php

use Symfony\Component\Finder\Finder;

/**
 * Guards against the RefreshDatabase footgun: the trait is opt-in per Feature
 * test (see tests/Pest.php), so a new DB-touching test that forgets it would
 * silently leak rows into the shared database and cause order-dependent flakes.
 *
 * Every Feature test that writes to the database must therefore declare
 * `uses(RefreshDatabase::class)` — unless it rebuilds the schema itself with
 * `migrate:fresh` (the migration tests, whose DDL can't run inside the trait's
 * wrapping transaction).
 */
it('requires every database-touching Feature test to isolate its database', function () {
    $dbWriteNeedles = ['->create(', '->make(', '->save(', 'factory(', 'DB::'];

    $offenders = [];

    foreach (Finder::create()->files()->in(__DIR__)->name('*.php') as $file) {
        // Skip this guard itself — it names the needles as string literals.
        if ($file->getRealPath() === __FILE__) {
            continue;
        }

        $source = $file->getContents();

        $touchesDatabase = collect($dbWriteNeedles)
            ->contains(static fn (string $needle): bool => str_contains($source, $needle));

        $isolated = str_contains($source, 'RefreshDatabase::class')
            || str_contains($source, 'migrate:fresh');

        if ($touchesDatabase && ! $isolated) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe(
        [],
        'These Feature tests write to the database without isolating it — add '
        .'`uses(RefreshDatabase::class);` (or rebuild the schema with migrate:fresh): '
        .implode(', ', $offenders),
    );
});
