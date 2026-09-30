<?php

namespace App\Domains\Auth\Filament\Pages;

use App\Domains\Auth\Support\MarkUserVerifiedOnLogin;
use App\Models\User;
use App\Support\PasswordPolicy;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

/**
 * Página de login personalizada con throttle:
 *   Límite y ventana definidos en config/password_policy.php → login (mismo bloque que portales web).
 */
class Login extends BaseLogin
{
    use WithRateLimiting;

    /** Mensaje de error general que se muestra como banner sobre el formulario. */
    public ?string $loginError = null;

    /**
     * Sobreescribe el formulario para insertar el banner de error
     * en la parte superior, antes de los campos.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('error_banner')
                    ->label('')
                    ->content(fn () => new HtmlString(
                        '<div class="cn-login-error-banner" role="alert">'
                        .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>'
                        .'<span>'.e($this->loginError).'</span>'
                        .'</div>'
                    ))
                    ->hidden(fn () => empty($this->loginError)),

                $this->getEmailFormComponent(),

                // Campo contraseña sin el hint integrado (el link va debajo del input)
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('current-password')
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2]),

                // Link "¿Olvidé mi contraseña?" debajo del campo contraseña
                Placeholder::make('forgot_password_link')
                    ->label('')
                    ->content(fn (): HtmlString => filament()->hasPasswordReset()
                        ? new HtmlString(
                            '<a href="'.filament()->getRequestPasswordResetUrl().'" '
                            .'class="cn-forgot-password-link">'
                            .'¿Ha olvidado su contraseña?'
                            .'</a>'
                        )
                        : new HtmlString('')
                    )
                    ->hidden(fn () => ! filament()->hasPasswordReset()),
            ])
            ->statePath('data');
    }

    public function authenticate(): LoginResponse
    {
        $maxAttempts = PasswordPolicy::loginMaxAttempts();
        $decaySeconds = PasswordPolicy::loginDecaySeconds();

        // Resetea el banner en cada nuevo intento
        $this->loginError = null;

        try {
            $this->rateLimit($maxAttempts, $decaySeconds);
        } catch (TooManyRequestsException $exception) {
            $seconds = $exception->secondsUntilAvailable;
            if ($seconds < 60) {
                $timeMessage = "menos de 1 minuto ({$seconds} segundo(s))";
            } else {
                $minutes = (int) floor($seconds / 60);
                $remainingSeconds = $seconds % 60;
                $timeMessage = $remainingSeconds > 0
                    ? "{$minutes} minuto(s) y {$remainingSeconds} segundo(s)"
                    : "{$minutes} minuto(s)";
            }

            // Mostrar el toast solo una vez por bloqueo (no acumular notificaciones)
            if (! session()->has('throttle_notified')) {
                session()->put('throttle_notified', true);
                Notification::make()
                    ->title('Demasiados intentos fallidos')
                    ->body("Has superado el límite de {$maxAttempts} intentos. Tu cuenta ha sido bloqueada temporalmente. Inténtalo de nuevo en {$timeMessage}.")
                    ->danger()
                    ->duration(10000)
                    ->send();
            }

            $this->loginError = "Demasiados intentos fallidos. Inténtalo de nuevo en {$timeMessage}.";
            throw ValidationException::withMessages(['authentication' => ' ']);
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $currentAttempts = session('login_failed_attempts', 0) + 1;
            session()->put('login_failed_attempts', $currentAttempts);
            $remaining = max(0, $maxAttempts - $currentAttempts);

            if ($remaining > 0) {
                $plural = $remaining === 1 ? 'intento' : 'intentos';
                $this->loginError = "Las credenciales no coinciden con los registros del sistema. Te quedan {$remaining} {$plural} antes de ser bloqueado temporalmente.";
            } else {
                $this->loginError = 'Las credenciales no coinciden con los registros del sistema. ¡Atención! Tu cuenta será bloqueada en el próximo intento fallido.';
            }
            throw ValidationException::withMessages(['authentication' => ' ']);
        }

        $user = Filament::auth()->user();
        MarkUserVerifiedOnLogin::apply($user);

        if ($user instanceof User && ! $user->canAccessPanel(Filament::getCurrentPanel())) {
            $portalRoute = $user->preferredPortalRoute();

            if ($portalRoute !== null) {
                session()->regenerate();
                $this->clearRateLimiter();
                session()->forget('throttle_notified');
                session()->forget('login_failed_attempts');

                $routeName = $portalRoute;

                return new class ($routeName) implements LoginResponse
                {
                    public function __construct(private string $routeName) {}

                    public function toResponse(Component $component): Response
                    {
                        return redirect()->intended(route($this->routeName));
                    }
                };
            }

            Filament::auth()->logout();
            $this->loginError = 'No tienes permiso para acceder al panel de administración.';
            throw ValidationException::withMessages(['authentication' => ' ']);
        }

        session()->regenerate();
        $this->clearRateLimiter();
        session()->forget('throttle_notified');
        session()->forget('login_failed_attempts');

        return app(LoginResponse::class);
    }
}
