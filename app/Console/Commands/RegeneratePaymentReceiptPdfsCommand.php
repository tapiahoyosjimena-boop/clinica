<?php

namespace App\Console\Commands;

use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Services\PaymentReceiptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegeneratePaymentReceiptPdfsCommand extends Command
{
    protected $signature = 'payments:regenerate-receipt-pdfs
                            {--invoice= : Regenerar solo este ID de comprobante}
                            {--force : Omitir confirmación}';

    protected $description = 'Vuelve a generar los PDF de comprobantes ya guardados (útil tras cambiar la plantilla receipt-pdf).';

    public function handle(PaymentReceiptService $receiptService): int
    {
        $invoiceId = $this->option('invoice');

        $query = Invoice::query()->whereNotNull('receipt_pdf_path');

        if ($invoiceId !== null && $invoiceId !== '') {
            $query->whereKey((int) $invoiceId);
        }

        $invoices = $query->orderBy('id')->get();

        if ($invoices->isEmpty()) {
            $this->warn('No hay comprobantes con PDF guardado en disco.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Se regenerarán '.$invoices->count().' PDF(s) con la plantilla actual. ¿Continuar?',
            true
        )) {
            $this->warn('Operación cancelada.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();

        foreach ($invoices as $invoice) {
            $oldPath = $invoice->receipt_pdf_path;

            try {
                $receiptService->generateAndStorePdf($invoice);
                $invoice->refresh();

                if (
                    filled($oldPath)
                    && $oldPath !== $invoice->receipt_pdf_path
                    && Storage::disk('public')->exists($oldPath)
                ) {
                    Storage::disk('public')->delete($oldPath);
                }

                $ok++;
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Comprobante #{$invoice->id} ({$invoice->invoice_number}): {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Listo: {$ok} regenerado(s), {$failed} error(es).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
