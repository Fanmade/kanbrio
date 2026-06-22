<?php

namespace App\Mcp\Concerns;

use App\Models\Note;
use App\Models\User;
use Laravel\Mcp\Request;
use RuntimeException;

trait PresentsNotes
{
    /**
     * The authenticated user as the concrete model. A tool only ever runs for an
     * authenticated token, so this narrows the request's user type honestly.
     */
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new RuntimeException('The MCP request is not authenticated.');
        }

        return $user;
    }

    /**
     * The structured payload for a single note.
     *
     * @return array<string, mixed>
     */
    protected function notePayload(Note $note, User $user, bool $withBody = true): array
    {
        $payload = [
            'id' => $note->id,
            'title' => $note->title,
            'project' => $note->project?->short_name,
            'is_public' => $note->is_public,
            'owned' => $note->user_id === $user->id,
            'converted_task' => $note->convertedTask?->reference,
        ];

        if ($withBody) {
            $payload['body'] = $note->body;
        }

        return $payload;
    }
}
