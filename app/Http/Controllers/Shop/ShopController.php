<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Orders\Models\Review;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Models\Payment;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\Restaurant;
use App\Services\FirmContext;
use App\Services\Geocoding\NominatimGeocoder;
use App\Services\Shop\DeliveryFeeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private readonly DeliveryFeeCalculator $deliveryFeeCalculator,
        private readonly NominatimGeocoder $nominatimGeocoder,
    ) {}

    public function home(FirmContext $firmContext): View
    {
        $firm = $firmContext->require();

        return view('shop.home', [
            'title' => 'Ana Sayfa',
            'firm' => $firm,
            'restaurants' => Restaurant::query()->where('firm_id', $firm->id)->where('status', 'active')->latest()->limit(12)->get(),
        ]);
    }

    public function restaurants(FirmContext $firmContext): View
    {
        $firm = $firmContext->require();

        return view('shop.restaurants', [
            'title' => 'Firmalar',
            'firm' => $firm,
            'restaurants' => Restaurant::query()->where('firm_id', $firm->id)->where('status', 'active')->paginate(20),
        ]);
    }

    public function restaurant(FirmContext $firmContext, Restaurant $restaurant): View
    {
        $firm = $firmContext->require();
        if ((int) $restaurant->firm_id !== (int) $firm->id) {
            abort(404);
        }

        $restaurant->load(['categories', 'products' => fn ($q) => $q->where('status', 'active')]);

        return view('shop.restaurant', [
            'title' => $restaurant->name,
            'firm' => $firm,
            'restaurant' => $restaurant,
        ]);
    }

    public function cart(FirmContext $firmContext): View
    {
        $firm = $firmContext->require();
        ['items' => $items, 'subtotal' => $subtotal] = $this->summarizeCartSession($firm);

        $restaurant = $this->firstRestaurantFromCart($firm);
        $deliveryFee = $this->deliveryFeeCalculator->compute($firm, $restaurant, null);
        $distanceMode = (bool) ($firm->mergedOperationSettings()['delivery_use_distance'] ?? false);
        $shopFixedDelivery = $restaurant !== null && $restaurant->shop_delivery_fee !== null;
        $shopFixedDeliveryAmount = $shopFixedDelivery ? (float) $restaurant->shop_delivery_fee : null;

        return view('shop.cart', [
            'title' => 'Sepetim',
            'firm' => $firm,
            'items' => $items,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'delivery_distance_mode' => $distanceMode,
            'shop_fixed_delivery' => $shopFixedDelivery,
            'shop_fixed_delivery_amount' => $shopFixedDeliveryAmount,
        ]);
    }

    public function addToCart(Request $request, FirmContext $firmContext): RedirectResponse
    {
        $firm = $firmContext->require();
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        if ((int) $product->restaurant->firm_id !== (int) $firm->id) {
            abort(403);
        }

        $key = $this->cartKey($firm->id);
        $cart = session()->get($key, []);
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $data['quantity'];
        session()->put($key, $cart);

        return back()->with('status', 'Sepete eklendi.');
    }

    public function updateCart(Request $request, FirmContext $firmContext): RedirectResponse
    {
        $firm = $firmContext->require();
        $data = $request->validate([
            'qty' => ['required', 'array'],
            'qty.*' => ['integer', 'min:0', 'max:99'],
        ]);

        $key = $this->cartKey($firm->id);
        $cart = session()->get($key, []);
        foreach ($data['qty'] as $productId => $q) {
            $pid = (int) $productId;
            if ($q === 0) {
                unset($cart[$pid]);
            } else {
                $cart[$pid] = $q;
            }
        }
        session()->put($key, $cart);

        return back()->with('status', 'Sepet güncellendi.');
    }

    public function checkoutForm(FirmContext $firmContext): View|RedirectResponse
    {
        $firm = $firmContext->require();
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        ['subtotal' => $subtotal] = $this->summarizeCartSession($firm);
        if ($subtotal <= 0) {
            return redirect()->route('shop.cart')->withErrors(['cart' => 'Sepet boş.']);
        }

        $user = Auth::user();
        $addresses = $user->addresses;
        if ($addresses->isEmpty()) {
            return redirect()->route('shop.profile')->withErrors(['address' => 'Teslimat için önce bir adres ekleyin.']);
        }

        $restaurant = $this->firstRestaurantFromCart($firm);
        if ($restaurant === null) {
            return redirect()->route('shop.cart')->withErrors(['cart' => 'Sepet geçersiz.']);
        }

        $addressFees = [];
        foreach ($addresses as $a) {
            $addressFees[$a->id] = $this->deliveryFeeCalculator->compute($firm, $restaurant, $a);
        }

        $first = $addresses->first();
        $deliveryFee = $addressFees[$first->id] ?? $this->deliveryFeeCalculator->compute($firm, $restaurant, null);
        $distanceMode = (bool) ($firm->mergedOperationSettings()['delivery_use_distance'] ?? false);
        $shopFixedDelivery = $restaurant->shop_delivery_fee !== null;
        $shopFixedDeliveryAmount = $shopFixedDelivery ? (float) $restaurant->shop_delivery_fee : null;

        return view('shop.checkout', [
            'title' => 'Sipariş Onayı',
            'firm' => $firm,
            'addresses' => $addresses,
            'address_fees' => $addressFees,
            'delivery_fee' => $deliveryFee,
            'subtotal' => $subtotal,
            'estimated_total' => $subtotal + $deliveryFee,
            'delivery_distance_mode' => $distanceMode,
            'shop_fixed_delivery' => $shopFixedDelivery,
            'shop_fixed_delivery_amount' => $shopFixedDeliveryAmount,
        ]);
    }

    public function checkout(Request $request, FirmContext $firmContext): RedirectResponse
    {
        $firm = $firmContext->require();
        $user = Auth::user();
        if ($user === null || $user->firm_id !== $firm->id) {
            abort(403);
        }

        $data = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'in:cash_on_delivery,card_on_delivery,online'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $addr = $user->addresses()->whereKey($data['address_id'])->firstOrFail();

        $cart = session()->get($this->cartKey($firm->id), []);
        if ($cart === []) {
            return redirect()->route('shop.cart')->withErrors(['cart' => 'Sepet boş.']);
        }

        $restaurantId = null;
        $total = 0;
        $lines = [];
        foreach ($cart as $productId => $qty) {
            $p = Product::query()->with('restaurant')->find($productId);
            if (! $p || (int) $p->restaurant->firm_id !== (int) $firm->id) {
                continue;
            }
            if ($restaurantId === null) {
                $restaurantId = $p->restaurant_id;
            }
            if ((int) $restaurantId !== (int) $p->restaurant_id) {
                return back()->withErrors(['cart' => 'Tek seferde tek restoran seçebilirsiniz.']);
            }
            $lineTotal = $p->effectiveUnitPrice() * $qty;
            $total += $lineTotal;
            $lines[] = [$p, $qty, $lineTotal];
        }

        if ($restaurantId === null) {
            return redirect()->route('shop.cart')->withErrors(['cart' => 'Sepet geçersiz.']);
        }

        $restaurant = Restaurant::query()
            ->where('firm_id', $firm->id)
            ->whereKey($restaurantId)
            ->firstOrFail();

        $deliveryFee = $this->deliveryFeeCalculator->compute($firm, $restaurant, $addr);

        $bundle = DB::transaction(function () use (
            $firm,
            $user,
            $addr,
            $restaurantId,
            $total,
            $deliveryFee,
            $data,
            $lines
        ): array {
            $order = Order::query()->create([
                'firm_id' => $firm->id,
                'source' => OrderSource::OwnShop->value,
                'user_id' => $user->id,
                'restaurant_id' => $restaurantId,
                'courier_id' => null,
                'delivery_address_id' => $addr->id,
                'status' => OrderStatus::Pending->value,
                'total_price' => $total + $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => 0,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as [$p, $qty, $_line]) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'price' => $p->effectiveUnitPrice(),
                    'quantity' => $qty,
                    'product_name' => $p->name,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::Pending->value,
                'meta' => null,
                'created_at' => now(),
            ]);

            $flash = 'Siparişiniz alındı.';
            $paymentRedirect = null;

            if ($data['payment_method'] === 'online') {
                $checkout = app(PaymentGatewayInterface::class)->startCheckout($order, [
                    'return_url' => route('shop.orders.show', $order),
                ]);

                $redirectUrl = isset($checkout['redirect_url']) && is_string($checkout['redirect_url'])
                    ? trim($checkout['redirect_url'])
                    : '';

                Payment::query()->create([
                    'order_id' => $order->id,
                    'provider' => (string) ($checkout['provider'] ?? 'null'),
                    'external_id' => isset($checkout['transaction_id']) ? (string) $checkout['transaction_id'] : null,
                    'amount' => $order->total_price,
                    'status' => $redirectUrl !== '' ? 'pending' : 'completed',
                    'meta' => $checkout,
                ]);

                if ($redirectUrl !== '') {
                    $paymentRedirect = $redirectUrl;
                } else {
                    $flash = 'Siparişiniz alındı. Online ödeme (demo sağlayıcı) onaylandı.';
                }
            }

            return ['order' => $order, 'payment_redirect' => $paymentRedirect, 'flash' => $flash];
        });

        session()->forget($this->cartKey($firm->id));

        $order = $bundle['order'];
        if (is_string($bundle['payment_redirect'] ?? null) && $bundle['payment_redirect'] !== '') {
            return redirect()->away($bundle['payment_redirect']);
        }

        return redirect()->route('shop.orders.show', $order)->with('status', $bundle['flash']);
    }

    public function orders(FirmContext $firmContext): View
    {
        $firm = $firmContext->require();
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        return view('shop.orders', [
            'title' => 'Siparişlerim',
            'firm' => $firm,
            'orders' => Order::query()->where('firm_id', $firm->id)->where('user_id', $user->id)->latest()->paginate(20),
        ]);
    }

    public function orderShow(FirmContext $firmContext, Order $order): View
    {
        $firm = $firmContext->require();
        if ((int) $order->firm_id !== (int) $firm->id || (int) $order->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $order->load(['items.product', 'restaurant', 'statusHistories', 'review']);

        return view('shop.order-show', [
            'title' => 'Sipariş #'.$order->id,
            'firm' => $firm,
            'order' => $order,
        ]);
    }

    public function storeReview(Request $request, FirmContext $firmContext, Order $order): RedirectResponse
    {
        $firm = $firmContext->require();
        if ((int) $order->firm_id !== (int) $firm->id || (int) $order->user_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($order->status !== OrderStatus::Delivered->value) {
            return back()->withErrors(['rating' => 'Yalnızca teslim edilmiş siparişlere yorum yazılabilir.']);
        }

        if ($order->review()->exists()) {
            return back()->withErrors(['rating' => 'Bu sipariş için zaten bir yorum var.']);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::query()->create([
            'order_id' => $order->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return back()->with('status', 'Yorumunuz kaydedildi, teşekkürler.');
    }

    public function profile(FirmContext $firmContext): View
    {
        $firm = $firmContext->require();

        return view('shop.profile', [
            'title' => 'Profilim',
            'firm' => $firm,
            'user' => Auth::user(),
        ]);
    }

    public function storeAddress(Request $request, FirmContext $firmContext): RedirectResponse
    {
        $firm = $firmContext->require();
        $user = Auth::user();
        if ($user === null || $user->firm_id !== $firm->id) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        if (($data['latitude'] ?? null) === null && ($data['longitude'] ?? null) === null) {
            $coords = $this->nominatimGeocoder->geocodeFreeText((string) $data['address']);
            if ($coords !== null) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }
        }

        $user->addresses()->create($data);

        return back()->with('status', 'Adres kaydedildi.');
    }

    private function cartKey(int $firmId): string
    {
        return 'cart.'.$firmId;
    }

    private function firstRestaurantFromCart(Firm $firm): ?Restaurant
    {
        ['items' => $items] = $this->summarizeCartSession($firm);
        if ($items === []) {
            return null;
        }

        return $items[0]['product']->restaurant;
    }

    /**
     * @return array{items: list<array{product: Product, qty: int, line: float}>, subtotal: float}
     */
    private function summarizeCartSession(Firm $firm): array
    {
        $cart = session()->get($this->cartKey($firm->id), []);
        $items = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $p = Product::query()->with('restaurant')->find($productId);
            if ($p && (int) $p->restaurant->firm_id === (int) $firm->id) {
                $line = $p->effectiveUnitPrice() * $qty;
                $subtotal += $line;
                $items[] = ['product' => $p, 'qty' => $qty, 'line' => $line];
            }
        }

        return ['items' => $items, 'subtotal' => $subtotal];
    }
}
