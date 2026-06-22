<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Freshness-token cache for the kanban boards. Each project carries a version
 * counter bumped whenever one of its tasks changes; board queries are cached
 * under a key that embeds that version, so an idle poll (no writes since the
 * last build) is a cheap version read + cache hit instead of a full task scan,
 * while any write bumps the version and the next render rebuilds.
 *
 * A short TTL on the cached results is a backstop: should any write path ever
 * miss its invalidation, the stale entry self-heals within the window.
 */
class BoardCache
{
    /**
     * Seconds a built board result lives before it must be rebuilt regardless of
     * the version (garbage-collects superseded versions and backstops misses).
     */
    public const int TTL = 60;

    private const string VERSION_PREFIX = 'board:ver:';

    /**
     * The current version for a single project.
     */
    public static function version(int $projectId): int
    {
        return (int) Cache::get(self::VERSION_PREFIX.$projectId, 0);
    }

    /**
     * A combined, order-independent token for a set of projects — changes when
     * any one of them is touched. Used to key the global (cross-project) board.
     *
     * @param  array<int, int>  $projectIds
     */
    public static function versionFor(array $projectIds): string
    {
        sort($projectIds);

        $parts = array_map(static fn (int $id): string => $id.':'.self::version($id), $projectIds);

        return substr(sha1(implode(',', $parts)), 0, 16);
    }

    /**
     * Invalidate every board showing the given project by bumping its version.
     */
    public static function touch(int $projectId): void
    {
        Cache::forever(self::VERSION_PREFIX.$projectId, self::version($projectId) + 1);
    }

    /**
     * Remember a built board result under the short backstop TTL.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember($key, self::TTL, $callback);
    }
}
