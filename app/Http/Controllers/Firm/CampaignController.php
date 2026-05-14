<?php

namespace App\Http\Controllers\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        $firmId = Auth::user()->firm_id;

        return view('firm.campaigns.index', [
            'title' => 'Kampanyalar',
            'campaigns' => Campaign::query()
                ->where('firm_id', $firmId)
                ->latest()
                ->paginate(40),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        return view('firm.campaigns.create', ['title' => 'Yeni kampanya']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $firmId = Auth::user()->firm_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_order' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $data['firm_id'] = $firmId;

        Campaign::query()->create($data);

        return redirect()->route('firm.campaigns.index')->with('status', 'Kampanya oluşturuldu.');
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        return view('firm.campaigns.edit', [
            'title' => 'Kampanya düzenle',
            'campaign' => $campaign,
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_order' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $campaign->update($data);

        return redirect()->route('firm.campaigns.index')->with('status', 'Kampanya güncellendi.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('firm.campaigns.index')->with('status', 'Kampanya silindi.');
    }
}
