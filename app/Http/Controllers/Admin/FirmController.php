<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use App\Modules\Firms\Services\FirmCreditService;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FirmController extends Controller
{
    public function __construct(private readonly FirmCreditService $firmCreditService) {}

    public function index(Request $request): View
    {
        $query = Firm::query();

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)->orWhere('domain', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('admin.firms.index', [
            'title' => 'Kurye şirketleri',
            'firms' => $query->latest('id')->paginate(20)->appends($request->query()),
            'filters' => $request->only(['q', 'status']),
            'globalCreditsPerOrder' => $this->firmCreditService->globalCreditsPerOrder(),
        ]);
    }

    public function create(): View
    {
        return view('admin.firms.create', [
            'title' => 'Yeni kurye şirketi',
            'globalCreditsPerOrder' => $this->firmCreditService->globalCreditsPerOrder(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'domain' => ['required', 'string', 'max:190', 'unique:firms,domain'],
            'credits_per_order_override' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'admin_name' => ['required', 'string', 'max:190'],
            'admin_email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $firm = Firm::query()->create([
            'name' => $data['name'],
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'domain' => strtolower($data['domain']),
            'credits_per_order_override' => $data['credits_per_order_override'] ?? null,
            'status' => 'active',
        ]);

        $roleId = Role::query()->where('name', Role::FIRM_ADMIN)->value('id');
        User::query()->create([
            'firm_id' => $firm->id,
            'role_id' => $roleId,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'status' => 'active',
        ]);

        return redirect()->route('admin.firms.index')->with('status', 'Kurye şirketi ve yönetici hesabı oluşturuldu.');
    }

    public function show(Firm $firm): View
    {
        $restaurantCount = Restaurant::query()->where('firm_id', $firm->id)->count();

        return view('admin.firms.show', [
            'title' => $firm->name,
            'firm' => $firm,
            'restaurantCount' => $restaurantCount,
        ]);
    }

    public function edit(Firm $firm): View
    {
        return view('admin.firms.edit', [
            'title' => 'Kurye şirketi düzenle',
            'firm' => $firm,
            'globalCreditsPerOrder' => $this->firmCreditService->globalCreditsPerOrder(),
        ]);
    }

    public function update(Request $request, Firm $firm): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'domain' => ['required', 'string', 'max:190', 'unique:firms,domain,'.$firm->id],
            'credits_per_order_override' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', 'in:active,inactive'],
            'logo' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
        ]);

        $logoValue = $data['logo'] ?? $firm->logo;
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('firm-logos', 'public');
            $logoValue = Storage::url($path);
        }

        $firm->update([
            'name' => $data['name'],
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'domain' => strtolower($data['domain']),
            'credits_per_order_override' => $data['credits_per_order_override'] ?? null,
            'status' => $data['status'],
            'logo' => $logoValue,
        ]);

        return redirect()->route('admin.firms.index')->with('status', 'Kurye şirketi güncellendi.');
    }
}
