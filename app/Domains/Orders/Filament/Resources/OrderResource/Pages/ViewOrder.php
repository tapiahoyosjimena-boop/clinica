<?php

namespace App\Domains\Orders\Filament\Resources\OrderResource\Pages;

use App\Domains\Orders\Filament\Resources\OrderResource;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Services\OrderCancellationService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Auth\Access\AuthorizationException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function resolveRecord(int|string $key): Order
    {
        return Order::with([
            'patient',
            'doctor',
            'receptionist',
            'responsibleUser',
            'equipment',
            'exams.requirements',
            'cancelledBy',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancelar_orden')
                ->label('Cancelar orden')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn () => auth()->user()?->can('cancel', $this->record) ?? false
                )
                ->form([
                    Textarea::make('motivo_cancelacion')
                        ->label('Motivo de cancelación')
                        ->required()
                        ->rows(3)
                        ->placeholder('Indique el motivo por el que se cancela la orden...'),
                ])
                ->modalHeading('Cancelar orden')
                ->modalDescription('No se puede cancelar si hay muestras en análisis o procesadas, o estudios de imagen ya iniciados. Si procede, las muestras solo «recibidas» y los estudios solo «programados» se anularán automáticamente.')
                ->modalSubmitActionLabel('Enviar')
                ->modalCancelActionLabel('Cancelar')
                ->action(function (array $data): void {
                    $order = $this->record;

                    try {
                        app(OrderCancellationService::class)->cancel($order, $data['motivo_cancelacion']);
                    } catch (AuthorizationException $e) {
                        Notification::make()
                            ->title('No se puede cancelar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record = Order::with([
                        'patient',
                        'doctor',
                        'receptionist',
                        'responsibleUser',
                        'equipment',
                        'exams.requirements',
                        'cancelledBy',
                    ])->findOrFail($order->id);

                    Notification::make()
                        ->title('Orden cancelada')
                        ->body('La orden fue cancelada. Se anularon muestras o estudios pendientes de inicio, si los había.')
                        ->danger()
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn () => OrderResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => OrderResource::canDelete($this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Información de la Orden')
                ->icon('heroicon-o-clipboard-document-list')
                ->description('Datos generales de la orden y personal asignado.')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('order_number')
                        ->label('N° Orden')
                        ->copyable()
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('type')
                        ->label('Tipo de orden')
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

                    TextEntry::make('patient.first_name')
                        ->label('Paciente')
                        ->formatStateUsing(fn ($state, $record) => $record->patient?->first_name.' '.$record->patient?->last_name.
                            ' — CI: '.$record->patient?->ci
                        ),

                    TextEntry::make('doctor.name')
                        ->label('Médico derivante')
                        ->badge()
                        ->color('success')
                        ->placeholder('Sin asignar'),

                    TextEntry::make('receptionist.name')
                        ->label('Recepcionista'),

                    TextEntry::make('responsibleUser.name')
                        ->label(fn (): string => $this->record->type === 'imagen'
                            ? 'Tecnólogo de imagen asignado'
                            : 'Bioquímico asignado')
                        ->badge()
                        ->color('info')
                        ->placeholder('Sin asignar'),

                    TextEntry::make('scheduled_date')
                        ->label('Fecha programada')
                        ->date('d/m/Y')
                        ->placeholder('Sin programar'),

                    TextEntry::make('scheduled_time')
                        ->label('Hora programada')
                        ->time('H:i')
                        ->placeholder('—'),

                    TextEntry::make('equipment.name')
                        ->label('Equipo de imagen asignado')
                        ->badge()
                        ->color('warning')
                        ->placeholder('Sin equipo asignado')
                        ->visible(fn ($record) => $record->type === 'imagen'),

                    TextEntry::make('created_at')
                        ->label('Creada el')
                        ->dateTime('d/m/Y H:i'),
                ]),

            Section::make('Cancelación de la orden')
                ->icon('heroicon-o-x-circle')
                ->description('Quién canceló la orden, cuándo y el motivo registrado.')
                ->columns(['default' => 1, 'sm' => 2])
                ->visible(fn (Order $record): bool => $record->status === 'cancelada')
                ->schema([
                    TextEntry::make('cancellation_reason')
                        ->label('Motivo de cancelación')
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('cancelledBy.name')
                        ->label('Cancelada por')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('cancelled_at')
                        ->label('Fecha y hora')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                ]),

            Section::make('Exámenes asignados')
                ->icon('heroicon-o-beaker')
                ->description('Estudios y análisis incluidos en esta orden.')
                ->schema([
                    RepeatableEntry::make('exams')
                        ->label('')
                        ->contained(true)
                        ->columns(['default' => 1, 'sm' => 2])
                        ->schema([
                            TextEntry::make('name')
                                ->label('Nombre')
                                ->weight('bold'),

                            TextEntry::make('type')
                                ->label('Tipo')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'laboratorio' => 'Laboratorio',
                                    'imagen' => 'Imagen',
                                    default => ucfirst($state),
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'laboratorio' => 'info',
                                    'imagen' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('price')
                                ->label('Precio')
                                ->money('BOB'),

                            TextEntry::make('exam_requirements')
                                ->label('Requisitos previos')
                                ->getStateUsing(
                                    fn ($record) => $record->requirements->pluck('description')->all()
                                )
                                ->listWithLineBreaks()
                                ->bulleted()
                                ->placeholder('Sin requisitos previos.')
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
