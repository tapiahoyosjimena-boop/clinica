<?php

namespace App\Http\Middleware;

use App\Domains\Auth\Support\SystemPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsPatient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('patient.login');
        }

        if ($user->can(SystemPermissions::ADMIN_PANEL)) {
            return redirect('/admin');
        }

        if ($user->canAccessDoctorPortal()) {
            return redirect()->route('doctor.dashboard');
        }

        if (! $user->canAccessPatientPortal()) {
            abort(403, 'No tiene permiso para acceder al portal del paciente.');
        }

        return $next($request);
    }
}
