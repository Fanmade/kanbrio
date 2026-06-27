<?php

namespace App\Authorization;

use Illuminate\Support\Str;

/**
 * Presentation catalog for the project permission set: maps each catalog
 * permission name to a human-readable, translatable label. This is the single
 * source of truth for how permissions are shown in the role picker; the raw
 * names stay the canonical identifiers (and drive the data-test selectors).
 *
 * Group labels are intentionally not mapped — the {@see ProjectRoleProvisioner}
 * group keys already read well as headings.
 */
class PermissionCatalog
{
    /**
     * Permission name => English label. The label doubles as the translation key
     * in lang/de.json. Every permission in {@see ProjectRoleProvisioner::CATALOG}
     * has an entry; PermissionCatalogTest guards that and the German coverage.
     *
     * @var array<string, string>
     */
    public const array LABELS = [
        'view-project' => 'View project',
        'manage-settings' => 'Manage settings',
        'delete-project' => 'Delete project',
        'view-activity-log' => 'View activity log',
        'manage-members' => 'Manage members',
        'invite-members' => 'Invite members',
        'manage-roles' => 'Manage roles',
        'create-task' => 'Create tasks',
        'edit-task' => 'Edit tasks',
        'delete-task' => 'Delete tasks',
        'close-task' => 'Close tasks',
        'cancel-task' => 'Cancel tasks',
        'archive-task' => 'Archive tasks',
        'manage-dependencies' => 'Manage dependencies',
        'manage-tags' => 'Manage tags',
        'tag-tasks' => 'Tag tasks',
        'manage-attachments' => 'Manage attachments',
        'delete-attachment' => 'Delete attachments',
        'create-comment' => 'Write comments',
        'moderate-comments' => 'Moderate comments',
    ];

    /**
     * The translated, human-readable label for a permission name. Falls back to a
     * title-cased form of the raw name for anything outside the catalog.
     */
    public static function label(string $permission): string
    {
        return __(self::LABELS[$permission] ?? Str::headline($permission));
    }
}
