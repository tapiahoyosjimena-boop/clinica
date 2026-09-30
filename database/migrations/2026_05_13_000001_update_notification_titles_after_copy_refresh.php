<?php

use App\Domains\Notifications\Notifications\EstudioImagenCreadoNotification;
use App\Domains\Notifications\Notifications\MuestraCreadaNotification;
use App\Domains\Notifications\Notifications\MuestraListaNotification;
use App\Domains\Notifications\Notifications\ResultadosListosMedicoNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<class-string, array{string, string}>
     */
    private const TITLE_REPLACEMENTS = [
        MuestraListaNotification::class => [
            '🔬 Muestra lista para análisis',
            '🔬 Muestra analizada con éxito',
        ],
        MuestraCreadaNotification::class => [
            '🧪 Nueva muestra registrada',
            '🧪 Llegó la muestra esperada',
        ],
        EstudioImagenCreadoNotification::class => [
            '🩻 Nuevo estudio de imagen',
            '🩻 Llegó el paciente para el examen de imagen',
        ],
        ResultadosListosMedicoNotification::class => [
            'Resultados listos para su paciente',
            '📄 Resultados listos de su paciente',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        foreach (self::TITLE_REPLACEMENTS as $type => [$from, $to]) {
            DB::table('notifications')
                ->where('type', $type)
                ->orderBy('created_at')
                ->chunk(100, function ($rows) use ($from, $to): void {
                    foreach ($rows as $row) {
                        $decoded = json_decode($row->data, true);
                        if (! is_array($decoded) || ($decoded['title'] ?? null) !== $from) {
                            continue;
                        }

                        $decoded['title'] = $to;

                        DB::table('notifications')->where('id', $row->id)->update([
                            'data' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        //
    }
};
