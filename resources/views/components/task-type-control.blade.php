@props([
    'type',
    'types',
    'model' => 'typeId',
    'canEdit' => false,
    'size' => 'sm',
])

@if ($canEdit)
    <x-badge-dropdown :model="$model" test="task-type-control">
        <x-slot:trigger>
            @if ($type)
                <flux:badge
                    as="button"
                    :size="$size"
                    :color="$type->color"
                    :icon="$type->icon"
                    icon:trailing="chevron-down"
                    class="cursor-pointer"
                >
                    {{ $type->name }}
                </flux:badge>
            @else
                <flux:badge as="button" :size="$size" color="zinc" icon:trailing="chevron-down" class="cursor-pointer">
                    {{ __('No type') }}
                </flux:badge>
            @endif
        </x-slot:trigger>

        <flux:menu.radio value="" data-test="task-type-option-none">{{ __('No type') }}</flux:menu.radio>
        @foreach ($types as $option)
            <flux:menu.radio :value="$option->id" :icon="$option->icon" data-test="task-type-option-{{ $option->id }}">
                {{ $option->name }}
            </flux:menu.radio>
        @endforeach
    </x-badge-dropdown>
@elseif ($type)
    <x-task-type-badge :type="$type" :size="$size" />
@else
    <flux:text size="sm" class="text-zinc-400">{{ __('No type') }}</flux:text>
@endif
