<?php

namespace App\Http\Middleware;

use App\Domains\Auth\Support\SystemPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsDoctor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('doctor.login');
        }

        if ($user->can(SystemPermissions::ADMIN_PANEL)) {
            return redirect('/admin');
        }

        if (! $user->canAccessDoctorPortal()) {
            if ($user->canAccessPatientPortal()) {
                return redirect()->route('patient.dashboard');
            }

            abort(403, 'No tiene permiso para acceder al portal del médico.');
        }

        return $next($request);
    }
}
