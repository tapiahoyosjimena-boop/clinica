<?php

namespace App\Filament\Pages;

use App\Domains\Auth\Support\SystemPermissions;
use App\Domains\Reportes\Filament\Pages\ReportsPage;
use App\Support\SystemAdministratorGuard;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Resumen operativo';

    protected static ?string $navigationLabel = 'Inicio';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (SystemAdministratorGuard::isPrimaryAdministrator($user)) {
            return true;
        }

        return $user->can(SystemPermissions::ADMIN_DASHBOARD);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('irReportes')
                ->label('Reportes')
                ->icon('heroicon-o-document-chart-bar')
                ->url(ReportsPage::getUrl())
                ->visible(fn (): bool => ReportsPage::canAccess()),
        ];
    }
}
