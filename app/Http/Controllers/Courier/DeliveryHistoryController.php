<?php

namespace App\Http\Controllers\Courier;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeliveryHistoryController extends Controller
{
    public function index(): View
    {
        $courier = Auth::user()->courierProfile;
        if ($courier === null) {
            abort(500, 'Kurye profili eksik.');
        }

        return view('courier.deliveries', [
            'title' => 'Teslimatlar',
            'orders' => Order::query()
                ->where('courier_id', $courier->id)
                ->where('status', OrderStatus::Delivered->value)
                ->with(['restaurant', 'customer'])
                ->latest()
                ->paginate(25),
        ]);
    }
}
