<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\RequiresWriteAccess;
use App\Support\ReferenceResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Updates a story\'s title and/or description, identified by its reference (e.g. "PROJ1"). Requires a write-access token; the user must be a member of the project.')]
class UpdateStoryTool extends Tool
{
    use RequiresWriteAccess;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->denyWithoutWriteAccess($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'reference' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'reference.required' => 'You must provide the story reference (e.g. "PROJ1").',
            'due_date' => 'The due date must be a calendar date in "YYYY-MM-DD" format. Pass null to clear it.',
        ]);

        $story = ReferenceResolver::story($validated['reference']);

        if ($story === null || ! $request->user()->can('update', $story)) {
            return Response::error('No story with reference "'.$validated['reference'].'" exists, or you do not have access to it. References look like "PROJ1".');
        }

        $updates = [];

        if ($request->has('title')) {
            $updates['title'] = $validated['title'];
        }

        if ($request->has('description')) {
            $updates['description'] = $validated['description'];
        }

        if ($request->has('due_date')) {
            $updates['due_date'] = $validated['due_date'];
        }

        if ($updates === []) {
            return Response::error('Provide a title, description and/or due date to update.');
        }

        $story->update($updates);

        return Response::structured([
            'reference' => $story->reference,
            'title' => $story->title,
            'description' => $story->description,
            'due_date' => $story->due_date?->format('Y-m-d'),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reference' => $schema->string()
                ->description('The reference of the story to update (e.g. "PROJ1").')
                ->required(),

            'title' => $schema->string()
                ->description('New title for the story.'),

            'description' => $schema->string()
                ->description('New description for the story.'),

            'due_date' => $schema->string()
                ->description('New due date in "YYYY-MM-DD" format. Pass null to clear it.'),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'reference' => $schema->string()->description('The story reference, e.g. "PROJ1".')->required(),
            'title' => $schema->string()->description('The updated story title.')->required(),
            'description' => $schema->string()->description('The updated story description; may be null.'),
            'due_date' => $schema->string()->description('The story due date in "YYYY-MM-DD" format; may be null.'),
        ];
    }
}
