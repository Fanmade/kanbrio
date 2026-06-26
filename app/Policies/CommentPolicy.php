<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    /**
     * A user may edit their own comment, or any comment in a project where they
     * hold moderate-comments.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $this->canModerate($user, $comment);
    }

    /**
     * A user may delete their own comment, or any comment in a project where they
     * hold moderate-comments.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $this->canModerate($user, $comment);
    }

    /**
     * Own (non-deleted) comments are always editable/deletable by their author;
     * others' comments need the moderate-comments permission in the project.
     */
    private function canModerate(User $user, Comment $comment): bool
    {
        if ($comment->is_deleted) {
            return false;
        }

        if ($comment->user_id === $user->id) {
            return true;
        }

        $project = $this->projectFor($comment);

        return $project !== null && $user->hasScopedPermission('moderate-comments', $project);
    }

    /**
     * The project a comment ultimately belongs to (via its task or project).
     */
    private function projectFor(Comment $comment): ?Project
    {
        $commentable = $comment->commentable;

        return match (true) {
            $commentable instanceof Project => $commentable,
            $commentable instanceof Task => $commentable->project,
            default => null,
        };
    }
}
