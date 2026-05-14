<?php

declare(strict_types=1);

namespace App\Http\Controllers\Restaurant;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class ManualOrderController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('restaurant.orders.index', ['open_new_order' => '1']);
    }

    public function store(Request $request): RedirectResponse
    {
        $rid = (int) Auth::user()->restaurant_id;

        $data = $request->validate([
            'source' => ['required', 'in:phone,walk_in'],
            'customer_name' => ['required', 'string', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:48'],
            'payment_method' => ['required', 'in:cash_on_delivery,card_on_delivery,online'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $lines = array_values(array_filter(
            $data['lines'] ?? [],
            static fn (array $row): bool => isset($row['product_id']) && (int) $row['product_id'] > 0
                && (int) ($row['quantity'] ?? 0) > 0
        ));

        if ($lines === []) {
            return back()->withErrors(['lines' => 'En az bir ürün satırı seçin.'])->withInput();
        }

        $data['lines'] = $lines;

        $source = $data['source'] === 'phone' ? OrderSource::Phone : OrderSource::WalkIn;
        $deliveryFee = (float) ($data['delivery_fee'] ?? 0);

        $order = DB::transaction(function () use ($data, $rid, $source, $deliveryFee): Order {
            $restaurant = Restaurant::query()->whereKey($rid)->firstOrFail();

            $total = 0.0;
            $built = [];
            foreach ($data['lines'] as $row) {
                $pid = (int) $row['product_id'];
                $qty = (int) $row['quantity'];
                $product = Product::query()
                    ->where('restaurant_id', $rid)
                    ->where('status', 'active')
                    ->whereKey($pid)
                    ->firstOrFail();
                $total += $product->effectiveUnitPrice() * $qty;
                $built[] = [$product, $qty];
            }

            $order = Order::query()->create([
                'firm_id' => $restaurant->firm_id,
                'source' => $source->value,
                'user_id' => null,
                'restaurant_id' => $rid,
                'courier_id' => null,
                'delivery_address_id' => null,
                'status' => OrderStatus::Pending->value,
                'total_price' => $total + $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => 0,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
            ]);

            foreach ($built as [$product, $qty]) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->effectiveUnitPrice(),
                    'quantity' => $qty,
                    'product_name' => $product->name,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::Pending->value,
                'meta' => ['source' => 'manual_restaurant'],
                'created_at' => now(),
            ]);

            return $order;
        });

        return redirect()
            ->route('restaurant.orders.show', $order)
            ->with('status', 'Yeni sipariş oluşturuldu.');
    }
}
