<?php

namespace App\Domains\Auth\Filament\Resources\PatientUserResource\Pages;

use App\Domains\Auth\Filament\Resources\PatientUserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientUser extends ViewRecord
{
    protected static string $resource = PatientUserResource::class;

    protected function resolveRecord(int|string $key): User
    {
        return User::query()
            ->with(['roles', 'patient'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar credenciales'),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Credenciales de acceso al portal')
                ->icon('heroicon-o-key')
                ->description('Los datos clínicos del paciente se gestionan en el módulo Pacientes.')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre en la cuenta'),

                    TextEntry::make('email')
                        ->label('Correo (acceso portal)')
                        ->copyable(),

                    TextEntry::make('roles.name')
                        ->label('Rol')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('email_verified_at')
                        ->label('Correo verificado')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('No verificado'),

                    TextEntry::make('created_at')
                        ->label('Cuenta creada')
                        ->dateTime('d/m/Y H:i'),
                ]),

            Section::make('Paciente vinculado')
                ->icon('heroicon-o-heart')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('patient.ci')
                        ->label('CI / Cédula')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('patient.full_name')
                        ->label('Nombre completo')
                        ->placeholder('—'),

                    TextEntry::make('patient.phone')
                        ->label('Teléfono')
                        ->placeholder('—'),

                    TextEntry::make('patient.email')
                        ->label('Correo en ficha paciente')
                        ->placeholder('—'),

                    TextEntry::make('patient.gender')
                        ->label('Género')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'masculino' => 'Masculino',
                            'femenino' => 'Femenino',
                            default => '—',
                        }),
                ]),
        ]);
    }
}
