@props(['icon' => 'tag', 'heading', 'test' => null])

{{--
    A centered, dashed-border placeholder card for an empty list: an icon, a
    heading, and whatever description/call-to-action sits in the default slot.
--}}
<div
    @if ($test) data-test="{{ $test }}" @endif
    {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 rounded-lg border border-dashed border-zinc-200 p-10 text-center dark:border-white/10']) }}
>
    <flux:icon :icon="$icon" class="size-8 text-zinc-300 dark:text-zinc-600" />
    <flux:heading size="sm">{{ $heading }}</flux:heading>
    {{ $slot }}
</div>
