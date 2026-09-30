<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalModulePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->can($permission)) {
            return $this->deny($request, $permission);
        }

        return $next($request);
    }

    protected function deny(Request $request, string $permission): Response
    {
        $message = 'No tiene permiso para acceder a esta sección.';

        if (str_starts_with($permission, 'patient.')) {
            return redirect()
                ->route('patient.dashboard')
                ->with('portal_warning', $message);
        }

        if (str_starts_with($permission, 'doctor.')) {
            return redirect()
                ->route('doctor.dashboard')
                ->with('portal_warning', $message);
        }

        abort(403, $message);
    }
}
