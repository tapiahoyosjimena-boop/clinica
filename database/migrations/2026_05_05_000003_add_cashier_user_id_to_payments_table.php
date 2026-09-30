<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cashier_user_id')
                ->nullable()
                ->after('payment_method_id')
                ->constrained('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cashier_user_id']);
            $table->dropColumn('cashier_user_id');
        });
    }
};
