<?php

namespace App\Filament\Widgets;

use App\Domains\Imaging\Filament\Resources\ImagingStudyResource;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Orders\Filament\Resources\OrderResource;
use App\Domains\Orders\Models\Order;
use App\Domains\Payments\Filament\Resources\InvoiceResource;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Results\Filament\Resources\ResultResource;
use App\Domains\Results\Models\Result;
use App\Domains\Samples\Filament\Resources\SampleResource;
use App\Domains\Samples\Models\Sample;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClinicOperationalStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -10;

    protected ?string $heading = 'Indicadores operativos';

    protected ?string $description = 'Resumen acotado a los mismos permisos y alcances que el resto del panel.';

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = [];

        if ($user->can('orders.access')) {
            $q = ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(Order::query());
            $active = (clone $q)->whereIn('status', ['pendiente', 'en_proceso'])->count();
            $stats[] = Stat::make('Órdenes activas', $active)
                ->description('Pendientes o en proceso')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(OrderResource::getUrl());
        }

        if ($user->can('results.access')) {
            $q = ResponsibleClinicalStaffScoping::scopeResultQueryForPanel(Result::query());
            $pending = (clone $q)->whereNull('validated_at')->count();
            $stats[] = Stat::make('Resultados por confirmar', $pending)
                ->description('Sin validar aún')
                ->descriptionIcon('heroicon-o-magnifying-glass')
                ->color($pending > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(ResultResource::getUrl());
        }

        if ($user->can('samples.access')) {
            $q = ResponsibleClinicalStaffScoping::scopeSampleQueryForPanel(Sample::query());
            $inLab = (clone $q)->where('status', 'en_analisis')->count();
            $stats[] = Stat::make('Muestras en análisis', $inLab)
                ->description('En laboratorio')
                ->descriptionIcon('heroicon-o-beaker')
                ->color($inLab > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-beaker')
                ->url(SampleResource::getUrl());
        }

        if ($user->can('imaging.access')) {
            $q = ResponsibleClinicalStaffScoping::scopeImagingStudyQueryForPanel(ImagingStudy::query());
            $open = (clone $q)->whereNotIn('status', ['completado', 'cancelado'])->count();
            $stats[] = Stat::make('Estudios de imagen abiertos', $open)
                ->description('Distintos de completado o cancelado')
                ->descriptionIcon('heroicon-o-photo')
                ->color($open > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-photo')
                ->url(ImagingStudyResource::getUrl());
        }

        if ($user->can('payments.access')) {
            $q = ResponsibleClinicalStaffScoping::scopeInvoiceQueryForPanel(Invoice::query());
            $pendingPay = (clone $q)->where('status', 'pendiente')->count();
            $stats[] = Stat::make('Comprobantes pendientes', $pendingPay)
                ->description('Pendientes de pago')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($pendingPay > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-banknotes')
                ->url(InvoiceResource::getUrl());
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        foreach ([
            'orders.access',
            'results.access',
            'samples.access',
            'imaging.access',
            'payments.access',
        ] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
