<?php

namespace App\Domains\Auth\Filament\Resources\UserResource\Pages;

use App\Domains\Auth\Filament\Resources\RoleResource;
use App\Domains\Auth\Filament\Resources\UserResource;
use App\Domains\Auth\Support\SystemPermissions;
use App\Models\User;
use App\Support\GenderOptions;
use App\Support\SystemAdministratorGuard;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function resolveRecord(int|string $key): User
    {
        return User::query()
            ->with(['roles.permissions'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        $role = $this->getRecord()->roles->first();

        return [
            Actions\Action::make('edit_role')
                ->label('Editar permisos del rol')
                ->icon('heroicon-o-shield-check')
                ->url(fn (): ?string => $role
                    ? RoleResource::getUrl('edit', ['record' => $role])
                    : null)
                ->visible((bool) $role)
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn (): bool => ! SystemAdministratorGuard::canDelete($this->getRecord())),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Datos personales')
                ->icon('heroicon-o-user')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre completo'),

                    TextEntry::make('email')
                        ->label('Correo electrónico')
                        ->copyable(),

                    TextEntry::make('phone')
                        ->label('Celular')
                        ->placeholder('—'),

                    TextEntry::make('gender')
                        ->label('Sexo')
                        ->formatStateUsing(fn (?string $state): string => GenderOptions::label($state) ?? '—'),

                    TextEntry::make('roles.name')
                        ->label('Rol')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('panel_access')
                        ->label('Acceso al panel admin')
                        ->getStateUsing(fn (User $record): string => $record->can(SystemPermissions::ADMIN_PANEL)
                            ? 'Sí'
                            : 'No'),

                    TextEntry::make('email_verified_at')
                        ->label('Correo verificado')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('No verificado'),

                    TextEntry::make('created_at')
                        ->label('Creado')
                        ->dateTime('d/m/Y H:i'),
                ]),

            Section::make('Acceso según el rol')
                ->icon('heroicon-o-key')
                ->description('Los permisos se gestionan en Roles; esta cuenta hereda todo del rol asignado.')
                ->schema([
                    TextEntry::make('role_access_summary')
                        ->label('Resumen')
                        ->getStateUsing(fn (User $record): HtmlString => RoleResource::formatRoleAccessSummaryHtml(
                            $record->roles->first()
                        ))
                        ->html()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
