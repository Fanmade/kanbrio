<flux:card>
    <button
        type="button"
        wire:click="toggleCollapsed"
        class="flex items-center gap-2 text-start"
        aria-expanded="{{ $collapsed ? 'false' : 'true' }}"
        aria-controls="activity-body-{{ $morphSubjectId }}"
    >
        <flux:icon :name="$collapsed ? 'chevron-right' : 'chevron-down'" variant="micro" class="text-zinc-400" />
        <flux:heading size="sm">{{ __('Activity') }}</flux:heading>
        <flux:badge size="sm" color="zinc">{{ $this->activityCount }}</flux:badge>
    </button>

    @unless ($collapsed)
        <ul id="activity-body-{{ $morphSubjectId }}" class="mt-3 flex flex-col gap-3">
            @forelse ($this->activities as $activity)
                @php
                    $old = \App\Enums\Status::tryFrom((string) $activity->old_value)?->label() ?? $activity->old_value;
                    $new = \App\Enums\Status::tryFrom((string) $activity->new_value)?->label() ?? $activity->new_value;
                    $oldPriority = \App\Enums\Priority::tryFrom((int) $activity->old_value)?->label() ?? $activity->old_value;
                    $newPriority = \App\Enums\Priority::tryFrom((int) $activity->new_value)?->label() ?? $activity->new_value;
                    $newValues = (array) json_decode((string) $activity->new_value, true);
                    $oldValues = (array) json_decode((string) $activity->old_value, true);
                    $conjunction = ' '.__('and').' ';
                    $addedList = \Illuminate\Support\Arr::join($newValues, ', ', $conjunction);
                    $removedList = \Illuminate\Support\Arr::join($oldValues, ', ', $conjunction);
                    $assigneeDescription = match (true) {
                        $newValues !== [] && $oldValues !== [] => __('assigned :added, unassigned :removed', ['added' => $addedList, 'removed' => $removedList]),
                        $newValues !== [] => __('assigned :users', ['users' => $addedList]),
                        $oldValues !== [] => __('unassigned :users', ['users' => $removedList]),
                        default => __('updated the assignees'),
                    };
                    $tagDescription = match (true) {
                        $newValues !== [] && $oldValues !== [] => __('added the tags :added, removed :removed', ['added' => $addedList, 'removed' => $removedList]),
                        $newValues !== [] => __('added the tags :tags', ['tags' => $addedList]),
                        $oldValues !== [] => __('removed the tags :tags', ['tags' => $removedList]),
                        default => __('updated the tags'),
                    };
                    $depAdded = json_decode((string) $activity->new_value, true);
                    $depRemoved = json_decode((string) $activity->old_value, true);
                    $dependencyDescription = match (true) {
                        is_array($depAdded) && ($depAdded['direction'] ?? null) === 'blocked_by' => __('is now blocked by :ref', ['ref' => $depAdded['reference']]),
                        is_array($depAdded) && ($depAdded['direction'] ?? null) === 'blocks' => __('now blocks :ref', ['ref' => $depAdded['reference']]),
                        is_array($depRemoved) && ($depRemoved['direction'] ?? null) === 'blocked_by' => __('is no longer blocked by :ref', ['ref' => $depRemoved['reference']]),
                        is_array($depRemoved) && ($depRemoved['direction'] ?? null) === 'blocks' => __('no longer blocks :ref', ['ref' => $depRemoved['reference']]),
                        default => __('updated the dependencies'),
                    };
                    $description = match ($activity->action) {
                        'created' => __('created this'),
                        'status_changed' => __('changed status from :old to :new', ['old' => $old, 'new' => $new]),
                        'priority_changed' => __('changed priority from :old to :new', ['old' => $oldPriority, 'new' => $newPriority]),
                        'assignee_changed' => $assigneeDescription,
                        'dependency_changed' => $dependencyDescription,
                        'tags_changed' => $tagDescription,
                        'archived' => __('archived this'),
                        'unarchived' => __('restored this from the archive'),
                        'commented' => __('added a comment'),
                        default => $activity->action,
                    };
                @endphp
                <li class="flex items-start gap-2 text-sm">
                    <x-user-avatar :user="$activity->user" :name="$activity->user?->name ?? __('System')" />
                    <div class="text-zinc-600 dark:text-zinc-300">
                        <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $activity->user?->name ?? __('System') }}</span>
                        {{ $description }}
                        <span class="text-zinc-400">· {{ $activity->created_at?->diffForHumans() }}</span>
                        @if ($activity->token_name)
                            <span class="text-zinc-400" data-test="activity-source">· {{ __('via token “:name”', ['name' => $activity->token_name]) }}</span>
                        @endif
                    </div>
                </li>
            @empty
                <li><flux:text size="sm" class="text-zinc-400">{{ __('No activity yet.') }}</flux:text></li>
            @endforelse
        </ul>
    @endunless
</flux:card>
