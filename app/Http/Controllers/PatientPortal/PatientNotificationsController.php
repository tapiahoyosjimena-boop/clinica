<?php

namespace App\Http\Controllers\PatientPortal;

use App\Http\Controllers\Concerns\HandlesPortalNotifications;
use App\Models\User;
use App\Support\PortalNotificationOpenRedirect;

class PatientNotificationsController
{
    use HandlesPortalNotifications;

    protected function portalNotificationsView(): string
    {
        return 'patient-portal.notifications';
    }

    protected function portalNotificationsRoute(): string
    {
        return 'patient.notifications';
    }

    protected function portalNotificationOpenTarget(User $user, ?string $storedUrl, string $fallback): string
    {
        return PortalNotificationOpenRedirect::targetForPatient($user, $storedUrl, $fallback);
    }
}
