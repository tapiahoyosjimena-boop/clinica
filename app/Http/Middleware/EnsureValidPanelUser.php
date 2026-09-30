<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Evita 403 con sesión inválida: usuario borrado o sin rol de panel → cierre de sesión y login.
 */
class EnsureValidPanelUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();
        if ($panel === null || $panel->getId() !== 'admin') {
            return $next($request);
        }

        $guard = Filament::auth();
        $user = $guard->user();

        if ($guard->check() && $user === null) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login');
        }

        if ($user instanceof User && ! $user->canAccessPanel($panel)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('status', 'Su cuenta no tiene acceso al panel de administración.');
        }

        return $next($request);
    }
}
