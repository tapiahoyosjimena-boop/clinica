<?php

namespace App\Http\Controllers;

use App\Domains\Auth\Notifications\PortalResetPasswordNotification;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Gestiona el flujo completo de recuperación de contraseña para los portales web
 * de paciente (/paciente/...) y médico (/medico/...).
 *
 * portal = 'patient' | 'doctor' (route default, no inyectar por posición en el método)
 */
class PortalPasswordResetController extends Controller
{
    // ── Paso 1: formulario de solicitud ──────────────────────────────────────

    public function showRequestForm(Request $request): View
    {
        $portal = $this->portalFromRoute($request);

        return view('portals.auth.password-reset-request', [
            'portal' => $portal,
            'loginRoute'   => $portal === 'patient' ? 'patient.login' : 'doctor.login',
            'sendRoute'    => $portal === 'patient' ? 'patient.password.send'  : 'doctor.password.send',
            'portalLabel'  => $portal === 'patient' ? 'Pacientes' : 'Médicos',
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $portal = $this->portalFromRoute($request);

        $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'El correo electrónico es obligatorio.',
             'email.email'    => 'Ingresa un correo electrónico válido.']
        );

        $resetRouteName = $portal === 'patient'
            ? 'patient.password.reset'
            : 'doctor.password.reset';

        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, $token) use ($resetRouteName) {
                $user->notify(new PortalResetPasswordNotification($token, $resetRouteName));
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Si el correo está registrado recibirás un enlace para restablecer tu contraseña en los próximos minutos.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Ya enviamos un enlace recientemente. Revisa tu correo o espera unos minutos.']);
        }

        return back()->with('status', 'Si el correo está registrado recibirás un enlace para restablecer tu contraseña en los próximos minutos.');
    }

    // ── Paso 2: formulario de nueva contraseña ───────────────────────────────

    public function showResetForm(Request $request): View
    {
        $portal = $this->portalFromRoute($request);
        $token = (string) $request->route('token');

        return view('portals.auth.password-reset-confirm', [
            'portal' => $portal,
            'token'  => $token,
            'email'  => $request->query('email', ''),
            'loginRoute'  => $portal === 'patient' ? 'patient.login' : 'doctor.login',
            'resetRoute'  => $portal === 'patient' ? 'patient.password.update' : 'doctor.password.update',
            'portalLabel' => $portal === 'patient' ? 'Pacientes' : 'Médicos',
            'passwordPolicyHint' => PasswordPolicy::helperText(),
            'passwordPlaceholder' => PasswordPolicy::placeholderText(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $portal = $this->portalFromRoute($request);
        $loginRoute = $portal === 'patient' ? 'patient.login' : 'doctor.login';

        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => PasswordPolicy::rules(required: true, confirmed: true),
            'password_confirmation' => ['required'],
        ], PasswordPolicy::validationMessages());

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route($loginRoute)
                ->with('status', 'Tu contraseña fue actualizada correctamente. Ya puedes iniciar sesión.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => match ($status) {
                Password::INVALID_TOKEN => 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.',
                Password::INVALID_USER  => 'No encontramos una cuenta con ese correo electrónico.',
                default                 => 'Ocurrió un error. Intenta nuevamente.',
            }]);
    }

    private function portalFromRoute(Request $request): string
    {
        $portal = $request->route('portal');

        if (! in_array($portal, ['patient', 'doctor'], true)) {
            abort(404);
        }

        return $portal;
    }
}
