<?php

use App\Enums\Priority;

it('ranks priorities from highest to lowest for pickers', function () {
    // KAN-293: the most urgent priority must sit at the top of every picker, not
    // the bottom. descending() is the single source of order for the selects.
    expect(Priority::descending())->toBe([
        Priority::Highest,
        Priority::High,
        Priority::Medium,
        Priority::Low,
        Priority::Lowest,
    ]);
});

it('gives every priority a distinct color and icon', function () {
    $colors = array_map(static fn (Priority $p): string => $p->color(), Priority::cases());
    $icons = array_map(static fn (Priority $p): string => $p->icon(), Priority::cases());

    expect($colors)->toBe(array_unique($colors))
        ->and($icons)->toBe(array_unique($icons));
});
