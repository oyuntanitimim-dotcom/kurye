<?php

namespace App\Http\Controllers\Firm;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\Courier;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Orders\Services\AutoDispatchService;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    /** @var list<string> Operasyon ekranı ile aynı “aktif” sipariş kümesi (durum / güncelleme izleme). */
    private const WATCH_BOARD_STATUSES = [
        'pending',
        'accepted',
        'preparing',
        'ready',
        'courier_assigned',
        'courier_accepted',
        'picked_up',
        'on_the_way',
    ];

    public function __construct(
        private readonly OrderStateService $orderStateService,
        private readonly AutoDispatchService $autoDispatchService
    ) {}

    /**
     * Firma genelinde yeni bekleyen sipariş tespiti (sesli uyarı poll).
     */
    public function poll(Request $request): JsonResponse
    {
        $firmId = (int) Auth::user()->firm_id;
        $courierReq = $this->readyAwaitingCourierStats($firmId);

        if ($request->boolean('bootstrap')) {
            $maxId = (int) (Order::query()->where('firm_id', $firmId)->max('id') ?? 0);

            return response()->json([
                'new_pending' => false,
                'max_id' => $maxId,
                'ready_awaiting_courier_count' => $courierReq['count'],
                'courier_request_max_order_id' => $courierReq['max_order_id'],
                'courier_request_signal_unix' => $courierReq['signal_unix'],
            ]);
        }

        $since = max(0, (int) $request->query('since_id', 0));
        $newPending = Order::query()
            ->where('firm_id', $firmId)
            ->where('status', OrderStatus::Pending->value)
            ->where('id', '>', $since)
            ->exists();
        $maxId = (int) (Order::query()->where('firm_id', $firmId)->max('id') ?? 0);

        return response()->json([
            'new_pending' => $newPending,
            'max_id' => $maxId,
            'ready_awaiting_courier_count' => $courierReq['count'],
            'courier_request_max_order_id' => $courierReq['max_order_id'],
            'courier_request_signal_unix' => $courierReq['signal_unix'],
        ]);
    }

    /**
     * @return array{count: int, max_order_id: int, signal_unix: int}
     */
    private function readyAwaitingCourierStats(int $firmId): array
    {
        $pool = Order::query()
            ->where('firm_id', $firmId)
            ->readyForFirmCourierPool();

        $count = (int) (clone $pool)->count();
        $maxOrderId = (int) ((clone $pool)->max('id') ?? 0);

        return [
            'count' => $count,
            'max_order_id' => $maxOrderId,
            'signal_unix' => $this->courierAlertSignalUnix($firmId),
        ];
    }

    /**
     * Çan/ses sinyali: restoran «Kurye çağır» zamanı ile atama kuyruğundaki siparişlerin güncellenme zamanının üstü.
     * Kurye reddedince sipariş tekrar havuza düşer; `restaurant_courier_requested_at` değişmez ama `updated_at` yenilenir — sinyal yükselir, çan tekrar çalar.
     */
    private function courierAlertSignalUnix(int $firmId): int
    {
        return max(
            $this->maxRestaurantCourierRequestSignalUnix($firmId),
            $this->maxReadyPoolUpdatedSignalUnix($firmId),
        );
    }

    /**
     * Hazır/kuryesiz kuyruğundaki siparişlerin en son güncellenme anı (son 7 gün).
     */
    private function maxReadyPoolUpdatedSignalUnix(int $firmId): int
    {
        $since = Carbon::now()->subDays(7);
        $raw = Order::query()
            ->where('firm_id', $firmId)
            ->readyForFirmCourierPool()
            ->where('updated_at', '>=', $since)
            ->max('updated_at');
        if ($raw === null) {
            return 0;
        }

        return (int) Carbon::parse((string) $raw)->timestamp;
    }

    /**
     * Otomatik atama siparişi hemen «kurye atandı» yapsa bile restoran «Kurye çağır» zamanı firma uyarısı için kalır.
     * Yalnızca son 7 gün içindeki çağrılar (eski veri sessizlik üretmesin).
     */
    private function maxRestaurantCourierRequestSignalUnix(int $firmId): int
    {
        $since = Carbon::now()->subDays(7);
        $raw = Order::query()
            ->where('firm_id', $firmId)
            ->whereNotNull('restaurant_courier_requested_at')
            ->where('restaurant_courier_requested_at', '>=', $since)
            ->max('restaurant_courier_requested_at');

        if ($raw === null) {
            return 0;
        }

        return (int) Carbon::parse((string) $raw)->timestamp;
    }

    /**
     * Üst çan / arka plan izleme: aktif siparişlerde değişiklik (ör. restoran “hazır”) imzası.
     */
    public function watchBoard(): JsonResponse
    {
        $firmId = (int) Auth::user()->firm_id;

        $active = Order::query()
            ->where('firm_id', $firmId)
            ->whereIn('status', self::WATCH_BOARD_STATUSES);

        $maxUpdated = (clone $active)->max('updated_at');
        $maxUpdatedIso = $maxUpdated !== null ? (string) $maxUpdated : '';

        $pending = (int) Order::query()
            ->where('firm_id', $firmId)
            ->where('status', OrderStatus::Pending->value)
            ->count();

        $readyAwaitingCourier = (int) Order::query()
            ->where('firm_id', $firmId)
            ->readyForFirmCourierPool()
            ->count();

        $signature = hash('sha256', $maxUpdatedIso.'|'.$pending.'|'.$readyAwaitingCourier);

        return response()->json([
            'signature' => $signature,
            'pending' => $pending,
            'ready_awaiting_courier' => $readyAwaitingCourier,
            'max_updated_at' => $maxUpdatedIso,
        ]);
    }

    public function index(Request $request): View
    {
        $firmId = Auth::user()->firm_id;

        $perPage = (int) $request->input('per_page', 30);
        if (! in_array($perPage, [10, 25, 30, 50], true)) {
            $perPage = 30;
        }

        $restaurantId = $request->filled('restaurant_id') ? (int) $request->input('restaurant_id') : null;
        $courierId = $request->filled('courier_id') ? (int) $request->input('courier_id') : null;
        if ($restaurantId !== null && $restaurantId > 0) {
            $ok = Restaurant::query()->where('firm_id', $firmId)->whereKey($restaurantId)->exists();
            if (! $ok) {
                $restaurantId = null;
            }
        }

        $firm = Firm::query()->find($firmId);
        $op = $firm !== null ? $firm->mergedOperationSettings() : [];

        $couriers = Courier::query()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $activeByCourier = Order::query()
            ->where('firm_id', $firmId)
            ->whereIn('status', [
                OrderStatus::CourierAssigned->value,
                OrderStatus::CourierAccepted->value,
                OrderStatus::PickedUp->value,
                OrderStatus::OnTheWay->value,
            ])
            ->whereNotNull('courier_id')
            ->selectRaw('courier_id, count(*) as c')
            ->groupBy('courier_id')
            ->pluck('c', 'courier_id');

        $courierMeta = [];
        foreach ($couriers as $c) {
            $courierMeta[(int) $c->id] = [
                'active_orders' => (int) ($activeByCourier[(int) $c->id] ?? 0),
            ];
        }

        $query = Order::query()
            ->where('firm_id', $firmId)
            ->with(['restaurant', 'customer', 'courier']);

        if ($restaurantId !== null && $restaurantId > 0) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($courierId !== null && $courierId > 0) {
            $query->where('courier_id', $courierId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->boolean('awaiting_courier')) {
            $query->readyForFirmCourierPool();
        }

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->toString().'%';
            $rawQ = trim((string) $request->input('q'));
            $query->where(function ($q) use ($needle, $rawQ): void {
                $q->where('customer_name', 'like', $needle)
                    ->orWhere('customer_phone', 'like', $needle);
                if ($rawQ !== '' && ctype_digit($rawQ)) {
                    $q->orWhere('id', (int) $rawQ);
                }
                $q->orWhereHas('restaurant', function ($rq) use ($needle): void {
                    $rq->where('name', 'like', $needle);
                });
            });
        }

        return view('firm.orders', [
            'title' => 'Siparişler',
            'orders' => $query->latest()->paginate($perPage)->appends($request->query()),
            'couriers' => $couriers,
            'courierMeta' => $courierMeta,
            'restaurants' => Restaurant::query()->where('firm_id', $firmId)->orderBy('name')->get(['id', 'name']),
            'autoDispatchEnabled' => (bool) ($op['auto_dispatch_enabled'] ?? false),
            'statuses' => OrderStatus::cases(),
            'filters' => $request->only(['status', 'q', 'per_page', 'awaiting_courier', 'restaurant_id', 'courier_id']),
        ]);
    }

    public function assignCourier(Request $request, Order $order): RedirectResponse
    {
        if ((int) $order->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }

        $data = $request->validate([
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
        ]);

        $courier = Courier::query()->where('firm_id', $order->firm_id)->whereKey($data['courier_id'])->firstOrFail();

        $previousCourierId = $order->courier_id !== null ? (int) $order->courier_id : null;
        if ($previousCourierId !== null && $previousCourierId === (int) $courier->id) {
            return back()->with('status', 'Bu kurye zaten atanmış.');
        }

        if (
            $order->status === OrderStatus::Ready->value
            && $previousCourierId === null
            && $order->restaurant_courier_requested_at === null
        ) {
            return back()->with('error', 'Restoran henüz kurye çağırmadı.');
        }

        $reassignStatuses = [
            OrderStatus::CourierAssigned->value,
            OrderStatus::CourierAccepted->value,
            OrderStatus::PickedUp->value,
            OrderStatus::OnTheWay->value,
        ];

        $order->update(['courier_id' => $courier->id]);
        $fresh = $order->fresh();

        if (
            $previousCourierId !== null
            && (int) $courier->id !== $previousCourierId
            && $fresh->status === OrderStatus::CourierAccepted->value
        ) {
            $this->orderStateService->transition($fresh, OrderStatus::CourierAssigned, [
                'event' => 'firm_reassigned_after_courier_accepted',
            ]);
            $fresh = $order->fresh();
            $this->orderStateService->recordCourierReassignment($fresh, $previousCourierId, (int) $courier->id);

            return back()->with('status', 'Kurye değiştirildi; yeni kurye atama onayı bekliyor.');
        }

        if (in_array($fresh->status, $reassignStatuses, true)) {
            $this->orderStateService->recordCourierReassignment($fresh, $previousCourierId, (int) $courier->id);

            return back()->with('status', 'Kurye güncellendi.');
        }

        $this->orderStateService->transition($fresh, OrderStatus::CourierAssigned);

        return back()->with('status', 'Kurye atandı.');
    }

    public function autoDispatch(Order $order): RedirectResponse
    {
        if ((int) $order->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }

        if (
            $order->status === OrderStatus::Ready->value
            && $order->courier_id === null
            && $order->restaurant_courier_requested_at === null
        ) {
            return back()->with('error', 'Restoran henüz kurye çağırmadı.');
        }

        $result = $this->autoDispatchService->dispatchOrder(
            $order,
            'manual_ui',
            (int) Auth::id()
        );

        return $result['ok']
            ? back()->with('status', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * GEÇİCİ: Kurye atandı durumundan hızlıca "teslim edildi" yapmak için.
     * Proje bitince kaldırılacak.
     */
    public function markDelivered(Order $order): RedirectResponse
    {
        if ((int) $order->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }

        $allowed = [
            OrderStatus::CourierAssigned->value,
            OrderStatus::CourierAccepted->value,
            OrderStatus::PickedUp->value,
            OrderStatus::OnTheWay->value,
        ];

        if (! in_array((string) $order->status, $allowed, true)) {
            return back()->with('error', 'Bu sipariş bu durumdan teslim edildi yapılamaz.');
        }

        $this->orderStateService->transition($order, OrderStatus::Delivered, [
            'event' => 'firm_quick_delivered',
            'message' => 'Geçici: firma panelinden hızlı teslim.',
        ]);

        return back()->with('status', 'Teslim edildi olarak işaretlendi.');
    }

    /**
     * Teslim edilmemiş / iptal edilmemiş siparişi firma tarafından iptal (kurye ataması öncesi/sonrası).
     */
    public function cancel(Order $order): RedirectResponse
    {
        if ((int) $order->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }

        if (in_array($order->status, [OrderStatus::Delivered->value, OrderStatus::Cancelled->value], true)) {
            return back()->with('error', 'Bu sipariş iptal edilemez (teslim edilmiş veya zaten iptal).');
        }

        $this->orderStateService->transition($order, OrderStatus::Cancelled, [
            'event' => 'firm_cancelled',
            'message' => 'Firma yönetimi tarafından iptal.',
        ]);

        return back()->with('status', 'Sipariş iptal edildi.');
    }

    public function show(Order $order): View
    {
        if ((int) $order->firm_id !== (int) Auth::user()->firm_id) {
            abort(403);
        }

        $order->load([
            'items', 'statusHistories', 'customer', 'deliveryAddress',
            'restaurant', 'courier', 'firm',
            'dispatchDecisions.chosenCourier', 'dispatchDecisions.createdBy',
        ]);

        return view('firm.orders.show', [
            'title' => 'Sipariş #'.$order->id,
            'order' => $order,
        ]);
    }
}
