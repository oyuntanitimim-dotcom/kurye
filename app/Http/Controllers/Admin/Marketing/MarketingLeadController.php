<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingContactLead;
use Illuminate\Contracts\View\View;

class MarketingLeadController extends Controller
{
    public function index(): View
    {
        $leads = MarketingContactLead::query()->latest()->paginate(25);

        return view('admin.marketing.leads.index', [
            'title' => 'İletişim talepleri',
            'leads' => $leads,
        ]);
    }

    public function show(MarketingContactLead $lead): View
    {
        if (! $lead->read) {
            $lead->update(['read' => true]);
        }

        return view('admin.marketing.leads.show', [
            'title' => 'Talep #'.$lead->id,
            'lead' => $lead,
        ]);
    }
}
