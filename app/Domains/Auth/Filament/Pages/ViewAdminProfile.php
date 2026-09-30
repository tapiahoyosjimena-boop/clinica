<?php

namespace App\Domains\Auth\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class ViewAdminProfile extends Page
{
    protected static bool $isDiscovered = false;

    protected static string $view = 'filament.pages.view-admin-profile';

    protected static ?string $slug = 'profile';

    protected static ?string $title = 'Mi perfil';

    public static function getLabel(): string
    {
        return 'Mi perfil';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Mi perfil';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Información de su cuenta en Clínica Norte.';
    }

    public static function registerRoutes(Panel $panel): void
    {
        static::routes($panel);
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();

        return $panel->generateRouteName('auth.'.static::getRelativeRouteName());
    }

    public static function getRelativeRouteName(): string
    {
        return 'profile';
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editAccount')
                ->label('Editar cuenta')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn (): string => EditAdminProfile::getUrl()),
        ];
    }

    public function getUser(): User
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user->load('roles');
    }
}
