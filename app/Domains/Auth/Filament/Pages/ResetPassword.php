<?php

namespace App\Domains\Auth\Filament\Pages;

use App\Support\PasswordPolicy;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;

/**
 * Página "Restablecer contraseña" del panel admin.
 * Extiende la implementación base de Filament y sobreescribe los textos al español.
 */
class ResetPassword extends BaseResetPassword
{
    public function getHeading(): string
    {
        return 'Nueva contraseña';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Crea una nueva contraseña para tu cuenta.';
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
                    ->readOnly()
                    ->placeholder('tucorreo@ejemplo.com'),

                TextInput::make('password')
                    ->label('Nueva contraseña')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->autocomplete('new-password')
                    ->helperText(PasswordPolicy::helperText())
                    ->rules(PasswordPolicy::rules(required: true, confirmed: false))
                    ->validationMessages(PasswordPolicy::filamentValidationMessages())
                    ->same('passwordConfirmation'),

                TextInput::make('passwordConfirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->autocomplete('new-password')
                    ->dehydrated(false),
            ]);
    }
}
