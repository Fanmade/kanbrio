@props([
    'title',
    'count' => null,
    'collapsed' => false,
    'bodyId',
    'toggle' => 'toggleCollapsed',
])

{{--
    A foldable section header: a chevron + title + optional count badge that toggles
    a Livewire-backed collapsed state, with the section body in the default slot
    (rendered only while expanded). Pairs with the TogglesCollapsedPreference concern
    on the component. Used by the comments list and the activity feed.
--}}
<button
    type="button"
    wire:click="{{ $toggle }}"
    class="flex items-center gap-2 text-start"
    aria-expanded="{{ $collapsed ? 'false' : 'true' }}"
    aria-controls="{{ $bodyId }}"
>
    <flux:icon :name="$collapsed ? 'chevron-right' : 'chevron-down'" variant="micro" class="text-zinc-400" />
    <flux:heading size="sm">{{ $title }}</flux:heading>
    @if ($count !== null)
        <flux:badge size="sm" color="zinc">{{ $count }}</flux:badge>
    @endif
</button>

@unless ($collapsed)
    {{ $slot }}
@endunless
