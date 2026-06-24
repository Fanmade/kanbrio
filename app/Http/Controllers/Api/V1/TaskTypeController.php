<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskTypeResource;
use App\Support\ReferenceResolver;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class TaskTypeController extends Controller
{
    /**
     * List a project's configured task types, in display order.
     */
    public function index(string $short_name): AnonymousResourceCollection
    {
        $project = ReferenceResolver::project($short_name);

        abort_if($project === null || Auth::user()->cannot('view', $project), 404);

        return TaskTypeResource::collection($project->taskTypes()->get());
    }
}
