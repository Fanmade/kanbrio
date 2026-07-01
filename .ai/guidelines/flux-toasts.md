# Flux Toasts

Always call `Flux::toast()` with **named arguments in signature order**. The
signature is `toast($text, $heading = null, $duration = 5000, $variant = null,
$position = null, $link = null)`, so `text:` comes **first** and `variant:`
after it — never the other way round.

```php
// Correct — named, text before variant
Flux::toast(text: __('Tag created.'), variant: 'success');

// Wrong — variant first. Functionally identical, but trips the IDE's
// "named arguments order does not match parameters order" inspection.
Flux::toast(variant: 'success', text: __('Tag created.'));
```

Why named and in order: `toast()`'s second positional parameter is `$heading`,
not `$variant`, so the intuitive `toast('Saved', 'success')` silently sets the
heading to "success" and never colours the toast. Naming every argument removes
that trap; keeping them in signature order keeps the inspection quiet.

`FluxToastConventionTest` enforces that every `Flux::toast()` call opens with a
named `text:` argument.
