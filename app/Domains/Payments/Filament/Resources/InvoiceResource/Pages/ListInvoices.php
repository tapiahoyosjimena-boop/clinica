<?php

namespace App\Domains\Payments\Filament\Resources\InvoiceResource\Pages;

use App\Domains\Payments\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        // Sin crear manual: el comprobante se emite al crear la orden.
        return [];
    }
}
