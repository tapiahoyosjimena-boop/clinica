<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Impone un único rol por usuario a nivel de base de datos.
 *
 * 1. Limpieza: si alguna instancia del modelo ya tiene varios roles asignados,
 *    conservar solo el de role_id más bajo (asignado primero) y eliminar el resto.
 * 2. Añadir restricción UNIQUE en (model_type, model_id) en model_has_roles
 *    para que la base de datos rechace insertar un segundo rol al mismo usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Limpiar registros duplicados (mantener solo el primer rol asignado) ──

        $duplicates = DB::table('model_has_roles')
            ->select('model_type', 'model_id')
            ->groupBy('model_type', 'model_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            // Obtener el role_id más bajo (el primero asignado) para este model
            $keepRoleId = DB::table('model_has_roles')
                ->where('model_type', $dup->model_type)
                ->where('model_id', $dup->model_id)
                ->orderBy('role_id')
                ->value('role_id');

            // Borrar todos los demás roles del mismo model
            DB::table('model_has_roles')
                ->where('model_type', $dup->model_type)
                ->where('model_id', $dup->model_id)
                ->where('role_id', '!=', $keepRoleId)
                ->delete();
        }

        // ── 2. Agregar restricción UNIQUE (model_type, model_id) ─────────────────

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unique(['model_type', 'model_id'], 'model_has_roles_model_unique');
        });
    }

    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropUnique('model_has_roles_model_unique');
        });
    }
};
