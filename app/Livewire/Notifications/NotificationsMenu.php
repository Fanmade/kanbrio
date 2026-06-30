<?php

namespace App\Livewire\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read int $unreadCount
 */
class NotificationsMenu extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotificationCount();
    }

    /**
     * The unread-count label shown on the menu badge, capped at "9+", or null
     * when there is nothing unread and the badge should be hidden.
     */
    #[Computed]
    public function unreadBadge(): ?string
    {
        $count = $this->unreadCount;

        if ($count === 0) {
            return null;
        }

        return $count > 9 ? '9+' : (string) $count;
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return Auth::user()->notifications()->latest()->limit(10)->get();
    }

    public function markAllRead(): void
    {
        $user = Auth::user();

        // Mark them read in one statement instead of hydrating every unread model.
        // A bulk update fires no model events, so the cached unread count (which is
        // busted by DatabaseNotification's `updated` event) must be cleared by hand.
        $user->unreadNotifications()->update(['read_at' => now()]);
        User::forgetUnreadNotificationCount($user->getKey());

        unset($this->unreadCount, $this->unreadBadge, $this->notifications);
    }

    /**
     * The short verb describing what a notification's underlying action did to its
     * subject (e.g. "commented on", "changed the status of"), shown on the menu
     * line before the subject reference.
     */
    public function actionLabel(string $action): string
    {
        return match ($action) {
            'created' => __('created'),
            'status_changed' => __('changed the status of'),
            'priority_changed' => __('changed the priority of'),
            'type_changed' => __('changed the type of'),
            'assignee_changed' => __('updated the assignees of'),
            'tags_changed' => __('updated the tags of'),
            'parent_changed' => __('moved'),
            'commented' => __('commented on'),
            'comment_deleted' => __('deleted a comment on'),
            'mentioned' => __('mentioned you in'),
            default => __('updated'),
        };
    }

    public function open(string $id): void
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();
        $notification?->markAsRead();

        $url = $notification?->data['url'] ?? null;

        unset($this->unreadCount, $this->unreadBadge, $this->notifications);

        if (is_string($url)) {
            $this->redirect($url, navigate: true);
        }
    }
}
