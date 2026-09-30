<?php

namespace App\Console\Commands;

use App\Domains\Results\Models\Result;
use App\Domains\Results\Services\ResultPdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegenerateResultPdfsCommand extends Command
{
    protected $signature = 'results:regenerate-pdfs
                            {--result= : Regenerar solo este ID de resultado}
                            {--force : Omitir confirmación}';

    protected $description = 'Vuelve a generar los PDF de resultados ya guardados (útil tras cambiar lab-pdf o imaging-pdf).';

    public function handle(ResultPdfService $resultPdfService): int
    {
        $resultId = $this->option('result');

        $query = Result::query()->whereNotNull('pdf_path');

        if ($resultId !== null && $resultId !== '') {
            $query->whereKey((int) $resultId);
        }

        $results = $query->orderBy('id')->get();

        if ($results->isEmpty()) {
            $this->warn('No hay resultados con PDF guardado en disco.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Se regenerarán '.$results->count().' PDF(s) con la plantilla actual. ¿Continuar?',
            true
        )) {
            $this->warn('Operación cancelada.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($results->count());
        $bar->start();

        foreach ($results as $result) {
            $oldPath = $result->pdf_path;

            try {
                $resultPdfService->generateAndStore($result);
                $result->refresh();

                if (
                    filled($oldPath)
                    && $oldPath !== $result->pdf_path
                    && Storage::disk('public')->exists($oldPath)
                ) {
                    Storage::disk('public')->delete($oldPath);
                }

                $ok++;
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Resultado #{$result->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Listo: {$ok} regenerado(s), {$failed} error(es).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
