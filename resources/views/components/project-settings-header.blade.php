@props(['project', 'title'])

{{--
    The header for a project settings sub-page (tags, task types): a back-to-project
    button, the page title, the project badge, and a right-aligned action in the
    default slot (e.g. a "New …" button).
--}}
<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
    <div class="flex min-w-0 items-center gap-3">
        <flux:button
            size="sm"
            variant="ghost"
            icon="arrow-left"
            :href="route('project.show', $project)"
            :aria-label="__('Back to project')"
            wire:navigate
            data-test="back-to-project"
        />
        <flux:heading size="xl" class="min-w-0 truncate">{{ $title }}</flux:heading>
        <x-project-badge :project="$project" />
    </div>

    {{ $slot }}
</div>
