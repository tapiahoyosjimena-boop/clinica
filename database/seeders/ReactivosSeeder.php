<?php

namespace Database\Seeders;

use App\Domains\Reactivos\Models\Provider;
use App\Domains\Reactivos\Models\Reagent;
use App\Models\User;
use App\Support\SystemAdministratorGuard;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ReactivosSeeder extends Seeder
{
    /**
     * Datos de prueba para el módulo Reactivos (proveedores + reactivos).
     * Idempotente: si ya existen los 3 proveedores de prueba, no duplica.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', SystemAdministratorGuard::PRIMARY_EMAIL)->first()
            ?? User::query()->role('Administrador')->first()
            ?? User::query()->orderBy('id')->first();

        if (! $admin) {
            $this->command?->warn('ReactivosSeeder: no hay usuarios. Ejecute AuthSeeder primero.');

            return;
        }

        Auth::login($admin);

        $tz = config('app.timezone', 'America/La_Paz');

        $providers = [
            [
                'name' => 'Distribuidora Médica Andina S.A.',
                'contact_person' => 'Ing. Carla Villarroel',
                'phone' => '+591 2 2445566',
                'email' => 'ventas@dma-bo.com',
                'address' => 'Av. Arce N° 2145, Of. 302, La Paz',
            ],
            [
                'name' => 'Importadora LabClínica Bolivia',
                'contact_person' => 'Lic. Marco Terán',
                'phone' => '+591 3 3367788',
                'email' => 'logistica@labclinica.bo',
                'address' => 'Zona Sur, Calle 15 Oeste, Santa Cruz de la Sierra',
            ],
            [
                'name' => 'REAGEN BOLIVIA S.R.L.',
                'contact_person' => 'Sra. Patricia Quispe',
                'phone' => '+591 2 2118899',
                'email' => 'pedidos@reagen-bo.com',
                'address' => 'Calle Colombia N° 789, Cochabamba',
            ],
        ];

        $providerModels = [];
        foreach ($providers as $row) {
            $providerModels[] = Provider::firstOrCreate(
                ['name' => $row['name']],
                $row
            );
        }

        $p0 = $providerModels[0]->id;
        $p1 = $providerModels[1]->id;
        $p2 = $providerModels[2]->id;

        $reagents = [
            [
                'name' => 'Reactivo CH50 (perfil lipídico automatizado)',
                'description' => 'Kit para analizadores Roche Cobas; uso en química clínica.',
                'unit' => 'L',
                'stock_quantity' => 12,
                'min_stock' => 15,
                'expiration_date' => Carbon::parse('2027-03-01', $tz),
                'provider_id' => $p0,
            ],
            [
                'name' => 'Urea UV (uricasa)',
                'description' => 'Reactivo líquido listo para uso; determinación de urea en suero.',
                'unit' => 'mL',
                'stock_quantity' => 80,
                'min_stock' => 20,
                'expiration_date' => now($tz)->addDays(18),
                'provider_id' => $p1,
            ],
            [
                'name' => 'Glucosa enzimática (GOD-PAP)',
                'description' => 'Reactivo para glucosa en suero/plasma.',
                'unit' => 'mL',
                'stock_quantity' => 200,
                'min_stock' => 30,
                'expiration_date' => now($tz)->subMonths(2),
                'provider_id' => $p0,
            ],
            [
                'name' => 'Creatinina (picrato enzimático)',
                'description' => 'Reactivo para creatinina en suero.',
                'unit' => 'L',
                'stock_quantity' => 50,
                'min_stock' => 10,
                'expiration_date' => Carbon::parse('2026-11-15', $tz),
                'provider_id' => $p2,
            ],
            [
                'name' => 'Hemoglobina glicosilada (HbA1c) — diluyente',
                'description' => 'Solución auxiliar para determinación HbA1c.',
                'unit' => 'mL',
                'stock_quantity' => 5,
                'min_stock' => 10,
                'expiration_date' => now($tz)->addDays(12),
                'provider_id' => $p1,
            ],
            [
                'name' => 'Ácido úrico (uricasa)',
                'description' => 'Reactivo para ácido úrico en suero.',
                'unit' => 'mL',
                'stock_quantity' => 90,
                'min_stock' => 25,
                'expiration_date' => null,
                'provider_id' => $p2,
            ],
            [
                'name' => 'Colesterol total (CHOD-PAP)',
                'description' => 'Reactivo para colesterol total.',
                'unit' => 'L',
                'stock_quantity' => 3,
                'min_stock' => 10,
                'expiration_date' => Carbon::parse('2027-01-20', $tz),
                'provider_id' => $p0,
            ],
        ];

        foreach ($reagents as $row) {
            Reagent::firstOrCreate(
                ['name' => $row['name']],
                $row
            );
        }

        Auth::logout();

        $this->command?->info('ReactivosSeeder: 3 proveedores y 7 reactivos de prueba listos (firstOrCreate por nombre).');
    }
}
