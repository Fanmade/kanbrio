@php($canManage = $this->canManageDependencies)

<div class="flex flex-col gap-3" data-test="dependencies">
    <div class="flex items-center gap-2">
        <flux:heading size="sm">{{ __('Dependencies') }}</flux:heading>
        @if ($this->isBlocked)
            <flux:badge size="sm" color="red" icon="lock-closed" data-test="blocked-badge">{{ __('Blocked') }}</flux:badge>
        @endif
    </div>

    <flux:card class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
            <flux:text size="xs" class="font-medium text-zinc-400">{{ __('Blocked by') }}</flux:text>
            @forelse ($this->blockerLinks as $link)
                @if ($link->blocker)
                    <x-dependency-item :item="$link->blocker" :link-id="$link->id" :can-remove="$canManage" />
                @endif
            @empty
                <flux:text size="sm" class="text-zinc-400">{{ __('Nothing is blocking this.') }}</flux:text>
            @endforelse
        </div>

        <div class="flex flex-col gap-1.5">
            <flux:text size="xs" class="font-medium text-zinc-400">{{ __('Blocks') }}</flux:text>
            @forelse ($this->blockingLinks as $link)
                @if ($link->dependent)
                    <x-dependency-item :item="$link->dependent" :link-id="$link->id" :can-remove="$canManage" />
                @endif
            @empty
                <flux:text size="sm" class="text-zinc-400">{{ __('This blocks nothing.') }}</flux:text>
            @endforelse
        </div>

        @if ($canManage)
            <form wire:submit="addDependency" class="flex flex-col gap-2">
                <div class="flex items-end gap-2">
                    <flux:select wire:model="dependencyDirection" :label="__('Relationship')" class="w-36">
                        <flux:select.option value="blocked_by">{{ __('Blocked by') }}</flux:select.option>
                        <flux:select.option value="blocks">{{ __('Blocks') }}</flux:select.option>
                    </flux:select>
                    <flux:input
                        wire:model="dependencyReference"
                        :label="__('Reference')"
                        placeholder="ABC1-2"
                        class="flex-1"
                        data-test="dependency-reference"
                    />
                    <flux:button type="submit" icon="plus" data-test="add-dependency">{{ __('Add') }}</flux:button>
                </div>
                <flux:error name="dependencyReference" />
            </form>
        @endif
    </flux:card>
</div>
