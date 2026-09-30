<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reasigna todos los registros del admin legacy (admin@clinicanorte.com)
 * al administrador principal (admin@tecnoweb.shop) y elimina la cuenta legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacyEmail  = 'admin@clinicanorte.com';
        $primaryEmail = 'admin@tecnoweb.shop';

        $legacyId  = DB::table('users')->where('email', $legacyEmail)->value('id');
        $primaryId = DB::table('users')->where('email', $primaryEmail)->value('id');

        if ($legacyId === null) {
            // Ya fue eliminado; nada que hacer.
            return;
        }

        if ($primaryId === null) {
            // El admin principal aún no existe; no podemos reasignar.
            return;
        }

        // ── 1. Reasignar stock_movements ──────────────────────────────────────
        DB::table('stock_movements')
            ->where('user_id', $legacyId)
            ->update(['user_id' => $primaryId]);

        // ── 2. Reasignar orders (receptionist_id, responsible_user_id, cancelled_by) ──
        foreach (['receptionist_id', 'responsible_user_id', 'cancelled_by', 'doctor_id'] as $col) {
            if (Schema::hasColumn('orders', $col)) {
                DB::table('orders')
                    ->where($col, $legacyId)
                    ->update([$col => $primaryId]);
            }
        }

        // ── 3. Reasignar samples (rejected_by) ────────────────────────────────
        if (Schema::hasColumn('samples', 'rejected_by')) {
            DB::table('samples')
                ->where('rejected_by', $legacyId)
                ->update(['rejected_by' => $primaryId]);
        }

        // ── 4. Reasignar payments (cashier_user_id) ───────────────────────────
        if (Schema::hasColumn('payments', 'cashier_user_id')) {
            DB::table('payments')
                ->where('cashier_user_id', $legacyId)
                ->update(['cashier_user_id' => $primaryId]);
        }

        // ── 5. Reasignar results (responsible_user_id) ────────────────────────
        if (Schema::hasColumn('results', 'responsible_user_id')) {
            DB::table('results')
                ->where('responsible_user_id', $legacyId)
                ->update(['responsible_user_id' => $primaryId]);
        }

        // ── 6. Eliminar roles/permisos del legacy y luego el usuario ─────────
        DB::table('model_has_roles')->where('model_id', $legacyId)->delete();
        DB::table('model_has_permissions')->where('model_id', $legacyId)->delete();

        DB::table('users')->where('id', $legacyId)->delete();
    }

    public function down(): void
    {
        // No reversible: la cuenta legacy no debe volver a existir.
    }
};
