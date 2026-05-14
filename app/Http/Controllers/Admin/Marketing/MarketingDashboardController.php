<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Contracts\View\View;

class MarketingDashboardController extends Controller
{
    public function index(MarketingBootstrap $bootstrap): View
    {
        $site = $bootstrap->ensureSiteExists();

        return view('admin.marketing.index', [
            'title' => 'Tanıtım sitesi',
            'site' => $site,
        ]);
    }
}
