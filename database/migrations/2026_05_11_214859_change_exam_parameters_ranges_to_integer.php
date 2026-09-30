<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Redondear decimales existentes al entero más cercano antes de cambiar el tipo de columna.
        DB::table('exam_parameters')->update([
            'reference_min' => DB::raw('ROUND(reference_min, 0)'),
            'reference_max' => DB::raw('ROUND(reference_max, 0)'),
            'critical_min' => DB::raw('ROUND(critical_min, 0)'),
            'critical_max' => DB::raw('ROUND(critical_max, 0)'),
        ]);

        Schema::table('exam_parameters', function (Blueprint $table) {
            $table->integer('reference_min')->nullable()->change();
            $table->integer('reference_max')->nullable()->change();
            $table->integer('critical_min')->nullable()->change();
            $table->integer('critical_max')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exam_parameters', function (Blueprint $table) {
            $table->decimal('reference_min', 10, 4)->nullable()->change();
            $table->decimal('reference_max', 10, 4)->nullable()->change();
            $table->decimal('critical_min', 10, 4)->nullable()->change();
            $table->decimal('critical_max', 10, 4)->nullable()->change();
        });
    }
};
