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

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-800 dark:text-white">{{ __('Description') }}</span>
                    <div class="flex gap-1">
                        <flux:button type="button" size="xs" :variant="$showPreview ? 'ghost' : 'filled'" wire:click="$set('showPreview', false)" data-test="create-task-write">{{ __('Write') }}</flux:button>
                        <flux:button type="button" size="xs" :variant="$showPreview ? 'filled' : 'ghost'" wire:click="$set('showPreview', true)" data-test="create-task-preview">{{ __('Preview') }}</flux:button>
                    </div>
                </div>

                @if ($showPreview)
                    <div class="min-h-[4.5rem] rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700" data-test="create-task-preview-content">
                        @if (trim($description) !== '')
                            <x-markdown :content="$description" />
                        @else
                            <flux:text class="text-sm text-zinc-400">{{ __('Nothing to preview.') }}</flux:text>
                        @endif
                    </div>
                @else
                    <flux:textarea wire:model="description" rows="3" :description="__('Markdown supported.')" data-test="create-task-description" />
                @endif
            </div>

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

            <div>
                <span class="text-sm font-medium text-zinc-800 dark:text-white">{{ __('Tags') }}</span>

                @if (count($tagNames) > 0)
                    <div class="mt-1 flex flex-wrap gap-1" data-test="create-task-tags">
                        @foreach ($tagNames as $index => $name)
                            <flux:badge size="sm" color="zinc" variant="pill" wire:key="draft-tag-{{ $index }}">
                                {{ $name }}
                                <flux:badge.close wire:click="removeDraftTag({{ $index }})" :aria-label="__('Remove tag')" data-test="create-task-remove-tag-{{ $index }}" />
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                <flux:input
                    size="sm"
                    class="mt-1"
                    wire:model.live.debounce.200ms="tagQuery"
                    :placeholder="__('Find or create a tag')"
                    data-test="create-task-tag-input"
                />

                @if (trim($tagQuery) !== '')
                    <div class="mt-1 flex flex-col gap-0.5" role="listbox">
                        @foreach ($this->tagSuggestions as $index => $suggestion)
                            <flux:button
                                type="button"
                                size="xs"
                                variant="ghost"
                                class="justify-start!"
                                wire:click="addSuggestedTag({{ $index }})"
                                data-test="create-task-tag-suggestion-{{ \Illuminate\Support\Str::slug($suggestion['name']) }}"
                            >
                                <x-tag-dot :color="$suggestion['color']" class="me-1.5 size-2" />{{ $suggestion['name'] }}
                            </flux:button>
                        @endforeach

                        @if ($this->canCreateTag)
                            <flux:button
                                type="button"
                                size="xs"
                                variant="ghost"
                                icon="plus"
                                class="justify-start!"
                                wire:click="createDraftTag"
                                data-test="create-task-tag-create"
                            >
                                {{ __('Create') }} “{{ trim($tagQuery) }}”
                            </flux:button>
                        @endif
                    </div>
                @endif
            </div>

            @if ($this->projectId && $this->members->isNotEmpty())
                <flux:select variant="listbox" multiple wire:model="assigneeIds" :label="__('Assignees')" :placeholder="__('Select assignees')" data-test="create-task-assignees">
                    @foreach ($this->members as $member)
                        <flux:select.option :value="$member->id">{{ $member->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="create-task-submit">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
