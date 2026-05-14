<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $courier = Auth::user()->courierProfile;
        if ($courier === null) {
            abort(500, 'Kurye profili eksik.');
        }

        return view('courier.dashboard', [
            'title' => 'Kurye Paneli',
            'activeOrders' => Order::query()
                ->where('courier_id', $courier->id)
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->with(['restaurant', 'customer'])
                ->latest()
                ->get(),
        ]);
    }
}
