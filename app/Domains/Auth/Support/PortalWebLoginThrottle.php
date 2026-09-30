<?php

namespace App\Domains\Auth\Support;

use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Throttle de intentos de login HTTP para portales (paciente / médico).
 * Misma política numérica que {@see Login} (config password_policy.login).
 */
final class PortalWebLoginThrottle
{
    /** Clave del error bag para credenciales / bloqueo (solo banner en vistas de portal). */
    public const ERROR_BAG_KEY = 'portal_login';

    private function maxAttempts(): int
    {
        return PasswordPolicy::loginMaxAttempts();
    }

    private function decaySeconds(): int
    {
        return PasswordPolicy::loginDecaySeconds();
    }

    /**
     * Clave por portal + IP + correo (evita que un usuario bloquee a otros en la misma red).
     */
    public function key(string $portal, Request $request, string $email): string
    {
        $normalized = Str::lower(trim($email));

        return 'portal-login:'.sha1($portal.'|'.$request->ip().'|'.$normalized);
    }

    /**
     * Si ya está bloqueado, mensaje para mostrar; si no, null.
     */
    public function blockedMessage(string $portal, Request $request, string $email): ?string
    {
        $key = $this->key($portal, $request, $email);
        if (! RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            return null;
        }

        return $this->lockoutMessageBody(RateLimiter::availableIn($key));
    }

    /**
     * Registrar fallo de contraseña / usuario y devolver el mensaje a mostrar.
     */
    public function messageAfterFailedAttempt(string $portal, Request $request, string $email): string
    {
        $key = $this->key($portal, $request, $email);
        RateLimiter::hit($key, $this->decaySeconds());

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            return $this->lockoutMessageBody(RateLimiter::availableIn($key));
        }

        $remaining = RateLimiter::remaining($key, $this->maxAttempts());

        if ($remaining === 1) {
            return 'Las credenciales no coinciden con los registros del sistema. ¡Atención! Tu cuenta será bloqueada en el próximo intento fallido.';
        }

        return "Las credenciales no coinciden con los registros del sistema. Te quedan {$remaining} intentos antes de ser bloqueado temporalmente.";
    }

    public function clear(string $portal, Request $request, string $email): void
    {
        RateLimiter::clear($this->key($portal, $request, $email));
    }

    private function lockoutMessageBody(int $secondsUntilAvailable): string
    {
        $timeMessage = $this->formatDurationForUser($secondsUntilAvailable);

        return "Demasiados intentos fallidos. Has superado el límite de {$this->maxAttempts()} intentos. Tu acceso ha sido bloqueado temporalmente. Inténtalo de nuevo en {$timeMessage}.";
    }

    private function formatDurationForUser(int $seconds): string
    {
        if ($seconds < 60) {
            return "menos de 1 minuto ({$seconds} segundo(s))";
        }

        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $remainingSeconds > 0
            ? "{$minutes} minuto(s) y {$remainingSeconds} segundo(s)"
            : "{$minutes} minuto(s)";
    }
}
