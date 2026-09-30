<?php

namespace App\Domains\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de recuperación de contraseña para portales web (paciente / médico).
 * No usa la notificación de Filament (reservada al panel /admin).
 */
class PortalResetPasswordNotification extends BaseResetPassword
{
    public function __construct(
        #[\SensitiveParameter] string $token,
        protected string $resetRouteName,
    ) {
        parent::__construct($token);
    }

    protected function resetUrl($notifiable): string
    {
        return url(route($this->resetRouteName, [
            'token' => $this->token,
        ], false)).'?email='.urlencode($notifiable->getEmailForPasswordReset());
    }

    protected function buildMailMessage($url): MailMessage
    {
        $expire = (string) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablecer contraseña — Clínica Norte')
            ->line('Recibimos una solicitud para restablecer la contraseña de su cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace caduca en {$expire} minutos.")
            ->line('Si usted no solicitó el cambio, puede ignorar este correo.');
    }
}
