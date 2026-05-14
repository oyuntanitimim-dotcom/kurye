<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $rid = Auth::user()->restaurant_id;

        return view('restaurant.dashboard', [
            'title' => 'Firma özeti',
            'pendingOrders' => Order::query()
                ->where('restaurant_id', $rid)
                ->whereIn('status', ['pending', 'accepted', 'preparing', 'ready'])
                ->with(['customer'])
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }
}
