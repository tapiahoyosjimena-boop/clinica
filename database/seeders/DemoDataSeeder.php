<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orquestador de datos operativos demo (VPS).
 *
 * Ejecutar tras migraciones y seeders base:
 * php artisan db:seed --class=DemoDataSeeder --force
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DoctorsDemoSeeder::class,
            PatientsDemoSeeder::class,
            OrdersDemoSeeder::class,
            SamplesDemoSeeder::class,
            ImagingStudiesDemoSeeder::class,
            // PaymentsDemoSeeder::class,
            // ResultsDemoSeeder::class,
        ]);
    }
}
