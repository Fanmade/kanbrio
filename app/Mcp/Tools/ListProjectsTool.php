<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists the projects the authenticated user is a member of, with their short_name, title, description and story count.')]
class ListProjectsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $projects = $request->user()->projects()
            ->withCount('stories')
            ->orderBy('title')
            ->get()
            ->map(static fn (Project $project): array => [
                'short_name' => $project->short_name,
                'title' => $project->title,
                'description' => $project->description,
                'story_count' => $project->stories_count,
            ]);

        return Response::json([
            'projects' => $projects->all(),
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
            //
        ];
    }
}
