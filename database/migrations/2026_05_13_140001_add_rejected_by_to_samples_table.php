<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->foreignId('rejected_by_user_id')
                ->nullable()
                ->after('motivo_rechazo')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
        });

        if (! Schema::hasTable('sample_status_histories')) {
            return;
        }

        $sampleIds = DB::table('samples')
            ->where('status', 'rechazada')
            ->whereNull('rejected_by_user_id')
            ->pluck('id');

        foreach ($sampleIds as $sampleId) {
            $row = DB::table('sample_status_histories')
                ->where('sample_id', $sampleId)
                ->where('new_status', 'rechazada')
                ->orderByDesc('id')
                ->first();

            if ($row !== null && $row->changed_by !== null) {
                DB::table('samples')->where('id', $sampleId)->update([
                    'rejected_by_user_id' => $row->changed_by,
                    'rejected_at' => $row->created_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropColumn(['rejected_by_user_id', 'rejected_at']);
        });
    }
};
