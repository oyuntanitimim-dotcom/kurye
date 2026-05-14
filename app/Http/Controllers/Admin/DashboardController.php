<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $pendingStatus = OrderStatus::Pending->value;
        $deliveredStatus = OrderStatus::Delivered->value;

        $stats = [
            ['label' => 'Kurye şirketleri', 'value' => Firm::query()->count()],
            ['label' => 'Aktif firmalar', 'value' => Firm::query()->where('status', 'active')->count()],
            ['label' => 'Firmalar', 'value' => Restaurant::query()->count()],
            ['label' => 'Siparişler', 'value' => Order::query()->count()],
            ['label' => 'Bekleyen sipariş', 'value' => Order::query()->where('status', $pendingStatus)->count()],
            ['label' => 'Bugün sipariş', 'value' => Order::query()->whereDate('created_at', today())->count()],
            ['label' => '24s teslim', 'value' => Order::query()
                ->where('status', $deliveredStatus)
                ->where('updated_at', '>=', now()->subDay())
                ->count()],
            ['label' => 'Kuryeler', 'value' => Courier::query()->count()],
            ['label' => 'Kullanıcılar', 'value' => User::query()->count()],
        ];

        return view('admin.dashboard', [
            'title' => 'Gösterge Paneli',
            'stats' => $stats,
        ]);
    }
}
