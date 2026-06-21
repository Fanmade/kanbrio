<div>
    <flux:modal wire:model="show" class="md:w-[32rem]" data-test="create-task-modal">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('New task') }}</flux:heading>

            <flux:select wire:model.live="projectId" :label="__('Project')" :placeholder="__('Select a project')" data-test="create-task-project">
                @foreach ($this->projects as $project)
                    <flux:select.option :value="$project->id">{{ $project->short_name }} · {{ $project->title }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->projectId && count($this->parentOptions) > 0)
                <flux:select wire:model="parentId" :label="__('Parent task')" :placeholder="__('None (top-level task)')" data-test="create-task-parent">
                    <flux:select.option :value="null">{{ __('None (top-level task)') }}</flux:select.option>
                    @foreach ($this->parentOptions as $id => $label)
                        <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="title" :label="__('Title')" data-test="create-task-title" />

            <flux:textarea wire:model="description" :label="__('Description')" rows="3" data-test="create-task-description" />

            <flux:select wire:model="priority" :label="__('Priority')" data-test="create-task-priority">
                @foreach (\App\Enums\Priority::ordered() as $priority)
                    <flux:select.option :value="$priority->value">{{ $priority->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model="dueDate" :label="__('Due date')" :description="__('Optional')" data-test="create-task-due-date" />

            <flux:select wire:model="status" :label="__('Status')" data-test="create-task-status">
                @foreach (\App\Enums\Status::columns() as $status)
                    <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="create-task-submit">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
