<?php

namespace Database\Seeders;

use App\Domains\Payments\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::firstOrCreate(
            ['name' => 'Efectivo'],
            ['is_active' => true]
        );

        PaymentMethod::firstOrCreate(
            ['name' => 'QR'],
            ['is_active' => true]
        );
    }
}
