<?php

use App\Domains\Notifications\Notifications\PagoRegistradoNotification;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Payments\Filament\Resources\InvoiceResource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $ordersListUrl = FilamentAdminListUrls::orders();

        DB::table('notifications')
            ->where('type', PagoRegistradoNotification::class)
            ->orderBy('created_at')
            ->chunk(100, function ($rows) use ($ordersListUrl): void {
                foreach ($rows as $row) {
                    $data = is_string($row->data) ? json_decode($row->data, true) : (array) $row->data;
                    if (! is_array($data)) {
                        continue;
                    }
                    $data['url'] = $ordersListUrl;
                    DB::table('notifications')->where('id', $row->id)->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $invoicesListUrl = InvoiceResource::getUrl();

        DB::table('notifications')
            ->where('type', PagoRegistradoNotification::class)
            ->orderBy('created_at')
            ->chunk(100, function ($rows) use ($invoicesListUrl): void {
                foreach ($rows as $row) {
                    $data = is_string($row->data) ? json_decode($row->data, true) : (array) $row->data;
                    if (! is_array($data)) {
                        continue;
                    }
                    $data['url'] = $invoicesListUrl;
                    DB::table('notifications')->where('id', $row->id)->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }
};
