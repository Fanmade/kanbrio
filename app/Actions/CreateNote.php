<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;

/**
 * Creates a note owned by the given user. Visibility is clamped to the
 * public-requires-a-project invariant (also enforced by the model).
 */
class CreateNote
{
    public function handle(
        User $owner,
        string $title,
        ?string $body = null,
        ?Project $project = null,
        bool $isPublic = false,
    ): Note {
        return $owner->notes()->create([
            'title' => $title,
            'body' => $body ?: null,
            'project_id' => $project?->id,
            'is_public' => $project !== null && $isPublic,
        ]);
    }
}
