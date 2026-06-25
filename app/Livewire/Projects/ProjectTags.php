<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Tag;
use App\Models\TaskType;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Per-project tag catalog management: rename (merging on a name collision),
 * recolor, delete and merge a project's tags. Renaming and recoloring are open
 * to any member holding `manage-tags`; deletion and merging are restricted to
 * `manage-settings` (admin/owner) since they destroy tags. Every change is
 * recorded in the project activity log by the Tag model itself.
 */
class ProjectTags extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public string $shortName;

    public bool $editing = false;

    public ?int $editingTagId = null;

    public string $editName = '';

    public string $editColor = 'zinc';

    /**
     * The chosen icon, or null for a colour-only tag. Declared null (not a
     * non-null default) so clearing it survives Livewire's omit-null hydration.
     */
    public ?string $editIcon = null;

    /**
     * The ids of the tags ticked for merging.
     *
     * @var list<int>
     */
    public array $selected = [];

    public bool $merging = false;

    /**
     * The tag the selected tags fold into — the one that survives the merge.
     */
    public ?int $mergeTargetId = null;

    public function mount(string $short_name): void
    {
        $this->shortName = $short_name;

        $this->authorize('manage-tags', $this->project());
    }

    #[Computed]
    public function project(): Project
    {
        $project = Project::where('short_name', $this->shortName)->firstOrFail();

        $this->authorize('manage-tags', $project);

        return $project;
    }

    /**
     * The project's tags, alphabetical, each with the number of tasks it is
     * applied to.
     *
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return $this->project()->tags()
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }

    /**
     * The ticked tags, most-used first — the candidates for a merge and the
     * options offered as the surviving tag. Scoped to the project, so a tampered
     * id from another project is silently dropped.
     *
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function selectedTags(): Collection
    {
        if ($this->selected === []) {
            return new Collection;
        }

        return $this->project()->tags()
            ->withCount('tasks')
            ->whereKey($this->selected)
            ->orderByDesc('tasks_count')
            ->orderBy('name')
            ->get();
    }

    /**
     * The colors a tag may be given: the shared palette plus the neutral default.
     *
     * @return list<string>
     */
    #[Computed]
    public function palette(): array
    {
        return [...Tag::PALETTE, 'zinc'];
    }

    /**
     * The curated Heroicons a tag may carry — the same set as task types.
     *
     * @return list<string>
     */
    #[Computed]
    public function icons(): array
    {
        return TaskType::ICONS;
    }

    /**
     * Open the edit dialog for one of the project's tags.
     */
    public function startEdit(int $tagId): void
    {
        $this->authorize('manage-tags', $this->project());

        $tag = $this->project()->tags()->whereKey($tagId)->firstOrFail();

        $this->editingTagId = $tag->id;
        $this->editName = $tag->name;
        $this->editColor = $tag->color;
        $this->editIcon = $tag->icon;
        $this->resetValidation();
        $this->editing = true;
    }

    /**
     * Clear the chosen icon, so the tag is identified by colour alone.
     */
    public function clearIcon(): void
    {
        $this->editIcon = null;
    }

    /**
     * Persist the edited name and color. A name that collides (case-insensitively)
     * with another of the project's tags merges this tag into that one rather
     * than failing the unique constraint.
     */
    public function saveEdit(): void
    {
        $project = $this->project();
        $this->authorize('manage-tags', $project);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editColor' => ['required', 'string', 'in:'.implode(',', $this->palette())],
            'editIcon' => ['nullable', 'string', 'in:'.implode(',', TaskType::ICONS)],
        ]);

        $tag = $project->tags()->whereKey($this->editingTagId)->firstOrFail();
        $name = trim($validated['editName']);

        $collision = $project->tags()
            ->whereKeyNot($tag->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($collision !== null) {
            $collision->recolor($validated['editColor']);
            $collision->forceFill(['icon' => $this->editIcon])->save();
            $tag->mergeInto($collision);

            Flux::toast(variant: 'success', text: __('Tags merged.'));
        } else {
            $tag->rename($name);
            $tag->recolor($validated['editColor']);
            $tag->forceFill(['icon' => $this->editIcon])->save();

            Flux::toast(variant: 'success', text: __('Tag updated.'));
        }

        $this->editing = false;
        $this->editingTagId = null;
        unset($this->tags);
    }

    /**
     * Delete one of the project's tags, detaching it from every task it was
     * applied to. Restricted to manage-settings (admin/owner).
     */
    public function deleteTag(int $tagId): void
    {
        $project = $this->project();
        $this->authorize('manageSettings', $project);

        $tag = $project->tags()->whereKey($tagId)->firstOrFail();
        $tag->deleteWithActivity();

        unset($this->tags);

        Flux::toast(variant: 'success', text: __('Tag deleted.'));
    }

    /**
     * Open the merge dialog for the ticked tags, defaulting the surviving tag to
     * the most-used of them. A no-op unless at least two tags are selected.
     */
    public function startMerge(): void
    {
        $this->authorize('manageSettings', $this->project());

        $selected = $this->selectedTags();

        if ($selected->count() < 2) {
            return;
        }

        $this->mergeTargetId = $selected->first()->id;
        $this->merging = true;
    }

    /**
     * Fold every other selected tag into the chosen surviving tag — re-pointing
     * their tasks first — then drop them. Restricted to manage-settings
     * (admin/owner). A no-op unless at least two valid tags are selected and the
     * surviving tag is one of them.
     */
    public function mergeTags(): void
    {
        $project = $this->project();
        $this->authorize('manageSettings', $project);

        $selected = $this->selectedTags();
        $target = $selected->firstWhere('id', $this->mergeTargetId);

        if ($target === null || $selected->count() < 2) {
            return;
        }

        $selected->reject(static fn (Tag $tag): bool => $tag->is($target))
            ->each(static fn (Tag $tag) => $tag->mergeInto($target));

        $this->reset('selected', 'merging', 'mergeTargetId');
        unset($this->tags, $this->selectedTags);

        Flux::toast(variant: 'success', text: __('Tags merged.'));
    }

    public function render(): View
    {
        return view('livewire.projects.project-tags');
    }
}
