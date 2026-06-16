<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Story;
use App\Models\Task;

/**
 * Resolves public references (e.g. "PROJ1" or "PROJ1-3") into models, mirroring
 * the URL resolution rules used by the scoped web routes in routes/web.php.
 */
class ReferenceResolver
{
    /**
     * The short name pattern: 2-4 uppercase letters, matching the web routes.
     */
    private const SHORT_NAME = '[A-Z]{2,4}';

    /**
     * Resolve a story reference (e.g. "PROJ1") into its model.
     *
     * Returns null when the reference is malformed or no matching story exists.
     */
    public static function story(string $reference): ?Story
    {
        if (! preg_match('/^('.self::SHORT_NAME.')(\d+)$/', trim($reference), $matches)) {
            return null;
        }

        [, $shortName, $storyNumber] = $matches;

        $project = Project::query()->where('short_name', $shortName)->first();

        if ($project === null) {
            return null;
        }

        return $project->stories()
            ->with(['tasks', 'project'])
            ->where('story_number', (int) $storyNumber)
            ->first();
    }

    /**
     * Resolve a task reference (e.g. "PROJ1-3") into its model.
     *
     * Returns null when the reference is malformed or no matching task exists.
     */
    public static function task(string $reference): ?Task
    {
        if (! preg_match('/^('.self::SHORT_NAME.')(\d+)-(\d+)$/', trim($reference), $matches)) {
            return null;
        }

        [, $shortName, $storyNumber, $taskNumber] = $matches;

        $project = Project::query()->where('short_name', $shortName)->first();

        if ($project === null) {
            return null;
        }

        return Task::query()
            ->with(['assignees', 'story.project'])
            ->whereHas('story', static fn ($query) => $query
                ->where('project_id', $project->id)
                ->where('story_number', (int) $storyNumber))
            ->where('task_number', (int) $taskNumber)
            ->first();
    }
}
