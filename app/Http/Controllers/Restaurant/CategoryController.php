<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\RestaurantCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    private const SORT_FIELDS = [
        'sort_order' => 'sort_order',
        'name' => 'name',
        'products_count' => 'products_count',
        'created_at' => 'created_at',
    ];

    public function index(Request $request): View
    {
        $rid = Auth::user()->restaurant_id;

        $perPage = (int) $request->input('per_page', 30);
        if (! in_array($perPage, [10, 25, 30, 50], true)) {
            $perPage = 30;
        }

        $hasProducts = (string) $request->input('has_products', 'all'); // all|with|without
        if (! in_array($hasProducts, ['all', 'with', 'without'], true)) {
            $hasProducts = 'all';
        }
        $minProducts = (int) $request->input('min_products', 0);
        $minProducts = max(0, min(999999, $minProducts));

        $from = $request->input('from'); // YYYY-MM-DD
        $to = $request->input('to');     // YYYY-MM-DD

        $sort = (string) $request->input('sort', 'sort_order');
        if (! array_key_exists($sort, self::SORT_FIELDS)) {
            $sort = 'sort_order';
        }
        $dir = strtolower((string) $request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = RestaurantCategory::query()
            ->where('restaurant_id', $rid)
            ->withCount('products')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($hasProducts === 'with', fn ($q) => $q->having('products_count', '>', 0))
            ->when($hasProducts === 'without', fn ($q) => $q->having('products_count', '=', 0))
            ->when($minProducts > 0, fn ($q) => $q->having('products_count', '>=', $minProducts))
            ->orderBy(self::SORT_FIELDS[$sort], $dir)
            ->orderBy('name');

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->toString().'%';
            $query->where('name', 'like', $needle);
        }

        $summaryBase = RestaurantCategory::query()
            ->where('restaurant_id', $rid)
            ->withCount('products')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim()->toString().'%'))
            ->when($hasProducts === 'with', fn ($q) => $q->having('products_count', '>', 0))
            ->when($hasProducts === 'without', fn ($q) => $q->having('products_count', '=', 0))
            ->when($minProducts > 0, fn ($q) => $q->having('products_count', '>=', $minProducts));

        $summary = (clone $summaryBase)
            ->get()
            ->reduce(fn ($carry, $c) => [
                'category_count' => $carry['category_count'] + 1,
                'products_in_categories' => $carry['products_in_categories'] + (int) ($c->products_count ?? 0),
            ], ['category_count' => 0, 'products_in_categories' => 0]);

        return view('restaurant.categories', [
            'title' => 'Kategoriler',
            'categories' => $query->paginate($perPage)->appends($request->query()),
            'filters' => $request->only(['q', 'per_page', 'has_products', 'min_products', 'from', 'to', 'sort', 'dir']),
            'summary' => [
                'category_count' => (int) ($summary['category_count'] ?? 0),
                'products_in_categories' => (int) ($summary['products_in_categories'] ?? 0),
                'product_count' => (int) Product::query()->where('restaurant_id', $rid)->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rid = (int) Auth::user()->restaurant_id;

        $hasProducts = (string) $request->input('has_products', 'all'); // all|with|without
        if (! in_array($hasProducts, ['all', 'with', 'without'], true)) {
            $hasProducts = 'all';
        }
        $minProducts = (int) $request->input('min_products', 0);
        $minProducts = max(0, min(999999, $minProducts));

        $from = $request->input('from'); // YYYY-MM-DD
        $to = $request->input('to');     // YYYY-MM-DD

        $sort = (string) $request->input('sort', 'sort_order');
        if (! array_key_exists($sort, self::SORT_FIELDS)) {
            $sort = 'sort_order';
        }
        $dir = strtolower((string) $request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = RestaurantCategory::query()
            ->where('restaurant_id', $rid)
            ->withCount('products')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim()->toString().'%'))
            ->when($hasProducts === 'with', fn ($q) => $q->having('products_count', '>', 0))
            ->when($hasProducts === 'without', fn ($q) => $q->having('products_count', '=', 0))
            ->when($minProducts > 0, fn ($q) => $q->having('products_count', '>=', $minProducts))
            ->orderBy(self::SORT_FIELDS[$sort], $dir)
            ->orderBy('name');

        $filename = 'kategoriler_'.$rid.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Kategori', 'Sıra', 'Ürün Sayısı', 'Oluşturma'], ';');

            $query->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $c) {
                    fputcsv($out, [
                        (string) $c->name,
                        (int) $c->sort_order,
                        (int) ($c->products_count ?? 0),
                        $c->created_at ? (string) $c->created_at : '',
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
        $rid = Auth::user()->restaurant_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        RestaurantCategory::query()->create([
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Kategori eklendi.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $rid = Auth::user()->restaurant_id;
        $rows = $request->input('categories', []);
        if (! is_array($rows) || $rows === []) {
            return back()->with('error', 'Kaydedilecek satır yok.');
        }

        $updated = 0;
        foreach ($rows as $id => $row) {
            if (! is_array($row)) {
                continue;
            }
            $category = RestaurantCategory::query()->where('restaurant_id', $rid)->whereKey((int) $id)->first();
            if ($category === null) {
                continue;
            }

            $validated = validator($row, [
                'name' => ['required', 'string', 'max:120'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            ])->validate();

            $category->update([
                'name' => $validated['name'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);
            $updated++;
        }

        return back()->with('status', $updated.' kategori güncellendi.');
    }

    public function destroyAll(): RedirectResponse
    {
        $rid = Auth::user()->restaurant_id;

        $deleted = 0;
        $skipped = 0;

        foreach (RestaurantCategory::query()->where('restaurant_id', $rid)->withCount('products')->get() as $c) {
            if ((int) $c->products_count > 0) {
                $skipped++;
                continue;
            }
            $c->delete();
            $deleted++;
        }

        $msg = $deleted.' kategori silindi.';
        if ($skipped > 0) {
            $msg .= ' '.$skipped.' kategori ürün bağlı olduğu için atlandı.';
        }

        return back()->with('status', $msg);
    }

    public function destroy(RestaurantCategory $category): RedirectResponse
    {
        if ((int) $category->restaurant_id !== (int) Auth::user()->restaurant_id) {
            abort(403);
        }
        if ($category->products()->exists()) {
            return back()->with('error', 'Bu kategori ürünlerde kullanıldığı için silinemez.');
        }
        $category->delete();

        return back()->with('status', 'Kategori silindi.');
    }
}
