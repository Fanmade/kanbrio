{{-- Parent rail row: the task's place in the tree, with a control to re-parent it
     or detach it to the top level. State lives in the ManagesParent trait. --}}
@php($parent = $this->task->parent)
<x-rail-row :label="__('Parent')">
    <div class="flex items-center gap-2">
        @if ($parent)
            <a
                href="{{ route('task.show', ['short_name' => $this->task->project->short_name, 'task_number' => $parent->task_number]) }}"
                wire:navigate
                class="font-mono text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100"
                data-test="parent-link"
            >{{ $parent->reference }}</a>
        @else
            <flux:text size="sm" class="text-zinc-400">{{ __('Top-level task') }}</flux:text>
        @endif

        @can('update', $this->task)
            <flux:button size="xs" variant="ghost" wire:click="startMoveParent" data-test="move-task">
                {{ __('Move') }}
            </flux:button>
        @endcan
    </div>
</x-rail-row>
