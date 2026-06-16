<?php

namespace App\Mcp\Tools;

use App\Enums\Status;
use App\Mcp\Concerns\RequiresWriteAccess;
use App\Support\ReferenceResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rules\Enum;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a new task in a story, identified by its reference (e.g. "PROJ1"). Requires a write-access token; the user must be a member of the project.')]
class CreateTaskTool extends Tool
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

        $statuses = implode('", "', array_map(static fn (Status $status): string => $status->value, Status::cases()));

        $validated = $request->validate([
            'reference' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', new Enum(Status::class)],
        ], [
            'reference.required' => 'You must provide the story reference to add the task to (e.g. "PROJ1").',
            'title.required' => 'You must provide a task title.',
            'due_date' => 'The due date must be a calendar date in "YYYY-MM-DD" format.',
            'status' => 'The status must be one of "'.$statuses.'".',
        ]);

        $story = ReferenceResolver::story($validated['reference']);

        if ($story === null || ! $request->user()->can('update', $story)) {
            return Response::error('No story with reference "'.$validated['reference'].'" exists, or you do not have access to it. References look like "PROJ1".');
        }

        $task = $story->tasks()->make([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);
        $task->status = isset($validated['status']) ? Status::from($validated['status']) : Status::Planned;
        $task->save();

        $task->setRelation('story', $story);

        return Response::structured([
            'reference' => $task->reference,
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'status' => $task->status->value,
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
                ->description('The reference of the story to add the task to (e.g. "PROJ1").')
                ->required(),

            'title' => $schema->string()
                ->description('The task title.')
                ->required(),

            'description' => $schema->string()
                ->description('Optional task description.'),

            'due_date' => $schema->string()
                ->description('Optional due date in "YYYY-MM-DD" format.'),

            'status' => $schema->string()
                ->enum(array_map(static fn (Status $status): string => $status->value, Status::cases()))
                ->description('Optional initial status. Defaults to "Planned".'),
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
            'reference' => $schema->string()->description('The created task reference, e.g. "PROJ1-3".')->required(),
            'title' => $schema->string()->description('The created task title.')->required(),
            'description' => $schema->string()->description('The task description; may be null.'),
            'due_date' => $schema->string()->description('The task due date in "YYYY-MM-DD" format; may be null.'),
            'status' => $schema->string()->description('The task status.')->required(),
        ];
    }
}
