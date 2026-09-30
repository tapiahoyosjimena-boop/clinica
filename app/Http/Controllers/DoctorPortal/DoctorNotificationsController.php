<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Concerns\HandlesPortalNotifications;
use App\Models\User;
use App\Support\PortalNotificationOpenRedirect;

class DoctorNotificationsController
{
    use HandlesPortalNotifications;

    protected function portalNotificationsView(): string
    {
        return 'doctor-portal.notifications';
    }

    protected function portalNotificationsRoute(): string
    {
        return 'doctor.notifications';
    }

    protected function portalNotificationOpenTarget(User $user, ?string $storedUrl, string $fallback): string
    {
        return PortalNotificationOpenRedirect::targetForDoctor($user, $storedUrl, $fallback);
    }
}
