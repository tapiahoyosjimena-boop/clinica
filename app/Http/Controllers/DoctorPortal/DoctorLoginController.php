<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Domains\Auth\Support\MarkUserVerifiedOnLogin;
use App\Domains\Auth\Support\PortalAccessRedirect;
use App\Domains\Auth\Support\PortalWebLoginThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DoctorLoginController
{
    private const PORTAL = 'doctor';

    public function showLogin(Request $request): View|RedirectResponse
    {
        // Solo redirigir si ya es médico; si hay otra sesión (p. ej. paciente), mostrar el formulario
        // para poder iniciar sesión como médico y reemplazar la sesión.
        if (Auth::check() && PortalAccessRedirect::shouldRedirectFromLoginForm(Auth::user(), self::PORTAL)) {
            return redirect()->route('doctor.dashboard');
        }

        return view('doctor-portal.auth.login');
    }

    public function login(Request $request, PortalWebLoginThrottle $throttle): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $email = $credentials['email'];

        if ($blocked = $throttle->blockedMessage(self::PORTAL, $request, $email)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([PortalWebLoginThrottle::ERROR_BAG_KEY => $blocked]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    PortalWebLoginThrottle::ERROR_BAG_KEY => $throttle->messageAfterFailedAttempt(self::PORTAL, $request, $email),
                ]);
        }

        $throttle->clear(self::PORTAL, $request, $email);

        $request->session()->regenerate();

        $user = Auth::user();
        MarkUserVerifiedOnLogin::apply($user);

        if (! $user->canAccessDoctorPortal()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    PortalWebLoginThrottle::ERROR_BAG_KEY => 'Esta cuenta no tiene permiso para el portal del médico.',
                ]);
        }

        return PortalAccessRedirect::afterWebLogin($user, self::PORTAL);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login');
    }
}
