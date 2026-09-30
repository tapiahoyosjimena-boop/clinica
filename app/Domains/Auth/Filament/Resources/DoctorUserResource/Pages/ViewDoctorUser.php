<?php

namespace App\Domains\Auth\Filament\Resources\DoctorUserResource\Pages;

use App\Domains\Auth\Filament\Resources\DoctorUserResource;
use App\Models\User;
use App\Support\GenderOptions;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDoctorUser extends ViewRecord
{
    protected static string $resource = DoctorUserResource::class;

    protected function resolveRecord(int|string $key): User
    {
        return User::query()
            ->with(['roles'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Datos del médico')
                ->icon('heroicon-o-academic-cap')
                ->description('Cuenta de acceso al portal médico (/medico/login).')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre completo'),

                    TextEntry::make('email')
                        ->label('Correo (acceso portal)')
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
                        ->color('success'),

                    TextEntry::make('email_verified_at')
                        ->label('Correo verificado')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('No verificado'),

                    TextEntry::make('created_at')
                        ->label('Registrado')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
