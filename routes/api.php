<?php

use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public REST API — version 1
|--------------------------------------------------------------------------
|
| Authenticated with Sanctum personal access tokens (the same read/write
| abilities the MCP server uses). Every response is scoped to the token
| owner's projects, exactly like the rest of the app — references that exist
| but belong to another user's projects 404 rather than leaking their
| existence. Mutating endpoints (added under KAN-45 / KAN-29) additionally
| require the `write` token ability.
|
*/

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(static function (): void {
        Route::get('user', UserController::class)->name('user');

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{short_name}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{short_name}/tasks', [TaskController::class, 'index'])->name('projects.tasks.index');

        Route::get('tasks/{reference}', [TaskController::class, 'show'])->name('tasks.show');

        // Mutations additionally require a token with the `write` ability.
        Route::middleware('token.write')->group(static function (): void {
            Route::post('projects/{short_name}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
            Route::patch('tasks/{reference}', [TaskController::class, 'update'])->name('tasks.update');

            Route::post('projects/{short_name}/comments', [CommentController::class, 'storeOnProject'])->name('projects.comments.store');
            Route::post('tasks/{reference}/comments', [CommentController::class, 'storeOnTask'])->name('tasks.comments.store');
        });
    });
