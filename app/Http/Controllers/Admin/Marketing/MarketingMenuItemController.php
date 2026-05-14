<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingMenu;
use App\Models\Marketing\MarketingMenuItem;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class MarketingMenuItemController extends Controller
{
    public function index(MarketingBootstrap $bootstrap): View
    {
        $site = $bootstrap->ensureSiteExists();
        $menu = MarketingMenu::query()->firstOrCreate(
            [
                'marketing_site_id' => $site->id,
                'key' => 'header',
            ],
            [],
        );

        $items = MarketingMenuItem::query()
            ->where('marketing_menu_id', $menu->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.marketing.menu', [
            'title' => 'Üst menü',
            'site' => $site,
            'menu' => $menu,
            'items' => $items,
        ]);
    }

    public function store(Request $request, MarketingBootstrap $bootstrap): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        $menu = MarketingMenu::query()->firstOrCreate(
            ['marketing_site_id' => $site->id, 'key' => 'header'],
            [],
        );

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'open_in_new_tab' => ['sometimes', 'boolean'],
        ]);

        $max = (int) MarketingMenuItem::query()->where('marketing_menu_id', $menu->id)->max('sort_order');

        MarketingMenuItem::query()->create([
            'marketing_menu_id' => $menu->id,
            'parent_id' => null,
            'label' => $validated['label'],
            'url' => $validated['url'],
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'sort_order' => $max + 1,
        ]);

        return redirect()->route('admin.marketing.menu.index')->with('ok', 'Menü öğesi eklendi.');
    }

    public function update(Request $request, MarketingBootstrap $bootstrap, MarketingMenuItem $item): RedirectResponse
    {
        $bootstrap->ensureSiteExists();
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'open_in_new_tab' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $item->update([
            'label' => $validated['label'],
            'url' => $validated['url'],
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'sort_order' => $validated['sort_order'],
        ]);

        return redirect()->route('admin.marketing.menu.index')->with('ok', 'Güncellendi.');
    }

    public function destroy(MarketingBootstrap $bootstrap, MarketingMenuItem $item): RedirectResponse
    {
        $bootstrap->ensureSiteExists();
        $item->delete();

        return redirect()->route('admin.marketing.menu.index')->with('ok', 'Silindi.');
    }
}
