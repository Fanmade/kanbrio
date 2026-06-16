<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\GetStoryTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListStoriesTool;
use App\Mcp\Tools\ListTasksTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Kanbrio')]
#[Version('0.1.0')]
#[Instructions(<<<'TEXT'
    Kanbrio is a project-management board. The data model is a hierarchy:

    - A project groups work and has a short_name (2-4 uppercase letters), title and description.
    - A project contains stories. A story is referenced by its project short_name plus its
      number, e.g. "PROJ1".
    - A story contains tasks. A task is referenced by its story reference plus its number,
      e.g. "PROJ1-3". Each task has a status: one of "Planned", "ToDo", "In progress" or "Done".

    You act as the authenticated user. Every tool is read-only and only ever returns data for
    projects the user is a member of; stories and tasks inherit access from their project. If a
    project, story or task does not exist or the user cannot view it, the tool returns an error.
    TEXT)]
class KanbrioServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListProjectsTool::class,
        GetProjectTool::class,
        ListStoriesTool::class,
        GetStoryTool::class,
        ListTasksTool::class,
        GetTaskTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
