<?php

use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Policies\InvoicePolicy;
use App\Models\User;

it('allows administrators to inspect invoices whose order was soft deleted', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->once()->with('payments.access')->andReturnTrue();
    $user->shouldReceive('hasRole')->once()->with('Administrador')->andReturnTrue();

    $invoice = new Invoice();
    $invoice->setRelation('order', null);

    expect((new InvoicePolicy())->view($user, $invoice))->toBeTrue();
});

it('denies non-administrators access to invoices without an active order', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->once()->with('payments.access')->andReturnTrue();
    $user->shouldReceive('hasRole')->once()->with('Administrador')->andReturnFalse();

    $invoice = new Invoice();
    $invoice->setRelation('order', null);

    expect((new InvoicePolicy())->view($user, $invoice))->toBeFalse();
});
