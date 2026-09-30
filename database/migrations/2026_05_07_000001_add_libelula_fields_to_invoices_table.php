<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Identificador de transacción devuelto por Libélula al registrar la deuda.
            $table->string('libelula_transaction_id')->nullable()->after('receipt_pdf_path');

            // URL de pago que Libélula genera para que el paciente complete el pago.
            $table->string('libelula_payment_url')->nullable()->after('libelula_transaction_id');

            // Último estado reportado por Libélula (pendiente, pagado, expirado, etc.).
            $table->string('libelula_status')->nullable()->after('libelula_payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'libelula_transaction_id',
                'libelula_payment_url',
                'libelula_status',
            ]);
        });
    }
};
