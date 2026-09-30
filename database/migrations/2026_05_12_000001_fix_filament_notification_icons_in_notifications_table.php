<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ICON_MAP = [
        'success' => 'heroicon-o-check-circle',
        'info' => 'heroicon-o-information-circle',
        'danger' => 'heroicon-o-exclamation-triangle',
        'warning' => 'heroicon-o-exclamation-triangle',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')->orderBy('created_at')->chunk(100, function ($rows): void {
            foreach ($rows as $row) {
                $decoded = json_decode($row->data, true);
                if (! is_array($decoded) || ! isset($decoded['icon'])) {
                    continue;
                }

                $icon = $decoded['icon'];
                if (! is_string($icon) || ! isset(self::ICON_MAP[$icon])) {
                    continue;
                }

                $decoded['icon'] = self::ICON_MAP[$icon];

                DB::table('notifications')->where('id', $row->id)->update([
                    'data' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        });
    }

    public function down(): void
    {
        // No se revierte: los nombres antiguos no son iconos Blade válidos.
    }
};
