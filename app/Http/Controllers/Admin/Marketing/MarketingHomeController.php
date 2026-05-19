<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingPage;
use App\Models\Marketing\MarketingPageVersion;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

class MarketingHomeController extends Controller
{
    public function edit(MarketingBootstrap $bootstrap): View
    {
        $site = $bootstrap->ensureSiteExists();
        $page = MarketingPage::query()
            ->where('marketing_site_id', $site->id)
            ->where('slug', 'home')
            ->firstOrFail();

        $latest = $page->versions()->orderByDesc('id')->first();
        $blocks = $latest?->blocks_json ?? MarketingBootstrap::defaultBlocks();

        return view('admin.marketing.home', [
            'title' => 'Ana sayfa blokları',
            'site' => $site,
            'page' => $page,
            'blocks' => $blocks,
            'blocksJson' => json_encode($blocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'previewUrl' => route('marketing.home', ['preview' => 1]),
        ]);
    }

    public function update(Request $request, MarketingBootstrap $bootstrap): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        $page = MarketingPage::query()
            ->where('marketing_site_id', $site->id)
            ->where('slug', 'home')
            ->firstOrFail();

        $raw = $request->input('blocks_json', '');
        try {
            $blocks = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['blocks_json' => 'Geçerli JSON girin.']);
        }

        if (! is_array($blocks)) {
            throw ValidationException::withMessages(['blocks_json' => 'Bloklar bir dizi olmalıdır.']);
        }

        $blocks = \App\Support\HtmlSanitizer::sanitizeMarketingBlocks($blocks);

        $nextVersion = (int) ($page->versions()->max('version') ?? 0) + 1;

        MarketingPageVersion::query()->create([
            'marketing_page_id' => $page->id,
            'version' => $nextVersion,
            'blocks_json' => $blocks,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.marketing.home.edit')->with('ok', 'Taslak sürüm kaydedildi.');
    }

    public function publish(MarketingBootstrap $bootstrap): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        $page = MarketingPage::query()
            ->where('marketing_site_id', $site->id)
            ->where('slug', 'home')
            ->firstOrFail();

        $latest = $page->versions()->orderByDesc('id')->first();
        if ($latest === null) {
            return redirect()->route('admin.marketing.home.edit')->with('error', 'Önce bir taslak kaydedin.');
        }

        $page->update([
            'published_version_id' => $latest->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->route('admin.marketing.home.edit')->with('ok', 'Yayınlandı. Ziyaretçiler son kaydedilen sürümü görür.');
    }
}
