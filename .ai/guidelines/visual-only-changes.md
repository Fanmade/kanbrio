# Visual-Only Changes Don't Need Tests

The general "every change must be programmatically tested" rule has one
exception: **purely presentational changes** do not require an automated test.
Verify them by eye (or in the browser) instead. Adding a brittle test that just
re-asserts the markup you wrote adds maintenance cost without catching real
regressions.

## What counts as visual-only (skip the test)

A change that only affects appearance and contains **no behavior**:

- Tailwind/CSS class changes: spacing, color, typography, sizing.
- Responsive layout tweaks (`sm:`/`md:`/`lg:` variants, stacking, flex/grid
  arrangement).
- Static markup or copy reflow that doesn't change what is rendered or when.

## Still write a test when behavior changes

Even a "small UI tweak" needs a test if it touches logic:

- Conditional rendering (`@if`/`@can`/`@unless`) that shows or hides an element.
- New or changed `wire:` actions, bindings, or computed values.
- A value that is computed, formatted, counted, or authorized.

## Rule of thumb

If the only thing that changed is **how existing markup looks** at a given
breakpoint, skip the test and check it visually. If **what** renders, or **what
it does**, changed — test it.
