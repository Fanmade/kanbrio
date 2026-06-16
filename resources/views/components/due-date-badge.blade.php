@props(['date', 'size' => 'sm'])

@if ($date)
    @php($isOverdue = ($date->isPast() && ! $date->isToday()))
    <flux:badge :size="$size" :color="$isOverdue ? 'red' : 'zinc'" icon="calendar">
        {{ $date->format('M j, Y') }}
    </flux:badge>
@endif
