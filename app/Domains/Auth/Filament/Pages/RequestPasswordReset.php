<?php

namespace App\Domains\Auth\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

/**
 * Página "Olvidé mi contraseña" del panel admin.
 * Extiende la implementación base de Filament y sobreescribe los textos al español.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function getHeading(): string
    {
        return 'Recuperar contraseña';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->autocomplete('email')
                    ->autofocus()
                    ->placeholder('tucorreo@ejemplo.com'),
            ]);
    }

    protected function getResetLinkSentNotificationTitle(): string
    {
        return 'Enlace enviado';
    }

    protected function getResetLinkSentNotificationBody(): string
    {
        return 'Si el correo está registrado en el sistema, recibirás un enlace para restablecer tu contraseña en los próximos minutos.';
    }
}
