<?php

namespace App\Domains\Auth\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class PortalAccessRedirect
{
    public static function afterWebLogin(User $user, string $intendedPortal): RedirectResponse
    {
        if ($user->can(SystemPermissions::ADMIN_PANEL)) {
            return redirect('/admin');
        }

        if ($intendedPortal === 'patient' && $user->canAccessPatientPortal()) {
            return redirect()->intended(route('patient.dashboard'));
        }

        if ($intendedPortal === 'doctor' && $user->canAccessDoctorPortal()) {
            return redirect()->intended(route('doctor.dashboard'));
        }

        if ($user->canAccessPatientPortal()) {
            return redirect()->route('patient.dashboard');
        }

        if ($user->canAccessDoctorPortal()) {
            return redirect()->route('doctor.dashboard');
        }

        return redirect('/admin');
    }

    public static function shouldRedirectFromLoginForm(User $user, string $portal): bool
    {
        return match ($portal) {
            'patient' => $user->canAccessPatientPortal(),
            'doctor' => $user->canAccessDoctorPortal(),
            default => false,
        };
    }

    public static function dashboardRouteForSession(User $user): ?string
    {
        if ($user->canAccessPatientPortal()) {
            return 'patient.dashboard';
        }

        if ($user->canAccessDoctorPortal()) {
            return 'doctor.dashboard';
        }

        if ($user->can(SystemPermissions::ADMIN_PANEL)) {
            return null;
        }

        return null;
    }
}
