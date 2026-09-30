<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['sample_id']);
            $table->unsignedBigInteger('sample_id')->nullable()->change();
            $table->foreign('sample_id')->references('id')->on('samples')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['sample_id']);
            $table->unsignedBigInteger('sample_id')->nullable(false)->change();
            $table->foreign('sample_id')->references('id')->on('samples')->onDelete('restrict');
        });
    }
};
