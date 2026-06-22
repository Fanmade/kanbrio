<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\PresentsNotes;
use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists the authenticated user\'s notes plus any public notes attached to projects the user is a member of. Each note reports its numeric id, title, attached project (if any), whether it is public, whether the user owns it, and the task it was converted into (if any). The body is omitted here; use get-note for a single note\'s body.')]
#[IsReadOnly]
class ListNotesTool extends Tool
{
    use PresentsNotes;

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->authenticatedUser($request);
        $projectIds = $user->projects()->pluck('projects.id');

        $notes = Note::query()
            ->where('title', '!=', '')
            ->where(static function (Builder $query) use ($user, $projectIds): void {
                $query->where('user_id', $user->id)
                    ->orWhere(static fn (Builder $shared): Builder => $shared
                        ->where('is_public', true)
                        ->whereIn('project_id', $projectIds));
            })
            ->with(['project', 'convertedTask.project'])
            ->latest('updated_at')
            ->get()
            ->map(fn (Note $note): array => $this->notePayload($note, $user, withBody: false))
            ->all();

        return Response::structured([
            'notes' => $notes,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'notes' => $schema->array()->items(
                $schema->object([
                    'id' => $schema->integer()->description('The note id.')->required(),
                    'title' => $schema->string()->description('The note title.')->required(),
                    'project' => $schema->string()->description('The attached project short_name, or null.'),
                    'is_public' => $schema->boolean()->description('Whether the note is public to its project.')->required(),
                    'owned' => $schema->boolean()->description('Whether the authenticated user owns the note.')->required(),
                    'converted_task' => $schema->string()->description('The task reference this note was converted into, or null.'),
                ])
            )->description('The accessible notes, newest first.')->required(),
        ];
    }
}
