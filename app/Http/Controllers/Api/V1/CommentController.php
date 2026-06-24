<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Support\ReferenceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Add a comment to a project, by its short name.
     */
    public function storeOnProject(Request $request, string $short_name): CommentResource
    {
        $project = ReferenceResolver::project($short_name);

        abort_if($project === null || Auth::user()->cannot('view', $project), 404);

        return $this->addComment($request, $project);
    }

    /**
     * Add a comment to a task, by its reference (e.g. "PROJ-42").
     */
    public function storeOnTask(Request $request, string $reference): CommentResource
    {
        $task = ReferenceResolver::task($reference);

        abort_if($task === null || Auth::user()->cannot('view', $task), 404);

        return $this->addComment($request, $task);
    }

    /**
     * Create the comment on the resolved commentable, recording the activity and
     * subscribing/notifying exactly as the web and MCP paths do.
     */
    protected function addComment(Request $request, Project|Task $commentable): CommentResource
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var Comment $comment */
        $comment = $commentable->comments()->create([
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        $commentable->recordActivity('commented');

        return new CommentResource($comment->load('user'));
    }
}
