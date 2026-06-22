<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    /**
     * A note is visible to its owner always, and to a project's members only when
     * it is public and attached to that project.
     */
    public function view(User $user, Note $note): bool
    {
        if ($note->user_id === $user->id) {
            return true;
        }

        return $note->is_public
            && $note->project !== null
            && $user->can('view', $note->project);
    }

    /**
     * Any authenticated user may capture a note.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner may edit a note.
     */
    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    /**
     * Only the owner may delete a note.
     */
    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    /**
     * Only the owner may change a note's project attachment or visibility.
     */
    public function changeVisibility(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }
}
