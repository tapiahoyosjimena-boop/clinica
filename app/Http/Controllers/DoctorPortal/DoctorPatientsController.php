<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Domains\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorPatientsController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('doctor_id', $user->id)
            ->with('patient')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('doctor-portal.patients', compact('orders'));
    }
}
