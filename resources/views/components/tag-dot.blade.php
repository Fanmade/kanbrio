@props(['color' => 'zinc', 'icon' => null])

{{--
    A tag's leading marker: its Heroicon when one is set, otherwise a small dot —
    both tinted with the tag's color. The color → class maps are written out in
    full (rather than an interpolated `bg-{$color}-500`) so Tailwind's JIT
    compiler keeps the classes — the same approach Flux uses for its badge colors.
--}}
@php
    $background = match ($color) {
        'red' => 'bg-red-500',
        'orange' => 'bg-orange-500',
        'amber' => 'bg-amber-500',
        'yellow' => 'bg-yellow-500',
        'lime' => 'bg-lime-500',
        'green' => 'bg-green-500',
        'emerald' => 'bg-emerald-500',
        'teal' => 'bg-teal-500',
        'cyan' => 'bg-cyan-500',
        'sky' => 'bg-sky-500',
        'blue' => 'bg-blue-500',
        'indigo' => 'bg-indigo-500',
        'violet' => 'bg-violet-500',
        'purple' => 'bg-purple-500',
        'fuchsia' => 'bg-fuchsia-500',
        'pink' => 'bg-pink-500',
        'rose' => 'bg-rose-500',
        default => 'bg-zinc-400',
    };
    $foreground = match ($color) {
        'red' => 'text-red-500',
        'orange' => 'text-orange-500',
        'amber' => 'text-amber-500',
        'yellow' => 'text-yellow-500',
        'lime' => 'text-lime-500',
        'green' => 'text-green-500',
        'emerald' => 'text-emerald-500',
        'teal' => 'text-teal-500',
        'cyan' => 'text-cyan-500',
        'sky' => 'text-sky-500',
        'blue' => 'text-blue-500',
        'indigo' => 'text-indigo-500',
        'violet' => 'text-violet-500',
        'purple' => 'text-purple-500',
        'fuchsia' => 'text-fuchsia-500',
        'pink' => 'text-pink-500',
        'rose' => 'text-rose-500',
        default => 'text-zinc-400',
    };
@endphp

@if ($icon)
    <span {{ $attributes->merge(['class' => "inline-flex shrink-0 {$foreground}"]) }}>
        <flux:icon :icon="$icon" variant="micro" class="size-3.5" />
    </span>
@else
    <span {{ $attributes->merge(['class' => "inline-block size-2 shrink-0 rounded-full {$background}"]) }}></span>
@endif
