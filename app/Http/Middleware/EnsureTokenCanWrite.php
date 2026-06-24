<?php

namespace App\Http\Middleware;

use App\Enums\TokenAbility;
use App\Mcp\Concerns\RequiresWriteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a mutating API endpoint on the access token carrying the `write` ability.
 * Read-only tokens (and any token missing `write`) get a 403. Mirrors the MCP
 * server's {@see RequiresWriteAccess} so both interfaces enforce
 * the same read/write split.
 */
class EnsureTokenCanWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->tokenCan(TokenAbility::Write->value) === true,
            403,
            __('This action requires a token with write access.'),
        );

        return $next($request);
    }
}
