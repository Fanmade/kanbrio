<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\Story;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Gets a single project by its short_name, including its stories. Only projects the authenticated user is a member of are accessible.')]
class GetProjectTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'short_name' => ['required', 'string'],
        ], [
            'short_name.required' => 'You must provide the project short_name (e.g. "PROJ").',
        ]);

        $user = $request->user();

        $project = Project::query()
            ->where('short_name', $validated['short_name'])
            ->with(['stories.project'])
            ->first();

        if ($project === null || ! $user->can('view', $project)) {
            return Response::error('No project named "'.$validated['short_name'].'" exists, or you do not have access to it.');
        }

        return Response::json([
            'short_name' => $project->short_name,
            'title' => $project->title,
            'description' => $project->description,
            'stories' => $project->stories->map(static fn (Story $story): array => [
                'reference' => $story->reference,
                'title' => $story->title,
            ])->all(),
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
            'short_name' => $schema->string()
                ->description('The project short_name, 2-4 uppercase letters (e.g. "PROJ").')
                ->required(),
        ];
    }
}
