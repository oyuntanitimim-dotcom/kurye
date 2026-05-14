<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Product;
use App\Modules\Restaurants\Models\ProductImage;
use App\Modules\Restaurants\Models\RestaurantCategory;
use App\Enums\OrderSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $rid = Auth::user()->restaurant_id;

        $perPage = (int) $request->input('per_page', 30);
        if (! in_array($perPage, [10, 25, 30, 50], true)) {
            $perPage = 30;
        }

        $query = Product::query()->where('restaurant_id', $rid)->with(['category', 'images']);

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->toString().'%';
            $query->where('name', 'like', $needle);
        }

        $phoneOrderCount = Order::query()
            ->where('restaurant_id', $rid)
            ->where('source', OrderSource::Phone->value)
            ->count();

        return view('restaurant.products', [
            'title' => 'Ürünler',
            'products' => $query->latest()->paginate($perPage)->appends($request->query()),
            'filters' => $request->only(['q', 'per_page']),
            'categories' => RestaurantCategory::query()->where('restaurant_id', $rid)->orderBy('sort_order')->orderBy('name')->get(),
            'phoneOrderCount' => $phoneOrderCount,
            'productCount' => Product::query()->where('restaurant_id', $rid)->count(),
        ]);
    }

    public function create(): View
    {
        $rid = Auth::user()->restaurant_id;

        return view('restaurant.product-form', [
            'title' => 'Yeni Ürün',
            'product' => new Product(['restaurant_id' => $rid, 'status' => 'active', 'stock' => 0]),
            'categories' => RestaurantCategory::query()->where('restaurant_id', $rid)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rid = Auth::user()->restaurant_id;
        $data = $this->validatedProductPayload($request, true);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::query()->create([
            ...$data,
            'restaurant_id' => $rid,
        ]);

        $this->syncNewImages($request, $product);

        return redirect()->route('restaurant.products.index')->with('status', 'Ürün eklendi.');
    }

    public function edit(Product $product): View
    {
        $this->authorizeProduct($product);
        $rid = Auth::user()->restaurant_id;

        return view('restaurant.product-form', [
            'title' => 'Ürün Düzenle',
            'product' => $product->load('images'),
            'categories' => RestaurantCategory::query()->where('restaurant_id', $rid)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);
        $data = $this->validatedProductPayload($request, true);

        if ($request->hasFile('image')) {
            if ($product->image && ! str_starts_with((string) $product->image, 'http')) {
                Storage::disk('public')->delete((string) $product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        $this->syncNewImages($request, $product);

        return redirect()->route('restaurant.products.index')->with('status', 'Ürün güncellendi.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorizeProduct($product);
        if ((int) $image->product_id !== (int) $product->id) {
            abort(404);
        }

        if ($image->path && ! str_starts_with((string) $image->path, 'http')) {
            Storage::disk('public')->delete((string) $image->path);
        }
        $wasPrimary = (bool) $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->first();
            if ($next) {
                $product->images()->update(['is_primary' => false]);
                $next->update(['is_primary' => true]);
            }
        }

        return back()->with('status', 'Görsel silindi.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $rid = Auth::user()->restaurant_id;
        $rows = $request->input('products', []);
        if (! is_array($rows) || $rows === []) {
            return back()->with('error', 'Kaydedilecek satır yok.');
        }

        $updated = 0;
        foreach ($rows as $id => $row) {
            if (! is_array($row)) {
                continue;
            }
            $product = Product::query()->where('restaurant_id', $rid)->whereKey((int) $id)->first();
            if ($product === null) {
                continue;
            }

            if (($row['category_id'] ?? '') === '') {
                $row['category_id'] = null;
            }
            foreach (['discounted_price', 'prep_time_minutes'] as $k) {
                if (array_key_exists($k, $row) && $row[$k] === '') {
                    $row[$k] = null;
                }
            }

            $validated = validator($row, $this->bulkRowRules($rid))->validate();

            $disc = $validated['discounted_price'] ?? null;
            if ($disc === '' || $disc === null) {
                $disc = null;
            }
            $prep = $validated['prep_time_minutes'] ?? null;
            if ($prep === '' || $prep === null) {
                $prep = null;
            } else {
                $prep = (int) $prep;
            }

            $product->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'discounted_price' => $disc !== null ? (string) $disc : null,
                'category_id' => $validated['category_id'] ?? null,
                'stock' => $validated['stock'],
                'prep_time_minutes' => $prep,
            ]);
            $updated++;
        }

        return back()->with('status', $updated.' ürün güncellendi.');
    }

    public function copyAll(): RedirectResponse
    {
        $rid = Auth::user()->restaurant_id;
        $sourceProducts = Product::query()->where('restaurant_id', $rid)->get();
        $n = 0;
        foreach ($sourceProducts as $p) {
            $newImage = null;
            if ($p->image && ! str_starts_with((string) $p->image, 'http')) {
                $src = (string) $p->image;
                if (Storage::disk('public')->exists($src)) {
                    $newPath = 'products/cp_'.uniqid('', true).'_'.basename($src);
                    Storage::disk('public')->copy($src, $newPath);
                    $newImage = $newPath;
                }
            }

            Product::query()->create([
                'restaurant_id' => $rid,
                'category_id' => $p->category_id,
                'name' => $p->name.' (kopya)',
                'description' => $p->description,
                'price' => $p->price,
                'discounted_price' => $p->discounted_price,
                'image' => $newImage,
                'status' => $p->status,
                'stock' => $p->stock,
                'prep_time_minutes' => $p->prep_time_minutes,
            ]);
            $n++;
        }

        return back()->with('status', $n.' ürün kopyalandı.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $rid = Auth::user()->restaurant_id;
        $deleted = 0;
        $skipped = 0;

        foreach (Product::query()->where('restaurant_id', $rid)->get() as $p) {
            if ($p->orderItems()->exists()) {
                $skipped++;

                continue;
            }
            if ($p->image && ! str_starts_with((string) $p->image, 'http')) {
                Storage::disk('public')->delete((string) $p->image);
            }
            $p->delete();
            $deleted++;
        }

        $msg = $deleted.' ürün silindi.';
        if ($skipped > 0) {
            $msg .= ' '.$skipped.' ürün sipariş geçmişi nedeniyle atlandı.';
        }

        return back()->with('status', $msg);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeProduct($product);
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Bu ürün siparişlerde kullanıldığı için silinemez.');
        }
        if ($product->image && ! str_starts_with((string) $product->image, 'http')) {
            Storage::disk('public')->delete((string) $product->image);
        }
        $product->delete();

        return back()->with('status', 'Ürün silindi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProductPayload(Request $request, bool $withImageRule = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discounted_price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer'],
            'stock' => ['required', 'integer', 'min:0'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'status' => ['required', 'in:active,inactive'],
        ];
        if ($withImageRule) {
            $rules['image'] = ['nullable', 'image', 'max:4096'];
            $rules['images'] = ['nullable', 'array'];
            $rules['images.*'] = ['image', 'max:4096'];
        }

        $data = $request->validate($rules);
        unset($data['image']);

        if (! array_key_exists('discounted_price', $data) || $data['discounted_price'] === '' || $data['discounted_price'] === null) {
            $data['discounted_price'] = null;
        }
        if (! array_key_exists('prep_time_minutes', $data) || $data['prep_time_minutes'] === '' || $data['prep_time_minutes'] === null) {
            $data['prep_time_minutes'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function bulkRowRules(int $restaurantId): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'price' => ['required', 'numeric', 'min:0'],
            'discounted_price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('restaurant_categories', 'id')->where(
                    fn ($q) => $q->where('restaurant_id', $restaurantId)
                ),
            ],
            'stock' => ['required', 'integer', 'min:0'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }

    private function authorizeProduct(Product $product): void
    {
        if ((int) $product->restaurant_id !== (int) Auth::user()->restaurant_id) {
            abort(403);
        }
    }

    private function syncNewImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }
        $files = $request->file('images');
        if (! is_array($files)) {
            return;
        }

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        foreach ($files as $file) {
            if ($file === null) {
                continue;
            }
            $path = $file->store('products', 'public');
            $img = $product->images()->create([
                'path' => $path,
                'is_primary' => false,
                'sort_order' => 0,
            ]);
            if (! $hasPrimary) {
                $product->images()->update(['is_primary' => false]);
                $img->update(['is_primary' => true]);
                $hasPrimary = true;
            }
        }
    }
}
