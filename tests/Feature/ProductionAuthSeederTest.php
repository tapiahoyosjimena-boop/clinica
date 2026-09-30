<?php

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Seeders\AuthSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates production roles without seeding demo staff accounts', function () {
    $previousEnvironment = app()->environment();

    try {
        app()->detectEnvironment(fn () => 'production');

        app(AuthSeeder::class)->run();

        expect(Role::query()->where('name', 'Administrador')->exists())->toBeTrue()
            ->and(User::query()->count())->toBe(0);
    } finally {
        app()->detectEnvironment(fn () => $previousEnvironment);
    }
});
