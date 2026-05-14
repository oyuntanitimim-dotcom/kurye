<?php

namespace App\Http\Controllers\Restaurant;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use App\Modules\Restaurants\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService
    ) {}

    /**
     * Bekleyen (pending) sipariş için hafif JSON poll; sesli uyarı istemcisi kullanır.
     *
     * `new_external_pending`: telefon / dükkân (işletmenin kendi girdiği) dışındaki bekleyenler —
     * pazar yeri (Yemeksepeti, Trendyol Yemek vb.) ve mağaza kanalı. İstemci bununla bip verir.
     */
    public function poll(Request $request): JsonResponse
    {
        $rid = (int) Auth::user()->restaurant_id;

        if ($request->boolean('bootstrap')) {
            $maxId = (int) (Order::query()->where('restaurant_id', $rid)->max('id') ?? 0);

            return response()->json([
                'new_pending' => false,
                'new_external_pending' => false,
                'max_id' => $maxId,
                'external_pending_count' => $this->externalMarketplacePendingCount($rid),
            ]);
        }

        $since = max(0, (int) $request->query('since_id', 0));
        $base = Order::query()
            ->where('restaurant_id', $rid)
            ->where('status', OrderStatus::Pending->value)
            ->where('id', '>', $since);

        $newPending = (clone $base)->exists();
        $newExternalPending = (clone $base)->where(function ($q): void {
            $q->whereNotIn('source', [
                OrderSource::Phone->value,
                OrderSource::WalkIn->value,
            ])->orWhereNull('source');
        })->exists();

        $maxId = (int) (Order::query()->where('restaurant_id', $rid)->max('id') ?? 0);

        return response()->json([
            'new_pending' => $newPending,
            'new_external_pending' => $newExternalPending,
            'max_id' => $maxId,
            'external_pending_count' => $this->externalMarketplacePendingCount($rid),
        ]);
    }

    /** Telefon / dükkân dışı bekleyen sipariş adedi (pazar yeri, mağaza vb.). */
    private function externalMarketplacePendingCount(int $restaurantId): int
    {
        return (int) Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', OrderStatus::Pending->value)
            ->where(function ($q): void {
                $q->whereNotIn('source', [
                    OrderSource::Phone->value,
                    OrderSource::WalkIn->value,
                ])->orWhereNull('source');
            })
            ->count();
    }

    public function index(Request $request): View
    {
        $rid = Auth::user()->restaurant_id;

        $products = Product::query()
            ->where('restaurant_id', $rid)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $openNewOrderModal = $request->boolean('open_new_order')
            || (string) old('_form', '') === 'new_order';

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [10, 25, 30, 50], true)) {
            $perPage = 25;
        }

        $ordersQuery = Order::query()
            ->where('restaurant_id', $rid)
            ->with(['customer', 'items']);

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->toString().'%';
            $rawQ = trim((string) $request->input('q'));
            $ordersQuery->where(function ($q) use ($needle, $rawQ): void {
                $q->where('customer_name', 'like', $needle)
                    ->orWhere('customer_phone', 'like', $needle);
                if ($rawQ !== '' && ctype_digit($rawQ)) {
                    $q->orWhere('id', (int) $rawQ);
                }
            });
        }

        return view('restaurant.orders', [
            'title' => 'Siparişler',
            'orders' => $ordersQuery->latest()->paginate($perPage)->appends($request->query()),
            'products' => $products,
            'openNewOrderModal' => $openNewOrderModal,
            'filters' => $request->only(['q', 'per_page']),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorizeRestaurant($order);

        $order->load([
            'items', 'statusHistories', 'customer', 'deliveryAddress',
            'restaurant', 'courier',
            'dispatchDecisions.chosenCourier', 'dispatchDecisions.createdBy',
        ]);

        return view('restaurant.orders.show', [
            'title' => 'Sipariş #'.$order->id,
            'order' => $order,
        ]);
    }

    public function accept(Order $order): RedirectResponse
    {
        $this->authorizeRestaurant($order);
        if ($order->status !== OrderStatus::Pending->value) {
            return back()->withErrors(['status' => 'Sipariş bu durumda onaylanamaz.']);
        }
        $this->orderStateService->transition($order, OrderStatus::Accepted);

        return back()->with('status', 'Sipariş onaylandı.');
    }

    public function preparing(Order $order): RedirectResponse
    {
        $this->authorizeRestaurant($order);
        $this->orderStateService->transition($order, OrderStatus::Preparing);

        return back()->with('status', 'Hazırlanıyor olarak işaretlendi.');
    }

    public function ready(Order $order): RedirectResponse
    {
        $this->authorizeRestaurant($order);
        $this->orderStateService->transition($order, OrderStatus::Ready);

        return back()->with('status', 'Sipariş hazır. Kurye şirketine haber vermek için «Kurye çağır»ya basın.');
    }

    public function requestCourier(Order $order): RedirectResponse
    {
        $this->authorizeRestaurant($order);

        if ($order->status !== OrderStatus::Ready->value) {
            return back()->withErrors(['status' => 'Sadece hazır siparişlerde kurye çağrılabilir.']);
        }

        if ($order->courier_id !== null) {
            return back()->withErrors(['status' => 'Bu siparişe zaten kurye atanmış.']);
        }

        if ($order->restaurant_courier_requested_at !== null) {
            return back()->with('status', 'Kurye şirketine zaten haber verildi.');
        }

        $order->update(['restaurant_courier_requested_at' => now()]);

        return back()->with('status', 'Kurye şirketi bilgilendirildi. Atama panelden yapılacak.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorizeRestaurant($order);

        if (! in_array($order->status, [
            OrderStatus::Pending->value,
            OrderStatus::Accepted->value,
            OrderStatus::Preparing->value,
        ], true)) {
            return back()->withErrors(['status' => 'Bu durumda sipariş iptal edilemez.']);
        }

        $this->orderStateService->transition($order, OrderStatus::Cancelled);

        return back()->with('status', 'Sipariş iptal edildi.');
    }

    private function authorizeRestaurant(Order $order): void
    {
        if ((int) $order->restaurant_id !== (int) Auth::user()->restaurant_id) {
            abort(403);
        }
    }
}
