<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\RestaurantCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsightsController extends Controller
{
    public function customers(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || $u->restaurant_id === null) {
            abort(403);
        }
        $rid = (int) $u->restaurant_id;

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $q = trim((string) $request->input('q', ''));

        $rows = Order::query()
            ->where('restaurant_id', $rid)
            ->when($dateFrom !== null, fn ($x) => $x->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($x) => $x->whereDate('created_at', '<=', $dateTo))
            ->when($q !== '', function ($x) use ($q): void {
                $needle = '%'.$q.'%';
                $x->where(function ($w) use ($needle): void {
                    $w->where('customer_name', 'like', $needle)
                        ->orWhere('customer_phone', 'like', $needle);
                });
            })
            ->selectRaw("
                coalesce(nullif(customer_name,''), 'Müşteri') as customer_name,
                coalesce(nullif(customer_phone,''), '-') as customer_phone,
                count(*) as order_count,
                coalesce(sum(total_price), 0) as total_spent,
                max(created_at) as last_order_at
            ")
            ->groupByRaw("coalesce(nullif(customer_name,''), 'Müşteri'), coalesce(nullif(customer_phone,''), '-')")
            ->orderByDesc('order_count')
            ->limit(100)
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->customer_name,
                'phone' => (string) $r->customer_phone,
                'order_count' => (int) $r->order_count,
                'total_spent' => (float) $r->total_spent,
                'last_order_at' => (string) $r->last_order_at,
            ])->values();

        return response()->json([
            'summary' => [
                'customer_count' => $rows->count(),
                'order_count' => (int) $rows->sum('order_count'),
                'total_spent' => (float) $rows->sum('total_spent'),
            ],
            'data' => $rows,
            'filters' => [
                'q' => $q,
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || $u->restaurant_id === null) {
            abort(403);
        }
        $rid = (int) $u->restaurant_id;

        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $rows = Product::query()
            ->where('restaurant_id', $rid)
            ->with('category:id,name')
            ->when($q !== '', fn ($x) => $x->where('name', 'like', '%'.$q.'%'))
            ->when($status !== 'all', fn ($x) => $x->where('status', $status))
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Product $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name,
                'price' => (float) $p->price,
                'stock' => (int) $p->stock,
                'status' => (string) $p->status,
            ])->values();

        return response()->json([
            'summary' => [
                'product_count' => $rows->count(),
                'active_count' => (int) $rows->where('status', 'active')->count(),
            ],
            'data' => $rows,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || $u->restaurant_id === null) {
            abort(403);
        }
        $rid = (int) $u->restaurant_id;

        $q = trim((string) $request->input('q', ''));

        $rows = RestaurantCategory::query()
            ->where('restaurant_id', $rid)
            ->withCount('products')
            ->when($q !== '', fn ($x) => $x->where('name', 'like', '%'.$q.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (RestaurantCategory $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'sort_order' => (int) $c->sort_order,
                'products_count' => (int) ($c->products_count ?? 0),
            ])->values();

        return response()->json([
            'summary' => [
                'category_count' => $rows->count(),
                'products_in_categories' => (int) $rows->sum('products_count'),
            ],
            'data' => $rows,
            'filters' => ['q' => $q],
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || $u->restaurant_id === null) {
            abort(403);
        }
        $rid = (int) $u->restaurant_id;

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $delivered = OrderStatus::Delivered->value;
        $cancelled = OrderStatus::Cancelled->value;

        $orders = Order::query()
            ->where('restaurant_id', $rid)
            ->when($dateFrom !== null, fn ($x) => $x->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($x) => $x->whereDate('created_at', '<=', $dateTo));
        $byStatus = (clone $orders)
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $deliveredScope = Order::query()
            ->where('restaurant_id', $rid)
            ->where('status', $delivered)
            ->when($dateFrom !== null, fn ($x) => $x->whereDate('updated_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($x) => $x->whereDate('updated_at', '<=', $dateTo));
        $sums = (clone $deliveredScope)->selectRaw('
            count(*) as delivered_count,
            coalesce(sum(total_price), 0) as revenue,
            coalesce(sum(delivery_fee), 0) as delivery_fees,
            coalesce(sum(discount_amount), 0) as discounts
        ')->first();

        $cancelledCount = Order::query()
            ->where('restaurant_id', $rid)
            ->where('status', $cancelled)
            ->when($dateFrom !== null, fn ($x) => $x->whereDate('updated_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($x) => $x->whereDate('updated_at', '<=', $dateTo))
            ->count();

        return response()->json([
            'by_status' => $byStatus,
            'order_total' => (int) (clone $orders)->count(),
            'delivered_count' => (int) ($sums?->delivered_count ?? 0),
            'cancelled_count' => (int) $cancelledCount,
            'revenue' => (float) ($sums?->revenue ?? 0),
            'delivery_fees' => (float) ($sums?->delivery_fees ?? 0),
            'discounts' => (float) ($sums?->discounts ?? 0),
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse((string) $request->input('date_from')) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse((string) $request->input('date_to')) : null;

        return [$dateFrom, $dateTo];
    }
}

