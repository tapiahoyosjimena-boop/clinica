<?php

namespace App\Domains\Auth\Filament\Pages;

use App\Support\GenderOptions;
use App\Support\PasswordPolicy;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Auth\EditProfile;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;

class EditAdminProfile extends EditProfile
{
    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): string
    {
        return 'Editar cuenta';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Editar cuenta';
    }

    public static function getSlug(): string
    {
        return 'profile/edit';
    }

    public static function getRelativeRouteName(): string
    {
        return 'profile.edit';
    }

    public static function registerRoutes(Panel $panel): void
    {
        static::routes($panel);
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();

        return $panel->generateRouteName(static::getRelativeRouteName());
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    protected function getRedirectUrl(): ?string
    {
        return ViewAdminProfile::getUrl();
    }

    protected function getCancelFormAction(): Action
    {
        return $this->backAction()->url(ViewAdminProfile::getUrl());
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perfil actualizado correctamente.';
    }

    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()
            ->label('Nombre completo');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(table: 'users', column: 'email', ignoreRecord: true);
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Celular')
            ->tel()
            ->maxLength(32)
            ->placeholder('Ej: 70000000')
            ->helperText('Número de contacto opcional.');
    }

    protected function getGenderFormComponent(): Component
    {
        return Select::make('gender')
            ->label('Sexo')
            ->options(GenderOptions::labels())
            ->native(false)
            ->placeholder('Seleccionar')
            ->nullable();
    }

    public function mount(): void
    {
        parent::mount();

        // Evita hash previo o autocompletado del navegador en campos de contraseña.
        data_set($this->data, 'change_password', false);
        data_set($this->data, 'password', null);
        data_set($this->data, 'passwordConfirmation', null);
    }

    protected function getChangePasswordCheckboxComponent(): Component
    {
        return Checkbox::make('change_password')
            ->label('Cambiar contraseña de acceso al panel')
            ->live()
            ->dehydrated(false)
            ->afterStateUpdated(function (?bool $state, Set $set): void {
                if (! $state) {
                    $set('password', null);
                    $set('passwordConfirmation', null);
                }
            });
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Nueva contraseña')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('new-password')
            ->helperText(PasswordPolicy::helperText())
            ->nullable()
            ->required(false)
            ->hidden(fn (Get $get): bool => ! $get('change_password'))
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
            ->rules(fn (Get $get): array => $get('change_password') && filled($get('password'))
                ? PasswordPolicy::rules(required: true, confirmed: false)
                : [])
            ->validationMessages(PasswordPolicy::filamentValidationMessages());
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Confirmar nueva contraseña')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('new-password')
            ->nullable()
            ->required(false)
            ->dehydrated(false)
            ->hidden(fn (Get $get): bool => ! $get('change_password'))
            ->rules(fn (Get $get): array => $get('change_password') && filled($get('password'))
                ? ['required', 'same:password']
                : [])
            ->validationMessages([
                'required' => 'Debe confirmar la nueva contraseña.',
                'same' => 'La confirmación de contraseña no coincide.',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password'], $data['remember_token']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['change_password']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make('Datos personales')
                            ->icon('heroicon-o-user')
                            ->schema([
                                $this->getNameFormComponent(),
                                $this->getEmailFormComponent(),
                                $this->getPhoneFormComponent(),
                                $this->getGenderFormComponent(),
                            ])
                            ->columns(['default' => 1, 'sm' => 2]),

                        Section::make('Seguridad')
                            ->icon('heroicon-o-lock-closed')
                            ->description('Marque la casilla solo si desea actualizar su contraseña de acceso al panel.')
                            ->schema([
                                $this->getChangePasswordCheckboxComponent(),
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                            ])
                            ->columns(['default' => 1, 'sm' => 2]),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }
}
