<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()->with(['firm', 'restaurant', 'customer']);

        if ($request->filled('firm_id')) {
            $query->where('firm_id', $request->integer('firm_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->boolean('active')) {
            $query->whereNotIn('status', [
                OrderStatus::Delivered->value,
                OrderStatus::Cancelled->value,
            ]);
        }

        if ($request->boolean('awaiting_courier')) {
            $query->where('status', OrderStatus::Ready->value)
                ->whereNull('courier_id');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return view('admin.orders', [
            'title' => 'Tüm Siparişler',
            'orders' => $query->latest()->paginate(40)->appends($request->query()),
            'firms' => Firm::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => OrderStatus::cases(),
            'filters' => $request->only(['firm_id', 'status', 'date_from', 'date_to', 'active', 'awaiting_courier']),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'items', 'statusHistories', 'customer', 'deliveryAddress',
            'restaurant', 'courier', 'firm',
            'dispatchDecisions.chosenCourier', 'dispatchDecisions.createdBy',
        ]);

        return view('admin.orders.show', [
            'title' => 'Sipariş #'.$order->id,
            'order' => $order,
        ]);
    }
}
