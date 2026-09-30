<?php

use App\Domains\Notifications\Notifications\PagoRegistradoNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TITLES = ['💳 Pago registrado', 'Pago registrado'];

    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->where('type', PagoRegistradoNotification::class)
            ->orderBy('created_at')
            ->chunk(100, function ($rows): void {
                foreach ($rows as $row) {
                    $decoded = json_decode($row->data, true);
                    if (! is_array($decoded)) {
                        continue;
                    }

                    $title = $decoded['title'] ?? null;
                    if (! is_string($title) || ! in_array($title, self::OLD_TITLES, true)) {
                        continue;
                    }

                    $decoded['title'] = 'Nueva orden asignada';

                    DB::table('notifications')->where('id', $row->id)->update([
                        'data' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // No se revierte el texto mostrado al usuario.
    }
};
