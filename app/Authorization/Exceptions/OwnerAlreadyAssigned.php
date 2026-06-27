<?php

namespace App\Authorization\Exceptions;

use App\Models\Project;
use RuntimeException;

/**
 * Thrown when a second user would be granted the owner role on a project. A
 * project has exactly one owner; ownership is transferred, never duplicated.
 */
class OwnerAlreadyAssigned extends RuntimeException
{
    public static function for(Project $project): self
    {
        return new self("Project [{$project->getKey()}] already has an owner.");
    }
}
