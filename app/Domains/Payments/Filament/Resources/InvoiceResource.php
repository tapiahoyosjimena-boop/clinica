<?php

namespace App\Domains\Payments\Filament\Resources;

use App\Domains\Orders\Models\Order;
use App\Domains\Payments\Filament\Resources\InvoiceResource\Pages;
use App\Domains\Payments\Models\Invoice;
use App\Support\ResponsibleClinicalStaffScoping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Pagos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Comprobante';

    protected static ?string $pluralModelLabel = 'Comprobantes de pago';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('payments.access') ?? false;
    }

    public static function canCreate(): bool
    {
        // Los comprobantes se generan automáticamente al crear la orden.
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->hasRole('Administrador') ?? false)
            && (auth()->user()?->can('payments.access') ?? false);
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view', $record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Orden')
                    ->description('Solo órdenes pendientes de pago sin comprobante previo.')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Orden')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->options(function () {
                                return ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(
                                    Order::query()
                                        ->where('status', 'pendiente')
                                        ->whereDoesntHave('invoice')
                                        ->with('patient')
                                        ->orderByDesc('created_at')
                                )
                                    ->get()
                                    ->mapWithKeys(fn (Order $o) => [
                                        $o->id => $o->order_number.' — '.($o->patient?->full_name ?? 'Paciente'),
                                    ]);
                            }),
                        Forms\Components\Placeholder::make('total_preview')
                            ->label('Total calculado (suma de precios de exámenes)')
                            ->content(function (Get $get): string {
                                $id = $get('order_id');
                                if (! $id) {
                                    return '—';
                                }
                                $order = Order::with('exams')->find($id);
                                if (! $order) {
                                    return '—';
                                }

                                return number_format((float) Invoice::calculateTotalForOrder($order), 2, ',', '.')
                                    .' '.(config('clinic_bank.currency') ?? 'BOB');
                            }),
                    ]),
                Forms\Components\Section::make('Datos para pago (QR / transferencia)')
                    ->description(new HtmlString(
                        'Banco: '.e(config('clinic_bank.bank_name')).'<br>'
                        .'Titular: '.e(config('clinic_bank.account_holder')).'<br>'
                        .'Cuenta: '.e(config('clinic_bank.account_number') ?: '—').'<br>'
                        .'Tipo: '.e(config('clinic_bank.account_type')).'<br>'
                        .'<em>El QR en comprobante PDF es placeholder; sin pasarela externa.</em>'
                    ))
                    ->schema([]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Orden')
                    ->state(fn (Invoice $record): string => $record->order?->order_number
                        ?? "Orden eliminada (#{$record->order_id})")
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.patient.full_name')
                    ->label('Paciente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(config('clinic_bank.currency', 'BOB'))
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => $state === 'pagada' ? 'Pagada' : 'Pendiente')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'pagada',
                    ]),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('issued_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('descargar_comprobante')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (Invoice $record): bool => static::canView($record))
                    ->disabled(fn (Invoice $record): bool => ! filled($record->receipt_pdf_path))
                    ->tooltip(fn (Invoice $record): ?string => filled($record->receipt_pdf_path)
                        ? null
                        : 'El comprobante PDF aún no está disponible.')
                    ->url(fn (Invoice $record): ?string => filled($record->receipt_pdf_path)
                        ? route('payments.invoices.pdf', ['invoice' => $record])
                        : null)
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->label('Borrar')
                    ->visible(fn (Invoice $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay comprobantes')
            ->emptyStateDescription('Los comprobantes se emiten desde una orden con exámenes y estado de pago pendiente.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Comprobante')
                    ->icon('heroicon-o-document-text')
                    ->description('Datos del comprobante, monto y estado de pago.')
                    ->schema([
                        TextEntry::make('invoice_number')->label('Número'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => $state === 'pagada' ? 'Pagada' : 'Pendiente')
                            ->color(fn (string $state) => $state === 'pagada' ? 'success' : 'warning'),
                        TextEntry::make('total_amount')
                            ->label('Total')
                            ->money(config('clinic_bank.currency', 'BOB')),
                        TextEntry::make('issued_at')
                            ->label('Emitido')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('order.order_number')->label('N° Orden'),
                        TextEntry::make('order.patient.full_name')->label('Paciente'),
                    ])
                    ->columns(['default' => 1, 'sm' => 2]),
                Section::make('Exámenes de la orden')
                    ->icon('heroicon-o-beaker')
                    ->description('Estudios y análisis incluidos en la orden asociada.')
                    ->schema([
                        TextEntry::make('exams_summary')
                            ->label('Detalle')
                            ->formatStateUsing(fn (?string $state): string => $state ? nl2br(e($state)) : '')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make('Pagos registrados')
                    ->icon('heroicon-o-banknotes')
                    ->description('Historial de pagos realizados para este comprobante.')
                    ->visible(fn (Invoice $record) => $record->payments()->exists())
                    ->schema([
                        TextEntry::make('payments_summary')
                            ->label('')
                            ->formatStateUsing(fn (?string $state): string => $state ? nl2br(e($state)) : '')
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return ResponsibleClinicalStaffScoping::scopeInvoiceQueryForPanel(
            parent::getEloquentQuery()
                ->with(['order.patient', 'order.exams', 'payments.paymentMethod', 'payments.cashier'])
        );
    }
}
