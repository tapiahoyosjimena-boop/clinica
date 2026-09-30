<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('libelula_qr_simple_url', 500)->nullable()->after('libelula_status');
            $table->string('libelula_codigo_recaudacion', 50)->nullable()->after('libelula_qr_simple_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['libelula_qr_simple_url', 'libelula_codigo_recaudacion']);
        });
    }
};
