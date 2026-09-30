<?php

namespace App\Domains\Notifications\Filament\Resources;

use App\Domains\Notifications\Filament\Resources\NotificationResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Notificación';

    protected static ?string $pluralModelLabel = 'Notificaciones';

    protected static ?string $slug = 'notifications';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('notifications.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        if (! (auth()->user()?->can('notifications.access') ?? false)) {
            return false;
        }

        return static::notificationBelongsToCurrentUser($record);
    }

    public static function canView($record): bool
    {
        if (! (auth()->user()?->can('notifications.access') ?? false)) {
            return false;
        }

        return static::notificationBelongsToCurrentUser($record);
    }

    // ─── Formulario (no aplica, solo lectura) ─────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('notifiable.name')
                    ->label('Destinatario')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('data.title')
                    ->label('Tipo de evento')
                    ->limit(60)
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('read_at')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Leída' : 'No leída')
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('no_leidas')
                    ->label('Solo no leídas')
                    ->query(fn (Builder $q) => $q->whereNull('read_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('marcar_leida')
                    ->label('Marcar leída')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (DatabaseNotification $record) => $record->read_at === null)
                    ->action(function (DatabaseNotification $record): void {
                        $record->markAsRead();

                        FilamentNotification::make()
                            ->title('Notificación marcada como leída')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('marcar_todas_leidas')
                    ->label('Marcar seleccionadas como leídas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        $records->each(fn (DatabaseNotification $n) => $n->markAsRead());

                        FilamentNotification::make()
                            ->title('Notificaciones marcadas como leídas')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Sin notificaciones')
            ->emptyStateDescription('Aquí verá solo sus propias alertas (resultados críticos, órdenes asignadas, pagos, etc.).')
            ->emptyStateIcon('heroicon-o-bell');
    }

    // ─── Query ────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return parent::getEloquentQuery()->whereRaw('0 = 1');
        }

        return parent::getEloquentQuery()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->with('notifiable')
            ->latest();
    }

    /**
     * Las filas de `notifications` son por destinatario (morph); el listado debe ser solo del usuario actual.
     */
    private static function notificationBelongsToCurrentUser(mixed $record): bool
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $record instanceof DatabaseNotification) {
            return false;
        }

        return (int) $record->notifiable_id === (int) $user->getKey()
            && $record->notifiable_type === $user->getMorphClass();
    }

    // ─── Páginas ──────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
