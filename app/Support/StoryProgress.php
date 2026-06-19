<?php

namespace App\Support;

/**
 * A story's completeness, derived from the state of its tasks.
 *
 * "Done" is the only status that counts toward completion; every other status
 * (Planned, ToDo, In progress) contributes to the total but not the done count.
 * Constructed from plain counts so callers that already aggregate task counts in
 * the database (e.g. the command palette) can build it without loading tasks.
 */
readonly class StoryProgress
{
    public function __construct(
        public int $done,
        public int $total,
    ) {}

    /**
     * The share of completed tasks, rounded to a whole percent (0 when empty).
     */
    public function percent(): int
    {
        return $this->total > 0 ? (int) round($this->done / $this->total * 100) : 0;
    }

    /**
     * Whether the story has any tasks to report progress on.
     */
    public function hasTasks(): bool
    {
        return $this->total > 0;
    }
}
