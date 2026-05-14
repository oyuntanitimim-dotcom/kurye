<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Faz‑2 (gelişmiş builder, çoklu dil, revizyon UI) ayrı PR kapsamında planlandı.
 */
class MarketingPhase2Controller extends Controller
{
    public function show(): View
    {
        return view('admin.marketing.phase2', [
            'title' => 'Faz 2 — Gelişmiş builder',
        ]);
    }
}
