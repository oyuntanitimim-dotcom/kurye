<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingFooterColumn;
use App\Models\Marketing\MarketingMenu;
use App\Models\Marketing\MarketingMenuItem;
use App\Models\Marketing\MarketingPage;
use App\Models\Marketing\MarketingSite;
use App\Models\Marketing\MarketingSlide;
use App\Modules\Users\Models\Role;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingSiteController extends Controller
{
    public function home(Request $request): View
    {
        $host = strtolower($request->getHost());
        $site = MarketingSite::query()
            ->where(function ($q) use ($host): void {
                $q->where('primary_domain', $host)
                    ->orWhere('primary_domain', 'www.'.$host);
            })
            ->first()
            ?? MarketingSite::query()->first();

        $menuItems = collect();
        if ($site !== null) {
            $menu = MarketingMenu::query()
                ->where('marketing_site_id', $site->id)
                ->where('key', 'header')
                ->first();
            if ($menu !== null) {
                $menuItems = MarketingMenuItem::query()
                    ->where('marketing_menu_id', $menu->id)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get();
            }
        }

        // Menü sırası: önce varsayılan başlıklar, sonra DB'deki ekstra öğeler.
        // (Kalıcı yazmaz; sadece görünümde toparlar.)
        $home = route('marketing.home');
        $defaultMenuItems = collect([
            ['label' => 'Anasayfa', 'url' => $home],
            ['label' => 'Hakkımızda', 'url' => $home.'#hakkimizda'],
            ['label' => 'Özellikler', 'url' => $home.'#ozellikler'],
            ['label' => 'Fiyatlarımız', 'url' => $home.'#fiyatlandirma'],
            ['label' => 'Referanslar', 'url' => $home.'#referanslar'],
            ['label' => 'Başvuru', 'url' => $home.'#basvuru'],
            ['label' => 'S.S.S.', 'url' => $home.'#sss'],
            ['label' => 'İletişim', 'url' => $home.'#iletisim'],
            ['label' => 'Panel girişi', 'url' => route('login')],
        ]);

        $existingMenuItems = $menuItems;
        $menuItems = collect();

        $seen = collect();
        foreach ($defaultMenuItems as $row) {
            $wantLabel = mb_strtolower(trim((string) $row['label']));
            $wantUrl = trim((string) $row['url']);
            $seen = $seen->push(['label' => $wantLabel, 'url' => $wantUrl]);
            $menuItems = $menuItems->push((object) [
                'label' => $row['label'],
                'url' => $row['url'],
                'open_in_new_tab' => false,
            ]);
        }

        foreach ($existingMenuItems as $item) {
            $label = mb_strtolower(trim((string) ($item->label ?? '')));
            $url = trim((string) ($item->url ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $exists = $seen->contains(fn (array $e): bool => $e['url'] === $url || $e['label'] === $label);
            if ($exists) {
                continue;
            }
            $menuItems = $menuItems->push($item);
            $seen = $seen->push(['label' => $label, 'url' => $url]);
        }

        $slides = $site !== null
            ? MarketingSlide::query()
                ->where('marketing_site_id', $site->id)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get()
            : collect();

        $footerColumns = $site !== null
            ? MarketingFooterColumn::query()
                ->where('marketing_site_id', $site->id)
                ->with('links')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $page = $site !== null
            ? MarketingPage::query()
                ->where('marketing_site_id', $site->id)
                ->where('slug', 'home')
                ->first()
            : null;

        $previewAllowed = $request->boolean('preview')
            && $request->user() !== null
            && $request->user()->role?->name === Role::SUPER_ADMIN;

        $blocks = [];
        if ($page !== null) {
            if ($previewAllowed) {
                $latest = $page->versions()->orderByDesc('id')->first();
                $blocks = $latest?->blocks_json ?? [];
            } elseif ($page->isPublished() && $page->publishedVersion !== null) {
                $blocks = $page->publishedVersion->blocks_json ?? [];
            }
        }

        $effectiveBlocks = (is_array($blocks) && $blocks !== [])
            ? $blocks
            : MarketingBootstrap::defaultBlocks();

        $heroSliderInUse = collect($effectiveBlocks)->contains(
            static fn ($b): bool => is_array($b) && ($b['type'] ?? '') === 'hero_slider',
        );

        if ($heroSliderInUse) {
            $slides = collect();
            if (is_array($blocks) && $blocks !== []) {
                $blocks = array_values(array_filter(
                    $blocks,
                    static fn ($b): bool => ! is_array($b) || ($b['type'] ?? '') !== 'hero',
                ));
            }
        }

        $title = $page?->title ?? config('marketing.brand_name');
        $metaDescription = $page?->meta_description;

        return view('marketing.home', [
            'site' => $site,
            'menuItems' => $menuItems,
            'slides' => $slides,
            'footerColumns' => $footerColumns,
            'blocks' => $blocks,
            'title' => $title,
            'metaDescription' => $metaDescription,
            'preview' => $previewAllowed,
        ]);
    }
}
