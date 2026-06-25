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
 * recolor and delete a project's tags. Renaming and recoloring are open to any
 * member holding `manage-tags`; deletion is restricted to `manage-settings`
 * (admin/owner). Every change is recorded in the project activity log by the
 * Tag model itself.
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

    public function render(): View
    {
        return view('livewire.projects.project-tags');
    }
}
