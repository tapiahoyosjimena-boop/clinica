<?php

namespace App\Domains\Patients\Filament\Resources\PatientResource\Pages;

use App\Domains\Orders\Filament\Resources\OrderResource;
use App\Domains\Orders\Models\Order;
use App\Domains\Patients\Filament\Resources\PatientResource;
use App\Domains\Patients\Models\Patient;
use App\Support\ResponsibleClinicalStaffScoping;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Relations\Relation;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function resolveRecord(int|string $key): Patient
    {
        return Patient::query()
            ->with([
                'orders' => function (Relation $relation): void {
                    ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel($relation->getQuery())
                        ->orderByDesc('created_at');
                },
                'orders.receptionist',
                'orders.responsibleUser',
                'orders.exams',
            ])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => PatientResource::canDelete($this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Identificación ──────────────────────────────────────────────────
            Section::make('Identificación')
                ->icon('heroicon-o-identification')
                ->description('Datos personales del paciente registrados en el sistema.')
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                ->schema([
                    TextEntry::make('full_name')
                        ->label('Nombre completo')
                        ->getStateUsing(fn (Patient $record) => $record->first_name.' '.$record->last_name)
                        ->weight('bold')
                        ->size(TextEntry\TextEntrySize::Large),

                    TextEntry::make('ci')
                        ->label('Cédula de Identidad')
                        ->copyable()
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('gender')
                        ->label('Género')
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'masculino' => 'info',
                            'femenino' => 'success',
                            default => 'warning',
                        }),

                    TextEntry::make('birth_date')
                        ->label('Fecha de nacimiento')
                        ->date('d/m/Y'),

                    TextEntry::make('age')
                        ->label('Edad')
                        ->getStateUsing(fn (Patient $record) => $record->birth_date->age.' años'),
                ]),

            // ── Datos de Contacto ───────────────────────────────────────────────
            Section::make('Datos de Contacto')
                ->icon('heroicon-o-phone')
                ->description('Información de contacto para comunicaciones y entregas.')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('phone')
                        ->label('Teléfono')
                        ->copyable(),

                    TextEntry::make('email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('Sin email registrado'),

                    TextEntry::make('address')
                        ->label('Dirección')
                        ->placeholder('Sin dirección registrada')
                        ->columnSpanFull(),
                ]),

            // ── Historial Clínico ───────────────────────────────────────────────
            Section::make('Historial Clínico')
                ->icon('heroicon-o-clipboard-document-list')
                ->description('Notas clínicas y órdenes vinculadas a este paciente.')
                ->collapsible()
                ->schema([
                    TextEntry::make('medical_history_notes')
                        ->label('Notas del historial clínico')
                        ->placeholder('Sin notas registradas')
                        ->columnSpanFull(),

                    RepeatableEntry::make('orders')
                        ->label('Órdenes')
                        ->placeholder('No hay órdenes registradas para este paciente (o ninguna visible con su perfil actual).')
                        ->contained(true)
                        ->columns(['default' => 2, 'lg' => 4])
                        ->schema([
                            TextEntry::make('order_number')
                                ->label('N° orden')
                                ->badge()
                                ->color('gray')
                                ->url(fn (Order $record): ?string => OrderResource::canView($record)
                                    ? OrderResource::getUrl('view', ['record' => $record])
                                    : null),

                            TextEntry::make('type')
                                ->label('Tipo')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'laboratorio' => 'Laboratorio',
                                    'imagen' => 'Imagen',
                                    default => ucfirst($state),
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'laboratorio' => 'primary',
                                    'imagen' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'pendiente' => 'Pendiente',
                                    'en_proceso' => 'En proceso',
                                    'completada' => 'Completada',
                                    'cancelada' => 'Cancelada',
                                    default => ucfirst($state),
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'pendiente' => 'warning',
                                    'en_proceso' => 'info',
                                    'completada' => 'success',
                                    'cancelada' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('scheduled_display')
                                ->label('Programación')
                                ->getStateUsing(function (Order $record): string {
                                    if (! $record->scheduled_date) {
                                        return '—';
                                    }
                                    $date = $record->scheduled_date->format('d/m/Y');
                                    $time = $record->scheduled_time
                                        ? Carbon::parse($record->scheduled_time)->format('H:i')
                                        : '';

                                    return trim($date.' '.$time);
                                }),

                            TextEntry::make('exams_count')
                                ->label('Exámenes')
                                ->getStateUsing(fn (Order $record): int => $record->exams->count())
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('receptionist.name')
                                ->label('Recepcionista')
                                ->placeholder('—'),

                            TextEntry::make('responsibleUser.name')
                                ->label('Profesional asignado')
                                ->badge()
                                ->color('info')
                                ->placeholder('Sin asignar'),

                            TextEntry::make('created_at')
                                ->label('Creada')
                                ->dateTime('d/m/Y H:i'),
                        ])
                        ->columnSpanFull(),
                ]),

            // ── Registro ────────────────────────────────────────────────────────
            Section::make('Registro')
                ->icon('heroicon-o-clock')
                ->description('Fechas de creación y última modificación del registro.')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Creado')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Última modificación')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
