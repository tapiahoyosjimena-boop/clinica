<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->timestamp('published_to_portal_at')->nullable()->after('pdf_path');
            $table->index('published_to_portal_at');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex(['published_to_portal_at']);
            $table->dropColumn('published_to_portal_at');
        });
    }
};
