<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\Project;

/**
 * Updates a note's title, body, project attachment and visibility, clamping to
 * the public-requires-a-project invariant (also enforced by the model).
 */
class UpdateNote
{
    public function handle(
        Note $note,
        string $title,
        ?string $body = null,
        ?Project $project = null,
        bool $isPublic = false,
    ): Note {
        $note->update([
            'title' => $title,
            'body' => $body ?: null,
            'project_id' => $project?->id,
            'is_public' => $project !== null && $isPublic,
        ]);

        return $note;
    }
}
