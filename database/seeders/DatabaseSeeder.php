<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Domains\Auth\Seeders\AuthSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthSeeder::class,
            ImagingEquipmentSeeder::class,
            CatalogSeeder::class,
            ExamRequirementsSeeder::class,
            LaboratoryExamParametersSeeder::class,
            PaymentMethodSeeder::class,
            ReactivosSeeder::class,
        ]);
    }
}
