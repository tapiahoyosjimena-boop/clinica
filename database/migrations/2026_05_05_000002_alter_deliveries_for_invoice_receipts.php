<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('id')->constrained('invoices')->nullOnDelete();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['result_id']);
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('result_id')->nullable()->change();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreign('result_id')->references('id')->on('results')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['result_id']);
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('result_id')->nullable(false)->change();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreign('result_id')->references('id')->on('results')->restrictOnDelete();
        });
    }
};
