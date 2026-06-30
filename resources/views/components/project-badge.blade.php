@props(['project'])

{{-- A project's short name as an indigo badge. Extra attributes (size, variant,
     …) pass through to flux:badge. --}}
<flux:badge color="indigo" {{ $attributes }}>{{ $project->short_name }}</flux:badge>
