@props(['tags'])

@if ($tags->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
        @foreach ($tags as $tag)
            <flux:badge size="sm" color="zinc" variant="pill" icon="tag">{{ $tag->name }}</flux:badge>
        @endforeach
    </div>
@endif
