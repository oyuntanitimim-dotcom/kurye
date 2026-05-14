<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Services\Geocoding\NominatimGeocoder;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\Address;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private readonly NominatimGeocoder $nominatimGeocoder,
    ) {}

    private const SORT_FIELDS = [
        'last_order_at' => 'last_order_at',
        'order_count' => 'order_count',
        'total_spent' => 'total_spent',
        'name' => 'name',
    ];

    public function index(Request $request): View
    {
        $rid = (int) Auth::user()->restaurant_id;

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [10, 25, 30, 50], true)) {
            $perPage = 25;
        }

        $type = (string) $request->input('type', 'all'); // all|registered|guest
        if (! in_array($type, ['all', 'registered', 'guest'], true)) {
            $type = 'all';
        }
        $minOrders = (int) $request->input('min_orders', 0);
        $minOrders = max(0, min(999999, $minOrders));

        $from = $request->input('from'); // YYYY-MM-DD
        $to = $request->input('to');     // YYYY-MM-DD

        $sort = (string) $request->input('sort', 'last_order_at');
        if (! array_key_exists($sort, self::SORT_FIELDS)) {
            $sort = 'last_order_at';
        }
        $dir = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $registered = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.restaurant_id', $rid)
            ->whereNotNull('o.user_id')
            ->selectRaw('CONCAT("u:", o.user_id) as customer_key')
            ->selectRaw('o.user_id as user_id')
            ->selectRaw('u.name as name')
            ->selectRaw('u.phone as phone')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(o.total_price) as total_spent')
            ->selectRaw('MAX(o.created_at) as last_order_at')
            ->groupBy('o.user_id', 'u.name', 'u.phone');

        $guests = DB::table('orders as o')
            ->where('o.restaurant_id', $rid)
            ->whereNull('o.user_id')
            ->whereNotNull('o.customer_phone')
            ->where('o.customer_phone', '!=', '')
            ->selectRaw('CONCAT("p:", o.customer_phone) as customer_key')
            ->selectRaw('NULL as user_id')
            ->selectRaw('MAX(NULLIF(o.customer_name, "")) as name')
            ->selectRaw('o.customer_phone as phone')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(o.total_price) as total_spent')
            ->selectRaw('MAX(o.created_at) as last_order_at')
            ->groupBy('o.customer_phone');

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->toString().'%';

            $registered->where(function ($q) use ($needle): void {
                $q->where('u.name', 'like', $needle)->orWhere('u.phone', 'like', $needle);
            });

            $guests->where(function ($q) use ($needle): void {
                $q->where('o.customer_name', 'like', $needle)->orWhere('o.customer_phone', 'like', $needle);
            });
        }

        if ($from) {
            $registered->whereDate('o.created_at', '>=', $from);
            $guests->whereDate('o.created_at', '>=', $from);
        }
        if ($to) {
            $registered->whereDate('o.created_at', '<=', $to);
            $guests->whereDate('o.created_at', '<=', $to);
        }

        if ($type === 'registered') {
            $union = $registered;
        } elseif ($type === 'guest') {
            $union = $guests;
        } else {
            $union = $registered->unionAll($guests);
        }

        $customers = DB::query()
            ->fromSub($union, 'c')
            ->when($minOrders > 0, fn ($q) => $q->where('order_count', '>=', $minOrders))
            ->orderBy(self::SORT_FIELDS[$sort], $dir)
            ->orderByDesc('last_order_at')
            ->paginate($perPage)
            ->appends($request->query());

        $pageUserIds = collect($customers->items())->pluck('user_id')->filter()->unique()->values();
        $pagePhones = collect($customers->items())->whereNull('user_id')->pluck('phone')->filter()->unique()->values();

        $latestOrders = Order::query()
            ->where('restaurant_id', $rid)
            ->where(function ($q) use ($pageUserIds, $pagePhones): void {
                if ($pageUserIds->isNotEmpty()) {
                    $q->whereIn('user_id', $pageUserIds->all());
                }
                if ($pagePhones->isNotEmpty()) {
                    $q->orWhere(function ($qq) use ($pagePhones): void {
                        $qq->whereNull('user_id')->whereIn('customer_phone', $pagePhones->all());
                    });
                }
            })
            ->with('deliveryAddress')
            ->latest()
            ->get(['id', 'user_id', 'customer_phone', 'delivery_address_id']);

        $neighborhoodByKey = [];
        foreach ($latestOrders as $o) {
            $key = $o->user_id ? 'u:'.$o->user_id : 'p:'.($o->customer_phone ?? '');
            if ($key === 'p:' || isset($neighborhoodByKey[$key])) {
                continue;
            }
            $addr = (string) ($o->deliveryAddress?->address ?? '');
            $neighborhoodByKey[$key] = $this->guessNeighborhood($addr);
        }

        $ridInt = (int) $rid;
        $customerRoleId = (int) (Role::query()->where('name', Role::CUSTOMER)->value('id') ?? 0);
        $deletableIds = $pageUserIds->isEmpty()
            ? collect()
            : User::query()
                ->whereIn('id', $pageUserIds->all())
                ->where('restaurant_id', $ridInt)
                ->where('role_id', $customerRoleId)
                ->pluck('id')
                ->flip();

        $summary = DB::query()
            ->fromSub($union, 'c')
            ->when($minOrders > 0, fn ($q) => $q->where('order_count', '>=', $minOrders))
            ->selectRaw('COUNT(*) as customer_count')
            ->selectRaw('SUM(order_count) as order_count')
            ->selectRaw('SUM(total_spent) as total_spent')
            ->first();

        return view('restaurant.customers', [
            'title' => 'Müşteriler',
            'customers' => $customers,
            'neighborhoodByKey' => $neighborhoodByKey,
            'deletableUserIds' => $deletableIds,
            'filters' => $request->only(['q', 'per_page', 'type', 'min_orders', 'from', 'to', 'sort', 'dir']),
            'summary' => [
                'customer_count' => (int) ($summary->customer_count ?? 0),
                'order_count' => (int) ($summary->order_count ?? 0),
                'total_spent' => (float) ($summary->total_spent ?? 0),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rid = (int) Auth::user()->restaurant_id;

        $type = (string) $request->input('type', 'all'); // all|registered|guest
        if (! in_array($type, ['all', 'registered', 'guest'], true)) {
            $type = 'all';
        }
        $minOrders = (int) $request->input('min_orders', 0);
        $minOrders = max(0, min(999999, $minOrders));

        $from = $request->input('from'); // YYYY-MM-DD
        $to = $request->input('to');     // YYYY-MM-DD

        $sort = (string) $request->input('sort', 'last_order_at');
        if (! array_key_exists($sort, self::SORT_FIELDS)) {
            $sort = 'last_order_at';
        }
        $dir = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $registered = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.restaurant_id', $rid)
            ->whereNotNull('o.user_id')
            ->selectRaw('CONCAT("u:", o.user_id) as customer_key')
            ->selectRaw('o.user_id as user_id')
            ->selectRaw('u.name as name')
            ->selectRaw('u.phone as phone')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(o.total_price) as total_spent')
            ->selectRaw('MAX(o.created_at) as last_order_at')
            ->groupBy('o.user_id', 'u.name', 'u.phone');

        $guests = DB::table('orders as o')
            ->where('o.restaurant_id', $rid)
            ->whereNull('o.user_id')
            ->whereNotNull('o.customer_phone')
            ->where('o.customer_phone', '!=', '')
            ->selectRaw('CONCAT("p:", o.customer_phone) as customer_key')
            ->selectRaw('NULL as user_id')
            ->selectRaw('MAX(NULLIF(o.customer_name, "")) as name')
            ->selectRaw('o.customer_phone as phone')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(o.total_price) as total_spent')
            ->selectRaw('MAX(o.created_at) as last_order_at')
            ->groupBy('o.customer_phone');

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->toString().'%';
            $registered->where(function ($q) use ($needle): void {
                $q->where('u.name', 'like', $needle)->orWhere('u.phone', 'like', $needle);
            });
            $guests->where(function ($q) use ($needle): void {
                $q->where('o.customer_name', 'like', $needle)->orWhere('o.customer_phone', 'like', $needle);
            });
        }

        if ($from) {
            $registered->whereDate('o.created_at', '>=', $from);
            $guests->whereDate('o.created_at', '>=', $from);
        }
        if ($to) {
            $registered->whereDate('o.created_at', '<=', $to);
            $guests->whereDate('o.created_at', '<=', $to);
        }

        if ($type === 'registered') {
            $union = $registered;
        } elseif ($type === 'guest') {
            $union = $guests;
        } else {
            $union = $registered->unionAll($guests);
        }

        $query = DB::query()
            ->fromSub($union, 'c')
            ->when($minOrders > 0, fn ($q) => $q->where('order_count', '>=', $minOrders))
            ->orderBy(self::SORT_FIELDS[$sort], $dir)
            ->orderByDesc('last_order_at');

        $filename = 'musteriler_'.$rid.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }

            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Müşteri', 'Telefon', 'Sipariş Sayısı', 'Toplam', 'Son Sipariş', 'Tür'], ';');

            $query->orderBy('customer_key')->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $c) {
                    $type = $c->user_id ? 'Kayıtlı' : 'Siparişten';
                    fputcsv($out, [
                        (string) ($c->name ?? '—'),
                        (string) ($c->phone ?? '—'),
                        (int) ($c->order_count ?? 0),
                        number_format((float) ($c->total_spent ?? 0), 2, ',', '.'),
                        $c->last_order_at ? (string) $c->last_order_at : '',
                        $type,
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rid = (int) Auth::user()->restaurant_id;
        $firmId = (int) Auth::user()->firm_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $customerRoleId = (int) (Role::query()->where('name', Role::CUSTOMER)->value('id') ?? 0);
        if ($customerRoleId <= 0) {
            return back()->with('error', 'Customer rolü bulunamadı.');
        }

        $digits = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        $local = $digits !== '' ? 'cust_'.$digits : 'cust_'.Str::lower(Str::random(10));
        $email = $local.'_'.time().'@local.test';

        $user = User::query()->create([
            'firm_id' => $firmId ?: null,
            'role_id' => $customerRoleId,
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => Str::random(32),
            'status' => 'active',
        ]);

        if (($data['address'] ?? '') !== '') {
            $addrText = (string) $data['address'];
            $coords = $this->nominatimGeocoder->geocodeFreeText($addrText);
            $row = [
                'user_id' => $user->id,
                'title' => 'Varsayılan',
                'address' => $addrText,
            ];
            if ($coords !== null) {
                $row['latitude'] = $coords['latitude'];
                $row['longitude'] = $coords['longitude'];
            }
            Address::query()->create($row);
        }

        return back()->with('status', 'Müşteri eklendi.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $rid = (int) Auth::user()->restaurant_id;
        if ((int) $user->restaurant_id !== $rid) {
            abort(403);
        }
        if ($user->role?->name !== Role::CUSTOMER) {
            abort(403);
        }
        if (Order::query()->where('restaurant_id', $rid)->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Bu müşterinin siparişi olduğu için silinemez.');
        }

        $user->delete();

        return back()->with('status', 'Müşteri silindi.');
    }

    public function destroyAll(): RedirectResponse
    {
        $rid = (int) Auth::user()->restaurant_id;
        $customerRoleId = (int) (Role::query()->where('name', Role::CUSTOMER)->value('id') ?? 0);

        $deleted = 0;
        $skipped = 0;

        $users = User::query()
            ->where('restaurant_id', $rid)
            ->where('role_id', $customerRoleId)
            ->get();

        foreach ($users as $u) {
            if (Order::query()->where('restaurant_id', $rid)->where('user_id', $u->id)->exists()) {
                $skipped++;
                continue;
            }
            $u->delete();
            $deleted++;
        }

        $msg = $deleted.' müşteri silindi.';
        if ($skipped > 0) {
            $msg .= ' '.$skipped.' müşteri sipariş geçmişi nedeniyle atlandı.';
        }

        return back()->with('status', $msg);
    }

    private function guessNeighborhood(string $address): string
    {
        $a = trim($address);
        if ($a === '') {
            return '—';
        }

        // naive heuristic: capture "... Mah" or "... Mahallesi" fragments
        if (preg_match('/([\\p{L}0-9\\s\\-]{2,60})\\s+(Mah\\.?|Mahallesi)\\b/iu', $a, $m)) {
            $name = trim((string) ($m[1] ?? ''));
            if ($name !== '') {
                return $name.' Mah.';
            }
        }

        return '—';
    }
}

