<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait HandlesPortalNotifications
{
    abstract protected function portalNotificationsView(): string;

    abstract protected function portalNotificationsRoute(): string;

    abstract protected function portalNotificationOpenTarget(User $user, ?string $storedUrl, string $fallback): string;

    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderByRaw('read_at IS NOT NULL')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view($this->portalNotificationsView(), compact('notifications'));
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();
        abort_unless($notification, 404);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $raw = $notification->data;
        $data = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        $storedUrl = $data['url'] ?? null;

        $target = $this->portalNotificationOpenTarget(
            $user,
            is_string($storedUrl) ? $storedUrl : null,
            route($this->portalNotificationsRoute())
        );

        return redirect()->to($target);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect()->route($this->portalNotificationsRoute());
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route($this->portalNotificationsRoute());
    }

    public function deleteAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return redirect()->route($this->portalNotificationsRoute());
    }
}
