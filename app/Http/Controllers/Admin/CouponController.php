<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Coupon;
use App\Modules\Firms\Models\Firm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Coupon::class);

        $query = Coupon::query()->with('firm');

        if ($request->filled('firm_id')) {
            $query->where('firm_id', $request->integer('firm_id'));
        }

        return view('admin.coupons.index', [
            'title' => 'Kuponlar',
            'coupons' => $query->latest()->paginate(40)->appends($request->query()),
            'firms' => Firm::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['firm_id']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create', [
            'title' => 'Yeni kupon',
            'firms' => Firm::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $data = $request->validate([
            'firm_id' => ['required', 'exists:firms,id'],
            'code' => [
                'required', 'string', 'max:64',
                Rule::unique('coupons')->where(fn ($q) => $q->where('firm_id', $request->integer('firm_id'))),
            ],
            'discount' => ['required', 'numeric', 'min:0'],
            'usage_limit' => ['required', 'integer', 'min:1'],
            'expire_date' => ['required', 'date'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['used_count'] = 0;

        Coupon::query()->create($data);

        return redirect()->route('admin.coupons.index')->with('status', 'Kupon oluşturuldu.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.edit', [
            'title' => 'Kupon düzenle',
            'coupon' => $coupon,
            'firms' => Firm::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $data = $request->validate([
            'firm_id' => ['required', 'exists:firms,id'],
            'code' => [
                'required', 'string', 'max:64',
                Rule::unique('coupons')->where(fn ($q) => $q->where('firm_id', $request->integer('firm_id')))->ignore($coupon->id),
            ],
            'discount' => ['required', 'numeric', 'min:0'],
            'usage_limit' => ['required', 'integer', 'min:1'],
            'expire_date' => ['required', 'date'],
        ]);

        $data['code'] = strtoupper($data['code']);

        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('status', 'Kupon güncellendi.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('status', 'Kupon silindi.');
    }
}
