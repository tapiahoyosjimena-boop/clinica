<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Domains\Orders\Models\Order;
use App\Domains\Results\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorDashboardController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $totalPatients = Order::query()
            ->where('doctor_id', $user->id)
            ->distinct('patient_id')
            ->count('patient_id');

        $pendingOrders = Order::query()
            ->where('doctor_id', $user->id)
            ->whereIn('status', ['pendiente', 'en_proceso'])
            ->count();

        $readyResults = Result::query()
            ->whereHas('order', fn ($q) => $q->where('doctor_id', $user->id))
            ->whereNotNull('validated_at')
            ->whereNotNull('pdf_path')
            ->count();

        $unreadNotifications = $user->unreadNotifications()->count();

        $recentOrders = Order::query()
            ->where('doctor_id', $user->id)
            ->with('patient')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('doctor-portal.dashboard', compact(
            'totalPatients',
            'pendingOrders',
            'readyResults',
            'unreadNotifications',
            'recentOrders'
        ));
    }
}
