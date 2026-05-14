<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Campaign;
use App\Modules\Firms\Models\Firm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Campaign::class);

        $query = Campaign::query()->with('firm');

        if ($request->filled('firm_id')) {
            $query->where('firm_id', $request->integer('firm_id'));
        }

        return view('admin.campaigns.index', [
            'title' => 'Kampanyalar',
            'campaigns' => $query->latest()->paginate(40)->appends($request->query()),
            'firms' => Firm::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['firm_id']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        return view('admin.campaigns.create', [
            'title' => 'Yeni kampanya',
            'firms' => Firm::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'firm_id' => ['required', 'exists:firms,id'],
            'name' => ['required', 'string', 'max:190'],
            'discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_order' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        Campaign::query()->create($data);

        return redirect()->route('admin.campaigns.index')->with('status', 'Kampanya oluşturuldu.');
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        return view('admin.campaigns.edit', [
            'title' => 'Kampanya düzenle',
            'campaign' => $campaign,
            'firms' => Firm::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'firm_id' => ['required', 'exists:firms,id'],
            'name' => ['required', 'string', 'max:190'],
            'discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_order' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $campaign->update($data);

        return redirect()->route('admin.campaigns.index')->with('status', 'Kampanya güncellendi.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('status', 'Kampanya silindi.');
    }
}
