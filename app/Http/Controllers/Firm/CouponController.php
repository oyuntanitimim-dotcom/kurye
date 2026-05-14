<?php

namespace App\Http\Controllers\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Coupon::class);

        $firmId = Auth::user()->firm_id;

        return view('firm.coupons.index', [
            'title' => 'Kuponlar',
            'coupons' => Coupon::query()
                ->where('firm_id', $firmId)
                ->latest()
                ->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('firm.coupons.create', ['title' => 'Yeni kupon']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $firmId = Auth::user()->firm_id;

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:64',
                Rule::unique('coupons')->where(fn ($q) => $q->where('firm_id', $firmId)),
            ],
            'discount' => ['required', 'numeric', 'min:0'],
            'usage_limit' => ['required', 'integer', 'min:1'],
            'expire_date' => ['required', 'date'],
        ]);

        $data['firm_id'] = $firmId;
        $data['code'] = strtoupper($data['code']);
        $data['used_count'] = 0;

        Coupon::query()->create($data);

        return redirect()->route('firm.coupons.index')->with('status', 'Kupon oluşturuldu.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('firm.coupons.edit', [
            'title' => 'Kupon düzenle',
            'coupon' => $coupon,
        ]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $firmId = Auth::user()->firm_id;

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:64',
                Rule::unique('coupons')->where(fn ($q) => $q->where('firm_id', $firmId))->ignore($coupon->id),
            ],
            'discount' => ['required', 'numeric', 'min:0'],
            'usage_limit' => ['required', 'integer', 'min:1'],
            'expire_date' => ['required', 'date'],
        ]);

        $data['code'] = strtoupper($data['code']);

        $coupon->update($data);

        return redirect()->route('firm.coupons.index')->with('status', 'Kupon güncellendi.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()->route('firm.coupons.index')->with('status', 'Kupon silindi.');
    }
}
