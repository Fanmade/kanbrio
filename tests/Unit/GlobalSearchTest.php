<?php

use App\Support\GlobalSearch;

it('selects a case-insensitive LIKE operator per driver', function (string $driver, string $expected) {
    expect(GlobalSearch::likeOperatorFor($driver))->toBe($expected);
})->with([
    // PostgreSQL `like` is case-sensitive, so it must use `ilike`.
    'postgres' => ['pgsql', 'ilike'],
    // SQLite/MySQL `like` already folds case and have no `ilike` keyword.
    'sqlite' => ['sqlite', 'like'],
    'mysql' => ['mysql', 'like'],
    'mariadb' => ['mariadb', 'like'],
    'sqlserver' => ['sqlsrv', 'like'],
]);
